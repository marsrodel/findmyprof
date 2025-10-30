<?php
  session_start();
  if (!isset($_SESSION['user_id'])) { header('Location: ../login.php'); exit; }
  require_once('../../server/db.php');
  $uid = (int)($_SESSION['user_id'] ?? 0);
  $name = 'USER';
  if ($uid > 0) {
    $rs = mysqli_query($conn, "SELECT name, role FROM users WHERE id={$uid} LIMIT 1");
    if ($rs && ($row = mysqli_fetch_assoc($rs))) {
      $_SESSION['role'] = $row['role'];
      $name = $row['name'] ?: $name;
    }
  }
  // Allow Instructor, Executive, Staff to use faculty interface; redirect Admins to admin panel
  if (($_SESSION['role'] ?? '') === 'Admin') { header('Location: ../admin_view/admin_dashboard.php'); exit; }

  // Handle Set Status submit
  $notice = '';
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_status'])) {
    $status = strtolower(trim($_POST['status'] ?? ''));
    $duration = (int)($_POST['duration'] ?? 0); // minutes, optional
    $building_id = (int)($_POST['building_id'] ?? 0);
    $desc = trim($_POST['description'] ?? '');
    $allowed = ['available','in class','meeting','break','dnd','out'];
    if (!in_array($status, $allowed)) { $status = 'available'; }

    // compute expires_at
    $expires = null;
    if ($duration > 0) {
      $expires = date('Y-m-d H:i:s', time() + ($duration * 60));
    }

    // fetch building name for note context
    $bname = '';
    if ($building_id > 0) {
      $rb = mysqli_query($conn, 'SELECT name FROM buildings WHERE id='.(int)$building_id.' LIMIT 1');
      if ($rb && ($br = mysqli_fetch_assoc($rb))) { $bname = (string)$br['name']; }
    }

    // Insert presence and a log (room_id NULL for manual status)
    $stmt = mysqli_prepare($conn, "INSERT INTO presence(faculty_user_id,room_id,status,note,source,expires_at) VALUES(?,NULL,?, ?, 'manual', ?)");
    mysqli_stmt_bind_param($stmt, 'isss', $uid, $status, $desc, $expires);
    $ok1 = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    $noteLog = trim(($bname !== '' ? ('building='.$bname) : '') . ($desc!=='' ? ($bname!==''?' • ':'').$desc : ''));
    $stmt2 = mysqli_prepare($conn, "INSERT INTO instructor_logs(faculty_user_id,action,status,room_id,source,note) VALUES(?,'status',?,NULL,'manual',?)");
    mysqli_stmt_bind_param($stmt2, 'iss', $uid, $status, $noteLog);
    $ok2 = mysqli_stmt_execute($stmt2);
    mysqli_stmt_close($stmt2);

    $notice = ($ok1 && $ok2) ? 'Status updated.' : 'Failed to update status.';
  }

  // Handle Update Status while in a room (room-bound status)
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_room_status'])) {
    $room_id = (int)($_POST['room_id'] ?? 0);
    $status = strtolower(trim($_POST['status'] ?? ''));
    $allowed = ['available','in class','meeting','break','dnd','out'];
    if (!in_array($status, $allowed)) { $status = 'available'; }

    // validate room
    $rv = mysqli_query($conn, 'SELECT id FROM rooms WHERE id='.(int)$room_id.' LIMIT 1');
    if (!($rv && mysqli_fetch_assoc($rv))) { $notice = 'Invalid room.'; }
    else {
      mysqli_begin_transaction($conn);
      $ok = true;
      try {
        if ($status === 'out') {
          // checkout: write presence(out, NULL room) and instructor_logs(action=checkout)
          $stmt = mysqli_prepare($conn, "INSERT INTO presence(faculty_user_id,room_id,status,source) VALUES(?,NULL,'out','manual')");
          mysqli_stmt_bind_param($stmt, 'i', $uid);
          $ok = $ok && mysqli_stmt_execute($stmt);
          mysqli_stmt_close($stmt);

          $stmt2 = mysqli_prepare($conn, "INSERT INTO instructor_logs(faculty_user_id,action,status,room_id,source) VALUES(?,'checkout','out',?,'manual')");
          mysqli_stmt_bind_param($stmt2, 'ii', $uid, $room_id);
          $ok = $ok && mysqli_stmt_execute($stmt2);
          mysqli_stmt_close($stmt2);
        } else {
          // presence with same room
          $stmt = mysqli_prepare($conn, "INSERT INTO presence(faculty_user_id,room_id,status,source) VALUES(?,?,'".$status."','manual')");
          mysqli_stmt_bind_param($stmt, 'ii', $uid, $room_id);
          $ok = $ok && mysqli_stmt_execute($stmt);
          mysqli_stmt_close($stmt);
          // instructor_logs action=status for the same room
          $stmt2 = mysqli_prepare($conn, "INSERT INTO instructor_logs(faculty_user_id,action,status,room_id,source) VALUES(?,'status',?,?,'manual')");
          mysqli_stmt_bind_param($stmt2, 'isi', $uid, $status, $room_id);
          $ok = $ok && mysqli_stmt_execute($stmt2);
          mysqli_stmt_close($stmt2);
        }
        if ($ok) { mysqli_commit($conn); $notice = ($status==='out' ? 'Checked out.' : 'Room status updated.'); }
        else { mysqli_rollback($conn); $notice = 'Failed to update room status.'; }
      } catch (Throwable $e) {
        if (mysqli_errno($conn)) { mysqli_rollback($conn); }
        $notice = 'Failed to update room status.';
      }
    }
  }

  // Load buildings for the select
  $buildings = [];
  $qb = mysqli_query($conn, 'SELECT id,name FROM buildings ORDER BY name ASC');
  if ($qb) { while ($r = mysqli_fetch_assoc($qb)) { $buildings[] = $r; } }

  // Determine current room/status from latest instructor log
  $current_room = null; $current_status = null;
  $qr = mysqli_query($conn, "SELECT l.room_id,l.status, r.room_number, r.room_name, b.name AS building_name
                               FROM instructor_logs l
                          LEFT JOIN rooms r ON r.id = l.room_id
                          LEFT JOIN buildings b ON b.id = r.building_id
                              WHERE l.faculty_user_id = ".$uid.
                            " ORDER BY l.created_at DESC, l.id DESC LIMIT 1");
  if ($qr && ($cr = mysqli_fetch_assoc($qr))) {
    if (!empty($cr['room_id']) && strtolower((string)$cr['status']) !== 'out') {
      $current_room = $cr; $current_status = $cr['status'];
    }
  }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>FindMyProf • Faculty Dashboard</title>
  <link rel="stylesheet" href="../../css/faculty.css" />
</head>
<body>
  <header class="topbar">
    <div class="topbar-inner">
      <div class="brand">
        <div class="brand-mark" aria-hidden="true">LOGO</div>
        <span class="brand-name">FindMyProf</span>
      </div>
      <h1 class="page-title">DASHBOARD</h1>
      <a class="btn ghost" href="../../server/logout.php">LOGOUT</a>
    </div>
  </header>

  <div class="layout">
    <aside class="sidebar">
      <div class="avatar">A</div>
      <nav class="menu">
        <a class="item active" href="faculty_dashboard.php">DASHBOARD</a>
        <a class="item" href="faculty_search.php">SEARCH</a>
        <a class="item" href="faculty_profile.php">PROFILE</a>
        <a class="item" href="faculty_logs.php">LOGS</a>
      </nav>
    </aside>

    <main class="content">
      <div class="content-head">
        <h2 class="welcome">Welcome, <?php echo htmlspecialchars($name); ?></h2>
        <a class="btn primary" href="scan.php">◎ SCAN QR CODE</a>
      </div>

      <div class="status-row">
        <span class="status-pill" id="statusToggle" style="cursor:pointer" onclick="toggleStatus()">
          <span class="dot"></span>
          STATUS
          <span class="chev">▾</span>
        </span>
      </div>

      <?php if ($notice !== '') { echo '<div class="notice" style="margin:10px 0">'.htmlspecialchars($notice).'</div>'; } ?>

      <section id="statusPanel" class="panel" style="display:none">
        <?php if ($current_room) { ?>
          <div class="panel-head"><h3>CURRENT ROOM</h3></div>
          <div class="panel-body" style="padding:12px 16px">
            <div class="field">
              <div class="label">STATUS</div>
              <div><strong><?php $cs=(string)($current_status??''); echo htmlspecialchars($cs==='dnd'?'DND':ucwords($cs)); ?></strong></div>
            </div>
            <div class="field">
              <div class="label">BUILDING</div>
              <div><strong><?php echo htmlspecialchars($current_room['building_name'] ?? ''); ?></strong></div>
            </div>
            <div class="field">
              <div class="label">ROOM</div>
              <div><strong><?php echo htmlspecialchars($current_room['room_number'] ?: $current_room['room_name']); ?></strong> <span class="muted"><?php echo htmlspecialchars($current_room['room_name']); ?></span></div>
            </div>
            <form class="toolbar" method="post" action="confirm_check.php" style="gap:10px;margin-top:10px;flex-wrap:wrap">
              <input type="hidden" name="room_id" value="<?php echo (int)$current_room['room_id']; ?>" />
              <input type="hidden" name="action" value="checkout" />
              <button class="btn" type="submit">Log out from this room</button>
            </form>
            <form class="toolbar" method="post" action="" style="gap:10px;margin-top:10px;flex-wrap:wrap">
              <input type="hidden" name="room_id" value="<?php echo (int)$current_room['room_id']; ?>" />
              <label>
                <span class="label">UPDATE STATUS</span>
                <select name="status">
                  <option value="available">Available</option>
                  <option value="in class">In Class</option>
                  <option value="meeting">Meeting</option>
                  <option value="break">Break</option>
                  <option value="dnd">DND</option>
                  <option value="out">Out</option>
                </select>
              </label>
              <button class="btn primary" type="submit" name="update_room_status" value="1">Update Status</button>
            </form>
          </div>
        <?php } else { ?>
          <div class="panel-head">
            <h3>SET STATUS</h3>
          </div>
          <div class="panel-body" style="padding:12px 16px">
            <form method="post" action="">
              <div class="field two-col">
                <label>
                  <span class="label">STATUS</span>
                  <select name="status">
                    <option value="Available">Available</option>
                    <option value="In class">In Class</option>
                    <option value="Meeting">Meeting</option>
                    <option value="Break">Break</option>
                    <option value="DND">DND</option>
                    <option value="Out">Out</option>
                  </select>
                </label>
                <label>
                  <span class="label">DURATION <span class="muted-note">(optional, minutes)</span></span>
                  <input type="number" min="0" name="duration" placeholder="Value" />
                </label>
              </div>
              <div class="field">
                <label>
                  <span class="label">BUILDING</span>
                  <select name="building_id">
                    <option value="0">-- none --</option>
                    <?php foreach ($buildings as $b) { ?>
                      <option value="<?php echo (int)$b['id']; ?>"><?php echo htmlspecialchars($b['name']); ?></option>
                    <?php } ?>
                  </select>
                </label>
              </div>
              <div class="field">
                <label>
                  <span class="label">DESCRIPTION</span>
                  <textarea name="description" rows="4" placeholder="Optional note"></textarea>
                </label>
              </div>
              <div class="form-actions" style="display:grid;place-items:center;margin-top:10px">
                <button class="btn primary" type="submit" name="set_status" value="1">SET STATUS</button>
              </div>
            </form>
          </div>
        <?php } ?>
      </section>

      <script>
        function toggleStatus(){
          var p = document.getElementById('statusPanel');
          if (!p) return;
          p.style.display = (p.style.display === 'none' || p.style.display === '') ? 'block' : 'none';
        }
      </script>

      <section class="panel placeholder">
        <div class="placeholder-inner">SCHEDULE</div>
      </section>

      <section class="panel placeholder">
        <div class="placeholder-inner">MAP</div>
      </section>
    </main>
  </div>

  <footer class="footer">
    <div class="footer-inner">© 2025 FindMyProf. All rights reserved.</div>
  </footer>
</body>
</html>

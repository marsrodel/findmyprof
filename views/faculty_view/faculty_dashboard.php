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
    $status = trim($_POST['status'] ?? '');
    $duration = (int)($_POST['duration'] ?? 0); // minutes, optional
    $building_id = (int)($_POST['building_id'] ?? 0);
    $desc = trim($_POST['description'] ?? '');
    $allowed = ['available','busy','meeting','away','out'];
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

  // Load buildings for the select
  $buildings = [];
  $qb = mysqli_query($conn, 'SELECT id,name FROM buildings ORDER BY name ASC');
  if ($qb) { while ($r = mysqli_fetch_assoc($qb)) { $buildings[] = $r; } }
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
        <div class="panel-head">
          <h3>SET STATUS</h3>
        </div>
        <div class="panel-body" style="padding:12px 16px">
          <form method="post" action="">
            <div class="field two-col">
              <label>
                <span class="label">STATUS</span>
                <select name="status">
                  <option value="available">available</option>
                  <option value="busy">busy</option>
                  <option value="meeting">meeting</option>
                  <option value="away">away</option>
                  <option value="out">out</option>
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

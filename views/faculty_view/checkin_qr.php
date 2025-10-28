<?php
  session_start();
  if (!isset($_SESSION['user_id'])) { header('Location: ../login.php'); exit; }
  require_once('../../server/db.php');
  $uid = (int)($_SESSION['user_id'] ?? 0);
  if ($uid > 0) {
    $rs = mysqli_query($conn, "SELECT role FROM users WHERE id={$uid} LIMIT 1");
    if ($rs && ($row = mysqli_fetch_assoc($rs))) { $_SESSION['role'] = $row['role']; }
  }
  if (($_SESSION['role'] ?? '') === 'Admin') { header('Location: ../admin_view/admin_dashboard.php'); exit; }

  // Resolve room id from ?rid or ?data JSON
  $rid = isset($_GET['rid']) ? (int)$_GET['rid'] : 0;
  $data = $_GET['data'] ?? '';
  if ($rid <= 0 && $data) {
    $decoded = null;
    if (strlen($data) > 0 && $data[0] === '{') { $decoded = json_decode($data, true); }
    if (!$decoded) {
      $try = json_decode(urldecode($data), true);
      if (json_last_error() === JSON_ERROR_NONE) { $decoded = $try; }
    }
    if (is_array($decoded)) {
      if (isset($decoded['rid'])) {
        $rid = (int)$decoded['rid'];
      } else {
        $bname = trim((string)($decoded['building'] ?? ''));
        $rnum  = trim((string)($decoded['room_number'] ?? ''));
        $rname = trim((string)($decoded['room_name'] ?? ''));
        if ($bname !== '' && ($rnum !== '' || $rname !== '')) {
          $sql = "SELECT r.id FROM rooms r JOIN buildings b ON b.id=r.building_id WHERE b.name=? AND (r.room_number=? OR r.room_name=?) LIMIT 1";
          $stmt = mysqli_prepare($conn, $sql);
          mysqli_stmt_bind_param($stmt, 'sss', $bname, $rnum, $rname);
          mysqli_stmt_execute($stmt);
          $res = mysqli_stmt_get_result($stmt);
          if ($row = mysqli_fetch_assoc($res)) { $rid = (int)$row['id']; }
          mysqli_stmt_close($stmt);
        }
      }
    }
  }

  if ($rid <= 0) {
    http_response_code(400);
    echo '<!DOCTYPE html><html><body><p>Invalid QR. Missing room.</p></body></html>';
    exit;
  }

  // Load room + building
  $room = null;
  $rs = mysqli_query($conn, "SELECT r.id, r.room_number, r.room_name, b.name AS building_name, r.building_id FROM rooms r JOIN buildings b ON b.id=r.building_id WHERE r.id=".(int)$rid." LIMIT 1");
  if ($rs && ($row = mysqli_fetch_assoc($rs))) { $room = $row; }
  if (!$room) { http_response_code(404); echo '<!DOCTYPE html><html><body><p>Room not found.</p></body></html>'; exit; }

  // Determine current presence (v_current_presence should exist)
  $curRoomId = null; $curStatus = null;
  $prs = mysqli_query($conn, "SELECT room_id, status FROM v_current_presence WHERE faculty_user_id=".$uid." LIMIT 1");
  if ($prs && ($p = mysqli_fetch_assoc($prs))) { $curRoomId = $p['room_id']; $curStatus = $p['status']; }

  $inThisRoom = $curRoomId !== null && (int)$curRoomId === (int)$room['id'] && $curStatus !== 'out';
  $action = $inThisRoom ? 'checkout' : 'checkin';
  $question = $inThisRoom ? 'Log out from this room?' : 'Log in to this room?';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Confirm <?php echo htmlspecialchars(ucfirst($action)); ?></title>
  <link rel="stylesheet" href="../../css/faculty.css" />
</head>
<body>
  <header class="topbar">
    <div class="topbar-inner">
      <div class="brand">
        <div class="brand-mark" aria-hidden="true">LOGO</div>
        <span class="brand-name">FindMyProf</span>
      </div>
      <h1 class="page-title">CONFIRM</h1>
      <a class="btn ghost" href="../../server/logout.php">LOGOUT</a>
    </div>
  </header>

  <div class="layout">
    <aside class="sidebar">
      <div class="avatar">A</div>
      <nav class="menu">
        <a class="item" href="faculty_dashboard.php">DASHBOARD</a>
        <a class="item" href="faculty_search.php">SEARCH</a>
        <a class="item" href="faculty_profile.php">PROFILE</a>
        <a class="item" href="faculty_logs.php">LOGS</a>
      </nav>
    </aside>

    <main class="content">
      <section class="panel">
        <div class="panel-head"><h3>QR Check <?php echo htmlspecialchars(ucfirst($action)); ?></h3></div>
        <div class="panel-body" style="padding:12px 16px">
          <div class="field">
            <div class="label">Building</div>
            <div><strong><?php echo htmlspecialchars($room['building_name']); ?></strong></div>
          </div>
          <div class="field">
            <div class="label">Room</div>
            <div><strong><?php echo htmlspecialchars($room['room_number'] ?: $room['room_name']); ?></strong> <span class="muted"><?php echo htmlspecialchars($room['room_name']); ?></span></div>
          </div>
          <div style="margin-top:12px"><?php echo htmlspecialchars($question); ?></div>
          <form class="toolbar" method="post" action="confirm_check.php" style="gap:10px;margin-top:10px">
            <input type="hidden" name="room_id" value="<?php echo (int)$room['id']; ?>" />
            <input type="hidden" name="action" value="<?php echo htmlspecialchars($action); ?>" />
            <button class="btn primary" type="submit">Confirm</button>
            <a class="btn ghost" href="scan.php" role="button">Cancel</a>
          </form>
        </div>
      </section>
    </main>
  </div>

  <footer class="footer">
    <div class="footer-inner">© 2025 FindMyProf. All rights reserved.</div>
  </footer>
</body>
</html>

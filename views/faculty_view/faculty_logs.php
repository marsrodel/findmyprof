<?php
  session_start();
  if (!isset($_SESSION['user_id'])) { header('Location: ../login.php'); exit; }
  require_once('../../server/db.php');
  // Refresh role
  $uid = (int)($_SESSION['user_id'] ?? 0);
  if ($uid > 0) {
    $rs = mysqli_query($conn, "SELECT role FROM users WHERE id={$uid} LIMIT 1");
    if ($rs && ($row = mysqli_fetch_assoc($rs))) { $_SESSION['role'] = $row['role']; }
  }
  // Allow Instructor, Executive, Staff to use faculty interface; redirect Admins
  if (($_SESSION['role'] ?? '') === 'Admin') { header('Location: ../admin_view/admin_dashboard.php'); exit; }

  // Load recent instructor logs (similar to prototype)
  $logs = [];
  $stmt = mysqli_prepare($conn, "SELECT il.id, il.created_at, il.action, il.status, il.note, il.source,
                                       r.room_number, r.room_name, b.name AS bname
                                  FROM instructor_logs il
                             LEFT JOIN rooms r ON r.id = il.room_id
                             LEFT JOIN buildings b ON b.id = r.building_id
                                 WHERE il.faculty_user_id = ?
                              ORDER BY il.created_at DESC, il.id DESC
                                 LIMIT 200");
  mysqli_stmt_bind_param($stmt, 'i', $uid);
  if (mysqli_stmt_execute($stmt)) {
    $res = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($res)) { $logs[] = $row; }
  }
  mysqli_stmt_close($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>FindMyProf • Faculty Logs</title>
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
        <a class="item" href="faculty_dashboard.php">DASHBOARD</a>
        <a class="item" href="faculty_search.php">SEARCH</a>
        <a class="item" href="faculty_profile.php">PROFILE</a>
        <a class="item active" href="faculty_logs.php">LOGS</a>
      </nav>
    </aside>

    <main class="content">
      <section class="log-wrap">
        <div class="log-card">
          <h3 class="title">PERSONAL LOGS</h3>
          <div class="log-panel">
            <div class="table logs-table">
              <div class="thead">
                <div class="th">TIME</div>
                <div class="th">BLDG & ROOM</div>
                <div class="th">ACTION</div>
                <div class="th">STATUS</div>
                <div class="th">SOURCE</div>
              </div>
              <div class="tbody">
                <?php if (empty($logs)) { echo '<div class="row"><div class="td" style="grid-column:1/-1;color:#6b7280">No logs yet.</div></div>'; } ?>
                <?php foreach ($logs as $row) { ?>
                  <?php
                    $roomDisp = (($row['bname'] ?: '—')) . ' • ' . ((($row['room_number'] ?: $row['room_name']) ?: '—'));
                  ?>
                  <div class="row">
                    <div class="td muted"><?php echo htmlspecialchars($row['created_at']); ?></div>
                    <div class="td"><?php echo htmlspecialchars($roomDisp); ?></div>
                    <div class="td"><?php echo htmlspecialchars($row['action']); ?></div>
                    <div class="td muted"><?php echo htmlspecialchars($row['status'] ?: '—'); ?></div>
                    <div class="td muted"><?php echo htmlspecialchars($row['source'] ?: '—'); ?></div>
                  </div>
                <?php } ?>
              </div>
            </div>
          </div>
        </div>
      </section>
    </main>
  </div>

  <footer class="footer">
    <div class="footer-inner">© 2025 FindMyProf. All rights reserved.</div>
  </footer>
</body>
</html>

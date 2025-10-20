<?php
  session_start();
  if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
  }
  require_once('../../server/db.php');
  $uid = (int)($_SESSION['user_id'] ?? 0);
  if ($uid > 0) {
    $rs = mysqli_query($conn, "SELECT role FROM users WHERE id={$uid} LIMIT 1");
    if ($rs && ($row = mysqli_fetch_assoc($rs))) { $_SESSION['role'] = $row['role']; }
  }
  if (($_SESSION['role'] ?? '') !== 'Admin') {
    header('Location: ../faculty_view/faculty_dashboard.php');
    exit;
  }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>FindMyProf • Admin Logs</title>
  <link rel="stylesheet" href="../../css/admin.css" />
</head>
<body>
  <header class="topbar">
    <div class="topbar-inner">
      <div class="brand">
        <div class="brand-mark" aria-hidden="true">LOGO</div>
        <span class="brand-name">FindMyProf</span>
      </div>
      <h1 class="page-title">ADMIN LOGS</h1>
      <a class="btn ghost" href="../../server/logout.php">LOGOUT</a>
    </div>
  </header>

  <div class="layout">
    <aside class="sidebar">
      <div class="avatar">A</div>
      <nav class="menu">
        <a class="item" href="admin_dashboard.php">DASHBOARD</a>
        <a class="item" href="executives_list.php">EXECUTIVES</a>
        <a class="item" href="instructors_list.php">INSTRUCTORS</a>
        <a class="item" href="buildings.php">BUILDINGS</a>
        <a class="item" href="add_building.php">ADD BUILDING</a>
        <a class="item" href="staff_list.php">STAFF</a>
        <a class="item active" href="logs.php">LOGS</a>
        <a class="item" href="add_personel.php">ADD PERSONEL</a>
      </nav>
    </aside>

    <main class="content">
      <section class="log-section">
        <div class="log-group">
          <h3 class="group-title">EXECUTIVE LOGS</h3>
          <div class="log-panel">
            <div class="table logs-table">
              <div class="thead">
                <div class="th">TIME</div>
                <div class="th">INSTRUCTOR</div>
                <div class="th">ACTION</div>
                <div class="th">STATUS</div>
                <div class="th">BLDG & ROOM</div>
                <div class="th">SOURCE</div>
              </div>
              <div class="tbody"></div>
            </div>
          </div>
        </div>

        <div class="log-group">
          <h3 class="group-title">INSTRUCTOR LOGS</h3>
          <div class="log-panel">
            <div class="table logs-table">
              <div class="thead">
                <div class="th">TIME</div>
                <div class="th">INSTRUCTOR</div>
                <div class="th">ACTION</div>
                <div class="th">STATUS</div>
                <div class="th">BLDG & ROOM</div>
                <div class="th">SOURCE</div>
              </div>
              <div class="tbody"></div>
            </div>
          </div>
        </div>

        <div class="log-group">
          <h3 class="group-title">STAFF LOGS</h3>
          <div class="log-panel">
            <div class="table logs-table">
              <div class="thead">
                <div class="th">TIME</div>
                <div class="th">INSTRUCTOR</div>
                <div class="th">ACTION</div>
                <div class="th">STATUS</div>
                <div class="th">BLDG & ROOM</div>
                <div class="th">SOURCE</div>
              </div>
              <div class="tbody"></div>
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

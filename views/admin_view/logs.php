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

  // Fetch logs per role category using instructor_logs as the source
  function fetch_logs_by_role(mysqli $conn, string $role): array {
    $rows = [];
    $sql = "SELECT l.created_at, l.action, l.status, l.source, l.room_id,
                   u.name AS instructor_name,
                   b.name AS building_name, r.room_number, r.room_name
              FROM instructor_logs l
              JOIN users u ON u.id = l.faculty_user_id
         LEFT JOIN rooms r ON r.id = l.room_id
         LEFT JOIN buildings b ON b.id = r.building_id
             WHERE u.role = ?
          ORDER BY l.created_at DESC
             LIMIT 200";
    if ($stmt = mysqli_prepare($conn, $sql)) {
      mysqli_stmt_bind_param($stmt, 's', $role);
      mysqli_stmt_execute($stmt);
      $res = mysqli_stmt_get_result($stmt);
      while ($row = mysqli_fetch_assoc($res)) { $rows[] = $row; }
      mysqli_stmt_close($stmt);
    }
    return $rows;
  }

  $execLogs = fetch_logs_by_role($conn, 'Executive');
  $instLogs = fetch_logs_by_role($conn, 'Instructor');
  $staffLogs = fetch_logs_by_role($conn, 'Staff');
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
              <div class="tbody">
                <?php if (empty($execLogs)) { echo '<div class="row"><div class="td" style="grid-column:1/-1;color:#6b7280">No executive logs.</div></div>'; } ?>
                <?php foreach ($execLogs as $r) { ?>
                  <?php
                    $loc = '—';
                    if (!empty($r['building_name']) && (!empty($r['room_number']) || !empty($r['room_name']))) {
                      $roomDisp = ($r['room_number'] !== null && $r['room_number'] !== '') ? $r['room_number'] : $r['room_name'];
                      $loc = $r['building_name'].' • '.$roomDisp;
                    }
                  ?>
                  <div class="row">
                    <div class="td"><?php echo htmlspecialchars($r['created_at'] ?? ''); ?></div>
                    <div class="td"><?php echo htmlspecialchars($r['instructor_name'] ?? ''); ?></div>
                    <div class="td"><?php echo htmlspecialchars($r['action'] ?? ''); ?></div>
                    <div class="td"><?php echo htmlspecialchars($r['status'] ?? ''); ?></div>
                    <div class="td"><?php echo htmlspecialchars($loc); ?></div>
                    <div class="td"><?php echo htmlspecialchars($r['source'] ?? ''); ?></div>
                  </div>
                <?php } ?>
              </div>
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
              <div class="tbody">
                <?php if (empty($instLogs)) { echo '<div class="row"><div class="td" style="grid-column:1/-1;color:#6b7280">No instructor logs.</div></div>'; } ?>
                <?php foreach ($instLogs as $r) { ?>
                  <?php
                    $loc = '—';
                    if (!empty($r['building_name']) && (!empty($r['room_number']) || !empty($r['room_name']))) {
                      $roomDisp = ($r['room_number'] !== null && $r['room_number'] !== '') ? $r['room_number'] : $r['room_name'];
                      $loc = $r['building_name'].' • '.$roomDisp;
                    }
                  ?>
                  <div class="row">
                    <div class="td"><?php echo htmlspecialchars($r['created_at'] ?? ''); ?></div>
                    <div class="td"><?php echo htmlspecialchars($r['instructor_name'] ?? ''); ?></div>
                    <div class="td"><?php echo htmlspecialchars($r['action'] ?? ''); ?></div>
                    <div class="td"><?php echo htmlspecialchars($r['status'] ?? ''); ?></div>
                    <div class="td"><?php echo htmlspecialchars($loc); ?></div>
                    <div class="td"><?php echo htmlspecialchars($r['source'] ?? ''); ?></div>
                  </div>
                <?php } ?>
              </div>
            </div>
          </div>
        </div>

        <!-- Staff logs section removed per requirement -->
      </section>
    </main>
  </div>

  <footer class="footer">
    <div class="footer-inner">© 2025 FindMyProf. All rights reserved.</div>
  </footer>
</body>
</html>

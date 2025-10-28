<?php
  session_start();
  if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
  }
  if (($_SESSION['role'] ?? '') !== 'Admin') {
    header('Location: ../faculty_view/faculty_dashboard.php');
    exit;
  }
  require_once('../../server/db.php');
  $rows = [];
  $q = mysqli_query($conn, "SELECT id,name,email,department,created_at FROM users WHERE role='Instructor' ORDER BY name ASC");
  if ($q) {
    while ($r = mysqli_fetch_assoc($q)) { $rows[] = $r; }
  }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>FindMyProf • Instructors</title>
  <link rel="stylesheet" href="../../css/admin.css" />
</head>
<body>
  <header class="topbar">
    <div class="topbar-inner">
      <div class="brand">
        <div class="brand-mark" aria-hidden="true">LOGO</div>
        <span class="brand-name">FindMyProf</span>
      </div>
      <h1 class="page-title">INSTRUCTORS</h1>
      <a class="btn ghost" href="../../server/logout.php">LOGOUT</a>
    </div>
  </header>

  <div class="layout">
    <aside class="sidebar">
      <div class="avatar">A</div>
      <nav class="menu">
        <a class="item" href="admin_dashboard.php">DASHBOARD</a>
        <a class="item" href="executives_list.php">EXECUTIVES</a>
        <a class="item active" href="instructors_list.php">INSTRUCTORS</a>
        <a class="item" href="buildings.php">BUILDINGS</a>
        <a class="item" href="add_building.php">ADD BUILDING</a>
        <a class="item" href="logs.php">LOGS</a>
        <a class="item" href="add_personel.php">ADD PERSONEL</a>
      </nav>
    </aside>

    <main class="content">
      <section class="toolbar">
        <input class="search-input" type="text" placeholder="Hinted search text" />
        <label class="select-wrap">
          <select>
            <option>Sort by Department</option>
          </select>
          <span class="chev" aria-hidden>▾</span>
        </label>
        <a class="btn add-qr" href="add_personel.php">➕ ADD INSTRUCTOR</a>
      </section>

      <section class="panel">
        <div class="table exec-table">
          <div class="thead">
            <div class="th">NAME</div>
            <div class="th">EMAIL</div>
            <div class="th">DEPARTMENT</div>
            <div class="th">CREATED</div>
            <div class="th">ACTION</div>
          </div>
          <div class="tbody">
            <?php if (empty($rows)) { echo '<div class="empty">No instructors found.</div>'; } ?>
            <?php foreach ($rows as $r) { ?>
              <div class="tr">
                <div class="td"><?php echo htmlspecialchars($r['name']); ?></div>
                <div class="td"><?php echo htmlspecialchars($r['email']); ?></div>
                <div class="td"><?php echo htmlspecialchars($r['department'] ?? ''); ?></div>
                <div class="td"><?php echo htmlspecialchars($r['created_at']); ?></div>
                <div class="td"><a class="btn small" href="edit_personel.php?id=<?php echo (int)$r['id']; ?>">Edit</a></div>
              </div>
            <?php } ?>
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

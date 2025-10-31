<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: ../login.php'); exit; }
require_once('../../server/db.php');
$uid = (int)($_SESSION['user_id'] ?? 0);
// Only Admins can access
if (($_SESSION['role'] ?? '') !== 'Admin') {
  // Try to fetch role if not set
  if ($uid > 0 && empty($_SESSION['role'])) {
    $rs = mysqli_query($conn, 'SELECT role FROM users WHERE id='.(int)$uid.' LIMIT 1');
    if ($rs && ($row = mysqli_fetch_assoc($rs))) { $_SESSION['role'] = $row['role']; }
  }
  if (($_SESSION['role'] ?? '') !== 'Admin') { header('Location: ../faculty_view/faculty_dashboard.php'); exit; }
}

// Fetch instructors and executives
$people = [];
$q = "SELECT id, name, email, role FROM users WHERE role IN ('Instructor','Executive') ORDER BY role ASC, name ASC";
$rs = mysqli_query($conn, $q);
if ($rs) { while ($row = mysqli_fetch_assoc($rs)) { $people[] = $row; } }

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>FindMyProf • Schedules</title>
  <link rel="stylesheet" href="../../css/faculty.css" />
  <style>
    .panel { border:1px solid #d9e2db; border-radius:12px; background:#fff }
    .panel-head { padding:10px 14px; border-bottom:1px solid #e2e8f0; font-weight:800 }
    .panel-body { padding:12px 16px }
    .list { width:100%; border-collapse:collapse; }
    .list th, .list td { border-bottom:1px solid #e5e7eb; padding:8px; text-align:left; font-size:14px }
    .list th { background:#f8fafc; font-weight:700 }
    .row { display:flex; gap:8px; align-items:center; flex-wrap:wrap }
    .search { padding:8px 10px; border:1px solid #cbd5e1; border-radius:8px; width:260px }
    .btn { background:#0b5b0c; color:#fff; border:none; border-radius:999px; padding:8px 12px; cursor:pointer }
    .toolbar { display:flex; justify-content:space-between; align-items:center; margin-bottom:10px; gap:8px; flex-wrap:wrap }
  </style>
</head>
<body>
  <header class="topbar">
    <div class="topbar-inner">
      <div class="brand">
        <div class="brand-mark" aria-hidden="true">LOGO</div>
        <span class="brand-name">FindMyProf</span>
      </div>
      <h1 class="page-title">SCHEDULES</h1>
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
        <a class="item active" href="manage_schedules.php">SCHEDULES</a>
        <a class="item" href="add_building.php">ADD BUILDING</a>
        <a class="item" href="logs.php">LOGS</a>
        <a class="item" href="add_personel.php">ADD PERSONEL</a>
      </nav>
    </aside>

    <main class="content">
      <section class="panel">
        <div class="panel-head">Select Instructor/Executive</div>
        <div class="panel-body">
          <div class="toolbar">
            <input id="search" class="search" type="search" placeholder="Search name or email" />
            <div class="muted">Total: <?php echo count($people); ?></div>
          </div>
          <table class="list" id="peopleTable">
            <thead>
              <tr>
                <th style="width:120px">Role</th>
                <th>Name</th>
                <th>Email</th>
                <th style="width:140px">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($people as $p){ ?>
                <tr data-name="<?php echo htmlspecialchars(strtolower($p['name'].$p['email'].$p['role'])); ?>">
                  <td><?php echo htmlspecialchars($p['role']); ?></td>
                  <td><?php echo htmlspecialchars($p['name']); ?></td>
                  <td><?php echo htmlspecialchars($p['email']); ?></td>
                  <td>
                    <a class="btn" href="edit_schedule.php?user_id=<?php echo (int)$p['id']; ?>">Manage</a>
                  </td>
                </tr>
              <?php } ?>
              <?php if (empty($people)) { echo '<tr><td colspan="4" class="muted">No instructors or executives found.</td></tr>'; } ?>
            </tbody>
          </table>
        </div>
      </section>

      <?php if (isset($_GET['user_id']) && ($targetId = (int)$_GET['user_id'])>0) { ?>
      <section class="panel" style="margin-top:14px">
        <div class="panel-head">Schedule Editor for User #<?php echo (int)$targetId; ?></div>
        <div class="panel-body">
          <div class="muted">Editor coming next. You can select a user above. (We will embed the same editor used in the faculty page, restricted to the selected user.)</div>
        </div>
      </section>
      <?php } ?>
    </main>
  </div>

  <footer class="footer">
    <div class="footer-inner">© 2025 FindMyProf. All rights reserved.</div>
  </footer>

  <script>
    (function(){
      var q = document.getElementById('search');
      var rows = Array.prototype.slice.call(document.querySelectorAll('#peopleTable tbody tr'));
      function filter(){
        var s = (q.value || '').toLowerCase().trim();
        rows.forEach(function(r){
          var key = r.getAttribute('data-name') || '';
          r.style.display = (!s || key.indexOf(s) !== -1) ? '' : 'none';
        });
      }
      if (q){ q.addEventListener('input', filter); }
    })();
  </script>
</body>
</html>

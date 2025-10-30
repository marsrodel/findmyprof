<?php
  session_start();
  if (!isset($_SESSION['user_id'])) { header('Location: ../login.php'); exit; }
  require_once('../../server/db.php');
  $uid = (int)($_SESSION['user_id'] ?? 0);
  if ($uid > 0) {
    $rs = mysqli_query($conn, "SELECT role,name FROM users WHERE id={$uid} LIMIT 1");
    if ($rs && ($row = mysqli_fetch_assoc($rs))) { $_SESSION['role'] = $row['role']; $me = $row; }
  }
  // Allow Instructor, Executive, Staff to use faculty interface; redirect Admins
  if (($_SESSION['role'] ?? '') === 'Admin') { header('Location: ../admin_view/admin_dashboard.php'); exit; }

  // Filters
  $q = trim($_GET['q'] ?? '');
  $dept = trim($_GET['dept'] ?? '');
  $bid = isset($_GET['bid']) ? (int)$_GET['bid'] : 0;

  // buildings
  $buildings = [];
  $gbrs = mysqli_query($conn, "SELECT id,name FROM buildings ORDER BY name ASC");
  if ($gbrs) { while ($row = mysqli_fetch_assoc($gbrs)) { $buildings[] = $row; } }

  // Instructors list
  $instructors = [];
  $where = ["u.role IN ('Instructor','Executive')"];
  if ($q !== '') {
    $like = '%'.mysqli_real_escape_string($conn, $q).'%';
    $where[] = "(u.name LIKE '$like' OR u.email LIKE '$like')";
  }
  if ($dept !== '') { $where[] = "u.department = '".mysqli_real_escape_string($conn,$dept)."'"; }
  if ($bid > 0) { $where[] = "b.id = ".(int)$bid; }
  $whereSql = implode(' AND ', $where);
  $sql = "SELECT u.id,u.name,u.email,u.department,u.role,
                 b.name AS building_name, r.room_number, r.room_name, lp.status
            FROM users u
       LEFT JOIN (
                  SELECT x.faculty_user_id,
                         x.status,
                         COALESCE(x.room_id, pn.room_id) AS room_id
                    FROM (
                          SELECT p2.faculty_user_id, p2.status, p2.room_id, p2.created_at
                            FROM presence p2
                            JOIN (
                                  SELECT faculty_user_id, MAX(created_at) AS max_created
                                    FROM presence
                                   WHERE DATE(created_at)=CURDATE()
                                GROUP BY faculty_user_id
                                 ) px
                              ON px.faculty_user_id = p2.faculty_user_id AND px.max_created = p2.created_at
                         ) x
                    LEFT JOIN (
                               SELECT p3.faculty_user_id, p3.room_id, p3.created_at
                                 FROM presence p3
                                WHERE DATE(p3.created_at)=CURDATE() AND p3.room_id IS NOT NULL
                             ) pn
                      ON pn.faculty_user_id = x.faculty_user_id
                     AND pn.created_at = (
                          SELECT MAX(created_at) FROM presence pp
                           WHERE pp.faculty_user_id = x.faculty_user_id AND DATE(pp.created_at)=CURDATE() AND pp.room_id IS NOT NULL
                     )
                 ) lp
              ON lp.faculty_user_id = u.id
       LEFT JOIN rooms r ON r.id = lp.room_id
       LEFT JOIN buildings b ON b.id = r.building_id
           WHERE $whereSql
        ORDER BY u.name ASC
           LIMIT 200";
  $rs = mysqli_query($conn, $sql);
  if ($rs) { while ($row = mysqli_fetch_assoc($rs)) { $instructors[] = $row; } }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>FindMyProf • Faculty Search</title>
  <link rel="stylesheet" href="../../css/faculty.css" />
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
        <a class="item" href="faculty_dashboard.php">DASHBOARD</a>
        <a class="item active" href="faculty_search.php">SEARCH</a>
        <a class="item" href="faculty_profile.php">PROFILE</a>
        <a class="item" href="faculty_logs.php">LOGS</a>
      </nav>
    </aside>

    <main class="content">
      <section class="toolbar">
        <!-- Form 1: Text search only -->
        <form method="get" action="faculty_search.php" style="display:flex;gap:10px;align-items:center;flex-wrap:nowrap">
          <input class="search-input" type="text" name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="Search instructor..." />
          <button class="btn" type="submit">Search</button>
        </form>
        <!-- Form 2: Filters only -->
        <form method="get" action="faculty_search.php" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap">
          <label class="select-wrap">
            <select name="dept">
              <option value="">Sort by Department</option>
              <?php $depts = ['CBA','CTHM','CEIT','CITTE','DLHS','GEN-ED'];
                foreach ($depts as $d) { $sel = ($dept===$d)?'selected':''; echo "<option value=\"$d\" $sel>$d</option>"; } ?>
            </select>
            <span class="chev" aria-hidden>▾</span>
          </label>
          <label class="select-wrap">
            <select name="bid">
              <option value="0">Sort by Building</option>
              <?php foreach ($buildings as $b) { $sel = ($bid===(int)$b['id'])?'selected':''; ?>
                <option value="<?php echo (int)$b['id']; ?>" <?php echo $sel; ?>><?php echo htmlspecialchars($b['name']); ?></option>
              <?php } ?>
            </select>
            <span class="chev" aria-hidden>▾</span>
          </label>
          <button class="btn" type="submit">Apply</button>
        </form>
      </section>

      <section class="search-grid">
        <div class="panel">
          <div class="table main-table">
            <div class="thead">
              <div class="th">STATUS</div>
              <div class="th">NAME</div>
              <div class="th">TIME</div>
              <div class="th">ROOM</div>
              <div class="th">BUILDING</div>
              <div class="th">COLLEGE</div>
              <div class="th">ROLE</div>
            </div>
            <div class="tbody">
              <?php if (empty($instructors)) { echo '<div class="row"><div class="td" style="grid-column:1/-1;color:#6b7280">No instructors found.</div></div>'; } ?>
              <?php foreach ($instructors as $u) { ?>
                <?php $isOut = strtolower((string)($u['status'] ?? '')) === 'out' || ($u['status'] ?? '') === ''; ?>
                <div class="row">
                  <div class="td"><?php $s=(string)($u['status'] ?? ''); $ls=strtolower($s); $sd=$s!=='' ? ($ls==='dnd'?'DND':ucwords($ls)) : 'No log/Out'; echo htmlspecialchars($sd); ?></div>
                  <div class="td"><strong><?php echo htmlspecialchars($u['name']); ?></strong><div class="muted"><?php echo htmlspecialchars($u['email']); ?></div></div>
                  <div class="td muted">—</div>
                  <div class="td"><?php echo htmlspecialchars($isOut ? '—' : ($u['room_number'] ?: $u['room_name'] ?: '—')); ?></div>
                  <div class="td"><?php echo htmlspecialchars($isOut ? '—' : ($u['building_name'] ?: 'Unknown')); ?></div>
                  <div class="td"><?php echo htmlspecialchars($u['department'] ?: '—'); ?></div>
                  <div class="td"><?php echo htmlspecialchars($u['role'] ?: '—'); ?></div>
                </div>
              <?php } ?>
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

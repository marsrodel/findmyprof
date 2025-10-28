<?php
  // Load buildings to populate the Building filter
  require_once('../../server/db.php');
  $q = trim($_GET['q'] ?? '');
  $dept = trim($_GET['dept'] ?? '');
  $bid = isset($_GET['bid']) ? (int)$_GET['bid'] : 0;
  $guest_buildings = [];
  $gbrs = mysqli_query($conn, "SELECT id,name FROM buildings ORDER BY name ASC");
  if ($gbrs) { while ($row = mysqli_fetch_assoc($gbrs)) { $guest_buildings[] = $row; } }

  // Fetch instructors with optional filters
  $instructors = [];
  $where = ["u.role IN ('Instructor','Executive')"];
  if ($q !== '') {
    $like = '%'.mysqli_real_escape_string($conn, $q).'%';
    $where[] = "(u.name LIKE '$like' OR u.email LIKE '$like')";
  }
  if ($dept !== '') { $where[] = "u.department='".mysqli_real_escape_string($conn,$dept)."'"; }
  if ($bid > 0) { $where[] = "b.id=".(int)$bid; }
  $whereSql = implode(' AND ', $where);
  $sql = "SELECT u.id,u.name,u.email,u.department,u.role,
                 b.name AS building_name, r.room_number, r.room_name, p.status
            FROM users u
       LEFT JOIN v_current_presence p ON p.faculty_user_id = u.id
       LEFT JOIN rooms r ON r.id = p.room_id
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
  <title>FindMyProf • Guest View</title>
  <link rel="stylesheet" href="../../css/guest.css" />
</head>
<body>
  <header class="navbar">
    <div class="nav-content">
      <div class="brand">
        <div class="brand-logo" aria-hidden="true">LOGO</div>
        <span class="brand-name">FindMyProf</span>
      </div>
      <a class="btn login-btn" href="../login.php">Login</a>
    </div>
  </header>

  <main class="page">
    <section class="toolbar">
      <!-- Form 1: Text search only -->
      <form method="get" action="guest.php">
        <input class="search" type="text" name="q" value="<?php echo htmlspecialchars($q); ?>" placeholder="Search instructor..." />
        <button class="btn" type="submit">Search</button>
      </form>

      <!-- Form 2: Filters only (department/building) -->
      <form method="get" action="guest.php">
        <div class="filters" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap">
          <label class="select-wrap">
            <select name="dept">
              <option value="">Sort by Department</option>
              <?php $depts = ['CBA','CTHM','CEIT','CITTE','DLHS','GEN-ED'];
                foreach ($depts as $d) { $sel = ($dept===$d)?'selected':''; echo "<option value=\"$d\" $sel>$d</option>"; } ?>
            </select>
            <span class="chev" aria-hidden="true">▾</span>
          </label>
          <label class="select-wrap">
            <select name="bid">
              <option value="0">Sort by Building</option>
              <?php foreach ($guest_buildings as $b) { $sel = ($bid===(int)$b['id'])?'selected':''; ?>
                <option value="<?php echo (int)$b['id']; ?>" <?php echo $sel; ?>><?php echo htmlspecialchars($b['name']); ?></option>
              <?php } ?>
            </select>
            <span class="chev" aria-hidden="true">▾</span>
          </label>
          <button class="btn" type="submit">Apply</button>
        </div>
      </form>
    </section>

    <section class="content">
      <div class="panel main-list">
        <div class="table">
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
              <?php
                $loc = 'Unknown';
                if (!empty($u['building_name']) && (!empty($u['room_number']) || !empty($u['room_name']))) {
                  $rdisp = ($u['room_number'] !== null && $u['room_number'] !== '') ? $u['room_number'] : $u['room_name'];
                  $loc = $u['building_name'].' • '.$rdisp;
                }
              ?>
              <div class="row">
                <div class="td"><?php echo htmlspecialchars($u['status'] ?: '—'); ?></div>
                <div class="td"><strong><?php echo htmlspecialchars($u['name']); ?></strong><div class="muted"><?php echo htmlspecialchars($u['email']); ?></div></div>
                <div class="td muted">—</div>
                <div class="td"><?php echo htmlspecialchars($u['room_number'] ?: $u['room_name'] ?: '—'); ?></div>
                <div class="td"><?php echo htmlspecialchars($u['building_name'] ?: 'Unknown'); ?></div>
                <div class="td"><?php echo htmlspecialchars($u['department'] ?: '—'); ?></div>
                <div class="td"><?php echo htmlspecialchars($u['role'] ?: '—'); ?></div>
              </div>
            <?php } ?>
          </div>
        </div>
      </div>
    </section>
  </main>
  <footer class="footer">
    <div class="footer-content">© 2025 FindMyProf. All rights reserved.</div>
  </footer>
</body>
</html>

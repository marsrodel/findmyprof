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
  // Data for search/monitor similar to prototype monitor.php
  $q = trim($_GET['q'] ?? '');
  $bid = isset($_GET['bid']) ? (int)$_GET['bid'] : 0;

  // Search instructors with current location
  $instructors = [];
  if ($q !== '') {
    $like = '%'.mysqli_real_escape_string($conn, $q).'%';
    $sql = "SELECT u.id,u.name,u.email,u.department,
                   b.name AS building_name, r.room_number, r.room_name, p.status
              FROM users u
         LEFT JOIN v_current_presence p ON p.faculty_user_id = u.id
         LEFT JOIN rooms r ON r.id = p.room_id
         LEFT JOIN buildings b ON b.id = r.building_id
             WHERE u.role='Instructor' AND (u.name LIKE '$like' OR u.email LIKE '$like')
          ORDER BY u.name ASC
             LIMIT 50";
    $qr = mysqli_query($conn, $sql);
    if ($qr) { while ($row = mysqli_fetch_assoc($qr)) { $instructors[] = $row; } }
  }

  // Buildings
  $buildings = [];
  $qr = mysqli_query($conn, "SELECT id,name FROM buildings ORDER BY name ASC");
  if ($qr) { while ($row = mysqli_fetch_assoc($qr)) { $buildings[] = $row; } }

  // Rooms grouped by building
  $roomsByBuilding = [];
  $qr = mysqli_query($conn, "SELECT id,building_id,room_number,room_name FROM rooms ORDER BY building_id, room_number, room_name");
  if ($qr) {
    while ($row = mysqli_fetch_assoc($qr)) { $roomsByBuilding[$row['building_id']][] = $row; }
  }

  // Current occupants per room (exclude 'out')
  $occByRoom = [];
  $qr = mysqli_query($conn, "SELECT p.room_id, u.name, p.status
                               FROM v_current_presence p
                               JOIN users u ON u.id = p.faculty_user_id
                              WHERE p.room_id IS NOT NULL AND p.status <> 'out'");
  if ($qr) {
    while ($row = mysqli_fetch_assoc($qr)) { $occByRoom[$row['room_id']][] = [$row['name'], $row['status']]; }
  }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>FindMyProf • Admin Dashboard</title>
  <link rel="stylesheet" href="../../css/admin.css" />
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
        <a class="item active" href="admin_dashboard.php">DASHBOARD</a>
        <a class="item" href="executives_list.php">EXECUTIVES</a>
        <a class="item" href="instructors_list.php">INSTRUCTORS</a>
        <a class="item" href="buildings.php">BUILDINGS</a>
        <a class="item" href="add_building.php">ADD BUILDING</a>
        <a class="item" href="staff_list.php">STAFF</a>
        <a class="item" href="logs.php">LOGS</a>
        <a class="item" href="add_personel.php">ADD PERSONEL</a>
      </nav>
    </aside>

    <main class="content">
      <div class="content-head">
        <h2 class="welcome">Welcome, Admin</h2>
        <a class="btn add-qr" href="add_building.php">➕ ADD BUILDING</a>
      </div>

      <section class="grid">
        <div class="panel search-panel">
          <div class="panel-head">
            <h3>SEARCH INSTRUCTOR</h3>
          </div>
          <form class="search-row" method="get" action="admin_dashboard.php">
            <input class="search-input" name="q" type="text" value="<?php echo htmlspecialchars($q); ?>" placeholder="Type instructor name or email..." />
            <button class="btn search-btn" type="submit">🔍 Search</button>
          </form>
          <div class="results">
            <?php if ($q === '') { echo '<div class="note">Type a name/email, then press Search.</div>'; }
                  elseif (empty($instructors)) { echo '<div class="note">No matches for \"'.htmlspecialchars($q).'\".</div>'; }
                  else { foreach ($instructors as $u) {
                    $loc = 'Outside';
                    if (!empty($u['building_name']) && (!empty($u['room_number']) || !empty($u['room_name']))) {
                      $rdisp = ($u['room_number'] !== null && $u['room_number'] !== '') ? $u['room_number'] : $u['room_name'];
                      $loc = $u['building_name'].' • '.$rdisp;
                    }
                    echo '<div style="display:flex;align-items:center;justify-content:space-between;padding:10px;border:1px solid #d9e2db;border-radius:10px;background:#e9f0ea;margin-bottom:8px">'
                          .'<div style="display:flex;gap:10px;align-items:center">'
                            .'<div style="width:36px;height:36px;border-radius:999px;background:#cbd5e1;display:grid;place-items:center;font-weight:700">👤</div>'
                            .'<div>'
                              .'<div style="font-weight:800">'.htmlspecialchars($u['name']).'</div>'
                              .'<div class="note">'.htmlspecialchars($u['email']).($u['department']?' • '.htmlspecialchars($u['department']):'').'</div>'
                              .'<div class="note">Location: '.htmlspecialchars($loc).($u['status']?' • Status: '.htmlspecialchars($u['status']):'').'</div>'
                            .'</div>'
                          .'</div>'
                          .'<a href="edit_personel.php?id='.(int)$u['id'].'" class="btn ghost">EDIT</a>'
                        .'</div>';
                  } }
            ?>
          </div>
        </div>

        <div class="panel buildings-panel">
          <div class="panel-head">
            <h3>BUILDINGS</h3>
          </div>
          <div class="buildings">
            <?php if (empty($buildings)) { echo '<div class="note">No buildings yet.</div>'; } else { ?>
              <?php foreach ($buildings as $b) { ?>
                <div class="building-item" style="border:1px solid #d9e2db;border-radius:10px;background:#e9f0ea;padding:10px;margin-bottom:8px">
                  <div class="row" style="justify-content:space-between;align-items:center;margin:0">
                    <div style="font-weight:800;color:#1a1a1a"><?php echo htmlspecialchars($b['name']); ?></div>
                    <?php if ($bid === (int)$b['id']) { ?>
                      <a href="admin_dashboard.php<?php echo $q!==''? ('?q='.urlencode($q)) : '' ; ?>" class="btn ghost">CLOSE</a>
                    <?php } else { ?>
                      <a href="admin_dashboard.php?bid=<?php echo (int)$b['id']; ?><?php echo $q!==''? '&q='.urlencode($q):''; ?>" class="btn ghost">SHOW</a>
                    <?php } ?>
                  </div>
                  <?php if ($bid === (int)$b['id']) { ?>
                    <?php $rs = $roomsByBuilding[$b['id']] ?? []; ?>
                    <?php if (empty($rs)) { echo '<div class="note" style="margin-top:6px">No rooms yet.</div>'; } else { ?>
                      <div class="list" style="margin-top:6px">
                        <?php foreach ($rs as $r) { ?>
                          <div class="item" style="padding:8px 10px;border-radius:8px;background:#fff">
                            <div style="font-weight:700;color:#1a1a1a"><?php echo htmlspecialchars(($r['room_name'] ?: $r['room_number'])); ?></div>
                            <div class="note"><?php echo htmlspecialchars($r['room_number']); ?></div>
                            <?php $occ = $occByRoom[$r['id']] ?? []; ?>
                            <?php if (!empty($occ)) { ?>
                              <div style="margin-top:6px">
                                <?php foreach ($occ as $row) { list($nm,$st) = $row; ?>
                                  <span class="pill"><?php echo htmlspecialchars($nm); ?><?php echo $st? ' • '.htmlspecialchars($st):''; ?></span>
                                <?php } ?>
                              </div>
                            <?php } else { ?>
                              <div class="note" style="margin-top:6px">No instructors in this room currently.</div>
                            <?php } ?>
                          </div>
                        <?php } ?>
                      </div>
                    <?php } ?>
                  <?php } ?>
                </div>
              <?php } ?>
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

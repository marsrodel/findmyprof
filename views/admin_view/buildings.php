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
  $bid = isset($_GET['bid']) ? (int)$_GET['bid'] : 0;
  $rid = isset($_GET['rid']) ? (int)$_GET['rid'] : 0;
  $buildings = [];
  $q = mysqli_query($conn, "SELECT b.id, b.code, b.name, (SELECT COUNT(*) FROM rooms r WHERE r.building_id=b.id) AS room_count FROM buildings b ORDER BY b.name ASC");
  if ($q) { while ($r = mysqli_fetch_assoc($q)) { $buildings[] = $r; } }
  $rooms = [];
  if ($bid > 0) {
    $stmt = mysqli_prepare($conn, "SELECT id, room_number, room_name FROM rooms WHERE building_id=? ORDER BY room_number, room_name");
    mysqli_stmt_bind_param($stmt, 'i', $bid);
    if (mysqli_stmt_execute($stmt)) {
      $res = mysqli_stmt_get_result($stmt);
      while ($row = mysqli_fetch_assoc($res)) { $rooms[] = $row; }
    }
    mysqli_stmt_close($stmt);
  }
  $qrImg = '';
  $qrPayload = '';
  $qrBldgName = '';
  $qrRoomName = '';
  $qrRoomNumber = '';
  if ($rid > 0) {
    $serverPath = dirname(__DIR__, 2) . '/public/qr/room_' . $rid . '.png';
    if (is_file($serverPath)) { $qrImg = '../../public/qr/room_' . $rid . '.png'; }
    $stmt = mysqli_prepare($conn, "SELECT qr_payload FROM rooms WHERE id=? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'i', $rid);
    if (mysqli_stmt_execute($stmt)) {
      $res = mysqli_stmt_get_result($stmt);
      if ($row = mysqli_fetch_assoc($res)) { $qrPayload = (string)($row['qr_payload'] ?? ''); }
    }
    mysqli_stmt_close($stmt);

    // Fetch building and room names for display
    $stmt = mysqli_prepare($conn, "SELECT b.name AS bname, r.room_name AS rname, r.room_number AS rnum FROM rooms r JOIN buildings b ON b.id=r.building_id WHERE r.id=? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'i', $rid);
    if (mysqli_stmt_execute($stmt)) {
      $res = mysqli_stmt_get_result($stmt);
      if ($row = mysqli_fetch_assoc($res)) {
        $qrBldgName = (string)($row['bname'] ?? '');
        $qrRoomName = (string)($row['rname'] ?? '');
        $qrRoomNumber = (string)($row['rnum'] ?? '');
      }
    }
    mysqli_stmt_close($stmt);
  }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>FindMyProf • Buildings</title>
  <link rel="stylesheet" href="../../css/admin.css" />
</head>
<body>
  <header class="topbar">
    <div class="topbar-inner">
      <div class="brand">
        <div class="brand-mark" aria-hidden="true">LOGO</div>
        <span class="brand-name">FindMyProf</span>
      </div>
      <h1 class="page-title">BUILDINGS</h1>
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
        <a class="item active" href="buildings.php">BUILDINGS</a>
        <a class="item" href="add_building.php">ADD BUILDING</a>
        <a class="item" href="logs.php">LOGS</a>
        <a class="item" href="add_personel.php">ADD PERSONEL</a>
      </nav>
    </aside>

    <main class="content">
      <h2 class="section-title">BUILDING INFO</h2>
      <section class="buildings-grid">
        <div class="subpanel">
          <div class="sub-head">BUILDINGS</div>
          <div class="list-box">
            <?php if (empty($buildings)) { echo '<div class="empty">No buildings yet.</div>'; } ?>
            <?php foreach ($buildings as $b) { ?>
              <div class="list-item" style="display:flex;justify-content:space-between;align-items:center;padding:8px;border:1px solid #d9e2db;border-radius:8px;background:#fff;margin-bottom:8px">
                <a href="buildings.php?bid=<?php echo (int)$b['id']; ?>" style="font-weight:600;color:#1a1a1a;text-decoration:underline"><?php echo htmlspecialchars($b['name']); ?></a>
                <div style="display:flex;gap:10px;align-items:center">
                  <span class="muted"><?php echo (int)$b['room_count']; ?> rooms</span>
                  <a href="edit_building.php?id=<?php echo (int)$b['id']; ?>" class="btn ghost">Edit</a>
                </div>
              </div>
            <?php } ?>
          </div>
        </div>
        <div class="subpanel">
          <div class="sub-head">ROOMS</div>
          <div class="list-box">
            <?php if ($bid<=0) { echo '<div class="empty">Select a building to view rooms.</div>'; } elseif (empty($rooms)) { echo '<div class="empty">No rooms for this building yet.</div>'; } ?>
            <?php foreach ($rooms as $r) { ?>
              <div class="list-item" style="display:flex;justify-content:space-between;align-items:center;padding:8px;border:1px solid #d9e2db;border-radius:8px;background:#fff;margin-bottom:8px">
                <div>
                  <div style="font-weight:700;color:#1a1a1a">
                    <?php
                      $rn = (string)($r['room_name'] ?? '');
                      $num = (string)($r['room_number'] ?? '');
                      $label = ($rn !== '' && $num !== '') ? ($rn . ' - ' . $num) : ($rn !== '' ? $rn : $num);
                      echo htmlspecialchars($label);
                    ?>
                  </div>
                </div>
                <a href="buildings.php?bid=<?php echo (int)$bid; ?>&rid=<?php echo (int)$r['id']; ?>" class="btn small">View QR</a>
              </div>
            <?php } ?>
          </div>
        </div>
        <div class="subpanel">
          <div class="sub-head">QR CODE</div>
          <div class="qr-panel">
            <div class="qr-box" style="display:grid;place-items:center">
              <?php if ($rid>0 && $qrImg) { ?>
                <img src="<?php echo htmlspecialchars($qrImg); ?>" alt="Room QR" style="max-width:220px;max-height:220px" />
              <?php } elseif ($rid>0) { ?>
                <div class="muted">QR not found for this room.</div>
              <?php } else { ?>
                <div class="muted">Select a room to view QR code.</div>
              <?php } ?>
            </div>
            <div>
              <div class="qr-meta">
                <div class="meta"><strong>BUILDING:</strong><br><span><?php echo $qrBldgName!=='' ? htmlspecialchars($qrBldgName) : '&lt;Building Name&gt;'; ?></span></div>
                <div class="meta"><strong>ROOM:</strong><br><span>
                  <?php
                    $qrLabel = ($qrRoomName !== '' && $qrRoomNumber !== '') ? ($qrRoomName . ' - ' . $qrRoomNumber) : ($qrRoomName !== '' ? $qrRoomName : $qrRoomNumber);
                    echo $qrLabel !== '' ? htmlspecialchars($qrLabel) : '&lt;Room Name&gt;';
                  ?>
                </span></div>
              </div>
              <?php if ($qrImg || $qrPayload) { ?>
                <div class="qr-actions" style="margin-top:10px">
                  <?php if ($qrPayload) { ?>
                    <a class="btn secondary" href="data:text/plain;charset=utf-8,<?php echo rawurlencode($qrPayload); ?>" download="room_<?php echo (int)$rid; ?>_payload.txt">⬇ Download to File</a>
                  <?php } ?>
                  <?php if ($qrImg) { ?>
                    <a class="btn secondary" href="<?php echo htmlspecialchars($qrImg); ?>" download>⬇ Download PNG</a>
                  <?php } ?>
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

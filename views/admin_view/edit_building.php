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

  $bid = isset($_GET['id']) ? (int)$_GET['id'] : 0;
  if ($bid <= 0) { header('Location: buildings.php'); exit; }

  // Handle POST actions (update building, save rooms, delete room, add room)
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update building details
    if (isset($_POST['update_building'])) {
      $name = trim($_POST['name'] ?? '');
      $code = trim($_POST['code'] ?? '');
      $desc = trim($_POST['description'] ?? '');
      if ($name !== '' && $code !== '') {
        $stmt = mysqli_prepare($conn, "UPDATE buildings SET name=?, code=?, description=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, 'sssi', $name, $code, $desc, $bid);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
      }
      header('Location: edit_building.php?id='.$bid.'&saved=1');
      exit;
    }

    // Delete a room
    if (isset($_POST['delete_room'])) {
      $rid = (int)$_POST['delete_room'];
      // remove png
      $png = dirname(__DIR__, 2) . '/public/qr/room_' . $rid . '.png';
      if (is_file($png)) { @unlink($png); }
      $stmt = mysqli_prepare($conn, "DELETE FROM rooms WHERE id=? AND building_id=?");
      mysqli_stmt_bind_param($stmt, 'ii', $rid, $bid);
      mysqli_stmt_execute($stmt);
      mysqli_stmt_close($stmt);
      header('Location: edit_building.php?id='.$bid.'&room_deleted=1');
      exit;
    }

    // Save edits to rooms (bulk)
    if (isset($_POST['save_rooms'])) {
      $names = $_POST['room_name'] ?? [];
      $numbers = $_POST['room_number'] ?? [];
      foreach ($names as $rid => $rname) {
        $rid = (int)$rid;
        $rname = trim((string)$rname);
        $rnum = trim((string)($numbers[$rid] ?? ''));
        $stmt = mysqli_prepare($conn, "UPDATE rooms SET room_name=?, room_number=? WHERE id=? AND building_id=?");
        mysqli_stmt_bind_param($stmt, 'ssii', $rname, $rnum, $rid, $bid);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        // refresh building name for payload
        $bname = '';
        $brs = mysqli_query($conn, 'SELECT name FROM buildings WHERE id='.$bid.' LIMIT 1');
        if ($brs && ($b = mysqli_fetch_assoc($brs))) { $bname = (string)$b['name']; }

        // update payload and regenerate QR
        $payload = json_encode([
          'building' => $bname,
          'room_name' => $rname,
          'room_number' => $rnum,
          'rid' => $rid,
        ], JSON_UNESCAPED_UNICODE);
        $stmt2 = mysqli_prepare($conn, "UPDATE rooms SET qr_payload=? WHERE id=?");
        mysqli_stmt_bind_param($stmt2, 'si', $payload, $rid);
        mysqli_stmt_execute($stmt2);
        mysqli_stmt_close($stmt2);

        $qrDir = dirname(__DIR__, 2) . '/public/qr';
        if (!is_dir($qrDir)) { @mkdir($qrDir, 0777, true); }
        $pngPath = $qrDir . '/room_' . $rid . '.png';
        $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($payload);
        $png = @file_get_contents($qrUrl);
        if ($png !== false) { @file_put_contents($pngPath, $png); }
      }

      // Insert any newly added rows
      $new_names = $_POST['new_room_name'] ?? [];
      $new_numbers = $_POST['new_room_number'] ?? [];
      $countNew = max(count($new_names), count($new_numbers));
      if ($countNew > 0) {
        // fetch building name once
        $bname = '';
        $brs = mysqli_query($conn, 'SELECT name FROM buildings WHERE id='.$bid.' LIMIT 1');
        if ($brs && ($b = mysqli_fetch_assoc($brs))) { $bname = (string)$b['name']; }
        for ($i=0; $i<$countNew; $i++) {
          $nrn = trim((string)($new_names[$i] ?? ''));
          $nnum = trim((string)($new_numbers[$i] ?? ''));
          if ($nrn === '' && $nnum === '') { continue; }
          $stmt = mysqli_prepare($conn, "INSERT INTO rooms(building_id,room_number,room_name,qr_payload) VALUES(?,?,?,'')");
          mysqli_stmt_bind_param($stmt, 'iss', $bid, $nnum, $nrn);
          mysqli_stmt_execute($stmt);
          $newRid = mysqli_insert_id($conn);
          mysqli_stmt_close($stmt);

          $payload = json_encode([
            'building' => $bname,
            'room_name' => $nrn,
            'room_number' => $nnum,
            'rid' => $newRid,
          ], JSON_UNESCAPED_UNICODE);
          $stmt2 = mysqli_prepare($conn, "UPDATE rooms SET qr_payload=? WHERE id=?");
          mysqli_stmt_bind_param($stmt2, 'si', $payload, $newRid);
          mysqli_stmt_execute($stmt2);
          mysqli_stmt_close($stmt2);

          $qrDir = dirname(__DIR__, 2) . '/public/qr';
          if (!is_dir($qrDir)) { @mkdir($qrDir, 0777, true); }
          $pngPath = $qrDir . '/room_' . $newRid . '.png';
          $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($payload);
          $png = @file_get_contents($qrUrl);
          if ($png !== false) { @file_put_contents($pngPath, $png); }
        }
      }
      header('Location: edit_building.php?id='.$bid.'&rooms_saved=1');
      exit;
    }

    // Add a room
    if (isset($_POST['add_room'])) {
      $rname = trim($_POST['room_name'] ?? '');
      $rnum = trim($_POST['room_number'] ?? '');
      if ($rname !== '' || $rnum !== '') {
        $stmt = mysqli_prepare($conn, "INSERT INTO rooms(building_id,room_number,room_name,qr_payload) VALUES(?,?,?,'')");
        mysqli_stmt_bind_param($stmt, 'iss', $bid, $rnum, $rname);
        mysqli_stmt_execute($stmt);
        $rid = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);

        // payload + png
        $bname = '';
        $brs = mysqli_query($conn, 'SELECT name FROM buildings WHERE id='.$bid.' LIMIT 1');
        if ($brs && ($b = mysqli_fetch_assoc($brs))) { $bname = (string)$b['name']; }
        $payload = json_encode([
          'building' => $bname,
          'room_name' => $rname,
          'room_number' => $rnum,
          'rid' => $rid,
        ], JSON_UNESCAPED_UNICODE);

        $stmt2 = mysqli_prepare($conn, "UPDATE rooms SET qr_payload=? WHERE id=?");
        mysqli_stmt_bind_param($stmt2, 'si', $payload, $rid);
        mysqli_stmt_execute($stmt2);
        mysqli_stmt_close($stmt2);

        $qrDir = dirname(__DIR__, 2) . '/public/qr';
        if (!is_dir($qrDir)) { @mkdir($qrDir, 0777, true); }
        $pngPath = $qrDir . '/room_' . $rid . '.png';
        $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($payload);
        $png = @file_get_contents($qrUrl);
        if ($png !== false) { @file_put_contents($pngPath, $png); }
      }
      header('Location: edit_building.php?id='.$bid.'&room_added=1');
      exit;
    }
  }

  $building = null;
  $rs = mysqli_query($conn, "SELECT * FROM buildings WHERE id=".$bid." LIMIT 1");
  if ($rs && mysqli_num_rows($rs) === 1) { $building = mysqli_fetch_assoc($rs); }
  if (!$building) { header('Location: buildings.php'); exit; }

  $rooms = [];
  $stmt = mysqli_prepare($conn, "SELECT id, room_number, room_name FROM rooms WHERE building_id = ? ORDER BY room_number, room_name");
  mysqli_stmt_bind_param($stmt, 'i', $bid);
  if (mysqli_stmt_execute($stmt)) {
    $res = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($res)) { $rooms[] = $row; }
  }
  mysqli_stmt_close($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>FindMyProf • Edit Building</title>
  <link rel="stylesheet" href="../../css/admin.css" />
</head>
<body>
  <header class="topbar">
    <div class="topbar-inner">
      <div class="brand">
        <div class="brand-mark" aria-hidden="true">LOGO</div>
        <span class="brand-name">FindMyProf</span>
      </div>
      <h1 class="page-title">EDIT BUILDING</h1>
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
        <a class="item" href="logs.php">LOGS</a>
        <a class="item" href="add_personel.php">ADD PERSONEL</a>
      </nav>
    </aside>

    <main class="content">
      <section class="form-section">
        <div class="form-head">
          <h3>BUILDING DETAILS</h3>
        </div>
        <div class="form-body">
          <form method="post" action="">
            <input type="hidden" name="id" value="<?php echo (int)$building['id']; ?>" />
            <div class="field two-col">
              <label>
                <span class="label">BUILDING NAME</span>
                <input type="text" name="name" value="<?php echo htmlspecialchars($building['name']); ?>" required />
              </label>
              <label>
                <span class="label">BUILDING CODE</span>
                <input type="text" name="code" value="<?php echo htmlspecialchars($building['code']); ?>" required />
              </label>
            </div>
            <div class="field">
              <label>
                <span class="label">BUILDING DESCRIPTION</span>
                <textarea rows="5" name="description" placeholder="Short description"><?php echo htmlspecialchars($building['description'] ?? ''); ?></textarea>
              </label>
            </div>
            <div class="form-actions">
              <div>
                <a class="btn ghost" href="buildings.php?bid=<?php echo (int)$bid; ?>">Cancel</a>
                <button class="btn" type="submit" name="update_building" value="1">Save Building</button>
              </div>
            </div>
          </form>
        </div>
      </section>

      <section class="form-section">
        <div class="form-head">
          <h3>ROOMS</h3>
        </div>
        <div class="form-body">
          <?php if (empty($rooms)) { echo '<div class="empty">No rooms yet.</div>'; } ?>

          <form method="post" action="">
            <div id="roomsEditWrap">
            <?php foreach ($rooms as $r) { ?>
              <div class="field two-col room-row" style="align-items:end">
                <label>
                  <span class="label">ROOM NAME</span>
                  <input type="text" name="room_name[<?php echo (int)$r['id']; ?>]" value="<?php echo htmlspecialchars($r['room_name']); ?>" />
                </label>
                <label>
                  <span class="label">ROOM NUMBER</span>
                  <input type="text" name="room_number[<?php echo (int)$r['id']; ?>]" value="<?php echo htmlspecialchars($r['room_number']); ?>" />
                </label>
                <div>
                  <button class="btn small" name="delete_room" value="<?php echo (int)$r['id']; ?>" onclick="return confirm('Delete this room?')">Delete</button>
                </div>
              </div>
            <?php } ?>
            </div>
            <div class="form-actions">
              <div>
                <button class="btn secondary" type="button" onclick="addNewRoom()">+ Add Room</button>
                <button class="btn" type="submit" name="save_rooms" value="1">Save Rooms</button>
              </div>
            </div>
            <script>
              function addNewRoom(){
                var wrap = document.getElementById('roomsEditWrap');
                var row = document.createElement('div');
                row.className = 'field two-col room-row';
                row.style.alignItems = 'end';
                row.innerHTML =
                  '<label>'+
                    '<span class="label">ROOM NAME</span>'+
                    '<input type="text" name="new_room_name[]" placeholder="Value" />'+
                  '</label>'+
                  '<label>'+
                    '<span class="label">ROOM NUMBER</span>'+
                    '<input type="text" name="new_room_number[]" placeholder="Value" />'+
                  '</label>'+
                  '<div><button class="btn small" type="button" onclick="this.closest(\'.room-row\').remove()">Delete</button></div>';
                wrap.appendChild(row);
              }
            </script>
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


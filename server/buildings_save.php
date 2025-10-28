<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header('Location: ../views/login.php');
  exit;
}
require_once __DIR__ . '/db.php';

$uid = (int)($_SESSION['user_id'] ?? 0);
$role = $_SESSION['role'] ?? '';
if ($uid > 0) {
  // refresh role from DB in case it changed
  $rs = mysqli_query($conn, "SELECT role FROM users WHERE id={$uid} LIMIT 1");
  if ($rs && ($row = mysqli_fetch_assoc($rs))) { $role = $_SESSION['role'] = $row['role']; }
}
if ($role !== 'Admin') {
  header('Location: ../views/faculty_view/faculty_dashboard.php');
  exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: ../views/admin_view/add_building.php');
  exit;
}

$name = trim($_POST['building_name'] ?? '');
$code = trim($_POST['building_code'] ?? '');
$desc = trim($_POST['building_description'] ?? '');
$room_names = $_POST['room_name'] ?? $_POST['room_name'] ?? $_POST['room_name[]'] ?? [];
$room_numbers = $_POST['room_number'] ?? $_POST['room_number'] ?? $_POST['room_number[]'] ?? [];

if ($name === '' || $code === '') {
  header('Location: ../views/admin_view/add_building.php?error=missing');
  exit;
}

// normalize rooms arrays
if (!is_array($room_names)) $room_names = [];
if (!is_array($room_numbers)) $room_numbers = [];
$rooms = [];
for ($i = 0; $i < max(count($room_names), count($room_numbers)); $i++) {
  $rname = trim($room_names[$i] ?? '');
  $rnum  = trim($room_numbers[$i] ?? '');
  if ($rname !== '' || $rnum !== '') {
    $rooms[] = [$rname, $rnum];
  }
}

mysqli_begin_transaction($conn);
try {
  // create building
  $stmt = mysqli_prepare($conn, "INSERT INTO buildings(code,name,description) VALUES(?,?,?)");
  mysqli_stmt_bind_param($stmt, 'sss', $code, $name, $desc);
  if (!mysqli_stmt_execute($stmt)) { throw new Exception('insert building failed'); }
  $building_id = mysqli_insert_id($conn);
  mysqli_stmt_close($stmt);

  // prepare insert room
  $stmtRoom = mysqli_prepare($conn, "INSERT INTO rooms(building_id,room_number,room_name,qr_payload) VALUES(?,?,?,?)");

  // ensure QR directory
  $qrDir = dirname(__DIR__) . '/public/qr';
  if (!is_dir($qrDir)) { @mkdir($qrDir, 0777, true); }

  foreach ($rooms as [$rname, $rnum]) {
    $payload = json_encode([
      'building' => $name,
      'room_name' => $rname,
      'room_number' => $rnum,
      // room id is not known until after insert; set placeholder and update after
    ], JSON_UNESCAPED_UNICODE);

    $nullPayload = $payload; // temporary; will update with rid after insert
    mysqli_stmt_bind_param($stmtRoom, 'isss', $building_id, $rnum, $rname, $nullPayload);
    if (!mysqli_stmt_execute($stmtRoom)) { throw new Exception('insert room failed'); }
    $room_id = mysqli_insert_id($conn);

    // update payload with rid
    $payload = json_encode([
      'building' => $name,
      'room_name' => $rname,
      'room_number' => $rnum,
      'rid' => $room_id,
    ], JSON_UNESCAPED_UNICODE);

    $stmtUpd = mysqli_prepare($conn, "UPDATE rooms SET qr_payload=? WHERE id=?");
    mysqli_stmt_bind_param($stmtUpd, 'si', $payload, $room_id);
    if (!mysqli_stmt_execute($stmtUpd)) { throw new Exception('update payload failed'); }
    mysqli_stmt_close($stmtUpd);

    // generate QR png via 3rd-party service
    $pngPath = $qrDir . '/room_' . $room_id . '.png';
    $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($payload);
    $png = @file_get_contents($qrUrl);
    if ($png !== false) { @file_put_contents($pngPath, $png); }
  }
  if (isset($stmtRoom)) { mysqli_stmt_close($stmtRoom); }

  mysqli_commit($conn);
  header('Location: ../views/admin_view/buildings.php?created=1');
  exit;
} catch (Throwable $e) {
  mysqli_rollback($conn);
  header('Location: ../views/admin_view/add_building.php?error=db');
  exit;
}

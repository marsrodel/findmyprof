<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');
require_once __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: buildings.php'); exit; }

$name = trim($_POST['building_name'] ?? '');
$code = trim($_POST['building_code'] ?? '');
$desc = trim($_POST['description'] ?? '');
$rooms = $_POST['rooms'] ?? ['name'=>[], 'number'=>[]];

if ($name === '' || $code === '') { header('Location: buildings.html?error=empty'); exit; }

try {
  $pdo->beginTransaction();

  // Create building
  $stmt = $pdo->prepare('INSERT INTO buildings(code,name,description) VALUES(?,?,?)');
  $stmt->execute([$code,$name,$desc !== '' ? $desc : null]);
  $building_id = (int)$pdo->lastInsertId();

  // Ensure QR output dir exists
  $qrDir = __DIR__ . '/../public/qr';
  if (!is_dir($qrDir)) { @mkdir($qrDir, 0777, true); }

  $roomNames = $rooms['name'] ?? [];
  $roomNumbers = $rooms['number'] ?? [];
  $n = max(count($roomNames), count($roomNumbers));

  for ($i=0; $i<$n; $i++) {
    $rname = isset($roomNames[$i]) ? trim($roomNames[$i]) : '';
    $rnum  = isset($roomNumbers[$i]) ? trim($roomNumbers[$i]) : '';
    if ($rname === '' && $rnum === '') { continue; }

    // Insert room (qr_payload temporary, update after id known)
    $stmt = $pdo->prepare('INSERT INTO rooms(building_id,room_number,room_name,qr_payload) VALUES(?,?,?,"")');
    $stmt->execute([$building_id, $rnum, $rname]);
    $room_id = (int)$pdo->lastInsertId();

    // QR payload contains Building name, Room name, and Room number
    $payload = json_encode([
      'building' => $name,
      'room_name' => $rname,
      'room_number' => $rnum,
      'rid' => $room_id
    ], JSON_UNESCAPED_UNICODE);

    // Save payload in DB
    $stmt = $pdo->prepare('UPDATE rooms SET qr_payload=? WHERE id=?');
    $stmt->execute([$payload, $room_id]);

    // Generate QR image using simple third-party API (no server lib required)
    // You can replace this with a local PHP QR library later.
    $pngPath = $qrDir . '/room_' . $room_id . '.png';
    $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($payload);
    // Fetch and save
    $png = @file_get_contents($qrUrl);
    if ($png !== false) { file_put_contents($pngPath, $png); }
  }

  $pdo->commit();
  header('Location: details.php?building_created=1');
  exit;

} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  header('Location: buildings.html?error=db');
  exit;
}

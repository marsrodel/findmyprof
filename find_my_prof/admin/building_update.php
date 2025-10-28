<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');
require_once __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: details.php'); exit; }

$id = (int)($_POST['id'] ?? 0);
$name = trim($_POST['name'] ?? '');
$code = trim($_POST['code'] ?? '');
$desc = trim($_POST['description'] ?? '');

if ($id <= 0 || $name === '' || $code === '') {
  header('Location: building_edit.php?id=' . $id . '&error=invalid');
  exit;
}

$stmt = $pdo->prepare('UPDATE buildings SET name=?, code=?, description=? WHERE id=?');
$stmt->execute([$name, $code, ($desc !== '' ? $desc : null), $id]);

// Regenerate QR for all rooms in this building (reflect new building name)
try {
  $rooms = $pdo->prepare('SELECT id, room_name, room_number FROM rooms WHERE building_id = ?');
  $rooms->execute([$id]);
  $qrDir = __DIR__ . '/../public/qr';
  if (!is_dir($qrDir)) { @mkdir($qrDir, 0777, true); }
  foreach ($rooms as $r) {
    $rid = (int)$r['id'];
    $roomName = (string)$r['room_name'];
    $roomNum = (string)($r['room_number'] ?? '');
    $payload = json_encode(['building'=>$name, 'room'=>($roomNum !== '' ? $roomNum : $roomName), 'rid'=>$rid], JSON_UNESCAPED_UNICODE);
    $pdo->prepare('UPDATE rooms SET qr_payload=? WHERE id=?')->execute([$payload,$rid]);
    $pngPath = $qrDir . '/room_' . $rid . '.png';
    $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($payload);
    $png = @file_get_contents($qrUrl);
    if ($png !== false) { file_put_contents($pngPath, $png); }
  }
} catch (Throwable $e) {
  // ignore QR errors for now
}

header('Location: building_edit.php?id=' . $id . '&updated=1');

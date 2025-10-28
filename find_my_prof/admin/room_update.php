<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');
require_once __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: details.php'); exit; }

$id = (int)($_POST['id'] ?? 0);
$room_name = trim($_POST['room_name'] ?? '');
$room_number = trim($_POST['room_number'] ?? '');

if ($id <= 0 || $room_name === '') { header('Location: details.php'); exit; }

// fetch to know building id
$stmt = $pdo->prepare('SELECT building_id FROM rooms WHERE id=?');
$stmt->execute([$id]);
$ridRow = $stmt->fetch();
if (!$ridRow) { header('Location: details.php'); exit; }
$bid = (int)$ridRow['building_id'];

// update room
$stmt = $pdo->prepare('UPDATE rooms SET room_name=?, room_number=? WHERE id=?');
$stmt->execute([$room_name, $room_number !== '' ? $room_number : null, $id]);

// optionally regenerate QR payload file to reflect name/number changes
try {
  $b = $pdo->prepare('SELECT name FROM buildings WHERE id=?');
  $b->execute([$bid]);
  $bname = (string)($b->fetchColumn());

  $payload = json_encode([
    'building' => $bname,
    'room_name' => $room_name,
    'room_number' => $room_number,
    'rid' => $id
  ], JSON_UNESCAPED_UNICODE);
  $pdo->prepare('UPDATE rooms SET qr_payload=? WHERE id=?')->execute([$payload,$id]);

  $qrDir = __DIR__ . '/../public/qr';
  if (!is_dir($qrDir)) { @mkdir($qrDir, 0777, true); }
  $pngPath = $qrDir . '/room_' . $id . '.png';
  $qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($payload);
  $png = @file_get_contents($qrUrl);
  if ($png !== false) { file_put_contents($pngPath, $png); }
} catch (Throwable $e) {
  // ignore QR errors for now
}

header('Location: building_edit.php?id=' . $bid . '&updated=1');

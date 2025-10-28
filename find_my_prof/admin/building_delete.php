<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');
require_once __DIR__ . '/../includes/db.php';

$bid = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($bid <= 0) { header('Location: details.php'); exit; }

try {
  // collect room ids to delete qr images
  $stmt = $pdo->prepare('SELECT id FROM rooms WHERE building_id = ?');
  $stmt->execute([$bid]);
  $roomIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

  $pdo->beginTransaction();
  $pdo->prepare('DELETE FROM buildings WHERE id = ?')->execute([$bid]);
  $pdo->commit();

  // remove qr images (best-effort)
  $qrDir = __DIR__ . '/../public/qr';
  foreach ($roomIds as $rid) {
    $png = $qrDir . '/room_' . (int)$rid . '.png';
    if (is_file($png)) { @unlink($png); }
  }

  header('Location: details.php?deleted=1');
  exit;
} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  header('Location: building_edit.php?id=' . $bid . '&error=db');
  exit;
}

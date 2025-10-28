<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('faculty');
require_once __DIR__ . '/../includes/db.php';

$uid = $_SESSION['user']['id'];
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: scan.php'); exit; }

$rid = (int)($_POST['room_id'] ?? 0);
$action = $_POST['action'] === 'checkout' ? 'checkout' : 'checkin';

// validate room
$stmt = $pdo->prepare('SELECT r.id, b.id AS bid FROM rooms r JOIN buildings b ON b.id=r.building_id WHERE r.id=?');
$stmt->execute([$rid]);
$room = $stmt->fetch();
if (!$room) { header('Location: scan.php?error=room'); exit; }

try {
  $pdo->beginTransaction();

  if ($action === 'checkin') {
    // presence row
    $pdo->prepare('INSERT INTO presence(faculty_user_id,room_id,status,source) VALUES(?,?,"available","qr")')
        ->execute([$uid,$rid]);
    // logs
    $pdo->prepare('INSERT INTO instructor_logs(faculty_user_id,action,status,room_id,source) VALUES(?,"checkin","available",?,"qr")')
        ->execute([$uid,$rid]);
    $pdo->prepare('INSERT INTO room_logs(room_id,faculty_user_id,action) VALUES(?,? ,"checkin")')
        ->execute([$rid,$uid]);
  } else {
    // checkout: mark presence as out
    $pdo->prepare('INSERT INTO presence(faculty_user_id,room_id,status,source) VALUES(?,?,"out","qr")')
        ->execute([$uid,$rid]);
    $pdo->prepare('INSERT INTO instructor_logs(faculty_user_id,action,status,room_id,source) VALUES(?,"checkout","out",?,"qr")')
        ->execute([$uid,$rid]);
    $pdo->prepare('INSERT INTO room_logs(room_id,faculty_user_id,action) VALUES(?,? ,"checkout")')
        ->execute([$rid,$uid]);
  }

  $pdo->commit();
} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  header('Location: scan.php?error=db');
  exit;
}

header('Location: scan.php?ok=1');

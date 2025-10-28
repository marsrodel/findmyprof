<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('faculty');
require_once __DIR__ . '/../includes/db.php';

$uid = $_SESSION['user']['id'];

// Resolve room from query
$rid = isset($_GET['rid']) ? (int)$_GET['rid'] : 0;
$data = $_GET['data'] ?? '';
if (!$rid && $data) {
  $decoded = null;
  if ($data[0] === '{') {
    $decoded = json_decode($data, true);
  } else {
    $try = json_decode(urldecode($data), true);
    if (json_last_error() === JSON_ERROR_NONE) { $decoded = $try; }
  }
  if (is_array($decoded)) {
    if (isset($decoded['rid'])) {
      $rid = (int)$decoded['rid'];
    } else {
      // Optional: match by building+room if rid missing
      $bname = trim((string)($decoded['building'] ?? ''));
      $rnum  = trim((string)($decoded['room_number'] ?? ''));
      $rname = trim((string)($decoded['room_name'] ?? ''));
      if ($bname !== '' && ($rnum !== '' || $rname !== '')) {
        $stmt = $pdo->prepare('SELECT r.id FROM rooms r JOIN buildings b ON b.id=r.building_id WHERE b.name=? AND (r.room_number=? OR r.room_name=?) LIMIT 1');
        $stmt->execute([$bname, $rnum, $rname]);
        $rid = (int)($stmt->fetchColumn() ?: 0);
      }
    }
  }
}

if ($rid <= 0) {
  http_response_code(400);
  echo '<!DOCTYPE html><html><body><p>Invalid QR. Missing room.</p></body></html>';
  exit;
}

// Load room+building
$stmt = $pdo->prepare('SELECT r.id, r.room_number, r.room_name, b.name AS building_name, r.building_id FROM rooms r JOIN buildings b ON b.id=r.building_id WHERE r.id=?');
$stmt->execute([$rid]);
$room = $stmt->fetch();
if (!$room) { http_response_code(404); echo '<!DOCTYPE html><html><body><p>Room not found.</p></body></html>'; exit; }

// Determine current presence
$pres = $pdo->prepare('SELECT room_id, status FROM v_current_presence WHERE faculty_user_id=? LIMIT 1');
$pres->execute([$uid]);
$cur = $pres->fetch();

$inThisRoom = $cur && (int)$cur['room_id'] === (int)$room['id'] && $cur['status'] !== 'out';
$action = $inThisRoom ? 'checkout' : 'checkin';
$question = $inThisRoom ? 'Log out from this room?' : 'Log in to this room?';

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Confirm <?= htmlspecialchars(ucfirst($action)) ?></title>
  <style>
    :root{--text:#0f172a;--muted:#475569;--border:rgba(15,23,42,.12);--bg:#f1f5f9;--card:#fff;--accent:#1f2937}
    *{box-sizing:border-box} html,body{height:100%}
    body{margin:0;font-family:system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial;color:var(--text);background:var(--bg)}
    .container{max-width:600px;margin:0 auto;padding:16px}
    .card{background:var(--card);border:1px solid var(--border);border-radius:12px;box-shadow:0 6px 18px rgba(15,23,42,.08);padding:16px;margin-top:20px}
    .title{font-weight:800;font-size:18px}
    .muted{color:var(--muted)}
    .btn{height:40px;padding:0 14px;border-radius:8px;border:0;background:#1f2937;color:#fff;cursor:pointer}
    .row{display:flex;gap:10px;flex-wrap:wrap;margin-top:12px}
    a.link{color:#111827}
  </style>
</head>
<body>
  <div class="container">
    <div class="title">QR Check <?= htmlspecialchars(ucfirst($action)) ?></div>
    <div class="card">
      <div class="muted">Building</div>
      <div><strong><?= htmlspecialchars($room['building_name']) ?></strong></div>
      <div class="muted" style="margin-top:8px">Room</div>
      <div><strong><?= htmlspecialchars($room['room_number'] ?: $room['room_name']) ?></strong> <span class="muted"><?= htmlspecialchars($room['room_name']) ?></span></div>
      <div style="margin-top:12px"><?= htmlspecialchars($question) ?></div>
      <form class="row" method="post" action="confirm_check.php">
        <input type="hidden" name="room_id" value="<?= (int)$room['id'] ?>" />
        <input type="hidden" name="action" value="<?= htmlspecialchars($action) ?>" />
        <button class="btn" type="submit">Confirm</button>
        <a class="link" href="scan.php">Cancel</a>
      </form>
    </div>
  </div>
</body>
</html>

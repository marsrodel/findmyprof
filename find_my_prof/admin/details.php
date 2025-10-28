<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');
require_once __DIR__ . '/../includes/db.php';

$bid = isset($_GET['bid']) ? (int)$_GET['bid'] : 0;
$rid = isset($_GET['rid']) ? (int)$_GET['rid'] : 0;

// Fetch buildings with room counts
$buildings = $pdo->query(
  "SELECT b.id, b.code, b.name, (
      SELECT COUNT(*) FROM rooms r WHERE r.building_id = b.id
   ) AS room_count
   FROM buildings b ORDER BY b.name ASC"
)->fetchAll();

$rooms = [];
if ($bid > 0) {
  $stmt = $pdo->prepare("SELECT id, room_number, room_name FROM rooms WHERE building_id = ? ORDER BY room_number, room_name");
  $stmt->execute([$bid]);
  $rooms = $stmt->fetchAll();
}

$qrImg = '';
if ($rid > 0) {
  // Expected path created by buildings_save.php
  $qrRel = '../public/qr/room_' . $rid . '.png';
  $qrPath = __DIR__ . '/../public/qr/room_' . $rid . '.png';
  if (is_file($qrPath)) {
    $qrImg = $qrRel;
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin • Building Details</title>
  <style>
    :root{--text:#0f172a;--muted:#475569;--border:rgba(15,23,42,.12);--bg:#f1f5f9;--card:#fff;--accent:#1f2937}
    *{box-sizing:border-box} html,body{height:100%}
    body{margin:0;font-family:system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial;color:var(--text);background:var(--bg)}
    .container{max-width:1200px;margin:0 auto;padding:16px}
    .top{display:flex;align-items:center;justify-content:space-between;margin:8px 0 16px}
    .title{font-size:22px;font-weight:800}
    .back{font-size:13px;color:var(--muted);text-decoration:underline}
    .grid{display:grid;grid-template-columns:320px 1fr 300px;gap:12px}
    @media (max-width:980px){.grid{grid-template-columns:1fr}}
    .card{background:var(--card);border:1px solid var(--border);border-radius:12px;box-shadow:0 6px 18px rgba(15,23,42,.08);padding:16px}
    .list{display:grid;gap:8px}
    .item{border:1px solid var(--border);border-radius:10px;padding:10px 12px;background:#fff;display:flex;justify-content:space-between;align-items:center}
    .muted{color:var(--muted)}
    .qrwrap{display:grid;place-items:center;height:260px;border:1px dashed var(--border);border-radius:12px;background:#fff}
    a.link{color:#111827}
  </style>
</head>
<body>
  <div class="container">
    <div class="top">
      <div class="title">Building Details</div>
      <a class="back" href="admin.html">← Back to Dashboard</a>
    </div>

    <div class="grid">
      <section class="card">
        <h3 style="margin:0 0 8px;font-size:16px">Buildings</h3>
        <div class="list">
          <?php if (!$buildings): ?>
            <div class="muted">No buildings yet.</div>
          <?php else: ?>
            <?php foreach ($buildings as $b): ?>
              <div class="item">
                <a class="link" href="details.php?bid=<?= (int)$b['id'] ?>"><?= htmlspecialchars($b['name']) ?></a>
                <div style="display:flex;gap:10px;align-items:center">
                  <span class="muted"><?= (int)$b['room_count'] ?> rooms</span>
                  <a class="back" href="building_edit.php?id=<?= (int)$b['id'] ?>">Edit</a>
                </div>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </section>

      <section class="card">
        <h3 style="margin:0 0 8px;font-size:16px">Rooms <?= $bid>0 ? '(Building ID: '.(int)$bid.')' : '' ?></h3>
        <div class="list">
          <?php if ($bid<=0): ?>
            <div class="muted">Select a building to view rooms.</div>
          <?php elseif (!$rooms): ?>
            <div class="muted">No rooms for this building yet.</div>
          <?php else: ?>
            <?php foreach ($rooms as $r): ?>
              <div class="item">
                <div>
                  <div><strong><?= htmlspecialchars($r['room_number'] ?: $r['room_name']) ?></strong></div>
                  <div class="muted"><?= htmlspecialchars($r['room_name']) ?></div>
                </div>
                <a class="link" href="details.php?bid=<?= (int)$bid ?>&rid=<?= (int)$r['id'] ?>">View QR</a>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </section>

      <section class="card">
        <h3 style="margin:0 0 8px;font-size:16px">QR Code</h3>
        <div class="qrwrap">
          <?php if ($rid>0 && $qrImg): ?>
            <img src="<?= htmlspecialchars($qrImg) ?>" alt="Room QR" style="max-width:240px;max-height:240px" />
          <?php elseif ($rid>0): ?>
            <div class="muted">QR not found for this room.</div>
          <?php else: ?>
            <div class="muted">Select a room to view QR code.</div>
          <?php endif; ?>
        </div>
        <?php if ($qrImg): ?>
          <div style="margin-top:8px"><a class="link" href="<?= htmlspecialchars($qrImg) ?>" download>Download QR PNG</a></div>
        <?php endif; ?>
      </section>
    </div>
  </div>
</body>
</html>

<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');
require_once __DIR__ . '/../includes/db.php';

$bid = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($bid <= 0) { header('Location: details.php'); exit; }

$stmt = $pdo->prepare('SELECT * FROM buildings WHERE id = ? LIMIT 1');
$stmt->execute([$bid]);
$building = $stmt->fetch();
if (!$building) { header('Location: details.php'); exit; }

$r = $pdo->prepare('SELECT id, room_number, room_name FROM rooms WHERE building_id = ? ORDER BY room_number, room_name');
$r->execute([$bid]);
$rooms = $r->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin • Edit Building</title>
  <style>
    :root{--text:#0f172a;--muted:#475569;--border:rgba(15,23,42,.12);--bg:#f1f5f9;--card:#fff;--accent:#1f2937}
    *{box-sizing:border-box} html,body{height:100%}
    body{margin:0;font-family:system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial;color:var(--text);background:var(--bg)}
    .container{max-width:1100px;margin:0 auto;padding:16px}
    .top{display:flex;align-items:center;justify-content:space-between;margin:8px 0 16px}
    .title{font-size:22px;font-weight:800}
    .back{font-size:13px;color:var(--muted);text-decoration:underline}
    .card{background:var(--card);border:1px solid var(--border);border-radius:12px;box-shadow:0 6px 18px rgba(15,23,42,.08);padding:16px}
    .row{display:flex;gap:10px;flex-wrap:wrap}
    .col{flex:1;min-width:240px}
    .field{display:grid;gap:6px;margin-bottom:10px}
    .label{font-size:13px;color:var(--muted)}
    .input,textarea,select{height:40px;padding:10px 12px;border:1px solid var(--border);border-radius:8px;font-size:14px;outline:none}
    textarea{height:84px;resize:vertical}
    .btn{height:40px;padding:0 14px;border-radius:8px;border:0;background:#1f2937;color:#fff;cursor:pointer}
    .list{display:grid;gap:8px;margin-top:10px}
    .item{border:1px solid var(--border);border-radius:10px;padding:10px 12px;background:#fff;display:flex;justify-content:space-between;align-items:center}
    a.link{color:#111827}
  </style>
</head>
<body>
  <div class="container">
    <div class="top">
      <div class="title">Edit Building</div>
      <a class="back" href="details.php?bid=<?= (int)$bid ?>">← Back to Details</a>
    </div>

    <section class="card">
      <form action="building_update.php" method="post">
        <input type="hidden" name="id" value="<?= (int)$building['id'] ?>" />
        <div class="row">
          <div class="col"><div class="field"><span class="label">Building Name</span>
            <input class="input" type="text" name="name" value="<?= htmlspecialchars($building['name']) ?>" required />
          </div></div>
          <div class="col"><div class="field"><span class="label">Building Code</span>
            <input class="input" type="text" name="code" value="<?= htmlspecialchars($building['code']) ?>" required />
          </div></div>
        </div>
        <div class="field"><span class="label">Description</span>
          <textarea name="description" placeholder="Short description"><?= htmlspecialchars($building['description'] ?? '') ?></textarea>
        </div>
        <div class="row">
          <button class="btn" type="submit">Save Building</button>
          <a class="back" href="building_delete.php?id=<?= (int)$bid ?>" onclick="return confirm('Delete this building and all its rooms?')">Delete Building</a>
        </div>
      </form>
    </section>

    <section class="card">
      <h3 style="margin:0 0 6px;font-size:16px">Rooms</h3>
      <div class="list">
        <?php if (!$rooms): ?><div class="back">No rooms yet.</div><?php endif; ?>
        <?php foreach ($rooms as $r): ?>
          <div class="item">
            <div>
              <div><strong><?= htmlspecialchars($r['room_number'] ?: $r['room_name']) ?></strong></div>
              <div class="back"><?= htmlspecialchars($r['room_name']) ?></div>
            </div>
            <div style="display:flex;gap:10px;align-items:center">
              <a class="link" href="room_edit.php?id=<?= (int)$r['id'] ?>">Edit</a>
              <a class="back" href="room_delete.php?id=<?= (int)$r['id'] ?>&bid=<?= (int)$bid ?>" onclick="return confirm('Delete this room?')">Delete</a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      <form action="room_add.php" method="post" style="margin-top:10px">
        <input type="hidden" name="building_id" value="<?= (int)$bid ?>" />
        <div class="row">
          <div class="col"><div class="field"><span class="label">Room Name</span><input class="input" type="text" name="room_name" placeholder="e.g., ACAD" required /></div></div>
          <div class="col"><div class="field"><span class="label">Room Number</span><input class="input" type="text" name="room_number" placeholder="e.g., 123" /></div></div>
        </div>
        <button class="btn" type="submit">+ Add Room</button>
      </form>
    </section>
  </div>
</body>
</html>

<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');
require_once __DIR__ . '/../includes/db.php';

$q = trim($_GET['q'] ?? '');
$bid = isset($_GET['bid']) ? (int)$_GET['bid'] : 0;

// Search instructors with current location
$instructors = [];
if ($q !== '') {
  $like = '%' . $q . '%';
  $stmt = $pdo->prepare(
    "SELECT u.id,u.name,u.email,u.department,
            b.name AS building_name, r.room_number, r.room_name, p.status
       FROM users u
  LEFT JOIN v_current_presence p ON p.faculty_user_id = u.id
  LEFT JOIN rooms r ON r.id = p.room_id
  LEFT JOIN buildings b ON b.id = r.building_id
      WHERE u.role='faculty' AND (u.name LIKE ? OR u.email LIKE ?)
   ORDER BY u.name ASC
      LIMIT 50"
  );
  $stmt->execute([$like,$like]);
  $instructors = $stmt->fetchAll();
}

// Buildings
$buildings = $pdo->query(
  "SELECT b.id, b.name FROM buildings b ORDER BY b.name ASC"
)->fetchAll();

// Rooms for all buildings
$roomsStmt = $pdo->query("SELECT id, building_id, room_number, room_name FROM rooms ORDER BY building_id, room_number, room_name");
$roomsAll = $roomsStmt->fetchAll();
$roomsByBuilding = [];
foreach ($roomsAll as $r) { $roomsByBuilding[$r['building_id']][] = $r; }

// Current occupants per room (exclude 'out')
$occStmt = $pdo->query(
  "SELECT p.room_id, u.name, p.status
     FROM v_current_presence p
     JOIN users u ON u.id = p.faculty_user_id
    WHERE p.room_id IS NOT NULL AND p.status <> 'out'"
);
$occByRoom = [];
foreach ($occStmt as $row) { $occByRoom[$row['room_id']][] = [$row['name'], $row['status']]; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin • Search & Show Buildings</title>
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
    .input{height:40px;padding:10px 12px;border:1px solid var(--border);border-radius:8px;font-size:14px;outline:none}
    .input:focus{border-color:var(--accent);box-shadow:0 0 0 3px rgba(31,41,55,.15)}
    .btn{height:40px;padding:0 14px;border-radius:8px;border:0;background:var(--accent);color:#fff;cursor:pointer}
    .item{border:1px solid var(--border);border-radius:10px;padding:10px 12px;background:#fff}
    .note{color:var(--muted);font-size:13px}
    .list{display:grid;gap:8px}
    a.link{color:#111827}
    .pill{display:inline-block;margin:2px 4px 0 0;padding:2px 8px;border-radius:999px;background:#e2e8f0;font-size:12px}
  </style>
</head>
<body>
  <div class="container">
    <div class="top">
      <div class="title">Search & Show Buildings</div>
      <a class="back" href="admin.html">← Back to Dashboard</a>
    </div>

    <section class="card">
      <div class="row">
        <div class="col">
          <div class="field"><span class="label">Search Instructor</span>
            <form class="row" method="get" action="monitor.php">
              <input class="input" type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Type instructor name..." />
              <button class="btn" type="submit">Search</button>
            </form>
          </div>
          <div class="list">
            <?php if ($q === ''): ?>
              <div class="item"><div class="note">Type a name/email, then press Search.</div></div>
            <?php elseif (!$instructors): ?>
              <div class="item"><div class="note">No matches for "<?= htmlspecialchars($q) ?>".</div></div>
            <?php else: ?>
              <?php foreach ($instructors as $u): ?>
                <div class="item">
                  <div><strong><?= htmlspecialchars($u['name']) ?></strong></div>
                  <div class="note"><?= htmlspecialchars($u['email']) ?><?= $u['department']? ' • '.htmlspecialchars($u['department']):'' ?></div>
                  <?php
                    $loc = 'Outside';
                    if (!empty($u['building_name']) && (!empty($u['room_number']) || !empty($u['room_name']))) {
                      $rdisp = $u['room_number'] !== null && $u['room_number'] !== '' ? $u['room_number'] : $u['room_name'];
                      $loc = $u['building_name'] . ' • ' . $rdisp;
                    }
                  ?>
                  <div class="note">Location: <?= htmlspecialchars($loc) ?><?= $u['status']? ' • Status: '.htmlspecialchars($u['status']):'' ?></div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
        <div class="col">
          <div class="field"><span class="label">Show Buildings</span></div>
          <div class="list">
            <?php if (!$buildings): ?>
              <div class="item"><div class="note">No buildings yet.</div></div>
            <?php else: ?>
              <?php foreach ($buildings as $b): ?>
                <div class="item">
                  <div style="display:flex;align-items:center;justify-content:space-between">
                    <?php if ($bid === (int)$b['id']): ?>
                      <a class="link" href="monitor.php<?= $q!=='' ? ('?q='.urlencode($q)) : '' ?>"><?= htmlspecialchars($b['name']) ?></a>
                      <a class="back" href="monitor.php<?= $q!=='' ? ('?q='.urlencode($q)) : '' ?>">Close</a>
                    <?php else: ?>
                      <a class="link" href="monitor.php?bid=<?= (int)$b['id'] ?><?= $q!=='' ? '&q='.urlencode($q):'' ?>"><?= htmlspecialchars($b['name']) ?></a>
                      <a class="back" href="monitor.php?bid=<?= (int)$b['id'] ?><?= $q!=='' ? '&q='.urlencode($q):'' ?>">Open</a>
                    <?php endif; ?>
                  </div>
                  <?php if ($bid === (int)$b['id']): ?>
                    <?php $rs = $roomsByBuilding[$b['id']] ?? []; ?>
                    <?php if (!$rs): ?>
                      <div class="note" style="margin-top:6px">No rooms yet.</div>
                    <?php else: ?>
                      <div class="list" style="margin-top:6px">
                        <?php foreach ($rs as $r): ?>
                          <div class="item" style="padding:8px 10px">
                            <div><strong><?= htmlspecialchars($r['room_number'] ?: $r['room_name']) ?></strong>
                              <span class="note"><?= htmlspecialchars($r['room_name']) ?></span>
                            </div>
                            <?php $occ = $occByRoom[$r['id']] ?? []; ?>
                            <?php if ($occ): ?>
                              <div style="margin-top:6px">
                                <?php foreach ($occ as $row): list($name,$st) = $row; ?>
                                  <span class="pill"><?= htmlspecialchars($name) ?><?= $st? ' • '.htmlspecialchars($st):'' ?></span>
                                <?php endforeach; ?>
                              </div>
                            <?php else: ?>
                              <div class="note" style="margin-top:6px">No instructors in this room currently.</div>
                            <?php endif; ?>
                          </div>
                        <?php endforeach; ?>
                      </div>
                    <?php endif; ?>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </section>
  </div>
</body>
</html>

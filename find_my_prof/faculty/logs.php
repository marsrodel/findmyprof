<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('faculty');
require_once __DIR__ . '/../includes/db.php';

$uid = $_SESSION['user']['id'];

$rows = $pdo->prepare(
  "SELECT il.id, il.created_at, il.action, il.status, il.note, il.source,
          r.room_number, r.room_name, b.name AS bname
     FROM instructor_logs il
LEFT JOIN rooms r ON r.id = il.room_id
LEFT JOIN buildings b ON b.id = r.building_id
    WHERE il.faculty_user_id = ?
 ORDER BY il.created_at DESC, il.id DESC
    LIMIT 200"
);
$rows->execute([$uid]);
$logs = $rows->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Faculty • My Logs</title>
  <style>
    :root{--text:#0f172a;--muted:#475569;--border:rgba(15,23,42,.12);--bg:#f1f5f9;--card:#fff}
    *{box-sizing:border-box} html,body{height:100%}
    body{margin:0;font-family:system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial;color:var(--text);background:var(--bg)}
    .container{max-width:1000px;margin:0 auto;padding:16px}
    .top{display:flex;align-items:center;justify-content:space-between;margin:8px 0 16px}
    .title{font-size:22px;font-weight:800}
    .back{font-size:13px;color:var(--muted);text-decoration:underline}
    .card{background:var(--card);border:1px solid var(--border);border-radius:12px;box-shadow:0 6px 18px rgba(15,23,42,.08);padding:16px}
    .table{width:100%;border-collapse:collapse}
    .table th,.table td{border-bottom:1px dashed var(--border);text-align:left;padding:8px}
    .note{color:var(--muted);font-size:13px}
  </style>
</head>
<body>
  <div class="container">
    <div class="top">
      <div class="title">My Logs</div>
      <a class="back" href="index.php">← Back</a>
    </div>

    <section class="card">
      <h3 style="margin:0 0 6px;font-size:16px">Recent Activity</h3>
      <?php if (!$logs): ?>
        <div class="note">No logs yet. Your QR check-ins/outs and status changes will appear here.</div>
      <?php else: ?>
      <table class="table">
        <thead><tr><th>Time</th><th>Action</th><th>Room</th><th>Details</th></tr></thead>
        <tbody>
          <?php foreach ($logs as $row): ?>
            <?php
              $roomDisp = ($row['bname'] ?: '—') . ' • ' . (($row['room_number'] ?: $row['room_name']) ?: '—');
              $details = [];
              if (!empty($row['status'])) $details[] = 'status=' . $row['status'];
              if (!empty($row['source'])) $details[] = 'source=' . $row['source'];
              if (!empty($row['note'])) $details[] = $row['note'];
              $detailsText = $details ? implode(' • ', $details) : '—';
            ?>
            <tr>
              <td class="note"><?= htmlspecialchars($row['created_at']) ?></td>
              <td><?= htmlspecialchars($row['action']) ?></td>
              <td><?= htmlspecialchars($roomDisp) ?></td>
              <td class="note"><?= htmlspecialchars($detailsText) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </section>
  </div>
</body>
</html>

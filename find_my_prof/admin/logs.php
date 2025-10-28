<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');
require_once __DIR__ . '/../includes/db.php';

// Recent instructor logs
$ilog = $pdo->query("SELECT il.id, il.created_at, il.action, il.status, il.note, il.source,
                            u.name AS uname, u.email,
                            r.room_number, r.room_name,
                            b.name AS bname
                       FROM instructor_logs il
                  LEFT JOIN users u ON u.id = il.faculty_user_id
                  LEFT JOIN rooms r ON r.id = il.room_id
                  LEFT JOIN buildings b ON b.id = r.building_id
                   ORDER BY il.created_at DESC, il.id DESC
                      LIMIT 200")->fetchAll();

// Recent room logs
$rlog = $pdo->query("SELECT rl.id, rl.created_at, rl.action, rl.note,
                            u.name AS uname,
                            r.room_number, r.room_name,
                            b.name AS bname
                       FROM room_logs rl
                  LEFT JOIN rooms r ON r.id = rl.room_id
                  LEFT JOIN buildings b ON b.id = r.building_id
                  LEFT JOIN users u ON u.id = rl.faculty_user_id
                   ORDER BY rl.created_at DESC, rl.id DESC
                      LIMIT 200")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin • Logs</title>
  <style>
    :root{--text:#0f172a;--muted:#475569;--border:rgba(15,23,42,.12);--bg:#f1f5f9;--card:#fff;--accent:#1f2937}
    *{box-sizing:border-box} html,body{height:100%}
    body{margin:0;font-family:system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial;color:var(--text);background:var(--bg)}
    .container{max-width:1200px;margin:0 auto;padding:16px}
    .top{display:flex;align-items:center;justify-content:space-between;margin:8px 0 16px}
    .title{font-size:22px;font-weight:800}
    .back{font-size:13px;color:var(--muted);text-decoration:underline}
    .card{background:var(--card);border:1px solid var(--border);border-radius:12px;box-shadow:0 6px 18px rgba(15,23,42,.08);padding:16px;margin-bottom:12px}
    table{width:100%;border-collapse:collapse}
    th,td{border-bottom:1px dashed var(--border);text-align:left;padding:8px;font-size:14px}
    .muted{color:var(--muted)}
  </style>
</head>
<body>
  <div class="container">
    <div class="top">
      <div class="title">System Logs</div>
      <a class="back" href="admin.html">← Back to Dashboard</a>
    </div>

    <section class="card">
      <h3 style="margin:0 0 6px;font-size:16px">Instructor Logs</h3>
      <?php if (!$ilog): ?>
        <div class="muted">No logs yet.</div>
      <?php else: ?>
      <table>
        <thead><tr>
          <th>Time</th><th>Instructor</th><th>Action</th><th>Status</th><th>Building • Room</th><th>Source</th><th>Note</th>
        </tr></thead>
        <tbody>
        <?php foreach ($ilog as $row): ?>
          <tr>
            <td class="muted"><?= htmlspecialchars($row['created_at']) ?></td>
            <td><?= htmlspecialchars($row['uname'] ?: '—') ?></td>
            <td><?= htmlspecialchars($row['action']) ?></td>
            <td><?= htmlspecialchars($row['status'] ?: '—') ?></td>
            <td><?= htmlspecialchars(($row['bname'] ?: '—') . ' • ' . (($row['room_number'] ?: $row['room_name']) ?: '—')) ?></td>
            <td class="muted"><?= htmlspecialchars($row['source'] ?: '—') ?></td>
            <td class="muted"><?= htmlspecialchars($row['note'] ?: '') ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </section>

    <section class="card">
      <h3 style="margin:0 0 6px;font-size:16px">Room Logs</h3>
      <?php if (!$rlog): ?>
        <div class="muted">No room logs yet.</div>
      <?php else: ?>
      <table>
        <thead><tr>
          <th>Time</th><th>Building • Room</th><th>Instructor</th><th>Action</th><th>Note</th>
        </tr></thead>
        <tbody>
        <?php foreach ($rlog as $row): ?>
          <tr>
            <td class="muted"><?= htmlspecialchars($row['created_at']) ?></td>
            <td><?= htmlspecialchars(($row['bname'] ?: '—') . ' • ' . (($row['room_number'] ?: $row['room_name']) ?: '—')) ?></td>
            <td><?= htmlspecialchars($row['uname'] ?: '—') ?></td>
            <td><?= htmlspecialchars($row['action']) ?></td>
            <td class="muted"><?= htmlspecialchars($row['note'] ?: '') ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php endif; ?>
    </section>
  </div>
</body>
</html>

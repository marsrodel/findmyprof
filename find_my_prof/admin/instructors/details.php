<?php
require_once __DIR__ . '/../../includes/auth.php';
require_role('admin');
require_once __DIR__ . '/../../includes/db.php';

$stmt = $pdo->query("SELECT id, name, email, department, created_at FROM users WHERE role = 'faculty' ORDER BY created_at DESC, id DESC");
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin • Instructor Details</title>
  <style>
    :root{--text:#0f172a;--muted:#475569;--border:rgba(15,23,42,.12);--bg:#f1f5f9;--card:#fff;--accent:#1f2937}
    *{box-sizing:border-box} html,body{height:100%}
    body{margin:0;font-family:system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial;color:var(--text);background:var(--bg)}
    .container{max-width:1100px;margin:0 auto;padding:16px}
    .top{display:flex;align-items:center;justify-content:space-between;margin:8px 0 16px}
    .title{font-size:22px;font-weight:800}
    .back{font-size:13px;color:var(--muted);text-decoration:underline}
    .card{background:var(--card);border:1px solid var(--border);border-radius:12px;box-shadow:0 6px 18px rgba(15,23,42,.08);padding:16px}
    .table{width:100%;border-collapse:collapse}
    .table th,.table td{border-bottom:1px dashed var(--border);text-align:left;padding:8px}
    .muted{color:var(--muted)}
    .badge{display:inline-block;padding:2px 8px;border-radius:999px;background:#e2e8f0;font-size:12px}
  </style>
</head>
<body>
  <div class="container">
    <div class="top">
      <div class="title">Instructor Details</div>
      <a class="back" href="index.php">← Back</a>
    </div>

    <section class="card">
      <h3 style="margin:0 0 6px;font-size:16px">All Instructors</h3>
      <table class="table">
        <thead><tr><th>Name</th><th>Email</th><th>Department</th><th>Created</th><th>Actions</th></tr></thead>
        <tbody>
        <?php if (!$users): ?>
          <tr><td class="muted" colspan="5">No instructors yet. This table will list them once added.</td></tr>
        <?php else: ?>
          <?php foreach ($users as $u): ?>
            <tr>
              <td><?= htmlspecialchars($u['name']) ?></td>
              <td><?= htmlspecialchars($u['email']) ?></td>
              <td><?= $u['department'] ? htmlspecialchars($u['department']) : '<span class="muted">—</span>' ?></td>
              <td class="muted"><?= htmlspecialchars($u['created_at']) ?></td>
              <td><a class="back" href="edit.php?id=<?= (int)$u['id'] ?>">Edit</a></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
      </table>
    </section>
  </div>
</body>
</html>

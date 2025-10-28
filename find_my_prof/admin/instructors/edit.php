<?php
require_once __DIR__ . '/../../includes/auth.php';
require_role('admin');
require_once __DIR__ . '/../../includes/db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { header('Location: details.php'); exit; }

$stmt = $pdo->prepare('SELECT id,name,email,department,role,contact FROM users WHERE id = ? AND role IN (\'faculty\',\'admin\') LIMIT 1');
$stmt->execute([$id]);
$user = $stmt->fetch();
if (!$user) { header('Location: details.php'); exit; }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin • Edit Account</title>
  <style>
    :root{--text:#0f172a;--muted:#475569;--border:rgba(15,23,42,.12);--bg:#f1f5f9;--card:#fff;--accent:#1f2937}
    *{box-sizing:border-box} html,body{height:100%}
    body{margin:0;font-family:system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial;color:var(--text);background:var(--bg)}
    .container{max-width:900px;margin:0 auto;padding:16px}
    .top{display:flex;align-items:center;justify-content:space-between;margin:8px 0 16px}
    .title{font-size:22px;font-weight:800}
    .back{font-size:13px;color:var(--muted);text-decoration:underline}
    .card{background:var(--card);border:1px solid var(--border);border-radius:12px;box-shadow:0 6px 18px rgba(15,23,42,.08);padding:16px}
    .row{display:flex;gap:10px;flex-wrap:wrap}
    .col{flex:1;min-width:240px}
    .field{display:grid;gap:6px;margin-bottom:10px}
    .label{font-size:13px;color:var(--muted)}
    .input,.select{height:40px;padding:10px 12px;border:1px solid var(--border);border-radius:8px;font-size:14px;outline:none}
    .btn{height:40px;padding:0 14px;border-radius:8px;border:0;background:var(--accent);color:#fff;cursor:pointer}
    .note{color:var(--muted);font-size:13px;margin-top:8px}
  </style>
</head>
<body>
  <div class="container">
    <div class="top">
      <div class="title">Edit Account</div>
      <a class="back" href="details.php">← Back to Details</a>
    </div>

    <section class="card">
      <form action="update.php" method="post">
        <input type="hidden" name="id" value="<?= htmlspecialchars($user['id']) ?>" />
        <div class="row">
          <div class="col"><div class="field"><span class="label">Full Name</span>
            <input class="input" type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required />
          </div></div>
          <div class="col"><div class="field"><span class="label">Email</span>
            <input class="input" type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required />
          </div></div>
        </div>
        <div class="row">
          <div class="col"><div class="field"><span class="label">Department</span>
            <select class="select" name="department">
              <option value="">— None —</option>
              <?php foreach (['CBA','CEIT','CTHM','CITTE','DLHS'] as $d): ?>
                <option value="<?= $d ?>" <?= $user['department']===$d?'selected':'' ?>><?= $d ?></option>
              <?php endforeach; ?>
            </select>
          </div></div>
          <div class="col"><div class="field"><span class="label">Role</span>
            <select class="select" name="role" required>
              <option value="faculty" <?= $user['role']==='faculty'?'selected':'' ?>>Faculty</option>
              <option value="admin" <?= $user['role']==='admin'?'selected':'' ?>>Admin</option>
            </select>
          </div></div>
        </div>
        <div class="row">
          <div class="col"><div class="field"><span class="label">Contact</span>
            <input class="input" type="text" name="contact" value="<?= htmlspecialchars($user['contact'] ?? '') ?>" />
          </div></div>
          <div class="col"><div class="field"><span class="label">New Password (optional)</span>
            <input class="input" type="password" name="new_password" placeholder="Leave blank to keep current" />
          </div></div>
        </div>
        <button class="btn" type="submit">Save Changes</button>
        <div class="note">Leave password blank to keep the current one.</div>
      </form>
    </section>
  </div>
</body>
</html>

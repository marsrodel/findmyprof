<?php
require_once __DIR__ . '/../../includes/auth.php';
require_role('admin');
require_once __DIR__ . '/../../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: details.php'); exit; }

$id = (int)($_POST['id'] ?? 0);
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$department = $_POST['department'] ?? null;
$role = $_POST['role'] ?? 'faculty';
$contact = trim($_POST['contact'] ?? '');
$new_password = $_POST['new_password'] ?? '';

if ($id <= 0 || $name === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
  header('Location: edit.php?id=' . $id . '&error=invalid');
  exit;
}
if (!in_array($role, ['admin','faculty'], true)) { $role = 'faculty'; }

// Unique email check (exclude self)
$stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ? AND id <> ?');
$stmt->execute([$email, $id]);
if ((int)$stmt->fetchColumn() > 0) {
  header('Location: edit.php?id=' . $id . '&error=exists');
  exit;
}

$params = [$name,$email,$role,$department,$contact,$id];
$sql = 'UPDATE users SET name=?, email=?, role=?, department=?, contact=?';
if ($new_password !== '') {
  $hash = password_hash($new_password, PASSWORD_BCRYPT);
  $sql = 'UPDATE users SET name=?, email=?, role=?, department=?, contact=?, password=? WHERE id=?';
  $params = [$name,$email,$role,$department,$contact,$hash,$id];
} else {
  $sql .= ' WHERE id=?';
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

header('Location: details.php?updated=1');

<?php
require_once __DIR__ . '/../../includes/auth.php';
require_role('admin');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$department = $_POST['department'] ?? null; // can be null
$role = $_POST['role'] ?? 'faculty';
$contact = trim($_POST['contact'] ?? '');

// Minimal checks for prototype
if ($name === '' || $email === '' || $password === '') {
    header('Location: add.html?error=empty');
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: add.html?error=email');
    exit;
}
if (!in_array($role, ['admin','faculty'], true)) {
    $role = 'faculty';
}

// Ensure email unique
$stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
$stmt->execute([$email]);
if ((int)$stmt->fetchColumn() > 0) {
    header('Location: add.html?error=exists');
    exit;
}

$hash = password_hash($password, PASSWORD_BCRYPT);
try {
    $stmt = $pdo->prepare('INSERT INTO users(name,email,password,role,department,contact) VALUES(?, ?, ?, ?, ?, ?)');
    $stmt->execute([$name,$email,$hash,$role,$department,$contact]);
} catch (Throwable $e) {
    header('Location: add.html?error=db');
    exit;
}

// Go back to instructors hub
header('Location: index.php?created=1');

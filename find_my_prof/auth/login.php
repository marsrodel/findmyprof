<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once __DIR__ . '/../includes/auth.php';

bootstrap_admin_if_empty();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../login.html');
    exit;
}

$email = trim($_POST['email'] ?? '');
$pass  = $_POST['password'] ?? '';

if ($email === '' || $pass === '') {
    header('Location: ../login.html?error=empty');
    exit;
}

$user = find_user_by_email($email);
if (!$user) {
    header('Location: ../login.html?error=invalid');
    exit;
}

// Allow bcrypt OR plain text match (for quick prototype/testing)
$ok = false;
if (is_string($user['password'])) {
    if (password_verify($pass, $user['password'])) {
        $ok = true;
    } elseif ($pass === $user['password']) {
        $ok = true;
    }
}
if (!$ok) {
    header('Location: ../login.html?error=invalid');
    exit;
}

// Keep minimal user info in session
$_SESSION['user'] = [
    'id' => $user['id'],
    'name' => $user['name'],
    'email' => $user['email'],
    'role' => $user['role'],
    'department' => $user['department'],
];

// Redirect by role
if ($user['role'] === 'admin') {
    header('Location: ../admin/index.php');
    exit;
}
if ($user['role'] === 'faculty') {
    header('Location: ../faculty/index.php');
    exit;
}

// Fallback
header('Location: ../login.html');

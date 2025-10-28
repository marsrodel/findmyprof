<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/db.php';

function current_user() {
    return $_SESSION['user'] ?? null;
}

function require_role($roles) {
    $u = current_user();
    if (!$u) {
        header('Location: ../login.html');
        exit;
    }
    $roles = is_array($roles) ? $roles : [$roles];
    if (!in_array($u['role'], $roles, true)) {
        http_response_code(403);
        echo 'Forbidden';
        exit;
    }
}

function find_user_by_email($email) {
    global $pdo;
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    return $stmt->fetch();
}

function bootstrap_admin_if_empty() {
    global $pdo;
    $count = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($count === 0) {
        $hash = password_hash('admin123', PASSWORD_BCRYPT);
        $stmt = $pdo->prepare('INSERT INTO users(name,email,password,role) VALUES(?,?,?,\'admin\')');
        $stmt->execute(['Administrator','admin@local',$hash]);
    }
}

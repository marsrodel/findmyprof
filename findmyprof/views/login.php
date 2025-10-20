<?php
session_start();
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  require_once('../server/db.php');
  $email = trim($_POST['email'] ?? '');
  $pass  = trim($_POST['password'] ?? '');
  if ($email !== '' && $pass !== '') {
    $stmt = mysqli_prepare($conn, "SELECT id, name, email, password, role FROM users WHERE email = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($res)) {
      $hash = $row['password'];
      $ok = password_verify($pass, $hash) || $pass === $hash; // allow legacy plain passwords
      if ($ok) {
        $_SESSION['user_id'] = $row['id'];
        $_SESSION['name'] = $row['name'];
        $_SESSION['role'] = $row['role'];
        if ($row['role'] === 'Admin') {
          header('Location: admin_view/admin_dashboard.php');
        } else {
          header('Location: faculty_view/faculty_dashboard.php');
        }
        exit;
      } else {
        $error = 'Invalid credentials';
      }
    } else {
      $error = 'Invalid credentials';
    }
    mysqli_stmt_close($stmt);
  } else {
    $error = 'Please enter email and password';
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>FindMyProf • Login</title>
  <link rel="stylesheet" href="../css/login.css" />
</head>
<body>
  <header class="navbar">
    <div class="nav-content">
      <div class="brand">
        <div class="brand-logo" aria-hidden="true">LOGO</div>
        <span class="brand-name">FindMyProf</span>
      </div>
    </div>
  </header>

  <main class="page">
    <section class="hero">
      <div class="left-graphic" aria-hidden="true">
        <div class="crest-placeholder">LOGO</div>
      </div>

      <div class="login-card" role="dialog" aria-labelledby="login-title">
        <h1 id="login-title" class="card-title">LOGIN</h1>
        <?php if ($error !== '') { echo '<div class="error">'.htmlspecialchars($error).'</div>'; } ?>
        <form class="login-form" action="" method="post" novalidate>
          <label class="field">
            <span class="label">Email</span>
            <input type="email" name="email" placeholder="Value" required />
          </label>
          <label class="field">
            <span class="label">Password</span>
            <input type="password" name="password" placeholder="Value" required />
          </label>

          <button type="submit" class="btn primary">Login</button>

          <div class="links">
            <a class="link" href="#">Forgot password?</a>
            <a class="link right" href="guest_view/guest.php">
              <span class="guest-icon" aria-hidden="true">👤</span>
              <span>Login as a Guest</span>
            </a>
          </div>
        </form>
      </div>
    </section>
  </main>

  <footer class="footer">
    <div class="footer-content">© 2025 FindMyProf. All rights reserved.</div>
  </footer>
</body>
</html>

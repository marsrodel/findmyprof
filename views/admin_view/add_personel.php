<?php
  session_start();
  if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
  }
  if (($_SESSION['role'] ?? '') !== 'Admin') {
    header('Location: ../faculty_view/faculty_dashboard.php');
    exit;
  }
  $msg = '';
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once('../../server/db.php');
    // handle PDF upload (optional)
    $pdf_ok = true;
    if (isset($_FILES['schedule']) && $_FILES['schedule']['error'] === UPLOAD_ERR_OK) {
      $type = mime_content_type($_FILES['schedule']['tmp_name']);
      $ext = strtolower(pathinfo($_FILES['schedule']['name'], PATHINFO_EXTENSION));
      if ($type !== 'application/pdf' || $ext !== 'pdf') { $pdf_ok = false; }
      else {
        $dir = dirname(__DIR__, 2) . '/public/schedules';
        if (!is_dir($dir)) { @mkdir($dir, 0777, true); }
        $fname = time() . '_' . preg_replace('/[^a-zA-Z0-9_.-]/','_', $_FILES['schedule']['name']);
        @move_uploaded_file($_FILES['schedule']['tmp_name'], $dir . '/' . $fname);
      }
    }
    $name = trim($_POST['full_name'] ?? '');
    $contact = trim($_POST['contact'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $role = trim($_POST['role'] ?? 'Instructor');
    $allowed_roles = ['Admin','Executive','Instructor'];
    if (!in_array($role, $allowed_roles)) { $role = 'Instructor'; }

    if ($name !== '' && $email !== '' && $password !== '' && $pdf_ok) {
      $hash = password_hash($password, PASSWORD_BCRYPT);
      $stmt = mysqli_prepare($conn, "INSERT INTO users(name,email,password,role,department,contact) VALUES(?,?,?,?,?,?)");
      mysqli_stmt_bind_param($stmt, 'ssssss', $name, $email, $hash, $role, $department, $contact);
      if (mysqli_stmt_execute($stmt)) {
        $msg = 'Account created.';
      } else {
        $msg = 'Failed to save.';
      }
      mysqli_stmt_close($stmt);
    } else {
      $msg = 'Please fill name, email and password. Only PDF is allowed.';
    }
  }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>FindMyProf • Add Personel</title>
  <link rel="stylesheet" href="../../css/admin.css" />
</head>
<body>
  <header class="topbar">
    <div class="topbar-inner">
      <div class="brand">
        <div class="brand-mark" aria-hidden="true">LOGO</div>
        <span class="brand-name">FindMyProf</span>
      </div>
      <h1 class="page-title">ADD PERSONEL</h1>
      <a class="btn ghost" href="../../server/logout.php">LOGOUT</a>
    </div>
  </header>

  <div class="layout">
    <aside class="sidebar">
      <div class="avatar">A</div>
      <nav class="menu">
        <a class="item" href="admin_dashboard.php">DASHBOARD</a>
        <a class="item" href="executives_list.php">EXECUTIVES</a>
        <a class="item" href="instructors_list.php">INSTRUCTORS</a>
        <a class="item" href="buildings.php">BUILDINGS</a>
        <a class="item" href="add_building.php">ADD BUILDING</a>
        <a class="item" href="logs.php">LOGS</a>
        <a class="item active" href="add_personel.php">ADD PERSONEL</a>
      </nav>
    </aside>

    <main class="content">
      <?php if ($msg !== '') { echo '<div class="notice">'.htmlspecialchars($msg).'</div>'; } ?>
      <form method="post" enctype="multipart/form-data">
      <section class="form-section">
        <div class="form-head">
          <h3>PERSONEL DETAILS</h3>
        </div>
        <div class="form-body">
          <div class="field two-col">
            <label>
              <span class="label">FULL NAME</span>
              <input type="text" name="full_name" placeholder="Value" />
            </label>
            <label>
              <span class="label">CONTACT</span>
              <input type="text" name="contact" placeholder="Value" />
            </label>
          </div>
          <div class="field two-col">
            <label>
              <span class="label">EMAIL</span>
              <input type="text" name="email" placeholder="Value" />
            </label>
            <label>
              <span class="label">PASSWORD</span>
              <input type="password" name="password" placeholder="Value" />
            </label>
          </div>
          <div class="field two-col">
            <label>
              <span class="label">DEPARTMENT</span>
              <select name="department">
                <option value="">--Select Department--</option>
                <option value="CBA">CBA</option>
                <option value="CEIT">CEIT</option>
                <option value="CITTE">CITTE</option>
                <option value="CTHM">CTHM</option>
                <option value="DLHS">DLHS</option>
                <option value="GEN-ED">GEN-ED</option>
              </select>
            </label>
            <label>
              <span class="label">ROLE</span>
              <select name="role">
                <option value="Instructor">Instructor</option>
                <option value="Admin">Admin</option>
                <option value="Executive">Executive</option>
              </select>
            </label>
          </div>
        </div>
      </section>

      <section class="form-section">
        <div class="form-head">
          <h3>SCHEDULE DETAILS</h3>
        </div>
        <div class="form-body">
          <div class="field">
            <label>
              <span class="label">PDF FILE</span>
              <input type="file" name="schedule" accept="application/pdf" />
            </label>
          </div>
          <div class="form-actions">
            <span class="muted-note">This form saves a faculty user account.</span>
            <button class="btn primary" type="submit">CREATE PERSONEL ACCOUNT</button>
          </div>
        </div>
      </section>
      </form>
    </main>
  </div>

  <footer class="footer">
    <div class="footer-inner">© 2025 FindMyProf. All rights reserved.</div>
  </footer>
</body>
</html>

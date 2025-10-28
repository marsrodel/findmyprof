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
  require_once('../../server/db.php');

  $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
  $msg = '';
  $user = null;

  if ($id > 0) {
    $rs = mysqli_query($conn, "SELECT id,name,email,role,department,contact FROM users WHERE id=".$id." LIMIT 1");
    if ($rs && mysqli_num_rows($rs) === 1) { $user = mysqli_fetch_assoc($rs); }
  }

  if (!$user) { $msg = 'User not found.'; }

  if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
    $name = trim($_POST['full_name'] ?? '');
    $contact = trim($_POST['contact'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $role = trim($_POST['role'] ?? 'Instructor');
    $allowed_roles = ['Admin','Executive','Instructor','Staff'];
    if (!in_array($role, $allowed_roles)) { $role = 'Instructor'; }

    // optional PDF upload (same behavior as add page)
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

    if ($name !== '' && $email !== '' && $pdf_ok) {
      if ($password !== '') {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = mysqli_prepare($conn, "UPDATE users SET name=?, email=?, password=?, role=?, department=?, contact=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, 'ssssssi', $name, $email, $hash, $role, $department, $contact, $id);
      } else {
        $stmt = mysqli_prepare($conn, "UPDATE users SET name=?, email=?, role=?, department=?, contact=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, 'sssssi', $name, $email, $role, $department, $contact, $id);
      }
      if (mysqli_stmt_execute($stmt)) {
        $msg = 'Account updated.';
        // refresh loaded data
        $rs = mysqli_query($conn, "SELECT id,name,email,role,department,contact FROM users WHERE id=".$id." LIMIT 1");
        if ($rs && mysqli_num_rows($rs) === 1) { $user = mysqli_fetch_assoc($rs); }
      } else {
        $msg = 'Failed to update.';
      }
      if (isset($stmt)) { mysqli_stmt_close($stmt); }
    } else {
      $msg = 'Please fill name and email. Only PDF is allowed.';
    }
  }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>FindMyProf • Edit Personel</title>
  <link rel="stylesheet" href="../../css/admin.css" />
</head>
<body>
  <header class="topbar">
    <div class="topbar-inner">
      <div class="brand">
        <div class="brand-mark" aria-hidden="true">LOGO</div>
        <span class="brand-name">FindMyProf</span>
      </div>
      <h1 class="page-title">EDIT PERSONEL</h1>
      <a class="btn ghost" href="#">LOGOUT</a>
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
        <a class="item" href="staff_list.php">STAFF</a>
        <a class="item" href="logs.php">LOGS</a>
        <a class="item" href="add_personel.php">ADD PERSONEL</a>
      </nav>
    </aside>

    <main class="content">
      <?php if ($msg !== '') { echo '<div class="notice">'.htmlspecialchars($msg).'</div>'; } ?>
      <?php if ($user) { ?>
      <form method="post" enctype="multipart/form-data">
      <section class="form-section">
        <div class="form-head">
          <h3>PERSONEL DETAILS</h3>
        </div>
        <div class="form-body">
          <div class="field two-col">
            <label>
              <span class="label">FULL NAME</span>
              <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['name']); ?>" />
            </label>
            <label>
              <span class="label">CONTACT</span>
              <input type="text" name="contact" value="<?php echo htmlspecialchars($user['contact'] ?? ''); ?>" />
            </label>
          </div>
          <div class="field two-col">
            <label>
              <span class="label">EMAIL</span>
              <input type="text" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" />
            </label>
            <label>
              <span class="label">PASSWORD</span>
              <input type="password" name="password" placeholder="Leave blank to keep current" />
            </label>
          </div>
          <div class="field two-col">
            <label>
              <span class="label">DEPARTMENT</span>
              <select name="department">
                <option value="" <?php echo ($user['department']===''?'selected':''); ?>>N/A</option>
                <option value="CBA" <?php echo ($user['department']==='CBA'?'selected':''); ?>>CBA</option>
                <option value="CEIT" <?php echo ($user['department']==='CEIT'?'selected':''); ?>>CEIT</option>
                <option value="CITTE" <?php echo ($user['department']==='CITTE'?'selected':''); ?>>CITTE</option>
                <option value="CTHM" <?php echo ($user['department']==='CTHM'?'selected':''); ?>>CTHM</option>
                <option value="DLHS" <?php echo ($user['department']==='DLHS'?'selected':''); ?>>DLHS</option>
              </select>
            </label>
            <label>
              <span class="label">ROLE</span>
              <select name="role">
                <?php $rval = $user['role']; ?>
                <option value="Instructor" <?php echo ($rval==='Instructor'?'selected':''); ?>>Instructor</option>
                <option value="Admin" <?php echo ($rval==='Admin'?'selected':''); ?>>Admin</option>
                <option value="Executive" <?php echo ($rval==='Executive'?'selected':''); ?>>Executive</option>
                <option value="Staff" <?php echo ($rval==='Staff'?'selected':''); ?>>Staff</option>
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
            <span class="muted-note">Leave password empty if you don't want to change it.</span>
            <div>
              <button class="btn ghost" type="button" onclick="history.back()">Cancel</button>
              <button class="btn primary" type="submit">UPDATE ACCOUNT</button>
            </div>
          </div>
        </div>
      </section>
      </form>
      <?php } ?>
    </main>
  </div>

  <footer class="footer">
    <div class="footer-inner">© 2025 FindMyProf. All rights reserved.</div>
  </footer>
</body>
</html>


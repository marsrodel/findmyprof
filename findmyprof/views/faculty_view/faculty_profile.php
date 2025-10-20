<?php
  session_start();
  if (!isset($_SESSION['user_id'])) { header('Location: ../login.php'); exit; }
  require_once('../../server/db.php');

  $uid = (int)($_SESSION['user_id'] ?? 0);
  // Allow Instructor, Executive, Staff to use faculty interface; redirect Admins
  if ($uid > 0) {
    $rs = mysqli_query($conn, "SELECT id,name,email,role,department,contact FROM users WHERE id={$uid} LIMIT 1");
    $user = ($rs && mysqli_num_rows($rs)===1) ? mysqli_fetch_assoc($rs) : null;
    if ($user) { $_SESSION['role'] = $user['role']; }
  }
  if (($_SESSION['role'] ?? '') === 'Admin') { header('Location: ../admin_view/admin_dashboard.php'); exit; }

  $msg = '';
  if ($_SERVER['REQUEST_METHOD'] === 'POST' && $uid > 0) {
    $name = trim($_POST['full_name'] ?? '');
    $contact = trim($_POST['contact'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $department = trim($_POST['department'] ?? '');

    // optional PDF upload (same as admin pages)
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
        $stmt = mysqli_prepare($conn, "UPDATE users SET name=?, email=?, password=?, department=?, contact=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, 'sssssi', $name, $email, $hash, $department, $contact, $uid);
      } else {
        $stmt = mysqli_prepare($conn, "UPDATE users SET name=?, email=?, department=?, contact=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, 'ssssi', $name, $email, $department, $contact, $uid);
      }
      if (mysqli_stmt_execute($stmt)) {
        $msg = 'Profile updated.';
        // refresh
        $rs = mysqli_query($conn, "SELECT id,name,email,role,department,contact FROM users WHERE id={$uid} LIMIT 1");
        if ($rs && mysqli_num_rows($rs)===1) { $user = mysqli_fetch_assoc($rs); }
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
  <title>FindMyProf • Faculty Profile</title>
  <link rel="stylesheet" href="../../css/faculty.css" />
</head>
<body>
  <header class="topbar">
    <div class="topbar-inner">
      <div class="brand">
        <div class="brand-mark" aria-hidden="true">LOGO</div>
        <span class="brand-name">FindMyProf</span>
      </div>
      <h1 class="page-title">DASHBOARD</h1>
      <a class="btn ghost" href="../../server/logout.php">LOGOUT</a>
    </div>
  </header>

  <div class="layout">
    <aside class="sidebar">
      <div class="avatar">A</div>
      <nav class="menu">
        <a class="item" href="faculty_dashboard.php">DASHBOARD</a>
        <a class="item" href="faculty_search.php">SEARCH</a>
        <a class="item active" href="faculty_profile.php">PROFILE</a>
        <a class="item" href="faculty_logs.php">LOGS</a>
      </nav>
    </aside>

    <main class="content">
      <?php if (!empty($msg)) { echo '<div class="notice">'.htmlspecialchars($msg).'</div>'; } ?>
      <section class="form-section">
        <div class="form-head">
          <h3>PERSONAL DETAILS</h3>
        </div>
        <div class="form-body">
          <form method="post" enctype="multipart/form-data">
            <div class="field two-col">
              <label>
                <span class="label">FULL NAME</span>
                <input type="text" name="full_name" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" />
              </label>
              <label>
                <span class="label">CONTACT</span>
                <input type="text" name="contact" value="<?php echo htmlspecialchars($user['contact'] ?? ''); ?>" />
              </label>
            </div>
            <div class="field two-col">
              <label>
                <span class="label">EMAIL</span>
                <input type="text" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" />
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
                  <?php $dept = (string)($user['department'] ?? ''); ?>
                  <option value="" <?php echo ($dept===''?'selected':''); ?>>N/A</option>
                  <option value="CBA" <?php echo ($dept==='CBA'?'selected':''); ?>>CBA</option>
                  <option value="CEIT" <?php echo ($dept==='CEIT'?'selected':''); ?>>CEIT</option>
                  <option value="CITTE" <?php echo ($dept==='CITTE'?'selected':''); ?>>CITTE</option>
                  <option value="CTHM" <?php echo ($dept==='CTHM'?'selected':''); ?>>CTHM</option>
                  <option value="DLHS" <?php echo ($dept==='DLHS'?'selected':''); ?>>DLHS</option>
                </select>
              </label>
              <label>
                <span class="label">UPLOAD SCHEDULE (PDF)</span>
                <input type="file" name="schedule" accept="application/pdf" />
              </label>
            </div>
            <div class="profile-actions">
              <button class="btn primary" type="submit">SAVE INSTRUCTOR DETAILS</button>
            </div>
          </form>
        </div>
      </section>
    </main>
  </div>

  <footer class="footer">
    <div class="footer-inner">© 2025 FindMyProf. All rights reserved.</div>
  </footer>
</body>
</html>

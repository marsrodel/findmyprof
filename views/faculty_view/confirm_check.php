<?php
  session_start();
  if (!isset($_SESSION['user_id'])) { header('Location: ../login.php'); exit; }
  require_once('../../server/db.php');
  $uid = (int)($_SESSION['user_id'] ?? 0);
  if ($uid > 0) {
    $rs = mysqli_query($conn, "SELECT role FROM users WHERE id={$uid} LIMIT 1");
    if ($rs && ($row = mysqli_fetch_assoc($rs))) { $_SESSION['role'] = $row['role']; }
  }
  if (($_SESSION['role'] ?? '') === 'Admin') { header('Location: ../admin_view/admin_dashboard.php'); exit; }
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: scan.php'); exit; }

  $rid = (int)($_POST['room_id'] ?? 0);
  $prevRid = (int)($_POST['previous_room_id'] ?? 0);
  $rawAction = $_POST['action'] ?? '';
  $action = ($rawAction === 'checkout') ? 'checkout' : (($rawAction === 'transfer') ? 'transfer' : 'checkin');
  if ($rid <= 0) { header('Location: scan.php?error=room'); exit; }

  // validate room
  $rs = mysqli_query($conn, 'SELECT r.id FROM rooms r WHERE r.id='.(int)$rid.' LIMIT 1');
  if (!($rs && mysqli_fetch_assoc($rs))) { header('Location: scan.php?error=room'); exit; }

  // Write rows similar to prototype logic
  $ok = true;
  mysqli_begin_transaction($conn);
  try {
    if ($action === 'checkin') {
      // presence available
      $stmt = mysqli_prepare($conn, "INSERT INTO presence(faculty_user_id,room_id,status,source) VALUES(?,?,'available','qr')");
      mysqli_stmt_bind_param($stmt, 'ii', $uid, $rid);
      $ok = $ok && mysqli_stmt_execute($stmt);
      mysqli_stmt_close($stmt);
      // instructor_logs
      $stmt = mysqli_prepare($conn, "INSERT INTO instructor_logs(faculty_user_id,action,status,room_id,source) VALUES(?,'checkin','available',?,'qr')");
      mysqli_stmt_bind_param($stmt, 'ii', $uid, $rid);
      $ok = $ok && mysqli_stmt_execute($stmt);
      mysqli_stmt_close($stmt);
      // room_logs
      $stmt = mysqli_prepare($conn, "INSERT INTO room_logs(room_id,faculty_user_id,action) VALUES(?,?,'checkin')");
      mysqli_stmt_bind_param($stmt, 'ii', $rid, $uid);
      $ok = $ok && mysqli_stmt_execute($stmt);
      mysqli_stmt_close($stmt);
    } else if ($action === 'checkout') {
      // presence out
      $stmt = mysqli_prepare($conn, "INSERT INTO presence(faculty_user_id,room_id,status,source) VALUES(?,?,'out','qr')");
      mysqli_stmt_bind_param($stmt, 'ii', $uid, $rid);
      $ok = $ok && mysqli_stmt_execute($stmt);
      mysqli_stmt_close($stmt);
      // instructor_logs
      $stmt = mysqli_prepare($conn, "INSERT INTO instructor_logs(faculty_user_id,action,status,room_id,source) VALUES(?,'checkout','out',?,'qr')");
      mysqli_stmt_bind_param($stmt, 'ii', $uid, $rid);
      $ok = $ok && mysqli_stmt_execute($stmt);
      mysqli_stmt_close($stmt);
      // room_logs
      $stmt = mysqli_prepare($conn, "INSERT INTO room_logs(room_id,faculty_user_id,action) VALUES(?,?,'checkout')");
      mysqli_stmt_bind_param($stmt, 'ii', $rid, $uid);
      $ok = $ok && mysqli_stmt_execute($stmt);
      mysqli_stmt_close($stmt);
    } else { // transfer: checkout previous room then checkin new room
      $curRid = null; $curStatus = null;
      $prs = mysqli_query($conn, "SELECT room_id, status FROM v_current_presence WHERE faculty_user_id=".$uid." LIMIT 1");
      if ($prs && ($p = mysqli_fetch_assoc($prs))) { $curRid = (int)$p['room_id']; $curStatus = (string)$p['status']; }

      if ($prevRid > 0 && $curRid && $curStatus !== 'out' && $curRid === $prevRid) {
        $stmt = mysqli_prepare($conn, "INSERT INTO presence(faculty_user_id,room_id,status,source) VALUES(?,?,'out','qr')");
        mysqli_stmt_bind_param($stmt, 'ii', $uid, $prevRid);
        $ok = $ok && mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        $stmt = mysqli_prepare($conn, "INSERT INTO instructor_logs(faculty_user_id,action,status,room_id,source) VALUES(?,'checkout','out',?,'qr')");
        mysqli_stmt_bind_param($stmt, 'ii', $uid, $prevRid);
        $ok = $ok && mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        $stmt = mysqli_prepare($conn, "INSERT INTO room_logs(room_id,faculty_user_id,action) VALUES(?,?,'checkout')");
        mysqli_stmt_bind_param($stmt, 'ii', $prevRid, $uid);
        $ok = $ok && mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
      }

      $stmt = mysqli_prepare($conn, "INSERT INTO presence(faculty_user_id,room_id,status,source) VALUES(?,?,'available','qr')");
      mysqli_stmt_bind_param($stmt, 'ii', $uid, $rid);
      $ok = $ok && mysqli_stmt_execute($stmt);
      mysqli_stmt_close($stmt);
      $stmt = mysqli_prepare($conn, "INSERT INTO instructor_logs(faculty_user_id,action,status,room_id,source) VALUES(?,'checkin','available',?,'qr')");
      mysqli_stmt_bind_param($stmt, 'ii', $uid, $rid);
      $ok = $ok && mysqli_stmt_execute($stmt);
      mysqli_stmt_close($stmt);
      $stmt = mysqli_prepare($conn, "INSERT INTO room_logs(room_id,faculty_user_id,action) VALUES(?,?,'checkin')");
      mysqli_stmt_bind_param($stmt, 'ii', $rid, $uid);
      $ok = $ok && mysqli_stmt_execute($stmt);
      mysqli_stmt_close($stmt);
    }

    if ($ok) { mysqli_commit($conn); } else { mysqli_rollback($conn); }
  } catch (Throwable $e) {
    if (mysqli_errno($conn)) { mysqli_rollback($conn); }
    $ok = false;
  }

  header('Location: faculty_logs.php');
  exit;

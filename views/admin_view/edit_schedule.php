<?php
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: ../login.php'); exit; }
require_once('../../server/db.php');
$uid = (int)($_SESSION['user_id'] ?? 0);
if ($uid > 0) {
  $rs = mysqli_query($conn, "SELECT role,name,email FROM users WHERE id={$uid} LIMIT 1");
  if ($rs && ($row = mysqli_fetch_assoc($rs))) { $_SESSION['role'] = $row['role']; $me = $row; }
}
// Restrict: only Admins can manage schedules (moved to admin side)
if (($_SESSION['role'] ?? '') !== 'Admin') { header('Location: ../faculty_view/faculty_dashboard.php'); exit; }
// Target instructor/executive to manage
$targetId = (int)($_GET['user_id'] ?? 0);
if ($targetId <= 0) { header('Location: manage_schedules.php'); exit; }
// Fetch target user for display
$target = null;
$tu = mysqli_query($conn, 'SELECT id,name,email,role FROM users WHERE id='.(int)$targetId.' LIMIT 1');
if ($tu && ($rowt = mysqli_fetch_assoc($tu))) { $target = $rowt; }

// Flash notice (PRG pattern)
$notice = '';
if (isset($_SESSION['flash_notice'])) { $notice = (string)$_SESSION['flash_notice']; unset($_SESSION['flash_notice']); }

// Manual save inserts directly into schedules with validation
if ($_SERVER['REQUEST_METHOD']==='POST') {
  $didAction = false;
  if (isset($_POST['upload_pdf'])) { $notice = 'Upload received. Parsing will be implemented next.'; $didAction=true; }
  // Delete existing row
  if (isset($_POST['delete_sched'])) {
    $delId = (int)($_POST['delete_id'] ?? 0);
    if ($delId>0) {
      $stmt = mysqli_prepare($conn, "DELETE FROM schedules WHERE id=? AND faculty_user_id=?");
      mysqli_stmt_bind_param($stmt, 'ii', $delId, $targetId);
      if (mysqli_stmt_execute($stmt)) { $notice = 'Schedule entry deleted.'; } else { $notice = 'Failed to delete.'; }
      mysqli_stmt_close($stmt);
      $didAction = true;
    }
  }
  // Edit/update existing row
  if (isset($_POST['update_sched'])) {
    $editId  = (int)($_POST['edit_id'] ?? 0);
    $course  = trim($_POST['course'] ?? '');
    $buildingId = (int)($_POST['building_id'] ?? 0);
    $roomIdForm = (int)($_POST['room_id'] ?? 0);
    $dayName = trim($_POST['day'] ?? '');
    $start   = trim($_POST['start_time'] ?? '');
    $end     = trim($_POST['end_time'] ?? '');
    $errors  = [];
    $map = ['Monday'=>1,'Tuesday'=>2,'Wednesday'=>3,'Thursday'=>4,'Friday'=>5,'Saturday'=>6,'Sunday'=>7];
    $dayOfWeek = $map[$dayName] ?? null;
    if (!$dayOfWeek) { $errors[] = 'Invalid day.'; }
    $validTime = function($t){ return preg_match('/^\d{2}:\d{2}$/', $t); };
    if (!$validTime($start) || !$validTime($end) || strtotime($start) >= strtotime($end)) { $errors[] = 'Invalid time window.'; }
    // Enforce bounds: 07:00 to 20:30
    $minT = strtotime('07:00');
    $maxT = strtotime('20:30');
    if (strtotime($start) < $minT || strtotime($end) > $maxT) {
      $errors[] = 'Time must be between 07:00 and 20:30.';
    }
    // Resolve room via IDs and validate relationship
    $roomId = null;
    if ($buildingId > 0 && $roomIdForm > 0) {
      $stmtR = mysqli_prepare($conn, "SELECT id FROM rooms WHERE id=? AND building_id=? LIMIT 1");
      mysqli_stmt_bind_param($stmtR, 'ii', $roomIdForm, $buildingId);
      if (mysqli_stmt_execute($stmtR)) {
        $resR = mysqli_stmt_get_result($stmtR);
        if ($rowR = mysqli_fetch_assoc($resR)) { $roomId = (int)$rowR['id']; }
      }
      mysqli_stmt_close($stmtR);
      if (!$roomId) { $errors[] = 'Selected room does not belong to the selected building.'; }
    } else { $errors[] = 'Building and Room required.'; }
    // Check time conflict with other schedules for the same user and day
    if (empty($errors) && $editId>0) {
      $q = mysqli_prepare($conn, "SELECT COUNT(*) AS cnt FROM schedules WHERE faculty_user_id=? AND day_of_week=? AND id<>? AND NOT (? >= end_time OR ? <= start_time)");
      mysqli_stmt_bind_param($q, 'iiiss', $targetId, $dayOfWeek, $editId, $start, $end);
      if (mysqli_stmt_execute($q)) {
        $res = mysqli_stmt_get_result($q);
        if ($rowC = mysqli_fetch_assoc($res)) { if ((int)$rowC['cnt'] > 0) { $errors[] = 'Time conflict with another schedule on the same day.'; } }
      }
      mysqli_stmt_close($q);
    }
    if (empty($errors) && $editId>0) {
      $stmt = mysqli_prepare($conn, "UPDATE schedules SET course_code=?, building_id=?, room_id=?, day_of_week=?, start_time=?, end_time=? WHERE id=? AND faculty_user_id=?");
      mysqli_stmt_bind_param($stmt, 'siiissii', $course, $buildingId, $roomId, $dayOfWeek, $start, $end, $editId, $targetId);
      if (mysqli_stmt_execute($stmt)) { $notice = 'Schedule entry updated.'; } else { $notice = 'Failed to update.'; }
      mysqli_stmt_close($stmt);
      $didAction = true;
    } else if (!empty($errors)) {
      $notice = 'Error: '.implode(' ', $errors);
      $didAction = true;
    }
  }
  if (isset($_POST['save_manual'])) {
    $course   = trim($_POST['course'] ?? '');
    $buildingId = (int)($_POST['building_id'] ?? 0);
    $roomIdForm = (int)($_POST['room_id'] ?? 0);
    $dayName  = trim($_POST['day'] ?? '');
    $start    = trim($_POST['start_time'] ?? '');
    $end      = trim($_POST['end_time'] ?? '');
    $errors   = [];

    // Validate day mapping
    $map = ['Monday'=>1,'Tuesday'=>2,'Wednesday'=>3,'Thursday'=>4,'Friday'=>5,'Saturday'=>6,'Sunday'=>7];
    $dayOfWeek = $map[$dayName] ?? null;
    if (!$dayOfWeek) { $errors[] = 'Invalid day.'; }
    // Validate times (HH:MM)
    $validTime = function($t){ return preg_match('/^\d{2}:\d{2}$/', $t); };
    if (!$validTime($start) || !$validTime($end)) { $errors[] = 'Start/End time must be HH:MM.'; }
    else if (strtotime($start) >= strtotime($end)) { $errors[] = 'End time must be after start time.'; }
    // Enforce bounds: 07:00 to 20:30
    $minT = strtotime('07:00');
    $maxT = strtotime('20:30');
    if (strtotime($start) < $minT || strtotime($end) > $maxT) {
      $errors[] = 'Time must be between 07:00 and 20:30.';
    }

    // Resolve room by IDs
    $roomId = null;
    if ($buildingId > 0 && $roomIdForm > 0) {
      $stmtR = mysqli_prepare($conn, "SELECT id FROM rooms WHERE id=? AND building_id=? LIMIT 1");
      mysqli_stmt_bind_param($stmtR, 'ii', $roomIdForm, $buildingId);
      if (mysqli_stmt_execute($stmtR)) {
        $resR = mysqli_stmt_get_result($stmtR);
        if ($rowR = mysqli_fetch_assoc($resR)) { $roomId = (int)$rowR['id']; }
      }
      mysqli_stmt_close($stmtR);
      if (!$roomId) { $errors[] = 'Room not found in selected building.'; }
    } else { $errors[] = 'Building and Room are required.'; }

    // Check time conflict with other schedules for the same user and day
    if (empty($errors)) {
      $q = mysqli_prepare($conn, "SELECT COUNT(*) AS cnt FROM schedules WHERE faculty_user_id=? AND day_of_week=? AND NOT (? >= end_time OR ? <= start_time)");
      mysqli_stmt_bind_param($q, 'iiss', $targetId, $dayOfWeek, $start, $end);
      if (mysqli_stmt_execute($q)) {
        $res = mysqli_stmt_get_result($q);
        if ($rowC = mysqli_fetch_assoc($res)) { if ((int)$rowC['cnt'] > 0) { $errors[] = 'Time conflict with another schedule on the same day.'; } }
      }
      mysqli_stmt_close($q);
    }
    if (empty($errors)) {
      $stmt = mysqli_prepare($conn, "INSERT INTO schedules (faculty_user_id, course_code, building_id, room_id, day_of_week, start_time, end_time) VALUES (?,?,?,?,?,?,?)");
      mysqli_stmt_bind_param($stmt, 'isiiiss', $targetId, $course, $buildingId, $roomId, $dayOfWeek, $start, $end);
      if (mysqli_stmt_execute($stmt)) {
        $notice = 'Schedule entry saved.';
      } else {
        $notice = 'Failed to save schedule.';
      }
      mysqli_stmt_close($stmt);
      $didAction = true;
    } else {
      $notice = 'Error: '.implode(' ', $errors);
      $didAction = true;
    }
  }
  // Redirect after POST to prevent resubmission on refresh
  if ($didAction) {
    $_SESSION['flash_notice'] = $notice;
    header('Location: edit_schedule.php?user_id='.(int)$targetId);
    exit;
  }
}
// Load Buildings and Rooms for dependent selects
$buildings = [];
$roomsByBuilding = [];
$rb = mysqli_query($conn, "SELECT id, name FROM buildings ORDER BY name");
if ($rb) {
  while ($b = mysqli_fetch_assoc($rb)) {
    // Derive a short code for display like "acad" from "Academic Building"
    $short = strtolower(substr(preg_replace('/\s+/', '', (string)$b['name']), 0, 4));
    $b['code'] = $short;
    $buildings[] = $b;
    $roomsByBuilding[(int)$b['id']] = [];
  }
}
// Build rooms list using actual DB room_name + room_number label (e.g., "ACAD 101")
$rr = mysqli_query($conn, "SELECT id, building_id, room_number, room_name FROM rooms ORDER BY room_number, room_name");
if ($rr) {
  while ($r = mysqli_fetch_assoc($rr)) {
    $bid = (int)$r['building_id'];
    $name = trim((string)($r['room_name'] ?? ''));
    $num  = trim((string)($r['room_number'] ?? ''));
    $label = trim($name . ( ($name!=='' && $num!=='') ? ' ' : '' ) . $num);
    if ($label==='') { $label = '—'; }
    $roomsByBuilding[$bid][] = [
      'id' => (int)$r['id'],
      'label' => $label,
    ];
  }
  @mysqli_free_result($rr);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>FindMyProf • Edit Schedule</title>
  <link rel="stylesheet" href="../../css/faculty.css" />
  <style>
    .tabs { display:flex; gap:6px; margin-bottom:10px; flex-wrap:wrap }
    .tabs .tab { padding:8px 12px; border-radius:999px; border:1px solid #cbd5e1; background:#fff; color:#111; text-decoration:none }
    .panel { border:1px solid #d9e2db; border-radius:12px; background:#fff }
    .panel-head { padding:10px 14px; border-bottom:1px solid #e2e8f0; font-weight:800 }
    .panel-body { padding:12px 16px }
    .two { display:grid; grid-template-columns: 1fr 1fr; gap:12px }
    .grid { display:grid; grid-template-columns: repeat(7, 1fr); gap:6px; }
    .cell { border:1px dashed #cbd5e1; border-radius:8px; padding:8px; min-height:56px; background:#f9fafb }
    .slot { background:#fff; border:1px solid #cbd5e1; border-radius:8px; padding:6px; margin-bottom:6px }
    .slot h4 { margin:0 0 4px 0; font-size:12px }
    .slot .meta { font-size:12px; color:#475569 }
    .slot .actions { display:flex; gap:6px; margin-top:6px; flex-wrap:wrap }
    .slot details summary { cursor:pointer; color:#0b5b0c }
    .slot .delete { background:#e53e3f; color:#fff; border:none; border-radius:999px; padding:6px 10px; cursor:pointer; }
    .slot .edit { background:#0b5b0c; color:#fff; border:none; border-radius:999px; padding:6px 10px; cursor:pointer; }
    .small { font-size:12px }
    .row { display:flex; gap:10px; align-items:center; margin:6px 0 }
    .label { font-size:12px; color:#64748b; display:block; margin-bottom:4px }
    .btn { background:#0b5b0c; color:#fff; border:none; border-radius:999px; padding:8px 14px; cursor:pointer; }
    .btn.ghost { background:#fff; color:#0b5b0c; border:1px solid #0b5b0c }
    .muted { color:#6b7280; font-size:13px }
    .help { background:#f1f5f9; border:1px solid #e2e8f0; border-radius:8px; padding:8px; font-size:13px; color:#334155 }
    @media (max-width: 900px){ .two { grid-template-columns: 1fr } }
    /* Timetable */
    .tt { width:100%; border-collapse:collapse; table-layout:fixed; font-size:11px; }
    .tt th, .tt td { border:1px solid #6b7280; padding:4px; vertical-align:top; line-height:1.15; text-align:center }
    .tt td:not(.time) { padding:0 }
    .tt th { background:#f8fafc; font-weight:700; font-size:11px; text-align:center }
    .tt .time { width:125px; background:#f8fafc; font-size:11px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; padding-right:8px }
    .tt tbody td:not(.time):hover { background:#eef2f7 }
    .tt td.has-slot { cursor: pointer; vertical-align: middle }
    .tt .slotbox { background:transparent; border:none; border-radius:0; padding:2px; font-size:11px; height:100%; box-sizing:border-box; text-align:center; cursor:pointer; transition: background-color .15s ease; display:flex; width:100%; height:100%; align-items:center; justify-content:center; flex-direction:column }
    .tt .slotbox:hover { background:#eef2f7 }
    .tt .slotbox .title { font-weight:400; margin-bottom:2px; text-align:center; color:#000 }
    .tt .slotbox .meta { color:#000; text-align:center }
    .tt .slotbox .small { color:#000 }
    /* Modal */
    .modal { position: fixed; inset: 0; background: rgba(0,0,0,0.45); display: none; align-items: center; justify-content: center; z-index: 3000 }
    .modal.open { display: flex }
    .modal-card { width: min(900px, 96vw); background: #fff; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.25); overflow: hidden }
    .modal-head { padding: 12px 16px; border-bottom: 1px solid #e2e8f0; font-weight: 800 }
    .modal-body { padding: 12px 16px }
    .modal-actions { padding: 10px 16px; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 8px }
    .btn.danger { background:#e53e3f; color:#fff }
    .error { background:#fee2e2; color:#991b1b; border:1px solid #fecaca; border-radius:8px; padding:8px; margin:8px 0; font-size:12px; display:none }
  </style>
</head>
<body>
  <header class="topbar">
    <div class="topbar-inner">
      <div class="brand">
        <div class="brand-mark" aria-hidden="true">LOGO</div>
        <span class="brand-name">FindMyProf</span>
      </div>
      <h1 class="page-title">EDIT SCHEDULE</h1>
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
        <a class="item" href="manage_schedules.php">SCHEDULES</a>
        <a class="item" href="add_building.php">ADD BUILDING</a>
        <a class="item" href="logs.php">LOGS</a>
        <a class="item" href="add_personel.php">ADD PERSONEL</a>
      </nav>
    </aside>

    <main class="content">
      <?php if ($notice !== '') { echo '<div class="notice" style="margin:10px 0">'.htmlspecialchars($notice).'</div>'; } ?>
      <div class="row" style="align-items:flex-start;margin:6px 0 10px;gap:0;flex-direction:column">
        <a class="btn ghost" href="manage_schedules.php">← Back</a>
        <div style="font-weight:800;margin-top:6px">
          <?php echo htmlspecialchars($target['name'] ?? ''); ?>
          <?php if (!empty($target['email'])) { echo ' <span class="muted">('.htmlspecialchars($target['email']).')</span>'; } ?>
        </div>
      </div>

      <section class="panel">
        <div class="panel-head">UPLOAD OR SCAN SCHEDULE (placeholder)</div>
        <div class="panel-body two">
          <form method="post" action="" enctype="multipart/form-data">
            <label>
              <span class="label">Upload PDF (from Registrar)</span>
              <input type="file" name="pdf" accept="application/pdf" />
            </label>
            <div class="row"><button class="btn" type="submit" name="upload_pdf" value="1">Upload</button><span class="muted">We will parse and extract sections/meeting times next.</span></div>
          </form>
          <div class="help">
            <div style="font-weight:700;margin-bottom:6px">How it will work</div>
            <div>- PDF is uploaded and parsed server-side.</div>
            <div>- Entries are shown for review, with errors highlighted (unknown rooms, conflicts, etc.).</div>
            <div>- You confirm to save to your schedule.</div>
          </div>
        </div>
      </section>
      <?php
        // Load target user's schedules for grid
        $schedules = [];
        $sql = "SELECT s.id, s.course_code, s.day_of_week, s.start_time, s.end_time,
                       b.id AS building_id, b.name AS building_name, r.id AS room_id, r.room_number, r.room_name
                  FROM schedules s
             LEFT JOIN rooms r ON r.id = s.room_id
             LEFT JOIN buildings b ON b.id = r.building_id
                 WHERE s.faculty_user_id = {$targetId}
              ORDER BY s.day_of_week ASC, s.start_time ASC, s.id ASC";
        $rs = mysqli_query($conn, $sql);
        if ($rs) { while ($row = mysqli_fetch_assoc($rs)) { $schedules[] = $row; } }
        $days = [1=>'Monday',2=>'Tuesday',3=>'Wednesday',4=>'Thursday',5=>'Friday',6=>'Saturday',7=>'Sunday'];
        $byDay = [1=>[],2=>[],3=>[],4=>[],5=>[],6=>[],7=>[]];
        foreach ($schedules as $row) { $d=(int)$row['day_of_week']; if(isset($byDay[$d])){ $byDay[$d][] = $row; } }
      ?>

      <section class="panel" style="margin-top:14px">
        <div class="panel-head">
          WEEKLY SCHEDULE <span class="small muted">(Click any schedule cell to edit or delete.)</span>
        </div>
        <div class="panel-body" style="overflow:auto">
          <?php
            // Build a 30-min slot grid from 07:00 to 20:30 (last row 20:00–20:30)
            $slots = [];
            $startHM = [7,0]; $endHM=[20,0];
            $h=$startHM[0]; $m=$startHM[1];
            while($h<$endHM[0] || ($h==$endHM[0] && $m<=$endHM[1])){
              $nH=$h; $nM=$m+30; if($nM>=60){ $nH++; $nM-=60; }
              $from = sprintf('%02d:%02d',$h,$m);
              $to   = sprintf('%02d:%02d',$nH,$nM);
              $slots[] = [$from,$to];
              $h=$nH; $m=$nM;
            }
            // Pre-index schedules by day for faster lookup
            $idx = $byDay;
          ?>
          <table class="tt">
            <colgroup>
              <col style="width:130px" />
              <col />
              <col />
              <col />
              <col />
              <col />
              <col />
              <col />
            </colgroup>
            <thead>
              <tr>
                <th class="time">Time</th>
                <?php foreach ($days as $dnum=>$dname){ echo '<th>'.$dname.'</th>'; } ?>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($slots as [$from,$to]){ ?>
                <tr>
                  <td class="time"><?php echo date('h:i A', strtotime($from)).'-'.date('h:i A', strtotime($to)); ?></td>
                  <?php foreach ($days as $dnum=>$dname){
                    // Determine if a schedule starts exactly at $from for this day
                    $rendered=false;
                    if (!empty($idx[$dnum])){
                      foreach ($idx[$dnum] as $k=>$row){
                        $st=substr($row['start_time'],0,5); $et=substr($row['end_time'],0,5);
                        if ($st >= $from && $st < $to){
                          // compute rowspan in 30-min units (ceil to cover partial blocks)
                          $span = max(1, (int)ceil((strtotime($et)-strtotime($st))/1800));
                          $title = trim($row['course_code'] ?? '');
                          $roomDisp = ($row['room_number'] ?: $row['room_name'] ?: '—');
                          // build day select options
                          $dayOptions = '';
                          foreach ($days as $dd=>$dn){ $sel = ($dd===(int)$row['day_of_week'])?'selected':''; $dayOptions .= '<option value="'.$dn.'" '.$sel.'>'.$dn.'</option>'; }
                          // build building options
                          $bOptions = '<option value="">-- Select Building --</option>';
                          foreach ($buildings as $b) { $sel = ((int)$row['building_id'] === (int)$b['id']) ? 'selected' : ''; $bOptions .= '<option value="'.(int)$b['id'].'" '.$sel.'>'.htmlspecialchars($b['name']).'</option>'; }
                          // build room options for current building
                          $roomOpts = '<option value="">-- Select Room --</option>';
                          $currBid = (int)($row['building_id'] ?? 0);
                          if ($currBid && isset($roomsByBuilding[$currBid])) {
                            foreach ($roomsByBuilding[$currBid] as $rrr) {
                              $sel = ($roomDisp === $rrr['label']) ? 'selected' : '';
                              $roomOpts .= '<option value="'.(int)$rrr['id'].'" '.$sel.'>'.htmlspecialchars($rrr['label']).'</option>';
                            }
                          }
                          $from12 = date('h:i A', strtotime($st));
                          $et12   = date('h:i A', strtotime($et));
                          echo '<td class="has-slot open-edit" rowspan="'.$span.'"'
                              .  ' data-id="'.(int)$row['id'].'"'
                              .  ' data-course="'.htmlspecialchars($row['course_code'] ?? '', ENT_QUOTES).'"'
                              .  ' data-building_id="'.(int)$currBid.'"'
                              .  ' data-room_id="'.(int)($row['room_id'] ?? 0).'"'
                              .  ' data-day="'.$days[(int)$row['day_of_week']].'"'
                              .  ' data-start="'.htmlspecialchars(substr($row['start_time'],0,5)).'"'
                              .  ' data-end="'.htmlspecialchars(substr($row['end_time'],0,5)).'"'
                              .'>'
                              .'<div class="slotbox">'
                              .  ($title !== '' ? '<div class="title">'.htmlspecialchars($title).'</div>' : '')
                              .  '<div class="meta">'.htmlspecialchars($row['building_name'] ?: '—').'</div>'
                              .  '<div class="meta">'.htmlspecialchars($roomDisp).'</div>'
                              .  '<div class="small">'.htmlspecialchars($from12.' - '.$et12).'</div>'
                              .'</div>'
                              .'</td>';
                          // mark rows covered by this span as consumed for subsequent time rows
                          $idx[$dnum][$k]['_busy_until'] = $et;
                          $rendered=true; break;
                        } elseif (isset($row['_busy_until']) && $from < $row['_busy_until'] && $st < $row['_busy_until']) {
                          // inside an active rowspan cell from earlier; skip cell
                          $rendered=true; break;
                        }
                      }
                    }
                    if (!$rendered) { echo '<td></td>'; }
                  } ?>
                </tr>
              <?php } ?>
            </tbody>
          </table>
        </div>
      </section>

      <section id="manual" class="panel" style="margin-top:14px">
        <div class="panel-head">MANUAL EDIT (placeholder)</div>
        <div class="panel-body">
          <form method="post" action="">
            <div class="row" style="flex-wrap:wrap;gap:12px">
              <label>
                <span class="label">Course/Subject</span>
                <input type="text" name="course" placeholder="e.g., IT 101" />
              </label>
              <label>
                <span class="label">Building</span>
                <select name="building_id" id="manualBuilding">
                  <option value="">-- Select Building --</option>
                  <?php foreach ($buildings as $b) { echo '<option value="'.(int)$b['id'].'">'.htmlspecialchars($b['name']).'</option>'; } ?>
                </select>
              </label>
              <label>
                <span class="label">Room</span>
                <select name="room_id" id="manualRoom" disabled>
                  <option value="">-- Select Room --</option>
                </select>
              </label>
              <label>
                <span class="label">Day</span>
                <select name="day">
                  <?php foreach (['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'] as $d) { echo '<option value="'.$d.'">'.$d.'</option>'; } ?>
                </select>
              </label>
              <label>
                <span class="label">Start Time</span>
                <select name="start_time" required>
                  <?php
                    for($h=7;$h<=20;$h++){
                      foreach([0,30] as $m){
                        if ($h===20 && $m===30) { continue; }
                        $val = sprintf('%02d:%02d',$h,$m);
                        echo '<option value="'.$val.'">'.date('h:i A', strtotime($val)).'</option>';
                      }
                    }
                  ?>
                </select>
              </label>
              <label>
                <span class="label">End Time</span>
                <select name="end_time" required>
                  <?php
                    for($h=7;$h<=20;$h++){
                      foreach([0,30] as $m){
                        $val = sprintf('%02d:%02d',$h,$m);
                        echo '<option value="'.$val.'">'.date('h:i A', strtotime($val)).'</option>';
                      }
                    }
                  ?>
                </select>
              </label>
            </div>
            <div id="manualError" class="error"></div>
            <div class="row" style="justify-content:center;margin-top:8px">
              <button class="btn" type="submit" name="save_manual" value="1">Save Entry</button>
            </div>
          </form>
          
        </div>
      </section>
      
      <!-- Edit Modal -->
      <div class="modal" id="editModal" aria-hidden="true">
        <div class="modal-card">
          <div class="modal-head">Edit Schedule</div>
          <div class="modal-body">
            <form method="post" id="editForm" class="small" action="">
              <input type="hidden" name="edit_id" id="edit_id" />
              <input type="hidden" name="delete_id" id="delete_id" />
              <div class="row" style="flex-wrap:wrap;gap:12px">
                <label>
                  <span class="label">Course/Subject</span>
                  <input type="text" name="course" id="edit_course" />
                </label>
                <label>
                  <span class="label">Building</span>
                  <select name="building_id" id="edit_building" class="building-select">
                    <option value="">-- Select Building --</option>
                    <?php foreach ($buildings as $b) { echo '<option value="'.(int)$b['id'].'">'.htmlspecialchars($b['name']).'</option>'; } ?>
                  </select>
                </label>
                <label>
                  <span class="label">Room</span>
                  <select name="room_id" id="edit_room" class="room-select" disabled>
                    <option value="">-- Select Room --</option>
                  </select>
                </label>
                <label>
                  <span class="label">Day</span>
                  <select name="day" id="edit_day">
                    <?php foreach (['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'] as $d) { echo '<option value="'.$d.'">'.$d.'</option>'; } ?>
                  </select>
                </label>
                <label>
                  <span class="label">Start Time</span>
                  <select name="start_time" id="edit_start" required>
                    <?php
                      for($h=7;$h<=20;$h++){
                        foreach([0,30] as $m){
                          if ($h===20 && $m===30) { continue; }
                          $val = sprintf('%02d:%02d',$h,$m);
                          echo '<option value="'.$val.'">'.date('h:i A', strtotime($val)).'</option>';
                        }
                      }
                    ?>
                  </select>
                </label>
                <label>
                  <span class="label">End Time</span>
                  <select name="end_time" id="edit_end" required>
                    <?php
                      for($h=7;$h<=20;$h++){
                        foreach([0,30] as $m){
                          $val = sprintf('%02d:%02d',$h,$m);
                          echo '<option value="'.$val.'">'.date('h:i A', strtotime($val)).'</option>';
                        }
                      }
                    ?>
                  </select>
                </label>
              </div>
              <div id="editError" class="error"></div>
              <div class="modal-actions">
                <button class="btn danger" type="submit" name="delete_sched" value="1" formnovalidate onclick="return confirm('Delete this entry?')">Delete</button>
                <button class="btn" type="submit" name="update_sched" value="1">Save Changes</button>
                <button class="btn ghost" type="button" id="editClose">Close</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </main>
  </div>

  <footer class="footer">
    <div class="footer-inner">© 2025 FindMyProf. All rights reserved.</div>
  </footer>
  <script>
    (function(){
      // Rooms map from PHP
      var roomsMap = <?php echo json_encode($roomsByBuilding, JSON_UNESCAPED_UNICODE); ?>;
      // Existing schedules for client-side conflict checks
      var schedulesData = <?php echo json_encode(array_map(function($r){
        return [
          'id' => (int)$r['id'],
          'day' => (int)$r['day_of_week'],
          'start' => substr($r['start_time'],0,5),
          'end' => substr($r['end_time'],0,5)
        ];
      }, $schedules), JSON_UNESCAPED_UNICODE); ?>;

      function buildOptions(arr){
        var frag = document.createDocumentFragment();
        var opt0 = document.createElement('option');
        opt0.value = '';
        opt0.textContent = '-- Select Room --';
        frag.appendChild(opt0);
        (arr||[]).forEach(function(r){
          var o = document.createElement('option');
          o.value = String(r.id);
          o.textContent = r.label;
          frag.appendChild(o);
        });
        return frag;
      }

      function onBuildingChange(bSelect, rSelect){
        var bid = parseInt(bSelect.value||'0',10);
        rSelect.innerHTML = '';
        if (bid && roomsMap[bid] && roomsMap[bid].length){
          rSelect.appendChild(buildOptions(roomsMap[bid]));
          rSelect.disabled = false;
        } else {
          rSelect.appendChild(buildOptions([]));
          rSelect.disabled = true;
        }
      }

      // Manual form dependent selects
      var manualB = document.getElementById('manualBuilding');
      var manualR = document.getElementById('manualRoom');
      if (manualB && manualR){
        manualB.addEventListener('change', function(){ onBuildingChange(manualB, manualR); });
      }

      // Modal wiring for timetable cells
      var modal = document.getElementById('editModal');
      var editForm = document.getElementById('editForm');
      var eId = document.getElementById('edit_id');
      var eCourse = document.getElementById('edit_course');
      var eBuild = document.getElementById('edit_building');
      var eRoom = document.getElementById('edit_room');
      var eDay = document.getElementById('edit_day');
      var eStart = document.getElementById('edit_start');
      var eEnd = document.getElementById('edit_end');
      var dId = document.getElementById('delete_id');
      var editErr = document.getElementById('editError');
      var manualErr = document.getElementById('manualError');

      function toDayNum(name){
        var map = {Monday:1,Tuesday:2,Wednesday:3,Thursday:4,Friday:5,Saturday:6,Sunday:7};
        return map[name] || null;
      }
      function overlap(aStart, aEnd, bStart, bEnd){
        return !(aStart >= bEnd || aEnd <= bStart);
      }
      function hasConflict(dayNum, start, end, excludeId){
        for (var i=0;i<schedulesData.length;i++){
          var s = schedulesData[i];
          if (s.day !== dayNum) continue;
          if (excludeId && String(s.id) === String(excludeId)) continue;
          if (overlap(start, end, s.start, s.end)) return true;
        }
        return false;
      }

      function openModal(){ modal.classList.add('open'); modal.setAttribute('aria-hidden','false'); }
      function closeModal(){ modal.classList.remove('open'); modal.setAttribute('aria-hidden','true'); }

      document.querySelectorAll('.open-edit').forEach(function(btn){
        btn.addEventListener('click', function(){
          if (editErr) { editErr.style.display='none'; editErr.textContent=''; }
          eId.value = this.dataset.id || '';
          if (dId) dId.value = eId.value;
          eCourse.value = this.dataset.course || '';
          eDay.value = this.dataset.day || 'Monday';
          eStart.value = this.dataset.start || '';
          eEnd.value = this.dataset.end || '';
          // Populate building and rooms
          var bid = parseInt(this.dataset.building_id || '0', 10);
          eBuild.value = bid ? String(bid) : '';
          onBuildingChange(eBuild, eRoom);
          var rid = parseInt(this.dataset.room_id || '0', 10);
          if (rid && !eRoom.disabled) { eRoom.value = String(rid); }
          openModal();
        });
      });

      document.getElementById('editClose').addEventListener('click', closeModal);
      modal.addEventListener('click', function(e){ if (e.target === modal) closeModal(); });

      // Client-side conflict validation on submit (Edit)
      editForm.addEventListener('submit', function(e){
        var isDelete = e.submitter && e.submitter.name === 'delete_sched';
        if (isDelete) return; // allow delete without validation
        if (editErr) { editErr.style.display='none'; editErr.textContent=''; }
        var dayNum = toDayNum(eDay.value);
        var st = (eStart.value||'').substring(0,5);
        var en = (eEnd.value||'').substring(0,5);
        // Require building and room
        if (!eBuild.value || !eRoom.value){
          e.preventDefault();
          editErr.textContent = 'Building and Room are required.';
          editErr.style.display = 'block';
          return;
        }
        // Client-side ordering validation
        if (st && en && st >= en){
          e.preventDefault();
          editErr.textContent = 'End time must be after start time.';
          editErr.style.display = 'block';
          return;
        }
        // Client-side conflict validation
        if (dayNum && st && en && hasConflict(dayNum, st, en, eId.value)){
          e.preventDefault();
          editErr.textContent = 'Time conflict with another schedule on the same day.';
          editErr.style.display = 'block';
        }
      });

      // Client-side conflict validation on submit (Manual)
      var manualForm = document.querySelector('#manual form');
      if (manualForm){
        manualForm.addEventListener('submit', function(e){
          if (manualErr) { manualErr.style.display='none'; manualErr.textContent=''; }
          var daySel = manualForm.querySelector('select[name="day"]');
          var stSel = manualForm.querySelector('select[name="start_time"]');
          var enSel = manualForm.querySelector('select[name="end_time"]');
          var bSel = document.getElementById('manualBuilding');
          var rSel = document.getElementById('manualRoom');
          var dayNum = toDayNum(daySel ? daySel.value : '');
          var st = stSel ? (stSel.value||'').substring(0,5) : '';
          var en = enSel ? (enSel.value||'').substring(0,5) : '';
          // Require building and room
          if (!bSel || !bSel.value || !rSel || !rSel.value){
            e.preventDefault();
            manualErr.textContent = 'Building and Room are required.';
            manualErr.style.display = 'block';
            return;
          }
          // Client-side ordering validation
          if (st && en && st >= en){
            e.preventDefault();
            manualErr.textContent = 'End time must be after start time.';
            manualErr.style.display = 'block';
            return;
          }
          // Client-side conflict validation
          if (dayNum && st && en && hasConflict(dayNum, st, en, null)){
            e.preventDefault();
            manualErr.textContent = 'Time conflict with another schedule on the same day.';
            manualErr.style.display = 'block';
          }
        });
      }
    })();
  </script>
</body>
</html>

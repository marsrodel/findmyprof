<?php
// A4 printable QR poster for a room. Opens as a clean page ready to Print to PDF.
// Usage: qr_poster.php?rid=123
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: ../login.php'); exit; }
require_once('../../server/db.php');
$rid = isset($_GET['rid']) ? (int)$_GET['rid'] : 0;
$qrImg = '';
$qrBldgName = '';
$qrRoomName = '';
$qrRoomNumber = '';
if ($rid > 0) {
  $serverPath = dirname(__DIR__, 2) . '/public/qr/room_' . $rid . '.png';
  if (is_file($serverPath)) { $qrImg = '../../public/qr/room_' . $rid . '.png'; }
  $stmt = mysqli_prepare($conn, "SELECT b.name AS bname, r.room_name AS rname, r.room_number AS rnum FROM rooms r JOIN buildings b ON b.id=r.building_id WHERE r.id=? LIMIT 1");
  mysqli_stmt_bind_param($stmt, 'i', $rid);
  if (mysqli_stmt_execute($stmt)) {
    $res = mysqli_stmt_get_result($stmt);
    if ($row = mysqli_fetch_assoc($res)) {
      $qrBldgName = (string)($row['bname'] ?? '');
      $qrRoomName = (string)($row['rname'] ?? '');
      $qrRoomNumber = (string)($row['rnum'] ?? '');
    }
  }
  mysqli_stmt_close($stmt);
}
$roomLabel = ($qrRoomName !== '' && $qrRoomNumber !== '') ? ($qrRoomName . ' - ' . $qrRoomNumber) : ($qrRoomName !== '' ? $qrRoomName : $qrRoomNumber);
$pageTitle = trim(($qrBldgName!==''?$qrBldgName:'Building'). ' - ' . ($roomLabel!==''?$roomLabel:'Room'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?php echo htmlspecialchars($pageTitle); ?> • QR Poster</title>
  <style>
    @page { size: A4; margin: 18mm; }
    html, body { height: 100%; }
    body {
      font-family: Arial, Helvetica, sans-serif;
      color: #111;
      margin: 0;
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
    }
    .page {
      width: 100%; height: 100%;
      display: grid;
      place-items: center;
      place-content: center;
      gap: 18mm;
      padding: 0;
      box-sizing: border-box;
      position: relative;
    }
    .frame { position:absolute; inset: 6mm; border: 2px solid #aaa; border-radius: 2mm; pointer-events: none; }
    .title { text-align: center; margin: 0; line-height: 1.25; }
    .title .bname { font-weight: 800; font-size: 32px; }
    .title .rname { font-weight: 800; font-size: 26px; margin-top: 6px; }
    .qr {
      display: grid; place-items: center;
    }
    .qr img { width: 85mm; height: 85mm; object-fit: contain; }
    .actions { position: fixed; top: 10px; right: 10px; display: flex; gap: 8px; z-index: 2000; }
    .btn { background:#222; color:#fff; border-radius:20px; padding:6px 12px; text-decoration:none; font-size:12px; }
    @media print {
      .actions { display:none; }
    }
  </style>
  <?php if ((int)($_GET['auto'] ?? 0) === 1) { ?>
  <script>
    window.addEventListener('load', function(){
      // Give images a brief moment if needed
      setTimeout(function(){ window.print(); }, 150);
    });
  </script>
  <?php } ?>
</head>
<body>
  <div class="actions">
    <a class="btn" href="#" onclick="window.print();return false;">Print</a>
  </div>
  <div class="page">
    <div class="frame" aria-hidden="true"></div>
    <div class="title">
      <div class="bname"><?php echo $qrBldgName!=='' ? htmlspecialchars($qrBldgName) : '&lt;Building Name&gt;'; ?></div>
      <div class="rname"><?php echo $roomLabel!=='' ? htmlspecialchars($roomLabel) : '&lt;Room Name&gt;'; ?></div>
    </div>
    <div class="qr">
      <?php if ($qrImg) { ?>
        <img src="<?php echo htmlspecialchars($qrImg); ?>" alt="Room QR" />
      <?php } else { ?>
        <div style="font-size:14px;color:#666">QR code image not found for this room.</div>
      <?php } ?>
    </div>
  </div>
</body>
</html>

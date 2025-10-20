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
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>FindMyProf • Scan QR</title>
  <link rel="stylesheet" href="../../css/faculty.css" />
  <script src="https://unpkg.com/html5-qrcode" defer></script>
</head>
<body>
  <header class="topbar">
    <div class="topbar-inner">
      <div class="brand">
        <div class="brand-mark" aria-hidden="true">LOGO</div>
        <span class="brand-name">FindMyProf</span>
      </div>
      <h1 class="page-title">SCAN QR</h1>
      <a class="btn ghost" href="../../server/logout.php">LOGOUT</a>
    </div>
  </header>

  <div class="layout">
    <aside class="sidebar">
      <div class="avatar">A</div>
      <nav class="menu">
        <a class="item" href="faculty_dashboard.php">DASHBOARD</a>
        <a class="item" href="faculty_search.php">SEARCH</a>
        <a class="item" href="faculty_profile.php">PROFILE</a>
        <a class="item" href="faculty_logs.php">LOGS</a>
      </nav>
    </aside>

    <main class="content">
      <section class="panel">
        <div class="panel-head"><h3>SCAN QR</h3></div>
        <div class="panel-body" style="padding:12px 16px">
          <div id="preview" style="display:grid;place-items:center;height:320px;border:2px dashed #cbd5e1;border-radius:12px;background:#fff;margin-bottom:10px"><span class="muted">Camera Preview</span></div>
          <div class="field two-col">
            <label>
              <span class="label">CAMERA</span>
              <select id="cameraSelect" class="input" style="min-width:220px"></select>
            </label>
            <div style="display:flex;gap:10px;align-items:center">
              <button id="startBtn" class="btn" type="button">Start Camera</button>
              <button id="stopBtn" class="btn" type="button" style="background:#6b7280">Stop</button>
              <input id="fileInput" type="file" accept="image/*" />
            </div>
          </div>
          <div id="status" class="muted" style="margin-top:6px">Grant camera permission to start scanning, or upload a QR image.</div>
        </div>
      </section>
    </main>
  </div>

  <footer class="footer">
    <div class="footer-inner">© 2025 FindMyProf. All rights reserved.</div>
  </footer>

  <script>
    let html5QrCode = null;
    const preview = document.getElementById('preview');
    const statusEl = document.getElementById('status');
    const startBtn = document.getElementById('startBtn');
    const stopBtn = document.getElementById('stopBtn');
    const fileInput = document.getElementById('fileInput');
    const cameraSelect = document.getElementById('cameraSelect');

    function onScanSuccess(decodedText) {
      try {
        const url = 'checkin_qr.php?data=' + encodeURIComponent(decodedText);
        window.location.href = url;
      } catch (e) {
        statusEl.textContent = 'Scan error: ' + e.message;
      }
    }
    function onScanFailure(error) {}

    async function populateDevices() {
      cameraSelect.innerHTML = '';
      try {
        const devices = await Html5Qrcode.getCameras();
        if (!devices || devices.length === 0) {
          const opt = document.createElement('option');
          opt.text = 'No camera found';
          cameraSelect.add(opt);
          return [];
        }
        devices.forEach((d,i)=>{
          const opt = document.createElement('option');
          opt.value = d.id;
          opt.text = d.label || ('Camera ' + (i+1));
          if (/back|rear/i.test(d.label)) opt.selected = true;
          cameraSelect.add(opt);
        });
        return devices;
      } catch (e) { statusEl.textContent = 'Cannot enumerate cameras: ' + e.message; return []; }
    }

    async function ensurePermissionOnce() {
      if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) return;
      try {
        const stream = await navigator.mediaDevices.getUserMedia({ video: true });
        stream.getTracks().forEach(t=>t.stop());
      } catch (e) {}
    }

    async function startCamera() {
      if (!window.Html5Qrcode) { statusEl.textContent = 'Scanner library not loaded yet.'; return; }
      try {
        if (!html5QrCode) { html5QrCode = new Html5Qrcode('preview'); }
        try { await ensurePermissionOnce(); } catch (e) {}

        let started = false;
        try {
          const devices = await populateDevices();
          if (devices && devices.length > 0) {
            const selected = cameraSelect.value || devices[0].id;
            await html5QrCode.start(selected, { fps: 10, qrbox: 250 }, onScanSuccess, onScanFailure);
            started = true;
          }
        } catch (e) {}
        if (!started) {
          await html5QrCode.start({ facingMode: { exact: 'environment' } }, { fps: 10, qrbox: 250 }, onScanSuccess, onScanFailure)
            .catch(async ()=>{
              await html5QrCode.start({ facingMode: 'environment' }, { fps: 10, qrbox: 250 }, onScanSuccess, onScanFailure);
            });
        }
        statusEl.textContent = 'Scanning...';
      } catch (e) { statusEl.textContent = 'Unable to start camera: ' + e.message; }
    }

    async function stopCamera() {
      try {
        if (html5QrCode && html5QrCode.isScanning) {
          await html5QrCode.stop();
          await html5QrCode.clear();
          statusEl.textContent = 'Camera stopped.';
        }
      } catch (e) { statusEl.textContent = 'Error stopping camera: ' + e.message; }
    }

    startBtn.addEventListener('click', startCamera);
    stopBtn.addEventListener('click', stopCamera);

    window.addEventListener('load', async () => {
      if (window.Html5Qrcode) { await populateDevices(); }
    });

    fileInput.addEventListener('change', async (ev) => {
      const f = ev.target.files && ev.target.files[0];
      if (!f) return;
      if (!window.Html5Qrcode) { statusEl.textContent = 'Scanner library not ready.'; return; }
      try {
        const tmpId = 'tmp-qr-reader';
        const tmpDiv = document.createElement('div');
        tmpDiv.id = tmpId;
        tmpDiv.style.display = 'none';
        document.body.appendChild(tmpDiv);
        const scanner = new Html5Qrcode(tmpId);
        const result = await scanner.scanFile(f, true);
        scanner.clear();
        tmpDiv.remove();
        onScanSuccess(result);
      } catch (e) { statusEl.textContent = 'Failed to read QR: ' + e.message; }
    });
  </script>
</body>
</html>

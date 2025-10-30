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
  <style>
    /* Tighter scan layout so it doesn't cover buttons */
    .scan-panel .panel-body{padding:12px 16px}
    #preview{width:100%;max-width:500px;height:240px;border:2px dashed #cbd5e1;border-radius:12px;background:#fff;margin:0 auto 10px;display:grid;place-items:center;overflow:hidden}
    .scan-controls{display:flex;flex-wrap:wrap;gap:10px;align-items:center}
    #cameraSelect{min-width:220px}
    @media (max-width: 640px){
      #preview{height:200px;max-width:100%}
    }
  </style>
  <!-- Prefer native BarcodeDetector; fallback to html5-qrcode (CDN) with service worker caching for offline reuse. -->
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
      <section class="panel scan-panel">
        <div class="panel-head"><h3>SCAN QR</h3></div>
        <div class="panel-body">
          <div id="preview">
            <video id="video" autoplay playsinline style="width:100%;height:100%;object-fit:cover"></video>
            <div id="h5c" style="width:100%;height:100%;display:none"></div>
          </div>
          <div class="field two-col">
            <label>
              <span class="label">CAMERA</span>
              <select id="cameraSelect" class="input"></select>
            </label>
            <div class="scan-controls">
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
    const HTML5_QRCODE_LOCAL = '../../public/js/html5-qrcode.min.js';
    const HTML5_QRCODE_CDNS = [
      'https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/minified/html5-qrcode.min.js',
      'https://unpkg.com/html5-qrcode@2.3.8/minified/html5-qrcode.min.js',
      'https://cdnjs.cloudflare.com/ajax/libs/html5-qrcode/2.3.8/html5-qrcode.min.js'
    ];
    if ('serviceWorker' in navigator) {
      window.addEventListener('load', () => {
        navigator.serviceWorker.register('../../public/sw.js').catch(()=>{});
      });
    }
    const video = document.getElementById('video');
    const statusEl = document.getElementById('status');
    const startBtn = document.getElementById('startBtn');
    const stopBtn = document.getElementById('stopBtn');
    const fileInput = document.getElementById('fileInput');
    const cameraSelect = document.getElementById('cameraSelect');
    const h5Container = document.getElementById('h5c');

    let stream = null;
    let detector = null;
    let scanning = false;
    let h5 = null; // Html5Qrcode instance when used

    function loadScriptOnce(src){
      return new Promise((resolve, reject) => {
        if (document.querySelector(`script[data-dyn="${src}"]`)) return resolve();
        const s = document.createElement('script');
        s.src = src; s.async = true; s.defer = true; s.dataset.dyn = src;
        s.onload = () => resolve();
        s.onerror = () => reject(new Error('Failed to load '+src));
        document.head.appendChild(s);
      });
    }

    async function ensureHtml5QrcodeReady(){
      if (window.Html5Qrcode) return true;
      // try local first (works fully offline if file is present)
      try { await loadScriptOnce(HTML5_QRCODE_LOCAL); } catch(e) {}
      if (window.Html5Qrcode) return true;
      // fallback to CDN (will be cached by SW after first online use)
      for (const url of HTML5_QRCODE_CDNS) {
        try { await loadScriptOnce(url); break; } catch(e) { /* try next */ }
      }
      if (!window.Html5Qrcode) return false;
      // wait up to 3s for global to appear
      const t0 = Date.now();
      while (!window.Html5Qrcode && Date.now()-t0 < 3000) { await new Promise(r=>setTimeout(r,100)); }
      return !!window.Html5Qrcode;
    }

    function onScanSuccess(text) {
      try {
        const url = 'checkin_qr.php?data=' + encodeURIComponent(text);
        window.location.href = url;
      } catch (e) {
        statusEl.textContent = 'Scan error: ' + e.message;
      }
    }

    async function ensureDevices() {
      cameraSelect.innerHTML = '';
      try {
        // Some browsers need a prompt to enumerate devices
        await navigator.mediaDevices.getUserMedia({ video: true }).then(s=>s.getTracks().forEach(t=>t.stop())).catch(()=>{});
        const devices = await navigator.mediaDevices.enumerateDevices();
        const cams = devices.filter(d=>d.kind==='videoinput');
        if (cams.length === 0) {
          const opt = document.createElement('option'); opt.text = 'No camera found'; cameraSelect.add(opt); return [];
        }
        cams.forEach((d,i)=>{
          const opt = document.createElement('option');
          opt.value = d.deviceId;
          opt.text = d.label || ('Camera ' + (i+1));
          if (/back|rear/i.test(d.label)) opt.selected = true;
          cameraSelect.add(opt);
        });
        return cams;
      } catch (e) { statusEl.textContent = 'Cannot enumerate cameras: ' + e.message; return []; }
    }

    async function startCamera() {
      // Try native path first
      if ('BarcodeDetector' in window) {
        try {
          if (!detector) detector = new BarcodeDetector({ formats: ['qr_code'] });
        } catch (e) { /* fallthrough to html5-qrcode */ }
      }

      if (detector) {
        try {
          await stopCamera();
          const cams = await ensureDevices();
          const selectedId = cameraSelect.value || (cams[0] && cams[0].deviceId);
          // Try with selected deviceId; fallback chains if it fails
          try {
            stream = await navigator.mediaDevices.getUserMedia({ video: selectedId ? { deviceId: { exact: selectedId } } : { facingMode: 'environment' }, audio: false });
          } catch (e1) {
            try {
              stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: { exact: 'environment' } }, audio: false });
            } catch (e2) {
              stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' }, audio: false });
            }
          }
          video.muted = true;
          video.srcObject = stream;
          try { await video.play(); } catch(_) {}
          video.style.display = '';
          h5Container.style.display = 'none';
          scanning = true;
          statusEl.textContent = 'Scanning...';
          requestAnimationFrame(scanLoop);
          return;
        } catch (e) { /* fallback to html5-qrcode */ }
      }

      // Fallback: html5-qrcode (works across browsers; cached by SW for offline)
      statusEl.textContent = 'Loading scanner...';
      const ok = await ensureHtml5QrcodeReady();
      if (!ok) { statusEl.textContent = 'Scanner not ready yet. Try again in a moment.'; return; }
      try {
        let cams = [];
        try { cams = await Html5Qrcode.getCameras(); } catch(_) { cams = []; }
        // populate selector if empty
        if (cameraSelect.options.length === 0 && cams && cams.length) {
          cams.forEach((d,i)=>{
            const opt = document.createElement('option');
            opt.value = d.id; opt.text = d.label || ('Camera ' + (i+1));
            if (/back|rear/i.test(d.label)) opt.selected = true;
            cameraSelect.add(opt);
          });
        }
        const sel = cameraSelect.value || (cams[0] && cams[0].id) || { facingMode:'environment' };
        // start html5-qrcode on its own container
        h5Container.style.display = '';
        video.style.display = 'none';
        if (!h5) h5 = new Html5Qrcode('h5c');
        try {
          await h5.start(sel, { fps: 10, qrbox: { width: 200, height: 200 } }, onScanSuccess, () => {});
        } catch (eStart) {
          // final fallback: try generic constraints object
          await h5.start({ facingMode: 'environment' }, { fps: 10, qrbox: { width: 200, height: 200 } }, onScanSuccess, () => {});
        }
        statusEl.textContent = 'Scanning...';
      } catch (e) {
        const msg = (e && e.name === 'NotAllowedError') ? 'Camera permission denied. Please allow camera access.' : ('Unable to start camera: ' + (e && e.message ? e.message : e));
        statusEl.textContent = msg;
      }
    }

    async function stopCamera() {
      scanning = false;
      if (stream) {
        stream.getTracks().forEach(t=>t.stop());
        stream = null;
      }
      try {
        if (h5 && h5._isScanning) { await h5.stop(); await h5.clear(); }
      } catch (_) {}
      h5Container.style.display = 'none';
      video.style.display = '';
      statusEl.textContent = 'Camera stopped.';
    }

    async function scanLoop() {
      if (!scanning || !detector) return;
      try {
        const barcodes = await detector.detect(video);
        if (barcodes && barcodes.length > 0) {
          scanning = false;
          await stopCamera();
          const val = barcodes[0].rawValue || barcodes[0].rawText || '';
          if (val) return onScanSuccess(val);
        }
      } catch (_) {}
      if (scanning) requestAnimationFrame(scanLoop);
    }

    window.addEventListener('load', ensureDevices);
    startBtn.addEventListener('click', startCamera);
    stopBtn.addEventListener('click', stopCamera);

    fileInput.addEventListener('change', async (ev) => {
      const f = ev.target.files && ev.target.files[0];
      if (!f) return;
      // Try native first
      if ('BarcodeDetector' in window) {
        try {
          if (!detector) detector = new BarcodeDetector({ formats: ['qr_code'] });
          const img = new Image();
          img.onload = async () => {
            try {
              const canvas = document.createElement('canvas');
              canvas.width = img.naturalWidth; canvas.height = img.naturalHeight;
              const ctx = canvas.getContext('2d');
              ctx.drawImage(img, 0, 0);
              const codes = await detector.detect(canvas);
              if (codes && codes.length > 0) { const val = codes[0].rawValue || codes[0].rawText || ''; if (val) return onScanSuccess(val); }
              statusEl.textContent = 'No QR found in image.';
            } catch (e) { statusEl.textContent = 'Failed to read QR: ' + e.message; }
          };
          img.onerror = () => { statusEl.textContent = 'Cannot load image.'; };
          img.src = URL.createObjectURL(f);
          return;
        } catch (e) { /* fallthrough */ }
      }
      // Fallback: html5-qrcode local decoding of image
      statusEl.textContent = 'Loading scanner...';
      const ok = await ensureHtml5QrcodeReady();
      if (!ok) { statusEl.textContent = 'Scanner not ready yet. Try again in a moment.'; return; }
      try {
        const tmpId = 'tmp-qr-reader';
        const tmpDiv = document.createElement('div'); tmpDiv.id = tmpId; tmpDiv.style.display = 'none'; document.body.appendChild(tmpDiv);
        const scanner = new Html5Qrcode(tmpId);
        const result = await scanner.scanFile(f, true);
        await scanner.clear(); tmpDiv.remove();
        onScanSuccess(result);
      } catch (e) { statusEl.textContent = 'Failed to read QR: ' + e.message; }
    });
  </script>
</body>
</html>

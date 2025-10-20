<?php
  session_start();
  if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
  }
  require_once('../../server/db.php');
  $uid = (int)($_SESSION['user_id'] ?? 0);
  if ($uid > 0) {
    $rs = mysqli_query($conn, "SELECT role FROM users WHERE id={$uid} LIMIT 1");
    if ($rs && ($row = mysqli_fetch_assoc($rs))) { $_SESSION['role'] = $row['role']; }
  }
  if (($_SESSION['role'] ?? '') !== 'Admin') {
    header('Location: ../faculty_view/faculty_dashboard.php');
    exit;
  }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>FindMyProf • Add Building</title>
  <link rel="stylesheet" href="../../css/admin.css" />
</head>
<body>
  <header class="topbar">
    <div class="topbar-inner">
      <div class="brand">
        <div class="brand-mark" aria-hidden="true">LOGO</div>
        <span class="brand-name">FindMyProf</span>
      </div>
      <h1 class="page-title">ADD BUILDING</h1>
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
        <a class="item active" href="add_building.php">ADD BUILDING</a>
        <a class="item" href="staff_list.php">STAFF</a>
        <a class="item" href="logs.php">LOGS</a>
        <a class="item" href="add_personel.php">ADD PERSONEL</a>
      </nav>
    </aside>

    <main class="content">
      <form method="post" action="../../server/buildings_save.php">
      <section class="form-section">
        <div class="form-head">
          <h3>BUILDING DETAILS</h3>
        </div>
        <div class="form-body">
          <div class="field two-col">
            <label>
              <span class="label">BUILDING NAME</span>
              <input type="text" name="building_name" placeholder="Value" required />
            </label>
            <label>
              <span class="label">BUILDING CODE</span>
              <input type="text" name="building_code" placeholder="Value" required />
            </label>
          </div>
          <div class="field">
            <label>
              <span class="label">BUILDING DESCRIPTION</span>
              <textarea rows="5" name="building_description" placeholder="Add building description."></textarea>
            </label>
          </div>
        </div>
      </section>

      <section class="form-section">
        <div class="form-head">
          <h3>ROOM DETAILS</h3>
        </div>
        <div class="form-body">
          <div id="roomsWrap">
            <div class="field two-col room-row">
              <label>
                <span class="label">ROOM NAME</span>
                <input type="text" name="room_name[]" placeholder="Value" />
              </label>
              <label>
                <span class="label">ROOM NUMBER</span>
                <input type="text" name="room_number[]" placeholder="Value" />
              </label>
              <div>
                <button class="btn small room-del" type="button" onclick="removeRoom(this)" style="display:none">Delete</button>
              </div>
            </div>
          </div>
          <div class="form-actions">
            <span class="muted-note">On save, QR codes will be generated for each room.</span>
            <div>
              <button class="btn secondary" type="button" onclick="addRoom()">+ Add Room</button>
              <button class="btn" type="submit">Save Building</button>
            </div>
          </div>
        </div>
      </section>
      <script>
        function addRoom(){
          var wrap = document.getElementById('roomsWrap');
          var row = document.createElement('div');
          row.className = 'field two-col room-row';
          row.innerHTML =
            '<label>'+
              '<span class="label">ROOM NAME</span>'+
              '<input type="text" name="room_name[]" placeholder="Value" />'+
            '</label>'+
            '<label>'+
              '<span class="label">ROOM NUMBER</span>'+
              '<input type="text" name="room_number[]" placeholder="Value" />'+
            '</label>'+
            '<div><button class="btn small room-del" type="button" onclick="removeRoom(this)">Delete</button></div>';
          wrap.appendChild(row);
          updateDeleteButtons();
        }
        function removeRoom(btn){
          var wrap = document.getElementById('roomsWrap');
          var rows = wrap.getElementsByClassName('room-row');
          var row = btn.closest('.room-row');
          if (rows.length > 1) {
            row.remove();
          } else {
            // keep at least one row: just clear fields
            var inputs = row.querySelectorAll('input');
            inputs.forEach(function(i){ i.value = ''; });
          }
          updateDeleteButtons();
        }
        function updateDeleteButtons(){
          var wrap = document.getElementById('roomsWrap');
          var rows = wrap.getElementsByClassName('room-row');
          var show = rows.length >= 2;
          Array.prototype.forEach.call(rows, function(r){
            var btn = r.querySelector('.room-del');
            if (btn) btn.style.display = show ? '' : 'none';
          });
        }
        // initialize visibility on load
        updateDeleteButtons();
      </script>
      </form>
    </main>
  </div>

  <footer class="footer">
    <div class="footer-inner">© 2025 FindMyProf. All rights reserved.</div>
  </footer>
</body>
</html>

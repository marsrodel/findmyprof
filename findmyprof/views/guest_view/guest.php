<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>FindMyProf • Guest View</title>
  <link rel="stylesheet" href="../../css/guest.css" />
</head>
<body>
  <header class="navbar">
    <div class="nav-content">
      <div class="brand">
        <div class="brand-logo" aria-hidden="true">LOGO</div>
        <span class="brand-name">FindMyProf</span>
      </div>
      <a class="btn login-btn" href="../login.php">Login</a>
    </div>
  </header>

  <main class="page">
    <section class="toolbar">
      <input class="search" type="text" placeholder="Hinted search text" />
      <div class="filters">
        <label class="select-wrap">
          <select>
            <option>Sort by Department</option>
          </select>
          <span class="chev" aria-hidden="true">▾</span>
        </label>
        <label class="select-wrap">
          <select>
            <option>Sort by Building</option>
          </select>
          <span class="chev" aria-hidden="true">▾</span>
        </label>
      </div>
    </section>

    <section class="content">
      <div class="panel main-list">
        <div class="table">
          <div class="thead">
            <div class="th">STATUS</div>
            <div class="th">NAME</div>
            <div class="th">TIME</div>
            <div class="th">ROOM</div>
            <div class="th">BUILDING</div>
            <div class="th">COLLEGE</div>
          </div>
          <div class="tbody">
            <!-- rows will go here later; left empty per static mock -->
          </div>
        </div>
      </div>

      <aside class="panel sidebar">
        <div class="table compact">
          <div class="thead">
            <div class="th">STATUS</div>
            <div class="th">NAME</div>
            <div class="th">BUILDING</div>
          </div>
          <div class="tbody">
            <!-- compact rows placeholder -->
          </div>
        </div>
      </aside>
    </section>
  </main>

  <footer class="footer">
    <div class="footer-content">© 2025 FindMyProf. All rights reserved.</div>
  </footer>
</body>
</html>

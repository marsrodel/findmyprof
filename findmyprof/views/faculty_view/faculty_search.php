<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>FindMyProf • Faculty Search</title>
  <link rel="stylesheet" href="../../css/faculty.css" />
</head>
<body>
  <header class="topbar">
    <div class="topbar-inner">
      <div class="brand">
        <div class="brand-mark" aria-hidden="true">LOGO</div>
        <span class="brand-name">FindMyProf</span>
      </div>
      <h1 class="page-title">INSTRUCTORS</h1>
      <a class="btn ghost" href="../../server/logout.php">LOGOUT</a>
    </div>
  </header>

  <div class="layout">
    <aside class="sidebar">
      <div class="avatar">A</div>
      <nav class="menu">
        <a class="item" href="faculty_dashboard.php">DASHBOARD</a>
        <a class="item active" href="faculty_search.php">SEARCH</a>
        <a class="item" href="faculty_profile.php">PROFILE</a>
        <a class="item" href="faculty_logs.php">LOGS</a>
      </nav>
    </aside>

    <main class="content">
      <section class="toolbar">
        <input class="search-input" type="text" placeholder="Hinted search text" />
        <label class="select-wrap">
          <select>
            <option>Sort by Department</option>
          </select>
          <span class="chev" aria-hidden>▾</span>
        </label>
        <label class="select-wrap">
          <select>
            <option>Sort by Building</option>
          </select>
          <span class="chev" aria-hidden>▾</span>
        </label>
        <!-- Add Instructor button intentionally omitted for faculty view -->
      </section>

      <section class="search-grid">
        <div class="panel">
          <div class="table main-table">
            <div class="thead">
              <div class="th">STATUS</div>
              <div class="th">NAME</div>
              <div class="th">TIME</div>
              <div class="th">ROOM</div>
              <div class="th">BUILDING</div>
              <div class="th">COLLEGE</div>
            </div>
            <div class="tbody"></div>
          </div>
        </div>
        <div class="panel">
          <div class="table side-table">
            <div class="thead">
              <div class="th">STATUS</div>
              <div class="th">NAME</div>
              <div class="th">BUILDING</div>
            </div>
            <div class="tbody"></div>
          </div>
        </div>
      </section>
    </main>
  </div>

  <footer class="footer">
    <div class="footer-inner">© 2025 FindMyProf. All rights reserved.</div>
  </footer>
</body>
</html>

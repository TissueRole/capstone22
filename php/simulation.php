<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Simulation</title>
  <link rel="stylesheet" href="simulation/sims/styles.css" />
  <link rel="stylesheet" href="simulation/sims/navbar-simulation.css" />
</head>
<body>
  <!-- Standalone Navbar for Simulation (No Bootstrap) -->
  <nav class="navbar-simulation">
    <div class="container-fluid">
      <a class="navbar-brand" href="index.php">
        <img src="../images/clearteenalogo.png" class="teenanimlogo" alt="home logo">
        TEEN-ANIM
      </a>
      <button class="navbar-toggler" id="navToggle" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <ul class="navbar-menu" id="navMenu">
        <li class="nav-item">
          <a class="nav-link" href="Forum/community.php">Farming Community</a>
        </li>
        <li class="nav-item">
          <a class="nav-link active" href="simulation.php">Simulation</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="modulepage.php">Module</a>
        </li>
      </ul>
    </div>
  </nav>

  <!-- Profile Dropdown (only if logged in) -->
  <div class="profile-dropdown-sim">
    <div class="dropdown">
      <button class="dropdown-toggle" id="profileDropdownSim" aria-expanded="false">
        <img src="../images/clearteenalogo.png" alt="Profile" class="profile-pic-navbar-sim">
      </button>
      <ul class="dropdown-menu" id="profileMenu">
        <li class="dropdown-header">
          <img src="../images/clearteenalogo.png" alt="Profile">
          <div style="font-weight: 600;">User Name</div>
          <div style="font-size: 0.95em; color: #388e3c; margin-top: 2px; font-weight: 500;">
            Student
          </div>
        </li>
        <li><hr class="dropdown-divider"></li>
        <li>
          <a class="dropdown-item" href="php/userpage.php">Profile</a>
        </li>
        <li><hr class="dropdown-divider"></li>
        <li><a class="dropdown-item" href="php/logout.php">Logout</a></li>
      </ul>
    </div>
  </div>

  <header class="header">
    <div>
      <p class="sub">Original simulation that shows how Water/light/temperature affect growth + health.</p>
    </div>
    <div class="header__actions">
      <button id="btnReset" class="btn">Reset</button>
      <button id="btnPlayPause" class="btn btn--primary">Play</button>
    </div>
  </header>

  <main class="layout">
    <section class="panel">
      <div class="card">
        <h2 class="card__title">Plant</h2>
        <div class="stageRow">
          <div class="badge" id="stageBadge">Stage: —</div>
          <div class="badge" id="statusBadge">Status: —</div>
        </div>

        <div class="viewer">
          <div id="lottie"></div>
          <div class="overlay">
            <div class="overlay__bar">
              <div class="overlay__barFill" id="healthBar"></div>
            </div>
            <div class="overlay__label" id="healthLabel">Health: 100%</div>
          </div>
        </div>

        <div class="progress">
          <div class="progress__row">
            <label for="timeScrub">Time</label>
            <input id="timeScrub" type="range" min="0" max="100" step="0.1" value="0" />
            <span id="timePct" class="mono">0%</span>
          </div>
          <div class="progress__row">
            <label for="speed">Speed</label>
            <input id="speed" type="range" min="0" max="3" step="0.05" value="1" />
            <span id="speedLabel" class="mono">1.00×</span>
          </div>
        </div>
      </div>

      <div class="card">
        <h2 class="card__title">Environment Inputs</h2>

        <div class="control">
          <div class="control__top">
            <label for="water">Water</label>
            <span class="mono" id="waterLabel">50%</span>
          </div>
          <input id="water" type="range" min="0" max="100" step="1" value="50" />
          <small class="hint">Too low dries out. Too high can cause root stress.</small>
        </div>

        <div class="control">
          <div class="control__top">
            <label for="light">Light</label>
            <span class="mono" id="lightLabel">60%</span>
          </div>
          <input id="light" type="range" min="0" max="100" step="1" value="60" />
          <small class="hint">More light increases growth, up to an optimal point.</small>
        </div>

        <div class="control">
          <div class="control__top">
            <label for="temp">Temperature</label>
            <span class="mono" id="tempLabel">24°C</span>
          </div>
          <input id="temp" type="range" min="5" max="40" step="1" value="24" />
          <small class="hint">Tomatoes prefer warm conditions; extremes slow growth and reduce health.</small>
        </div>

        <div class="card__footer">
          <div class="kpi">
            <div class="kpi__label">Growth rate</div>
            <div class="kpi__value mono" id="growthRate">—</div>
          </div>
          <div class="kpi">
            <div class="kpi__label">Health change</div>
            <div class="kpi__value mono" id="healthDelta">—</div>
          </div>
        </div>
      </div>
    </section>

    <aside class="panel">
      <div class="card">
        <h2 class="card__title">How it works</h2>
        <ul class="bullets">
          <li>Growth is simulated as a <strong>progress value</strong> (0–100%).</li>
          <li>Inputs change <strong>growth rate</strong> and <strong>health</strong> continuously.</li>
          <li>The tomato animation is scrubbed to the corresponding frame.</li>
        </ul>
        <p class="note">This is an original implementation (not copied from any site). You can tune the formulas in <code>sim.js</code>.</p>
      </div>

      <div class="card">
        <h2 class="card__title">Stages</h2>
        <div class="stages" id="stageList"></div>
      </div>
    </aside>
  </main>

  <footer class="footer">
    <span class="muted">Tip: keep water ~50%, light ~70%, temp ~24°C for best growth.</span>
  </footer>

  <!-- Vanilla JS for dropdown and mobile menu -->
  <script>
    // Mobile menu toggle
    const navToggle = document.getElementById('navToggle');
    const navMenu = document.getElementById('navMenu');
    
    if (navToggle) {
      navToggle.addEventListener('click', () => {
        navMenu.classList.toggle('show');
      });
    }
    
    // Profile dropdown toggle
    const profileDropdown = document.getElementById('profileDropdownSim');
    const profileMenu = document.getElementById('profileMenu');
    
    if (profileDropdown) {
      profileDropdown.addEventListener('click', (e) => {
        e.stopPropagation();
        profileMenu.classList.toggle('show');
      });
    }
    
    // Close dropdown when clicking outside
    document.addEventListener('click', (e) => {
      if (profileMenu && !e.target.closest('.dropdown')) {
        profileMenu.classList.remove('show');
      }
      if (navMenu && !e.target.closest('.navbar-simulation') && window.innerWidth < 992) {
        navMenu.classList.remove('show');
      }
    });
  </script>
  
  <!-- Load Lottie -->
  <script src="https://unpkg.com/lottie-web@5.12.2/build/player/lottie.min.js"></script>
  <script>
    if (!window.lottie || typeof window.lottie.loadAnimation !== 'function') {
      console.error('Lottie failed to load. window.lottie=', window.lottie);
    }
  </script>
  <script type="module" src="simulation/sims/sim.js"></script>
</body>
</html>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Simulation</title>
  <link rel="stylesheet" href="simulation/sim/styles.css" />
</head>
<body>
  
  <div class="header">
    <div>
      <h1>Tomato Growth Simulator</h1>
      <p class="sub">Original simulation using your tomato animation. Water/light/temperature affect growth + health.</p>
    </div>
    <div class="header__actions">
      <button id="btnReset" class="btn">Reset</button>
      <button id="btnPlayPause" class="btn btn--primary">Play</button>
    </div>
</div>

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

  <!-- Load Lottie (avoid SRI mismatch issues by not pinning an integrity hash) -->
  <script src="https://unpkg.com/lottie-web@5.12.2/build/player/lottie.min.js"></script>
  <script>
    // Basic guard to make failures obvious
    if (!window.lottie || typeof window.lottie.loadAnimation !== 'function') {
      console.error('Lottie failed to load. window.lottie=', window.lottie);
    }
  </script>
  <script type="module" src="simulation/sims/sim.js"></script>
</body>
</html>

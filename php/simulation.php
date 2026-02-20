<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>🍅 Tomato Growth Simulator</title>
  <link rel="stylesheet" href="simulation/sims/styles.css?v=4" />
  <script src="https://cdnjs.cloudflare.com/ajax/libs/lottie-web/5.12.2/lottie.min.js"></script>
</head>

<body>
  <!-- Fixed Header -->
  <header class="sim-header">
    <div class="header-left">
      <h1>🍅 Tomato Simulator</h1>
    </div>
    <div class="header-center">
      <button id="btnPlayPause" class="btn btn-icon">▶️</button>
      <button id="btnReset" class="btn btn-icon">🔄</button>
      <div class="time-scrubber-compact">
        <input type="range" id="timeScrub" min="0" max="100" value="0" class="scrubber-input" />
        <span id="timePct" class="time-label">0%</span>
      </div>
      <div class="speed-control-compact">
        <label>Speed:</label>
        <input type="range" id="speed" min="0.1" max="5" step="0.1" value="1" class="speed-input" />
        <span id="speedLabel" class="speed-label">1.00×</span>
      </div>
    </div>
    <div class="header-scenario">
      <select id="scenarioSelectHeader" class="scenario-select-header">
        <option value="">🎯 Select Scenario</option>
      </select>
    </div>
    <div class="header-right">
      <button id="btnToggleControls" class="btn btn-icon" title="Environmental Controls">⚙️</button>
    </div>
  </header>

  <!-- Main Simulation Canvas -->
  <main class="sim-canvas">
    <!-- Floating Status Cards -->
    <div class="status-cards">
      <div class="status-card status-card-health">
        <span class="status-emoji" id="statusEmoji">🌟</span>
        <div class="status-content">
          <span class="status-value" id="healthValue">100%</span>
          <span class="status-label">Health</span>
        </div>
      </div>
      <!-- Health Bar (inline in status column) -->
      <div class="status-card status-card-healthbar">
        <div class="health-bar-inline">
          <div id="healthBarFill" class="health-bar-fill"></div>
          <div id="healthBarText" class="health-bar-text">100%</div>
        </div>
      </div>
      <div class="status-card status-card-stage">
        <span class="status-emoji">🌱</span>
        <div class="status-content">
          <span class="status-value" id="stageValue">Seed</span>
          <span class="status-label">Stage</span>
        </div>
      </div>
      <div class="status-card status-card-day">
        <span class="status-emoji">📅</span>
        <div class="status-content">
          <span class="status-value" id="dayValue">Day 0</span>
        </div>
      </div>
      <!-- Metrics -->
      <div class="status-card status-card-metric">
        <span class="status-emoji">📈</span>
        <div class="status-content">
          <span class="status-value" id="growthRate">0.00 d/s</span>
          <span class="status-label">Growth</span>
        </div>
      </div>
      <div class="status-card status-card-metric">
        <span class="status-emoji">💚</span>
        <div class="status-content">
          <span class="status-value" id="healthDelta">+0.00%/s</span>
          <span class="status-label">Health Δ</span>
        </div>
      </div>
    </div>

    <!-- Plant Viewer (Center) -->
    <div class="plant-viewer-canvas">
      <div id="lottie" class="lottie-container"></div>
    </div>
  </main>

  <!-- Drawer Backdrop -->
  <div id="drawerBackdrop" class="drawer-backdrop"></div>

  <!-- Side Drawer (Controls) -->
  <div id="controlsDrawer" class="side-drawer">
    <div class="drawer-header">
      <h3>⚙️ Environmental Controls</h3>
      <button id="btnCloseControls" class="close-btn">✕</button>
    </div>
    <div class="drawer-body">
      <!-- Water -->
      <div class="control-section">
        <div class="control-header">
          <span class="control-icon">💧</span>
          <span class="control-title">Water</span>
          <span id="waterLabel" class="control-value">50%</span>
        </div>
        <input type="range" id="water" min="0" max="100" value="60" class="control-slider" />
        <div class="control-hint">Optimal: 50-75%</div>
      </div>

      <!-- Light -->
      <div class="control-section">
        <div class="control-header">
          <span class="control-icon">☀️</span>
          <span class="control-title">Light Intensity</span>
          <span id="lightLabel" class="control-value">50%</span>
        </div>
        <input type="range" id="light" min="0" max="100" value="75" class="control-slider" />
        <div class="control-hint">Optimal: varies by stage</div>
      </div>

      <!-- Temperature -->
      <div class="control-section">
        <div class="control-header">
          <span class="control-icon">🌡️</span>
          <span class="control-title">Temperature</span>
          <span id="tempLabel" class="control-value">25°C</span>
        </div>
        <input type="range" id="temp" min="10" max="40" value="25" class="control-slider" />
        <div class="control-hint">Optimal: 21-29°C</div>
      </div>

      <!-- Soil & Nutrients -->
      <div class="control-section-group">
        <h4 class="section-group-title">🧪 Soil & Nutrients</h4>

        <!-- pH -->
        <div class="control-section">
          <div class="control-header">
            <span class="control-title">Soil pH</span>
            <span id="phLabel" class="control-value">6.5</span>
          </div>
          <input type="range" id="soilPh" min="4.0" max="9.0" step="0.1" value="6.5" class="control-slider" />
          <div class="control-hint">Optimal: 6.0-6.8</div>
        </div>

        <!-- Nitrogen -->
        <div class="control-section">
          <div class="control-header">
            <span class="control-title">Nitrogen (N)</span>
            <span id="nitrogenLabel" class="control-value">70%</span>
          </div>
          <input type="range" id="nitrogen" min="0" max="100" value="70" class="control-slider" />
          <div class="control-hint">Optimal: 60-80%</div>
        </div>

        <!-- Phosphorus -->
        <div class="control-section">
          <div class="control-header">
            <span class="control-title">Phosphorus (P)</span>
            <span id="phosphorusLabel" class="control-value">60%</span>
          </div>
          <input type="range" id="phosphorus" min="0" max="100" value="60" class="control-slider" />
          <div class="control-hint">Optimal: 50-70%</div>
        </div>

        <!-- Potassium -->
        <div class="control-section">
          <div class="control-header">
            <span class="control-title">Potassium (K)</span>
            <span id="potassiumLabel" class="control-value">75%</span>
          </div>
          <input type="range" id="potassium" min="0" max="100" value="75" class="control-slider" />
          <div class="control-hint">Optimal: 65-85%</div>
        </div>

        <!-- Container Size -->
        <div class="control-section">
          <div class="control-header">
            <span class="control-title">Container Size</span>
          </div>
          <select id="containerSize" class="control-select">
            <option value="SMALL">Small (1-2 gal)</option>
            <option value="MEDIUM" selected>Medium (3-5 gal)</option>
            <option value="LARGE">Large (7-10 gal)</option>
          </select>
        </div>
      </div>

      <!-- Soil Amendments (shown during pH scenarios) -->
      <div id="amendmentsPanel" class="control-section-group" style="display: none;">
        <h4 class="section-group-title">🧪 Soil Amendments</h4>
        <p class="amendment-hint">Diagnose the pH problem and apply appropriate treatments</p>
        <p style="margin: 0 0 10px 0; font-size: 0.9rem; color: var(--text-secondary);">
          Current pH: <span id="phDisplay">6.5</span> | Target: 6.0-6.8
        </p>

        <div class="amendment-buttons-grid">
          <button id="btnApplyLime" class="btn btn-treatment-compact" title="Raises pH">
            <span class="treatment-icon">🟢</span>
            <span>Lime</span>
          </button>
          <button id="btnApplyDolomite" class="btn btn-treatment-compact" title="Raises pH + Mg">
            <span class="treatment-icon">🟢</span>
            <span>Dolomite</span>
          </button>
          <button id="btnApplySulfur" class="btn btn-treatment-compact" title="Lowers pH">
            <span class="treatment-icon">🔴</span>
            <span>Sulfur</span>
          </button>
          <button id="btnApplyIron" class="btn btn-treatment-compact" title="Lowers pH + Fe">
            <span class="treatment-icon">🔴</span>
            <span>Iron</span>
          </button>
        </div>

        <div class="amendment-log-container">
          <div class="amendment-log-header" id="amendmentLogHeader">
            <strong>Treatment Log</strong>
            <span class="toggle-icon">▼</span>
          </div>
          <div id="amendmentLog" class="amendment-log collapsed"></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Bottom Drawer (Scenarios) -->
  <div id="scenarioBottomSheet" class="bottom-sheet">
    <div class="bottom-sheet-backdrop" id="bottomSheetBackdrop"></div>
    <div class="bottom-sheet-content" id="bottomSheetContent">
      <div class="bottom-sheet-handle"></div>
      <!-- Issue #2 & #9: Left drag handle for expand/collapse -->
      <div class="bottom-sheet-drag-handle-left" id="bottomSheetDragHandleLeft" title="Drag to expand/collapse">⇅</div>

      <!-- Collapsed Header (always visible) -->
      <div class="bottom-sheet-header" id="bottomSheetHeader">
        <div class="scenario-title-compact">
          <h4 id="scenarioTitleCompact"></h4>
          <span id="scenarioCurrentObjective" class="current-objective"></span>
        </div>
        <div class="scenario-progress-compact">
          <div class="progress-bar-mini">
            <div id="scenarioProgressFillMini" class="progress-fill-mini"></div>
          </div>
          <span id="scenarioProgressPercent" class="progress-percent">0%</span>
        </div>
        <button id="btnCancelScenarioCompact" class="btn-cancel-compact" title="Cancel Scenario">✕</button>
      </div>

      <!-- Expanded Body (hidden by default) -->
      <div class="bottom-sheet-body" id="bottomSheetBody">
        <!-- Scenario Details (shown when scenario is active) -->
        <div id="scenarioDetails" style="display: none; margin-top: var(--spacing-lg);">
          <h3 id="scenarioTitle"></h3>
          <p id="scenarioDescription"></p>

          <div class="scenario-objectives">
            <strong>📋 Objectives:</strong>
            <ul id="scenarioObjectives"></ul>
          </div>

          <div class="scenario-hints" style="margin-top: var(--spacing-md);">
            <strong>💡 Hints:</strong>
            <ul id="scenarioHints"></ul>
          </div>

          <div class="scenario-educational" style="margin-top: var(--spacing-md);">
            <strong>📚 Educational Content:</strong>
            <p id="scenarioEducational"></p>
          </div>

          <div class="scenario-success" style="margin-top: var(--spacing-md);">
            <strong>✅ Success Criteria:</strong>
            <ul id="scenarioSuccessCriteria"></ul>
          </div>

          <div class="scenario-progress" style="margin-top: var(--spacing-md);">
            <strong>Progress:</strong>
            <div id="scenarioProgressBar" class="progress-bar">
              <div id="scenarioProgressFill" class="progress-fill"></div>
            </div>
            <p id="scenarioStatus"></p>
          </div>

          <div class="bottom-sheet-actions">
            <button id="btnCompleteScenario" class="btn btn-success" disabled>Complete Scenario</button>
            <button id="btnCancelScenario" class="btn btn-secondary">Cancel Scenario</button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="simulation/sims/config.js"></script>
  <script src="simulation/sims/sim.js?v=4"></script>
</body>

</html>
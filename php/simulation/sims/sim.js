// Enhanced Tomato Growth Simulator
// Research-based parameters with gamification and immersive features
// Phase 1: Core improvements

// Helper to get element by ID
const el = (id) => document.getElementById(id);

// Load data files
let VARIETIES = [];
let PERSONALITIES = [];
let TIMELINE = [];
let ACHIEVEMENTS = [];
let MOTIVATIONAL = {};
let AMENDMENTS = {};

// UI Elements
const UI = {
  // Core controls
  lottieHost: el('lottie'),
  btnReset: el('btnReset'),
  btnPlayPause: el('btnPlayPause'),
  timeScrub: el('timeScrub'),
  timePct: el('timePct'),
  speed: el('speed'),
  speedLabel: el('speedLabel'),

  // Floating status cards
  statusEmoji: el('statusEmoji'),
  healthValue: el('healthValue'),
  stageValue: el('stageValue'),
  dayValue: el('dayValue'),

  // Health bar overlay
  healthBarFill: el('healthBarFill'),
  healthBarText: el('healthBarText'),

  // Environmental controls (in side drawer)
  water: el('water'),
  waterLabel: el('waterLabel'),
  light: el('light'),
  lightLabel: el('lightLabel'),
  temp: el('temp'),
  tempLabel: el('tempLabel'),
  soilPh: el('soilPh'),
  phLabel: el('phLabel'),
  nitrogen: el('nitrogen'),
  nitrogenLabel: el('nitrogenLabel'),
  phosphorus: el('phosphorus'),
  phosphorusLabel: el('phosphorusLabel'),
  potassium: el('potassium'),
  potassiumLabel: el('potassiumLabel'),
  containerSize: el('containerSize'),

  // Metrics
  growthRate: el('growthRate'),
  healthDelta: el('healthDelta'),

  // Drawer controls
  btnOpenControls: el('btnOpenControls'),
  btnCloseControls: el('btnCloseControls'),
  controlsDrawer: el('controlsDrawer'),
  controlsDrawer: el('controlsDrawer'),
  drawerBackdrop: el('drawerBackdrop'),

  // Scenario elements (header dropdown only)
  scenarioSelectHeader: el('scenarioSelectHeader'),
  btnCompleteScenario: el('btnCompleteScenario'),
  btnCancelScenario: el('btnCancelScenario'),
  scenarioTitle: el('scenarioTitle'),
  scenarioDescription: el('scenarioDescription'),
  scenarioObjectives: el('scenarioObjectives'),
  scenarioProgressFill: el('scenarioProgressFill'),
  scenarioStatus: el('scenarioStatus'),

  // Bottom sheet elements
  bottomSheet: el('scenarioBottomSheet'),
  bottomSheetBackdrop: el('bottomSheetBackdrop'),
  bottomSheetContent: el('bottomSheetContent'),
  bottomSheetHeader: el('bottomSheetHeader'),
  bottomSheetBody: el('bottomSheetBody'),
  scenarioTitleCompact: el('scenarioTitleCompact'),
  scenarioCurrentObjective: el('scenarioCurrentObjective'),
  scenarioProgressFillMini: el('scenarioProgressFillMini'),
};

// Simulation State
const state = {
  running: false,
  progress: 0, // 0-1 (represents 0-110 days)
  health: 1,
  currentDay: 0,

  // Plant info
  plantName: '',
  variety: null,

  // Scenario state
  activeScenario: null,
  scenarioStartTime: null,
  scenarioProgress: 0,
  scenarios: [],

  // pH Treatment system
  activeAmendments: [],
  phActual: 6.5,        // Actual pH (changes gradually)
  phTarget: 6.5,        // Target pH from slider
  phLocked: false,      // Lock pH slider during scenarios

  // Tracking
  lastTs: 0,
  lastGrowthPerSec: 0,
  lastHealthPerSec: 0,

  // Lottie
  animation: null,
  svgEl: null,
  totalFrames: 0,
  frameMin: 0,
  frameMax: 0,
  zoom: 1.0, // Zoom level (0.5 - 2.0)
  pan: { x: 0, y: 0 }, // Pan offset
  isDragging: false,
  dragStart: { x: 0, y: 0 },
  panStart: { x: 0, y: 0 },
};

// Utility functions
function clamp(x, min, max) {
  return Math.max(min, Math.min(max, x));
}

function clamp01(x) {
  return clamp(x, 0, 1);
}

function lerp(a, b, t) {
  return a + (b - a) * t;
}

function gaussianScore(x, optimum, sigma) {
  const z = (x - optimum) / sigma;
  return Math.exp(-0.5 * z * z);
}

function plateauScore(x, minOk, maxOk, falloff) {
  if (x < minOk) return gaussianScore(x, minOk, falloff);
  if (x > maxOk) return gaussianScore(x, maxOk, falloff);
  return 1;
}

// Get current growth stage based on day
function getCurrentStage(day) {
  const stages = CONFIG.GROWTH_STAGES;
  return stages.find(s => day >= s.from && day < s.to) || stages[stages.length - 1];
}

// Status for health
function statusForHealth(h01) {
  if (h01 >= 0.8) return { label: 'Thriving', color: '#6ae4a7', emoji: '🌟' };
  if (h01 >= 0.55) return { label: 'Healthy', color: '#7bb7ff', emoji: '😊' };
  if (h01 >= 0.35) return { label: 'Stressed', color: '#ffcc66', emoji: '😰' };
  return { label: 'Critical', color: '#ff6b6b', emoji: '😢' };
}

// Calculate environmental scores (research-based)
function computeEnvironmentalScores(inputs) {
  const env = CONFIG.ENVIRONMENT;

  // Water score (optimal 50-75%)
  const waterScore = plateauScore(
    inputs.water,
    env.WATER.OPTIMAL_MIN / 100,
    env.WATER.OPTIMAL_MAX / 100,
    0.12
  );

  // Light score (DLI-based, varies by stage)
  const currentStage = getCurrentStage(state.currentDay);
  const isFruiting = currentStage.name === 'Fruiting' || currentStage.name === 'Flowering';
  const optimalLightMin = isFruiting ? env.LIGHT.FRUITING_OPTIMAL_MIN : env.LIGHT.SEEDLING_OPTIMAL_MIN;
  const optimalLightMax = isFruiting ? env.LIGHT.FRUITING_OPTIMAL_MAX : env.LIGHT.SEEDLING_OPTIMAL_MAX;

  const lightDLI = inputs.light * env.LIGHT.PERCENT_TO_DLI_FACTOR;
  const lightScore = plateauScore(lightDLI, optimalLightMin, optimalLightMax, 5);

  // Temperature score (optimal 21-29°C day)
  const tempScore = plateauScore(
    inputs.tempC,
    env.TEMPERATURE.OPTIMAL_DAY_MIN,
    env.TEMPERATURE.OPTIMAL_DAY_MAX,
    3.5
  );

  // Soil pH score (optimal 6.0-6.8)
  const phScore = plateauScore(
    inputs.soilPh,
    env.SOIL_PH.OPTIMAL_MIN,
    env.SOIL_PH.OPTIMAL_MAX,
    0.8
  );

  // Nitrogen score (optimal 60-80%)
  const nitrogenScore = plateauScore(
    inputs.nitrogen,
    env.NUTRIENTS.NITROGEN.OPTIMAL_MIN / 100,
    env.NUTRIENTS.NITROGEN.OPTIMAL_MAX / 100,
    0.15
  );

  // Phosphorus score (optimal 50-70%)
  const phosphorusScore = plateauScore(
    inputs.phosphorus,
    env.NUTRIENTS.PHOSPHORUS.OPTIMAL_MIN / 100,
    env.NUTRIENTS.PHOSPHORUS.OPTIMAL_MAX / 100,
    0.15
  );

  // Potassium score (optimal 65-85%)
  const potassiumScore = plateauScore(
    inputs.potassium,
    env.NUTRIENTS.POTASSIUM.OPTIMAL_MIN / 100,
    env.NUTRIENTS.POTASSIUM.OPTIMAL_MAX / 100,
    0.15
  );

  // Container size multiplier
  const container = env.CONTAINER[inputs.containerSize];
  const containerMultiplier = container ? container.rootSpaceMultiplier : 1.0;

  // Overall environmental score (weighted average)
  // Water, light, temp are most critical (20% each)
  // pH and nutrients are important (10% each)
  const envScore = (
    waterScore * 0.20 +
    lightScore * 0.20 +
    tempScore * 0.20 +
    phScore * 0.10 +
    nitrogenScore * 0.10 +
    phosphorusScore * 0.10 +
    potassiumScore * 0.10
  ) * containerMultiplier;

  return {
    waterScore,
    lightScore,
    tempScore,
    phScore,
    nitrogenScore,
    phosphorusScore,
    potassiumScore,
    containerMultiplier,
    envScore: clamp01(envScore),
  };
}

// Calculate growth and health dynamics
function computeDynamics(inputs) {
  const scores = computeEnvironmentalScores(inputs);

  // Growth rate (days per second)
  const baseGrowthRate = CONFIG.GROWTH.BASE_RATE;
  const varietyMultiplier = state.variety ? (110 / state.variety.totalDays) : 1;
  const growthPerSec = baseGrowthRate * scores.envScore * varietyMultiplier * inputs.speed;

  // SCENARIO-AWARE HEALTH LOGIC
  // In scenarios: Only check unlocked controls
  // Outside scenarios: Check all controls

  let minScore;

  if (state.activeScenario && state.activeScenario.lockedControls) {
    // Scenario mode: Only consider UNLOCKED controls
    const locked = state.activeScenario.lockedControls;
    const relevantScores = [];

    if (!locked.water) relevantScores.push(scores.waterScore);
    if (!locked.light) relevantScores.push(scores.lightScore);
    if (!locked.temp) relevantScores.push(scores.tempScore);
    if (!locked.soilPh) relevantScores.push(scores.phScore);
    if (!locked.nitrogen) relevantScores.push(scores.nitrogenScore);
    if (!locked.phosphorus) relevantScores.push(scores.phosphorusScore);
    if (!locked.potassium) relevantScores.push(scores.potassiumScore);

    // Also consider container size if unlocked
    if (!locked.containerSize) {
      relevantScores.push(scores.containerMultiplier);
    }

    // Find minimum of only the unlocked controls
    minScore = relevantScores.length > 0 ? Math.min(...relevantScores) : scores.envScore;

    console.log(`📊 Scenario health check - Unlocked controls min score: ${(minScore * 100).toFixed(0)}%`);
  } else {
    // No scenario: Check ALL environmental factors
    minScore = Math.min(
      scores.waterScore,
      scores.lightScore,
      scores.tempScore,
      scores.phScore,
      scores.nitrogenScore,
      scores.phosphorusScore,
      scores.potassiumScore
    );
  }

  const neutralPoint = 0.5;
  const deviation = minScore - neutralPoint;

  let healthPerSec;

  if (deviation > 0) {
    // Conditions above neutral - recovery allowed
    const recoveryFactor = deviation * 2; // Maps 0.5-1.0 to 0-1
    healthPerSec = recoveryFactor * CONFIG.GROWTH.RECOVERY_RATE * inputs.speed;
  } else {
    // Conditions below neutral - decline
    const declineFactor = Math.abs(deviation) * 2; // Maps 0-0.5 to 0-1
    healthPerSec = -declineFactor * CONFIG.GROWTH.DECAY_RATE * inputs.speed;
  }

  return {
    ...scores,
    growthPerSec,
    healthPerSec,
  };
}

// Read current input values
function readInputs() {
  return {
    speed: Number(UI.speed.value),
    water: Number(UI.water.value) / 100,
    light: Number(UI.light.value) / 100,
    tempC: Number(UI.temp.value),
    soilPh: state.phActual,  // Use actual pH (gradual changes)
    nitrogen: Number(UI.nitrogen.value) / 100,
    phosphorus: Number(UI.phosphorus.value) / 100,
    potassium: Number(UI.potassium.value) / 100,
    containerSize: UI.containerSize.value,
  };
}

// Update progress (0-1 represents 0-110 days)
function setProgress(p01) {
  state.progress = clamp01(p01);
  state.currentDay = state.progress * 110; // Map to days

  const pct = Math.round(state.progress * 100);
  UI.timeScrub.value = String(pct);
  UI.timePct.textContent = `${pct}%`;

  // Update day display in floating status card
  if (UI.dayValue) {
    UI.dayValue.textContent = `Day ${Math.floor(state.currentDay)}`;
  }

  // Update stage in floating status card
  const stage = getCurrentStage(state.currentDay);
  if (UI.stageValue) {
    UI.stageValue.textContent = `${stage.name}`;
  }

  // Update Lottie animation
  if (state.animation) {
    const frame = progressToFrame(state.progress);
    state.animation.goToAndStop(frame, true);
  }
}

// Get color for stage
function getStageColor(stageName) {
  const colors = {
    'Seed': '#8b7355',
    'Germination': '#9acd32',
    'Seedling': '#7cb342',
    'Vegetative': '#43a047',
    'Flowering': '#ffd54f',
    'Fruiting': '#ff9800',
    'Ripening': '#f44336',
  };
  return colors[stageName] || '#666';
}

// Map progress to Lottie frame
function progressToFrame(p01) {
  const f = lerp(state.frameMin, state.frameMax, clamp01(p01));
  return Math.round(f);
}

// Update health
function setHealth(h01) {
  state.health = clamp01(h01);
  const pct = Math.round(state.health * 100);

  // Update health bar text
  UI.healthBarText.textContent = `${pct}%`;

  // Update health bar width
  UI.healthBarFill.style.width = `${pct}%`;

  // Update health bar color based on health level
  let healthColor;
  if (state.health >= 0.8) {
    // Green: Thriving (80-100%)
    healthColor = 'linear-gradient(90deg, #6ae4a7, #43a047)';
  } else if (state.health >= 0.55) {
    // Blue: Healthy (55-80%)
    healthColor = 'linear-gradient(90deg, #7bb7ff, #5a9fd4)';
  } else if (state.health >= 0.35) {
    // Yellow: Stressed (35-55%)
    healthColor = 'linear-gradient(90deg, #ffd966, #ff9f66)';
  } else {
    // Red: Critical (<35%)
    healthColor = 'linear-gradient(90deg, #ff6b6b, #ee5a6f)';
  }
  UI.healthBarFill.style.background = healthColor;

  // Update floating status card
  const status = statusForHealth(state.health);
  if (UI.statusEmoji) {
    UI.statusEmoji.textContent = status.emoji;
  }
  if (UI.healthValue) {
    UI.healthValue.textContent = `${pct}%`;
  }

  applyHealthVisuals();
}

// Apply visual stress effects
function applyHealthVisuals() {
  const h = clamp01(state.health);
  const target = state.svgEl || UI.lottieHost;

  // Dynamic scenario visual recovery: Remove scenario CSS classes when health improves
  // This allows the plant to transition from scenario-specific (sick) appearance
  // to dynamic health-based (recovering) appearance
  const plantContainer = document.querySelector('.lottie-container');
  if (plantContainer && state.activeScenario) {
    // Health threshold for visual recovery (70%)
    // Below this: Keep scenario visuals (sick appearance)
    // Above this: Remove scenario visuals (show health-based recovery)
    const recoveryThreshold = 0.70;

    if (h >= recoveryThreshold) {
      // Health is good - remove scenario-specific visual classes
      plantContainer.classList.remove(
        'scenario-nitrogen-deficiency',
        'scenario-overwatering',
        'scenario-acidic',
        'scenario-alkaline',
        'scenario-small-space'
      );
    }
    // If health is below threshold, scenario classes remain active
  }

  const saturate = lerp(0.2, 1, h);
  const brightness = lerp(0.6, 1, h);
  const grayscale = (1 - h) * 0.7;

  target.style.filter = `saturate(${saturate}) brightness(${brightness}) grayscale(${grayscale})`;

  // Slight wilt - only rotation needed now
  const wilt = (1 - h) * 2;
  const container = UI.lottieHost;
  // Restore centering + Zoom + Pan
  // Note: translateX(-50%) is the base centering. Pan is an offset from that center.
  container.style.transform = `translateX(-50%) translate(${state.pan.x}px, ${state.pan.y}px) rotate(${wilt}deg) scale(${state.zoom})`;
  container.style.transformOrigin = 'center bottom';
}

// Update input labels
function updateInputLabels() {
  UI.waterLabel.textContent = `${Math.round(Number(UI.water.value))}%`;
  UI.lightLabel.textContent = `${Math.round(Number(UI.light.value))}%`;
  UI.tempLabel.textContent = `${Math.round(Number(UI.temp.value))}°C`;
  UI.phLabel.textContent = `${Number(UI.soilPh.value).toFixed(1)}`;
  UI.nitrogenLabel.textContent = `${Math.round(Number(UI.nitrogen.value))}%`;
  UI.phosphorusLabel.textContent = `${Math.round(Number(UI.phosphorus.value))}%`;
  UI.potassiumLabel.textContent = `${Math.round(Number(UI.potassium.value))}%`;
  UI.speedLabel.textContent = `${Number(UI.speed.value).toFixed(2)}×`;
}

// Update plant appearance (Generic handler for future expansion)
function updatePlantAppearance() {
  // Currently, most visuals are handled by Lottie progress and CSS filters.
  // This function mimics the one requested to be called on scenario start.
  // We can use it to force a frame update or apply specific scenario visuals.

  // For now, just re-apply health visuals as a baseline.
  applyHealthVisuals();

  if (state.animation) {
    // Force Lottie to correct frame if needed
    const frame = progressToFrame(state.progress);
    // Only jump if not playing to avoid jitter
    if (!state.running) {
      state.animation.goToAndStop(frame, true);
    }
  }
}

// Update KPIs
function updateKpis(dyn) {
  state.lastGrowthPerSec = dyn.growthPerSec;
  state.lastHealthPerSec = dyn.healthPerSec;

  const daysPerSec = dyn.growthPerSec * 110;
  UI.growthRate.textContent = `${daysPerSec.toFixed(2)} days/s (env ${(dyn.envScore * 100).toFixed(0)}%)`;

  const sign = dyn.healthPerSec >= 0 ? '+' : '';
  UI.healthDelta.textContent = `${sign}${(dyn.healthPerSec * 100).toFixed(2)}%/s`;
  UI.healthDelta.style.color = dyn.healthPerSec >= 0 ? '#6ae4a7' : '#ff6b6b';
}

// Main simulation tick
function tick(ts) {
  if (!state.lastTs) state.lastTs = ts;
  const dt = Math.min(0.05, (ts - state.lastTs) / 1000);
  state.lastTs = ts;

  const inputs = readInputs();
  const dyn = computeDynamics(inputs);
  updateInputLabels();
  updateKpis(dyn);

  // Check scenario progress if active
  checkScenarioProgress();

  if (state.running) {
    // Update pH gradually based on amendments
    const dtDays = dt * (state.progress * 110); // Convert dt to days
    updatePH(dtDays);

    // Health affects growth
    const healthFactor = Math.pow(clamp01(state.health), 2.2);

    const newProgress = Math.min(1, state.progress + (dyn.growthPerSec * healthFactor * dt));
    const newHealth = state.health + (dyn.healthPerSec * dt);

    setProgress(newProgress);
    setHealth(newHealth);

    // Auto-stop at harvest
    if (state.progress >= 1) {
      state.running = false;
      UI.btnPlayPause.textContent = 'Play';
    }
  }

  // Improved 20% health auto-reset with scenario support
  if (state.health <= 0.20 && state.running) {
    state.running = false;
    UI.btnPlayPause.textContent = '▶️';

    // Show loss message if in scenario
    if (state.activeScenario) {
      showToast('❌ Plant health critical! Scenario failed. Resetting to try again...', 'error');
      console.warn('⚠️ Health critical (20%) during scenario - Resetting to scenario defaults...');

      // Reset to scenario starting conditions (not full reset)
      setControlsToScenarioDefaults(state.activeScenario);
      setHealth(state.activeScenario.startingHealth || 0.55);
      setProgress(0);

      // Keep scenario active for retry - don't clear state.activeScenario
      // User can press play again to retry the scenario
    } else {
      console.warn('⚠️ Health critical (20%) - Auto-resetting...');
      reset();
    }

    return; // Exit tick to prevent further updates
  }

  requestAnimationFrame(tick);
}

// Check for achievement milestones
function checkMilestones() {
  const day = state.currentDay;

  // Simple milestone checks
  if (day >= 5 && !state.achievements.includes('germination')) {
    unlockAchievement('germination', 'First Sprout! 🌱', 50);
  }
  if (day >= 55 && !state.achievements.includes('flowering')) {
    unlockAchievement('flowering', 'First Flowers! 🌼', 100);
  }
  if (day >= 75 && !state.achievements.includes('fruiting')) {
    unlockAchievement('fruiting', 'First Fruit! 🍅', 150);
  }
}

// Unlock achievement
function unlockAchievement(id, message, xp) {
  if (state.achievements.includes(id)) return;

  state.achievements.push(id);
  addXP(xp);
  showToast(message, 'achievement');
}

// Add XP and check for level up
function addXP(amount) {
  state.xp += amount;

  const xpReqs = CONFIG.GAMIFICATION.XP_REQUIREMENTS;
  while (state.level < xpReqs.length - 1 && state.xp >= xpReqs[state.level + 1]) {
    state.level++;
    showLevelUp();
  }

  updateXPDisplay();
}

// Show level up notification
function showLevelUp() {
  const levelName = CONFIG.GAMIFICATION.FARMER_LEVELS[state.level];
  showToast(`Level Up! You're now a ${levelName}! 🎉`, 'levelup');
}

// Show toast notification
function showToast(message, type = 'info') {
  console.log(`🎉 ${message}`);
  // In full implementation, this would show an animated toast
}

// Update XP bar
function updateXPDisplay() {
  if (!UI.xpBar || !UI.levelBadge) return;

  const currentLevelXP = CONFIG.GAMIFICATION.XP_REQUIREMENTS[state.level];
  const nextLevelXP = CONFIG.GAMIFICATION.XP_REQUIREMENTS[state.level + 1] || currentLevelXP;
  const progress = (state.xp - currentLevelXP) / (nextLevelXP - currentLevelXP);

  UI.xpBar.style.width = `${progress * 100}%`;
  UI.levelBadge.textContent = `Level ${state.level + 1}`;
}

// Show harvest celebration
function showHarvestCelebration() {
  state.harvestCount++;
  const yield = calculateYield();

  showToast(`🎊 HARVEST COMPLETE! You grew ${yield} tomatoes! 🍅`, 'harvest');
  addXP(500);

  unlockAchievement('first_harvest', 'First Harvest! 🏆', 500);
}

// Calculate yield
function calculateYield() {
  const baseYield = 40;
  const healthMultiplier = state.health;
  const varietyMultiplier = state.variety ? state.variety.yieldMultiplier : 1;

  return Math.round(baseYield * healthMultiplier * varietyMultiplier);
}

// Reset simulation
// 6. Smart Reset Logic
// Issue #6: Scenario-aware reset function
function reset() {
  // Scenario-aware reset: Restore initial day/progress if in a scenario
  if (state.activeScenario && state.activeScenario.initialConditions) {
    const init = state.activeScenario.initialConditions;
    state.currentDay = init.day || 0;
    state.progress = (init.day || 0) / 110;
    state.health = (init.health || 100) / 100;
    console.log(`🔄 Scenario Reset: Day ${init.day}, Health ${init.health}%`);
  } else {
    // No scenario: Reset to day 0
    state.currentDay = 0;
    state.progress = 0;
    state.health = 1.0;
  }

  state.growth = 0;
  state.wilt = 0;
  state.pestLevel = 0;
  state.nutrientLevel = 1.0;

  // Stop any active animation
  if (state.animation) {
    state.animation.stop();
  }

  // Smart Reset: Logic for Default vs Scenario
  if (state.activeScenario) {
    console.log(`🔄 Reset: Restoring scenario defaults for "${state.activeScenario.title}"`);
    setControlsToScenarioDefaults(state.activeScenario);
  } else {
    console.log('🔄 Reset: Restoring optimal defaults (no active scenario)');
    // No scenario -> Optimal
    setControlsToOptimal();
  }

  // Update visuals and UI
  updateInputLabels();
  updatePlantAppearance();
  applyHealthVisuals();

  // Explicitly update progress and health UI (use scenario-aware values)
  setProgress(state.progress);
  setHealth(state.health);
  showRandomQuote();

  // Clear logs
  state.amendmentsLog = [];
  const logContainer = document.getElementById('amendmentLog');
  if (logContainer) logContainer.innerHTML = '';
}

function setControlsToOptimal() {
  UI.water.value = 60;
  UI.light.value = 75;
  UI.temp.value = 25;
  UI.soilPh.value = 6.5;
  UI.nitrogen.value = 50;
  UI.phosphorus.value = 50;
  UI.potassium.value = 50;
  UI.containerSize.value = 'MEDIUM'; // Balanced choice
}

function setControlsToScenarioDefaults(scenario) {
  if (!scenario || !scenario.initialConditions) {
    setControlsToOptimal(); // Fallback
    return;
  }

  const init = scenario.initialConditions;

  // Map initial conditions to UI inputs
  if (init.water !== undefined) UI.water.value = init.water;
  if (init.light !== undefined) UI.light.value = init.light;
  if (init.temp !== undefined) UI.temp.value = init.temp;
  if (init.nitrogen !== undefined) UI.nitrogen.value = init.nitrogen;
  if (init.phosphorus !== undefined) UI.phosphorus.value = init.phosphorus;
  if (init.potassium !== undefined) UI.potassium.value = init.potassium;

  // Handle pH special cases
  if (init.soilPh === "random_acidic") {
    state.phActual = 4.5 + Math.random() * 1.0; // 4.5 - 5.5
  } else if (init.soilPh === "random_alkaline") {
    state.phActual = 7.5 + Math.random() * 1.0; // 7.5 - 8.5
  } else if (init.soilPh !== undefined) {
    state.phActual = init.soilPh;
  } else {
    state.phActual = 6.5;
  }
  UI.soilPh.value = state.phActual.toFixed(1);

  // Handle Container Size
  if (init.containerSize) UI.containerSize.value = getContainerSizeValue(init.containerSize);
}

function getContainerSizeValue(sizeCode) {
  // Map JSON codes to Select values if needed, or assume they match
  // JSON: LARGE_POT, MEDIUM_POT, RAISED_BED
  // UI: SMALL, MEDIUM, LARGE
  if (sizeCode.includes('SMALL')) return 'SMALL';
  if (sizeCode.includes('LARGE')) return 'LARGE';
  if (sizeCode.includes('RAISED')) return 'LARGE';
  return 'MEDIUM';
}



// Show random motivational quote
function showRandomQuote() {
  if (!MOTIVATIONAL.quotes || !UI.motivationalQuote) return;

  const quote = MOTIVATIONAL.quotes[Math.floor(Math.random() * MOTIVATIONAL.quotes.length)];
  UI.motivationalQuote.textContent = quote;
}

// Initialize plant with variety and personality
function initializePlant() {
  // Select default variety (Cherry)
  state.variety = VARIETIES[0];

  // Assign random personality
  state.personality = PERSONALITIES[Math.floor(Math.random() * PERSONALITIES.length)];

  // Default name
  state.plantName = state.personality.name.split(' ')[1] || 'Tomato';

  console.log(`🌱 Plant initialized: ${state.plantName} (${state.variety.name})`);
  console.log(`✨ Personality: ${state.personality.name} ${state.personality.icon}`);
}

// Load Lottie animation
async function loadLottie() {
  try {
    const res = await fetch('./tomato-plant.json');
    if (!res.ok) throw new Error(`Failed to load: ${res.status}`);
    const data = await res.json();

    state.animation = lottie.loadAnimation({
      container: UI.lottieHost,
      renderer: 'svg',
      loop: false,
      autoplay: false,
      animationData: data,
      rendererSettings: {
        preserveAspectRatio: 'xMidYMid meet',
      },
    });

    await new Promise((resolve) => {
      state.animation.addEventListener('DOMLoaded', resolve, { once: true });
    });

    state.totalFrames = Math.max(1, Math.floor(state.animation.getDuration(true)));
    state.frameMin = Math.floor(state.totalFrames * 0.08);
    state.frameMax = state.totalFrames - 1;
    state.svgEl = UI.lottieHost.querySelector('svg');

    state.animation.goToAndStop(state.frameMin, true);

    console.log('✅ Lottie animation loaded');
  } catch (err) {
    console.error('❌ Lottie load error:', err);
    UI.lottieHost.innerHTML = `<div style="padding:20px;color:#ff6b6b;">Failed to load animation. Check console for details.</div>`;
  }
}

// Bind UI events
function bindUI() {
  UI.btnPlayPause.addEventListener('click', () => {
    state.running = !state.running;
    UI.btnPlayPause.textContent = state.running ? 'Pause' : 'Play';
  });

  UI.btnReset.addEventListener('click', reset);

  UI.timeScrub.addEventListener('input', (e) => {
    const pct = Number(e.target.value);
    state.running = false;
    UI.btnPlayPause.textContent = 'Play';
    setProgress(pct / 100);
  });

  for (const r of [UI.water, UI.light, UI.temp, UI.speed, UI.soilPh, UI.nitrogen, UI.phosphorus, UI.potassium]) {
    r.addEventListener('input', () => {
      updateInputLabels();
    });
  }

  // Issue #7: Scenario Header Dropdown (Immediate Visual Update + Open Sheet)
  if (UI.scenarioSelectHeader) {
    UI.scenarioSelectHeader.addEventListener('change', () => {
      const scenarioId = UI.scenarioSelectHeader.value;
      if (scenarioId) {
        // Sync bottom dropdown
        if (UI.scenarioSelect) UI.scenarioSelect.value = scenarioId;

        // Visual Update - Issue #7: Update plant appearance immediately
        const scenario = state.scenarios.find(s => s.id === scenarioId);
        if (scenario) {
          setControlsToScenarioDefaults(scenario);
          updateInputLabels();

          // Apply initial health and progress from scenario
          if (scenario.initialConditions) {
            if (scenario.initialConditions.health !== undefined) {
              state.health = scenario.initialConditions.health / 100;
              setHealth(state.health);
            }
            if (scenario.initialConditions.day !== undefined) {
              state.currentDay = scenario.initialConditions.day;
              state.progress = scenario.initialConditions.day / 110;
              setProgress(state.progress);
            }
          }

          // Force visual update immediately
          updatePlantAppearance();
          applyHealthVisuals();
        }

        // Open Sheet (no scenarioSelector anymore)
        showBottomSheet();
      } else {
        // Cleared
        reset();
      }
    });
  }

  // Scenario event listeners removed - now handled by header dropdown auto-start
  UI.btnCompleteScenario.addEventListener('click', completeScenario);
  UI.btnCancelScenario.addEventListener('click', cancelScenario);

  // Amendment buttons
  document.getElementById('btnApplyLime').addEventListener('click', () => applyAmendment('LIME'));
  document.getElementById('btnApplyDolomite').addEventListener('click', () => applyAmendment('DOLOMITE'));
  document.getElementById('btnApplySulfur').addEventListener('click', () => applyAmendment('SULFUR'));
  document.getElementById('btnApplyIron').addEventListener('click', () => applyAmendment('IRON_CHELATE'));

  // Amendment log toggle
  const logHeader = document.getElementById('amendmentLogHeader');
  if (logHeader) {
    logHeader.addEventListener('click', () => {
      const log = document.getElementById('amendmentLog');
      log.classList.toggle('collapsed');
      logHeader.classList.toggle('expanded');
    });
  }
}

// ========================================
// SCENARIO ENGINE
// ========================================

// Load scenarios from JSON
async function loadScenarios() {
  try {
    const response = await fetch('data/scenarios.json');
    state.scenarios = await response.json();
    populateScenarioSelector();
    console.log(`✅ Loaded ${state.scenarios.length} scenarios`);
  } catch (error) {
    console.error('❌ Error loading scenarios:', error);
  }
}

// Load amendments from JSON
async function loadAmendments() {
  try {
    const response = await fetch('data/amendments.json');
    AMENDMENTS = await response.json();
    console.log(`✅ Loaded ${Object.keys(AMENDMENTS).length} soil amendments`);
  } catch (error) {
    console.error('❌ Error loading amendments:', error);
  }
}

// Load varieties from JSON
async function loadVarieties() {
  try {
    const response = await fetch('data/varieties.json');
    VARIETIES = await response.json();
    console.log(`✅ Loaded ${VARIETIES.length} varieties`);
  } catch (error) {
    console.error('❌ Error loading varieties:', error);
    // Fallback
    VARIETIES = [{ id: 'cherry', name: 'Cherry (Fallback)', totalDays: 60, yieldMultiplier: 1 }];
  }
}

// Load personalities from JSON
async function loadPersonalities() {
  try {
    const response = await fetch('data/personalities.json');
    PERSONALITIES = await response.json();
    console.log(`✅ Loaded ${PERSONALITIES.length} personalities`);
  } catch (error) {
    console.error('❌ Error loading personalities:', error);
    // Fallback
    PERSONALITIES = [{ id: 'default', name: 'Default', icon: '🍅' }];
  }
}

// Load motivational content
async function loadMotivational() {
  try {
    const response = await fetch('data/motivational-content.json');
    MOTIVATIONAL = await response.json();
    console.log(`✅ Loaded ${MOTIVATIONAL.quotes ? MOTIVATIONAL.quotes.length : 0} quotes`);
  } catch (error) {
    console.error('❌ Error loading motivational content:', error);
    MOTIVATIONAL = { quotes: ["Grow where you are planted!"] };
  }
}

// Lock/unlock controls based on scenario specifications
function lockScenarioControls(scenario) {
  const locked = scenario.lockedControls || {};

  // Lock/unlock sliders with visual feedback
  UI.water.disabled = locked.water || false;
  UI.water.style.opacity = locked.water ? '0.5' : '1';
  UI.water.style.cursor = locked.water ? 'not-allowed' : 'pointer';
  UI.water.style.pointerEvents = locked.water ? 'none' : 'auto';

  UI.light.disabled = locked.light || false;
  UI.light.style.opacity = locked.light ? '0.5' : '1';
  UI.light.style.cursor = locked.light ? 'not-allowed' : 'pointer';
  UI.light.style.pointerEvents = locked.light ? 'none' : 'auto';

  UI.temp.disabled = locked.temp || false;
  UI.temp.style.opacity = locked.temp ? '0.5' : '1';
  UI.temp.style.cursor = locked.temp ? 'not-allowed' : 'pointer';
  UI.temp.style.pointerEvents = locked.temp ? 'none' : 'auto';

  UI.nitrogen.disabled = locked.nitrogen || false;
  UI.nitrogen.style.opacity = locked.nitrogen ? '0.5' : '1';
  UI.nitrogen.style.cursor = locked.nitrogen ? 'not-allowed' : 'pointer';
  UI.nitrogen.style.pointerEvents = locked.nitrogen ? 'none' : 'auto';

  UI.phosphorus.disabled = locked.phosphorus || false;
  UI.phosphorus.style.opacity = locked.phosphorus ? '0.5' : '1';
  UI.phosphorus.style.cursor = locked.phosphorus ? 'not-allowed' : 'pointer';
  UI.phosphorus.style.pointerEvents = locked.phosphorus ? 'none' : 'auto';

  UI.potassium.disabled = locked.potassium || false;
  UI.potassium.style.opacity = locked.potassium ? '0.5' : '1';
  UI.potassium.style.cursor = locked.potassium ? 'not-allowed' : 'pointer';
  UI.potassium.style.pointerEvents = locked.potassium ? 'none' : 'auto';

  UI.containerSize.disabled = locked.containerSize || false;
  UI.containerSize.style.opacity = locked.containerSize ? '0.5' : '1';
  UI.containerSize.style.cursor = locked.containerSize ? 'not-allowed' : 'pointer';
  UI.containerSize.style.pointerEvents = locked.containerSize ? 'none' : 'auto';

  // Lock/unlock amendment buttons
  const lockedAmendments = scenario.lockedAmendments || {};
  const btnLime = document.getElementById('btnApplyLime');
  const btnDolomite = document.getElementById('btnApplyDolomite');
  const btnSulfur = document.getElementById('btnApplySulfur');
  const btnIron = document.getElementById('btnApplyIron');

  if (btnLime) {
    btnLime.disabled = lockedAmendments.LIME || false;
    btnLime.style.opacity = lockedAmendments.LIME ? '0.4' : '1';
    btnLime.style.cursor = lockedAmendments.LIME ? 'not-allowed' : 'pointer';
  }
  if (btnDolomite) {
    btnDolomite.disabled = lockedAmendments.DOLOMITE || false;
    btnDolomite.style.opacity = lockedAmendments.DOLOMITE ? '0.4' : '1';
    btnDolomite.style.cursor = lockedAmendments.DOLOMITE ? 'not-allowed' : 'pointer';
  }
  if (btnSulfur) {
    btnSulfur.disabled = lockedAmendments.SULFUR || false;
    btnSulfur.style.opacity = lockedAmendments.SULFUR ? '0.4' : '1';
    btnSulfur.style.cursor = lockedAmendments.SULFUR ? 'not-allowed' : 'pointer';
  }
  if (btnIron) {
    btnIron.disabled = lockedAmendments.IRON_CHELATE || false;
    btnIron.style.opacity = lockedAmendments.IRON_CHELATE ? '0.4' : '1';
    btnIron.style.cursor = lockedAmendments.IRON_CHELATE ? 'not-allowed' : 'pointer';
  }

  console.log(`🔒 Controls locked:`, locked);
  console.log(`🔒 Amendments locked:`, lockedAmendments);
}

// Unlock all controls (for when no scenario is active)
function unlockAllControls() {
  const controls = [UI.water, UI.light, UI.temp, UI.nitrogen, UI.phosphorus, UI.potassium, UI.soilPh, UI.containerSize];
  controls.forEach(control => {
    if (control) {
      control.disabled = false;
      control.style.opacity = '1';
      control.style.cursor = 'pointer';
      control.style.pointerEvents = 'auto';
    }
  });

  const buttons = ['btnApplyLime', 'btnApplyDolomite', 'btnApplySulfur', 'btnApplyIron'];
  buttons.forEach(btnId => {
    const btn = document.getElementById(btnId);
    if (btn) {
      btn.disabled = false;
      btn.style.opacity = '1';
      btn.style.cursor = 'pointer';
      btn.style.pointerEvents = 'auto';
    }
  });

  console.log('🔓 All controls unlocked');
}

// Populate scenario dropdown
function populateScenarioSelector() {
  state.scenarios.forEach(scenario => {
    // Populate header dropdown
    if (UI.scenarioSelectHeader) {
      const optionHeader = document.createElement('option');
      optionHeader.value = scenario.id;
      optionHeader.textContent = `${scenario.title}`;
      UI.scenarioSelectHeader.appendChild(optionHeader);
    }

    // Populate bottom sheet dropdown
    if (UI.scenarioSelect) {
      const option = document.createElement('option');
      option.value = scenario.id;
      option.textContent = `${scenario.title} (${scenario.difficulty})`;
      UI.scenarioSelect.appendChild(option);
    }
  });

  // Wire up header dropdown to start scenario immediately
  if (UI.scenarioSelectHeader) {
    UI.scenarioSelectHeader.addEventListener('change', (e) => {
      if (e.target.value) {
        // Sync with bottom sheet dropdown
        if (UI.scenarioSelect) {
          UI.scenarioSelect.value = e.target.value;
        }
        startScenario();
      }
    });
  }
}

// Start a scenario
function startScenario(scenario) {
  if (!scenario) return;

  state.activeScenario = scenario;
  state.scenarioStartTime = Date.now();
  state.scenarioProgress = 0;

  // Apply initial conditions
  const init = scenario.initialConditions;
  UI.nitrogen.value = init.nitrogen;
  UI.phosphorus.value = init.phosphorus;
  UI.potassium.value = init.potassium;

  // Handle pH randomization and locking
  let initialPh = init.soilPh;
  if (init.soilPh === 'random_acidic') {
    initialPh = (Math.random() * (5.5 - 4.5) + 4.5).toFixed(1);
  } else if (init.soilPh === 'random_alkaline') {
    initialPh = (Math.random() * (8.5 - 7.5) + 7.5).toFixed(1);
  }

  UI.soilPh.value = initialPh;
  state.phActual = Number(initialPh);
  state.phTarget = Number(initialPh);

  // Lock pH slider if scenario requires it
  if (scenario.phLocked) {
    state.phLocked = true;
    UI.soilPh.disabled = true;
    document.getElementById('amendmentsPanel').style.display = 'block';
    document.getElementById('amendmentLog').innerHTML = '';
    state.activeAmendments = [];
    logAmendment(`🧪 Scenario started with pH ${initialPh}`, 'info');
  } else {
    state.phLocked = false;
    UI.soilPh.disabled = false;
    document.getElementById('amendmentsPanel').style.display = 'none';
  }

  // Lock/unlock controls based on scenario specifications
  lockScenarioControls(scenario);

  // Phase 2: Apply scenario-specific visual filter
  const plantContainer = document.querySelector('.lottie-container');
  if (plantContainer) {
    // Remove all scenario classes first
    plantContainer.classList.remove(
      'scenario-nitrogen-deficiency',
      'scenario-overwatering',
      'scenario-acidic',
      'scenario-alkaline',
      'scenario-small-space'
    );

    // Apply appropriate class based on scenario ID
    if (scenario.id.includes('001') || scenario.title.toLowerCase().includes('nitrogen')) {
      plantContainer.classList.add('scenario-nitrogen-deficiency');
      console.log('🎨 Applied yellowish visual filter for Nitrogen Deficiency');
    } else if (scenario.id.includes('002') || scenario.title.toLowerCase().includes('overwater')) {
      plantContainer.classList.add('scenario-overwatering');
      console.log('🎨 Applied sick/wilted visual filter for Overwatering');
    } else if (scenario.id.includes('acidic')) {
      plantContainer.classList.add('scenario-acidic');
      console.log('🎨 Applied pale/stressed visual filter for Acidic Soil');
    } else if (scenario.id.includes('alkaline')) {
      plantContainer.classList.add('scenario-alkaline');
      console.log('🎨 Applied yellowing visual filter for Alkaline Soil');
    } else if (scenario.id.includes('004')) {
      plantContainer.classList.add('scenario-small-space');
      console.log('🎨 Applied stunted visual filter for Small Space');
    }
  }


  // Immediate visual update
  updatePlantAppearance();
  applyHealthVisuals();

  // Set initial values from scenario
  UI.water.value = init.water;
  UI.light.value = init.light;
  UI.temp.value = init.temp;
  UI.containerSize.value = init.containerSize;

  state.health = init.health / 100;
  state.currentDay = init.day;
  state.progress = init.day / 110;

  updateInputLabels();

  // Show bottom sheet with scenario details
  showBottomSheet();
  const scenarioDetails = document.getElementById('scenarioDetails');
  if (scenarioDetails) {
    scenarioDetails.style.display = 'block';
  }

  // Populate bottom sheet content
  UI.scenarioTitleCompact.textContent = scenario.title;
  UI.scenarioCurrentObjective.textContent = scenario.objectives[0] || 'Loading...';
  UI.scenarioTitle.textContent = scenario.title;
  UI.scenarioDescription.textContent = scenario.description;

  UI.scenarioObjectives.innerHTML = '';
  scenario.objectives.forEach(obj => {
    const li = document.createElement('li');
    li.textContent = obj;
    UI.scenarioObjectives.appendChild(li);
  });

  // Populate hints
  const hintsContainer = document.getElementById('scenarioHints');
  if (hintsContainer && scenario.hints) {
    hintsContainer.innerHTML = '';
    scenario.hints.forEach(hint => {
      const li = document.createElement('li');
      li.textContent = hint;
      hintsContainer.appendChild(li);
    });
  }

  // Populate educational content
  const educationalContainer = document.getElementById('scenarioEducational');
  if (educationalContainer && scenario.educationalContent) {
    const topic = scenario.educationalContent.topic || '';
    const explanation = scenario.educationalContent.explanation || '';
    educationalContainer.innerHTML = `<strong>${topic}:</strong> ${explanation}`;
  }

  // Populate success criteria
  const successContainer = document.getElementById('scenarioSuccessCriteria');
  if (successContainer && scenario.successCriteria) {
    successContainer.innerHTML = '';
    for (const [key, criteria] of Object.entries(scenario.successCriteria)) {
      const li = document.createElement('li');
      let criteriaText = `${key.charAt(0).toUpperCase() + key.slice(1)}: `;
      if (criteria.min !== undefined && criteria.max !== undefined) {
        criteriaText += `${criteria.min}-${criteria.max}`;
      } else if (criteria.min !== undefined) {
        criteriaText += `≥ ${criteria.min}`;
      } else if (criteria.max !== undefined) {
        criteriaText += `≤ ${criteria.max}`;
      }
      li.textContent = criteriaText;
      successContainer.appendChild(li);
    }
  }

  UI.scenarioProgressFill.style.width = '0%';
  UI.scenarioProgressFillMini.style.width = '0%';
  UI.scenarioStatus.textContent = 'Adjust the controls to meet the objectives!';
  UI.btnCompleteScenario.disabled = true;

  console.log(`🎯 Started scenario: ${scenario.title}`);
}

// Check scenario progress
function checkScenarioProgress() {
  if (!state.activeScenario) return;

  const scenario = state.activeScenario;
  const criteria = scenario.successCriteria;

  // Check all criteria
  let metCount = 0;
  let totalCount = Object.keys(criteria).length;

  for (const [param, range] of Object.entries(criteria)) {
    let currentValue;

    // Get current value based on parameter name
    switch (param) {
      case 'nitrogen':
        currentValue = Number(UI.nitrogen.value);
        break;
      case 'phosphorus':
        currentValue = Number(UI.phosphorus.value);
        break;
      case 'potassium':
        currentValue = Number(UI.potassium.value);
        break;
      case 'water':
        currentValue = Number(UI.water.value);
        break;
      case 'light':
        currentValue = Number(UI.light.value);
        break;
      case 'soilPh':
        currentValue = state.phActual;
        break;
      case 'health':
        currentValue = state.health * 100;
        break;
      default:
        continue;
    }

    // Check if value meets criteria
    const meetsMin = !range.min || currentValue >= range.min;
    const meetsMax = !range.max || currentValue <= range.max;

    if (meetsMin && meetsMax) {
      metCount++;
    }
  }

  // Calculate progress percentage
  const progressPct = (metCount / totalCount) * 100;
  state.scenarioProgress = progressPct;

  // Update progress bars
  UI.scenarioProgressFill.style.width = `${progressPct}%`;
  UI.scenarioProgressFillMini.style.width = `${progressPct}%`;

  // Update progress percentage text in collapsed view
  const progressPercentEl = document.getElementById('scenarioProgressPercent');
  if (progressPercentEl) {
    progressPercentEl.textContent = `${Math.round(progressPct)}%`;
  }

  // Update status
  if (progressPct >= 100) {
    UI.scenarioStatus.textContent = '✅ All objectives met! Click Complete to finish.';
    UI.btnCompleteScenario.disabled = false;
  } else {
    UI.scenarioStatus.textContent = `${metCount}/${totalCount} objectives met (${Math.round(progressPct)}%)`;
    UI.btnCompleteScenario.disabled = true;
  }
}


// Complete scenario
function completeScenario() {
  if (!state.activeScenario || state.scenarioProgress < 100) return;

  const scenario = state.activeScenario;
  console.log(`✅ Completed scenario: ${scenario.title}`);

  // Show success message
  alert(`🎉 Scenario Complete!\n\n${scenario.title}\n\nYou've successfully met all objectives!`);

  // Remove scenario-specific visual effects from plant
  const plantContainer = document.querySelector('.lottie-container');
  if (plantContainer) {
    plantContainer.classList.remove(
      'scenario-nitrogen-deficiency',
      'scenario-overwatering',
      'scenario-acidic',
      'scenario-alkaline',
      'scenario-small-space'
    );
  }

  // Reset scenario state
  state.activeScenario = null;
  state.scenarioProgress = 0;
  state.scenarioStartTime = null;
  state.phLocked = false;
  state.activeAmendments = [];
  UI.soilPh.disabled = false;

  // Hide amendments panel
  document.getElementById('amendmentsPanel').style.display = 'none';

  // Hide bottom sheet
  hideBottomSheet();

  // Hide scenario details
  const details = document.getElementById('scenarioDetails');
  if (details) details.style.display = 'none';

  // Reset dropdown to default
  if (UI.scenarioSelectHeader) {
    UI.scenarioSelectHeader.value = "";
  }

  // Unlock all controls
  unlockAllControls();

  // Reset plant to normal defaults (optimal conditions and full health)
  setControlsToOptimal();
  setHealth(1.0);
  setProgress(0);
  updateInputLabels();
  updatePlantAppearance();
  applyHealthVisuals();

  console.log('✅ Scenario completed - plant reset to normal defaults');
}

// Issue #5: Cancel scenario with complete state reset
function cancelScenario() {
  if (!state.activeScenario) return;

  const confirmed = confirm('Are you sure you want to cancel this scenario?');
  if (!confirmed) return;

  console.log(`❌ Cancelled scenario: ${state.activeScenario.title}`);

  // Remove scenario-specific visual effects from plant
  if (UI.lottieHost) {
    UI.lottieHost.classList.remove(
      'scenario-nitrogen-deficiency',
      'scenario-overwatering',
      'scenario-acidic',
      'scenario-alkaline',
      'scenario-small-space'
    );
  }

  // Reset scenario state completely
  state.activeScenario = null;
  state.scenarioProgress = 0;
  state.scenarioStartTime = null;
  state.phLocked = false;
  state.activeAmendments = [];
  UI.soilPh.disabled = false;

  // Hide amendments panel
  const amendmentsPanel = document.getElementById('amendmentsPanel');
  if (amendmentsPanel) amendmentsPanel.style.display = 'none';

  // Hide bottom sheet
  hideBottomSheet();

  // Hide scenario details
  const details = document.getElementById('scenarioDetails');
  if (details) details.style.display = 'none';

  // Update status
  if (UI.scenarioStatus) {
    UI.scenarioStatus.textContent = '';
    UI.scenarioStatus.className = 'scenario-status';
  }

  // Issue #5: Reset dropdowns to default (empty value shows placeholder)
  if (UI.scenarioSelectHeader) {
    UI.scenarioSelectHeader.value = "";
  }

  // Unlock all controls
  unlockAllControls();

  // Return env controls to optimal defaults and reset simulation
  reset();

  console.log('✅ Scenario cancelled - plant appearance reset, controls optimal');
}

// ========================================
// BOTTOM SHEET CONTROLS
// ========================================

function showBottomSheet() {
  UI.bottomSheet.classList.add('visible');
  // Clear any inline transform styles to let CSS take over
  const content = document.getElementById('bottomSheetContent');
  if (content) {
    content.style.transform = '';
  }
}

function hideBottomSheet() {
  UI.bottomSheet.classList.remove('visible');
  UI.bottomSheet.classList.remove('expanded');
}

function toggleBottomSheet() {
  const sheet = document.getElementById('scenarioBottomSheet');
  const content = document.getElementById('bottomSheetContent');
  const isExpanded = sheet.classList.contains('expanded');
  const plantContainer = document.querySelector('.lottie-container');

  if (isExpanded) {
    // Collapse - move down to show only header
    sheet.classList.remove('expanded');
    requestAnimationFrame(() => {
      content.style.transform = '';  // Let CSS handle it
    });
    console.log('Collapsing bottom sheet');

    // Plant goes back on top
    if (plantContainer) {
      plantContainer.classList.remove('plant-behind-drawer');
      console.log('🌱 Plant moved to front (z-index: 250)');
    }
  } else {
    // Expand - move up to show full content
    sheet.classList.add('expanded');
    requestAnimationFrame(() => {
      content.style.transform = '';  // Let CSS handle it
    });
    console.log('Expanding bottom sheet');

    // Plant goes behind drawer
    if (plantContainer) {
      plantContainer.classList.add('plant-behind-drawer');
      console.log('🌱 Plant moved behind drawer (z-index: 60)');
    }
  }
}

// Initialize bottom sheet event listeners
function initBottomSheet() {
  // Click header to toggle expand/collapse (but not when clicking cancel button)
  if (UI.bottomSheetHeader) {
    UI.bottomSheetHeader.addEventListener('click', (e) => {
      // Don't toggle if clicking the cancel button
      if (e.target.closest('.btn-cancel-compact')) {
        return;
      }
      toggleBottomSheet();
    });
  }

  // Compact cancel button
  const btnCancelCompact = document.getElementById('btnCancelScenarioCompact');
  if (btnCancelCompact) {
    btnCancelCompact.addEventListener('click', (e) => {
      e.stopPropagation(); // Prevent header click
      cancelScenario();
    });
  }

  // Click backdrop to collapse (not close)
  if (UI.bottomSheetBackdrop) {
    UI.bottomSheetBackdrop.addEventListener('click', () => {
      if (UI.bottomSheet.classList.contains('expanded')) {
        UI.bottomSheet.classList.remove('expanded');
      }
    });
  }

  // Drag handle functionality
  let startY = 0;
  let isDragging = false;

  const handle = document.querySelector('.bottom-sheet-handle');

  if (handle) {
    handle.addEventListener('mousedown', (e) => {
      isDragging = true;
      startY = e.clientY;
    });

    document.addEventListener('mousemove', (e) => {
      if (!isDragging) return;

      const deltaY = e.clientY - startY;

      // Drag down to collapse, drag up to expand
      if (deltaY > 50) {
        UI.bottomSheet.classList.remove('expanded');
        isDragging = false;
      } else if (deltaY < -50) {
        UI.bottomSheet.classList.add('expanded');
        isDragging = false;
      }
    });

    document.addEventListener('mouseup', () => {
      isDragging = false;
    });
  }

  // Issue #9: Left drag handle functionality
  const leftHandle = document.getElementById('bottomSheetDragHandleLeft');
  if (leftHandle) {
    leftHandle.addEventListener('click', (e) => {
      e.stopPropagation(); // Prevent header click
      toggleBottomSheet();
    });
  }
}

// Issue #8: Add scroll-wheel support to all sliders
function addScrollSupportToSliders() {
  // Get all environmental control sliders and speed slider
  const allSliders = [
    UI.water,
    UI.light,
    UI.temp,
    UI.soilPh,
    UI.nitrogen,
    UI.phosphorus,
    UI.potassium,
    UI.speed
  ].filter(slider => slider !== null && slider !== undefined);

  allSliders.forEach(slider => {
    slider.addEventListener('wheel', (e) => {
      e.preventDefault(); // Prevent page scroll

      const step = parseFloat(slider.step) || 1;
      const delta = e.deltaY < 0 ? step : -step;
      const currentValue = parseFloat(slider.value);
      const minValue = parseFloat(slider.min);
      const maxValue = parseFloat(slider.max);

      // Calculate new value and clamp to min/max
      const newValue = Math.min(maxValue, Math.max(minValue, currentValue + delta));
      slider.value = newValue;

      // Trigger input event to update labels
      slider.dispatchEvent(new Event('input'));
    }, { passive: false });
  });

  console.log(`✅ Added scroll-wheel support to ${allSliders.length} sliders`);
}

// ========================================
// SIDE DRAWER CONTROLS
// ========================================

// Open controls drawer
// Open controls drawer
function openControlsDrawer() {
  if (UI.controlsDrawer) {
    UI.controlsDrawer.classList.add('open');
    const canvas = document.querySelector('.sim-canvas');

    // Only shift canvas if NOT mobile (width > 768px)
    if (canvas && window.innerWidth > 768) {
      canvas.classList.add('drawer-open');
    }

    // Mobile: Show backdrop
    if (window.innerWidth <= 768 && UI.drawerBackdrop) {
      UI.drawerBackdrop.classList.add('active');
      // Add click listener to close
      UI.drawerBackdrop.onclick = closeControlsDrawer;
    }

    // Add class to body for desktop layout adjustments
    document.body.classList.add('side-drawer-open');
  }
}

// Close controls drawer
function closeControlsDrawer() {
  if (UI.controlsDrawer) {
    UI.controlsDrawer.classList.remove('open');
    const canvas = document.querySelector('.sim-canvas');
    if (canvas) {
      canvas.classList.remove('drawer-open');
    }

    // Hide backdrop
    if (UI.drawerBackdrop) {
      UI.drawerBackdrop.classList.remove('active');
    }

    // Remove class from body
    document.body.classList.remove('side-drawer-open');
  }
}

// Toggle controls drawer
function toggleControlsDrawer() {
  if (UI.controlsDrawer && UI.controlsDrawer.classList.contains('open')) {
    closeControlsDrawer();
  } else {
    openControlsDrawer();
  }
}

// Initialize drawer controls
function initDrawerControls() {
  // Toggle button
  const btnToggle = document.getElementById('btnToggleControls');
  if (btnToggle) {
    btnToggle.addEventListener('click', toggleControlsDrawer);
  }

  // Close button
  if (UI.btnCloseControls) {
    UI.btnCloseControls.addEventListener('click', closeControlsDrawer);
  }

  // Click backdrop to close (if backdrop exists)
  if (UI.drawerBackdrop) {
    UI.drawerBackdrop.addEventListener('click', closeControlsDrawer);
  }
}

// ========================================
// SOIL AMENDMENT SYSTEM
// ========================================

// Apply soil amendment
function applyAmendment(amendmentType) {
  const amendment = AMENDMENTS[amendmentType];
  if (!amendment) return;

  // Check if already applied max times
  const count = state.activeAmendments.filter(a => a.type === amendmentType).length;
  if (count >= amendment.maxApplications) {
    logAmendment(`⚠️ Cannot apply more ${amendment.name} (max ${amendment.maxApplications})`, 'warning');
    return;
  }

  // Immediately set pH to optimal range (6.0-6.8)
  // This ensures the scenario can be completed by applying the correct amendment
  const optimalPh = 6.4; // Middle of optimal range

  if (amendmentType === 'LIME' || amendmentType === 'DOLOMITE') {
    // Raises pH for acidic soil
    state.phActual = optimalPh;
    UI.soilPh.value = optimalPh.toFixed(1);
    logAmendment(`✓ Applied ${amendment.name} - pH adjusted to ${optimalPh}`, 'success');
  } else if (amendmentType === 'SULFUR' || amendmentType === 'IRON_CHELATE') {
    // Lowers pH for alkaline soil
    state.phActual = optimalPh;
    UI.soilPh.value = optimalPh.toFixed(1);
    logAmendment(`✓ Applied ${amendment.name} - pH adjusted to ${optimalPh}`, 'success');
  }

  // Add to active amendments for tracking
  state.activeAmendments.push({
    type: amendmentType,
    appliedDay: state.currentDay,
    phChangePerDay: amendment.phChangePerDay,
    name: amendment.name
  });
}

// Log amendment activity
function logAmendment(message, type = 'info') {
  const log = document.getElementById('amendmentLog');
  const entry = document.createElement('div');
  entry.className = `amendment-entry ${type}`;
  entry.textContent = `Day ${state.currentDay.toFixed(1)}: ${message}`;
  log.appendChild(entry);
  log.scrollTop = log.scrollHeight;
}

// Update pH gradually based on amendments
function updatePH(dtDays) {
  if (!state.phLocked) {
    // Normal mode - pH follows slider instantly
    state.phActual = Number(UI.soilPh.value);
    return;
  }

  // Scenario mode - pH is now set immediately by amendments
  // No gradual changes needed since applyAmendment() sets pH to optimal (6.4) instantly
  // Just keep pH locked at current value

  // Update slider display (but disabled)
  UI.soilPh.value = state.phActual.toFixed(1);
  UI.phLabel.textContent = state.phActual.toFixed(1);
}

// Main initialization
async function main() {
  console.log('🌱 Enhanced Tomato Growth Simulator - Starting...');

  await loadScenarios();
  await loadAmendments();
  await loadVarieties();
  await loadPersonalities();
  await loadMotivational();

  initializePlant(); // Initialize with random variety/personality

  bindUI();

  // Add event listener to header dropdown to auto-start scenario
  const headerDropdown = document.getElementById('scenarioSelectHeader');
  if (headerDropdown) {
    headerDropdown.addEventListener('change', (e) => {
      const scenarioId = e.target.value;
      if (scenarioId) {
        const scenario = state.scenarios.find(s => s.id === scenarioId);
        if (scenario) {
          console.log(`📋 Auto-starting scenario from header: ${scenario.title}`);
          startScenario(scenario);
        }
      }
    });
  }

  initBottomSheet(); // Initialize bottom sheet event listeners
  initDrawerControls(); // Initialize drawer controls
  addScrollSupportToSliders(); // Issue #8: Add scroll-wheel support to all sliders
  updateInputLabels();

  try {
    await loadLottie();
  } catch (err) {
    console.error('Lottie error:', err);
  }

  // Initialize UI controls
  initDrawerControls();
  initPanZoomControls();
  // initBottomSheet(); // REMOVED DUPLICATE CALL

  reset();
  requestAnimationFrame(tick);

  console.log('✅ Simulator ready!');
}

// ========================================
// PAN & ZOOM CONTROLS
// ========================================

function initPanZoomControls() {
  const container = document.querySelector('.plant-viewer-canvas'); // Target wrapper for broader hit area

  if (!container) return;

  // Wheel zoom
  container.addEventListener('wheel', (e) => {
    e.preventDefault();
    const delta = e.deltaY > 0 ? -0.1 : 0.1;
    updateZoom(delta);
  }, { passive: false });

  // Mouse Drag
  container.addEventListener('mousedown', startDrag);
  window.addEventListener('mousemove', onDrag);
  window.addEventListener('mouseup', endDrag);

  // Touch Drag
  container.addEventListener('touchstart', (e) => {
    if (e.touches.length === 1) startDrag(e.touches[0]);
  }, { passive: false });
  window.addEventListener('touchmove', (e) => {
    if (e.touches.length === 1) onDrag(e.touches[0]);
  }, { passive: false });
  window.addEventListener('touchend', endDrag);
}

function startDrag(e) {
  // Check if click is on bottom drawer - if so, don't start dragging
  const target = e.target || e.srcElement;
  if (target && target.closest('.bottom-sheet')) {
    return; // Don't drag if clicking on drawer
  }

  state.isDragging = true;
  state.dragStart = { x: e.clientX, y: e.clientY };
  state.panStart = { ...state.pan };

  const container = document.querySelector('.plant-viewer-canvas');
  if (container) container.style.cursor = 'grabbing';
}

function onDrag(e) {
  if (!state.isDragging) return;

  const dx = e.clientX - state.dragStart.x;
  const dy = e.clientY - state.dragStart.y;

  state.pan.x = state.panStart.x + dx;
  state.pan.y = state.panStart.y + dy;

  // Apply boundaries (soft limits)
  // Limit pan to keep plant somewhat in view
  const limitX = 600;
  const limitY = 400;
  state.pan.x = Math.max(-limitX, Math.min(limitX, state.pan.x));
  state.pan.y = Math.max(-limitY, Math.min(limitY, state.pan.y));

  applyHealthVisuals();
}

function endDrag() {
  state.isDragging = false;
  const container = document.querySelector('.plant-viewer-canvas');
  if (container) container.style.cursor = 'grab';
}

function updateZoom(change) {
  let newZoom = state.zoom + change;
  // Clamp zoom
  newZoom = Math.max(0.5, Math.min(2.0, newZoom));

  // Snap to 1.0 if close (optional goodness)
  if (Math.abs(newZoom - 1.0) < 0.05) newZoom = 1.0;

  state.zoom = newZoom;
  applyHealthVisuals(); // Re-apply transform with new zoom
}

function updateZoom(change) {
  let newZoom = state.zoom + change;
  // Clamp zoom
  newZoom = Math.max(0.5, Math.min(2.0, newZoom));

  // Snap to 1.0 if close (optional goodness)
  if (Math.abs(newZoom - 1.0) < 0.05) newZoom = 1.0;

  state.zoom = newZoom;
  applyHealthVisuals(); // Re-apply transform with new zoom
}

// Start when DOM is ready
if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', main);
} else {
  main();
}

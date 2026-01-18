// Tomato Growth Simulator (original)
// - Loads the tomato Lottie JSON from ../First Project/Tomato plant.json
// - Simulates growth (0..1) and health (0..1)
// - Water / light / temperature affect growth rate and health change

const el = (id) => document.getElementById(id);

const UI = {
  lottieHost: el('lottie'),

  btnReset: el('btnReset'),
  btnPlayPause: el('btnPlayPause'),

  timeScrub: el('timeScrub'),
  timePct: el('timePct'),

  speed: el('speed'),
  speedLabel: el('speedLabel'),

  water: el('water'),
  waterLabel: el('waterLabel'),

  light: el('light'),
  lightLabel: el('lightLabel'),

  temp: el('temp'),
  tempLabel: el('tempLabel'),

  stageBadge: el('stageBadge'),
  statusBadge: el('statusBadge'),

  healthBar: el('healthBar'),
  healthLabel: el('healthLabel'),

  growthRate: el('growthRate'),
  healthDelta: el('healthDelta'),

  stageList: el('stageList'),
};

// Stages are original (not copied). These are just convenient ranges.
const STAGES = [
  { name: 'Seed', from: 0, to: 8 },
  { name: 'Germination', from: 8, to: 18 },
  { name: 'Sprout', from: 18, to: 32 },
  { name: 'Vegetative', from: 32, to: 55 },
  { name: 'Flowering', from: 55, to: 72 },
  { name: 'Fruiting', from: 72, to: 100 },
];

function clamp01(x) {
  return Math.max(0, Math.min(1, x));
}

function lerp(a, b, t) {
  return a + (b - a) * t;
}

function pct01ToPct100(x01) {
  return Math.round(clamp01(x01) * 100);
}

function gaussianScore(x, optimum, sigma) {
  // Returns 0..1, peak at optimum
  const z = (x - optimum) / sigma;
  return Math.exp(-0.5 * z * z);
}

function plateauScore(x, minOk, maxOk, falloff) {
  // 0..1. 1 inside [minOk,maxOk]. Smoothly decays outside.
  if (x < minOk) return gaussianScore(x, minOk, falloff);
  if (x > maxOk) return gaussianScore(x, maxOk, falloff);
  return 1;
}

function stageForPct(pct) {
  const s = STAGES.find((st) => pct >= st.from && pct < st.to) ?? STAGES[STAGES.length - 1];
  return s;
}

function statusForHealth(h01) {
  if (h01 >= 0.8) return { label: 'Thriving', color: 'var(--accent)' };
  if (h01 >= 0.55) return { label: 'Stable', color: 'var(--accent2)' };
  if (h01 >= 0.35) return { label: 'Stressed', color: 'var(--warn)' };
  return { label: 'Critical', color: 'var(--bad)' };
}

function renderStages() {
  UI.stageList.innerHTML = '';
  for (const s of STAGES) {
    const row = document.createElement('div');
    row.className = 'stage';

    const left = document.createElement('div');
    left.className = 'stage__name';
    left.textContent = s.name;

    const right = document.createElement('div');
    right.className = 'stage__range mono';
    right.textContent = `${s.from}–${s.to}%`;

    row.append(left, right);
    UI.stageList.appendChild(row);
  }
}

renderStages();

// --- Lottie setup ---
let animation = null;
let totalFrames = 0;

// We don't want the soil/background to "grow". Many Lottie files animate
// an initial reveal/bounce for background layers in the first frames.
// So we start the plant growth mapping from a later baseline.
let frameMin = 0;
let frameMax = 0;

// Tweakables (original heuristics)
const VISUAL = {
  // Percentage of the animation timeline to skip at the start
  // (helps avoid the soil popping/scaling when pressing Play)
  baselineSkip: 0.08,

  // When health is low, we prevent the simulation from reaching late fruit frames
  // so it won't show fully healthy fruit on a dying plant.
  fruitHealthGate: 0.45, // below this: cap progress
  fruitProgressCap: 0.68, // about "Flowering" end in our stage model

  // Visual stress filter ranges
  // Applied to the rendered SVG element (not the whole viewer) so the UI/soil box
  // doesn't get washed out.
  minSaturate: 0.18,
  minBrightness: 0.6,
  minContrast: 0.88,

  // Seed-drop: accelerate the early timeline so the seed drop feels quick.
  // We treat the first part as an intro segment and simulate it faster.
  seedDropPctEnd: 0.12,      // end of the "seed drop" portion (0..1 of progress)
  seedDropSpeedMultiplier: 3.25, // how much faster intro runs while playing
};

async function loadLottie() {
  // Fetch the JSON from your existing folder
  const res = await fetch('simulation/Plants/Tomato.json');
  if (!res.ok) throw new Error(`Failed to load Lottie JSON: ${res.status} ${res.statusText}`);
  const data = await res.json();

  animation = lottie.loadAnimation({
    container: UI.lottieHost,
    renderer: 'svg',
    loop: false,
    autoplay: false,
    animationData: data,
    rendererSettings: {
      preserveAspectRatio: 'xMidYMid meet',
      progressiveLoad: true,
    },
  });

  await new Promise((resolve) => {
    animation.addEventListener('DOMLoaded', resolve, { once: true });
  });

  totalFrames = Math.max(1, Math.floor(animation.getDuration(true)));

  // Skip the early timeline where background/soil often scales/pops.
  frameMin = Math.floor((totalFrames - 1) * VISUAL.baselineSkip);
  frameMax = totalFrames - 1;

  // Cache the generated SVG element (lottie-web renders an <svg> into the container)
  state.svgEl = UI.lottieHost.querySelector('svg');

  // Render baseline frame (so pressing Play doesn't "grow" the soil)
  animation.goToAndStop(frameMin, true);
}

// --- Simulation state ---
const state = {
  running: false,

  // progress and health in 0..1
  progress: 0,
  health: 1,

  // cached reference to the generated SVG so we can apply filters only to
  // the artwork (and keep soil/container UI colors stable)
  svgEl: null,

  // last computed outputs for UI
  lastGrowthPerSec: 0,
  lastHealthPerSec: 0,

  // time integration
  lastTs: 0,
};

function readInputs() {
  return {
    speed: Number(UI.speed.value),
    water: Number(UI.water.value) / 100,
    light: Number(UI.light.value) / 100,
    tempC: Number(UI.temp.value),
  };
}

function computeDynamics(inputs) {
  // Scores 0..1 for each factor (original approximations)
  // Water: best around ~0.55, acceptable 0.35..0.75
  const waterScore = plateauScore(inputs.water, 0.35, 0.75, 0.12);

  // Light: best around ~0.7, acceptable 0.45..0.9
  const lightScore = plateauScore(inputs.light, 0.45, 0.9, 0.18);

  // Temp: tomatoes often like ~24C; acceptable ~18..30
  const tempScore = plateauScore(inputs.tempC, 18, 30, 3.5);

  // Growth rate (progress per second)
  // Baseline tuned so full growth takes ~2–4 minutes depending on conditions.
  const baseGrowth = 0.008; // 0.8% per second at perfect conditions
  const envScore = (waterScore * 0.38) + (lightScore * 0.34) + (tempScore * 0.28);
  const growthPerSec = baseGrowth * envScore * inputs.speed;

  // Health change per second
  // Slightly recovers when near optimal; decays when far from optimal.
  const stress = 1 - envScore;
  const recover = (envScore > 0.75) ? 0.018 : 0; // recover up to ~1.8% per sec
  const decay = stress * 0.028; // decay up to ~2.8% per sec
  const healthPerSec = (recover - decay) * inputs.speed;

  return {
    waterScore,
    lightScore,
    tempScore,
    envScore,
    growthPerSec,
    healthPerSec,
  };
}

function progressToFrame(p01) {
  const f = lerp(frameMin, frameMax, clamp01(p01));
  return Math.round(f);
}

function setProgress(p01) {
  state.progress = clamp01(p01);
  const pct = pct01ToPct100(state.progress);
  UI.timeScrub.value = String(pct);
  UI.timePct.textContent = `${pct}%`;

  // Stage
  const stage = stageForPct(pct);
  UI.stageBadge.textContent = `Stage: ${stage.name}`;

  // Lottie frame
  if (animation) {
    const frame = progressToFrame(state.progress);
    animation.goToAndStop(frame, true);
  }
}

function applyHealthVisuals() {
  // Make the plant look stressed/dying when health is poor.
  // Apply filters to the rendered SVG only, so the surrounding viewer/soil container
  // doesn't wash out.
  const h = clamp01(state.health);

  const saturate = lerp(VISUAL.minSaturate, 1, h);
  const brightness = lerp(VISUAL.minBrightness, 1, h);
  const contrast = lerp(VISUAL.minContrast, 1, h);
  const grayscale = (1 - h) * 0.75;

  const target = state.svgEl ?? UI.lottieHost;
  target.style.filter = `saturate(${saturate}) brightness(${brightness}) contrast(${contrast}) grayscale(${grayscale})`;

  // A slight visual "wilt" impression for low health
  const wilt = (1 - h) * 2.5; // degrees
  target.style.transform = `rotate(${wilt}deg)`;
  target.style.transformOrigin = '50% 85%';
}

function setHealth(h01) {
  state.health = clamp01(h01);
  const pct = pct01ToPct100(state.health);
  UI.healthLabel.textContent = `Health: ${pct}%`;
  UI.healthBar.style.transform = `scaleX(${state.health})`;

  const status = statusForHealth(state.health);
  UI.statusBadge.textContent = `Status: ${status.label}`;
  UI.statusBadge.style.borderColor = status.color;
  UI.statusBadge.style.color = status.color;

  applyHealthVisuals();
}

function updateInputLabels() {
  UI.waterLabel.textContent = `${Math.round(Number(UI.water.value))}%`;
  UI.lightLabel.textContent = `${Math.round(Number(UI.light.value))}%`;
  UI.tempLabel.textContent = `${Math.round(Number(UI.temp.value))}°C`;
  UI.speedLabel.textContent = `${Number(UI.speed.value).toFixed(2)}×`;
}

function updateKpis(dyn) {
  state.lastGrowthPerSec = dyn.growthPerSec;
  state.lastHealthPerSec = dyn.healthPerSec;

  UI.growthRate.textContent = `${(dyn.growthPerSec * 100).toFixed(2)}%/s (env ${(dyn.envScore * 100).toFixed(0)}%)`;

  const sign = dyn.healthPerSec >= 0 ? '+' : '';
  UI.healthDelta.textContent = `${sign}${(dyn.healthPerSec * 100).toFixed(2)}%/s`;
  UI.healthDelta.style.color = dyn.healthPerSec >= 0 ? 'var(--accent)' : 'var(--bad)';
}

function tick(ts) {
  if (!state.lastTs) state.lastTs = ts;
  const dt = Math.min(0.05, (ts - state.lastTs) / 1000); // clamp to avoid jumps
  state.lastTs = ts;

  const inputs = readInputs();
  const dyn = computeDynamics(inputs);
  updateInputLabels();
  updateKpis(dyn);

  if (state.running) {
    // If health gets low, growth slows sharply.
    // Below ~35% health the plant almost stops.
    const healthFactor = Math.pow(clamp01(state.health), 2.2);

    // Make the seed-drop intro quick: while we're in the early part of the animation,
    // boost the growth speed regardless of environment a bit.
    const seedBoost = (state.progress < VISUAL.seedDropPctEnd) ? VISUAL.seedDropSpeedMultiplier : 1;

    // Gate fruiting: unhealthy plants should not show healthy fruit.
    // If health is too low, cap how far the animation can progress.
    const progressCap = (state.health < VISUAL.fruitHealthGate)
      ? VISUAL.fruitProgressCap
      : 1;

    // If capped, also reduce growth further near the cap to avoid jitter.
    const nearCap = progressCap < 1 ? clamp01((progressCap - state.progress) / 0.08) : 1;

    const newProgress = Math.min(
      progressCap,
      state.progress + (dyn.growthPerSec * seedBoost * healthFactor * nearCap * dt)
    );

    // Health changes; if plant is mature, buffer a bit; if it's unhealthy and still
    // being pushed with bad conditions, let it decline a bit faster.
    const maturityShield = lerp(1, 0.5, state.progress);
    const sickPenalty = lerp(1, 1.35, clamp01((0.5 - state.health) / 0.5));
    const newHealth = state.health + (dyn.healthPerSec * maturityShield * sickPenalty * dt);

    setProgress(newProgress);
    setHealth(newHealth);

    // Stop automatically when fully grown.
    if (state.progress >= 1) {
      state.running = false;
      UI.btnPlayPause.textContent = 'Play';
    }
  }

  requestAnimationFrame(tick);
}

function reset() {
  state.running = false;
  UI.btnPlayPause.textContent = 'Play';
  state.lastTs = 0;

  // Reset visuals
  if (state.svgEl) {
    state.svgEl.style.filter = '';
    state.svgEl.style.transform = '';
  }
  UI.lottieHost.style.filter = '';
  UI.lottieHost.style.transform = '';

  setProgress(0);
  setHealth(1);
}

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

  for (const r of [UI.water, UI.light, UI.temp, UI.speed]) {
    r.addEventListener('input', () => {
      updateInputLabels();
    });
  }
}

async function main() {
  bindUI();
  updateInputLabels();

  try {
    await loadLottie();
  } catch (err) {
    UI.lottieHost.innerHTML = `<div style="padding:14px;color:#ffb3b3;font-family:ui-monospace,monospace;">Failed to load animation. Ensure the path <strong>../First Project/Tomato plant.json</strong> exists.\n\n${String(err)}</div>`;
  }

  reset();
  requestAnimationFrame(tick);
}

main();

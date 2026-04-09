// Central configuration for tomato growth simulator
// All values based on scientific research

const CONFIG = {
  // Time scaling: 1 real second = X simulated days
  TIME_SCALE: {
    REAL_SECONDS_PER_SIM_DAY: 1, // 1 second = 1 day (adjustable by speed slider)
  },

  // Growth stages with research-based durations (in simulated days)
  GROWTH_STAGES: [
    { name: 'Seed', from: 0, to: 5, description: 'Dormant seed in soil' },
    { name: 'Germination', from: 5, to: 10, description: 'Cotyledon emergence, radicle development' },
    { name: 'Seedling', from: 10, to: 25, description: 'First true leaves, 8-15 cm height' },
    { name: 'Vegetative', from: 25, to: 50, description: 'Rapid stem and leaf growth, 30-60 cm height' },
    { name: 'Flowering', from: 50, to: 75, description: 'Yellow flowers emerging, pollination' },
    { name: 'Fruiting', from: 75, to: 110, description: 'Green fruit development and maturation' },
  ],

  // Environmental optimal ranges (research-based)
  ENVIRONMENT: {
    TEMPERATURE: {
      // All values in Celsius
      MIN: 5,
      MAX: 45,
      OPTIMAL_DAY_MIN: 21,
      OPTIMAL_DAY_MAX: 29,
      OPTIMAL_NIGHT_MIN: 15,
      OPTIMAL_NIGHT_MAX: 20,
      GERMINATION_OPTIMAL: 24,
      GROWTH_STOPS_BELOW: 10,
      POLLEN_STERILITY_ABOVE: 32,
      LYCOPENE_STOPS_ABOVE: 30,
    },

    WATER: {
      // Percentage of field capacity (0-100%)
      MIN: 0,
      MAX: 100,
      OPTIMAL_MIN: 50,
      OPTIMAL_MAX: 75,
      DROUGHT_THRESHOLD: 30,
      WATERLOG_THRESHOLD: 85,
      // Stage-specific sensitivity
      CRITICAL_STAGES: ['Flowering', 'Fruiting'], // Most sensitive to water stress
    },

    LIGHT: {
      // Daily Light Integral (DLI) in mol/m²/d
      MIN_DLI: 0,
      MAX_DLI: 40,
      SEEDLING_OPTIMAL_MIN: 10,
      SEEDLING_OPTIMAL_MAX: 15,
      FRUITING_OPTIMAL_MIN: 20,
      FRUITING_OPTIMAL_MAX: 30,
      MIN_HOURS_FOR_FRUIT: 6,
      OPTIMAL_HOURS: 14,
      // Conversion: inputs.light is 0-1 (slider 0-100 divided by 100), maps to 0-40 DLI
      PERCENT_TO_DLI_FACTOR: 40,
    },

    SOIL_PH: {
      MIN: 4.5,
      MAX: 8.5,
      OPTIMAL_MIN: 6.0,
      OPTIMAL_MAX: 6.8,
      CALCIUM_DEFICIENCY_BELOW: 5.5,
      IRON_DEFICIENCY_ABOVE: 7.5,
    },

    NUTRIENTS: {
      // NPK levels (0-100% representing concentration)
      NITROGEN: {
        OPTIMAL_MIN: 60,
        OPTIMAL_MAX: 80,
        DEFICIENCY_THRESHOLD: 40,
      },
      PHOSPHORUS: {
        OPTIMAL_MIN: 50,
        OPTIMAL_MAX: 70,
        DEFICIENCY_THRESHOLD: 30,
      },
      POTASSIUM: {
        OPTIMAL_MIN: 65,
        OPTIMAL_MAX: 85,
        DEFICIENCY_THRESHOLD: 45,
      },
    },

    CONTAINER: {
      // Container sizes affect root space and water retention
      SMALL_POT: {
        name: 'Small Pot (5L)',
        volume: 5,
        waterRetention: 0.6, // Dries out faster
        rootSpaceMultiplier: 0.7, // Limited root growth
      },
      MEDIUM_POT: {
        name: 'Medium Pot (10L)',
        volume: 10,
        waterRetention: 0.8,
        rootSpaceMultiplier: 0.9,
      },
      LARGE_POT: {
        name: 'Large Pot (20L)',
        volume: 20,
        waterRetention: 1.0,
        rootSpaceMultiplier: 1.0,
      },
      RAISED_BED: {
        name: 'Raised Bed',
        volume: 50,
        waterRetention: 1.2, // Best water retention
        rootSpaceMultiplier: 1.2, // Optimal root growth
      },
    },
  },

  // Growth rate parameters
  GROWTH: {
    BASE_RATE: 0.009, // Base progress per second at perfect conditions (0.9% per second)
    HEALTH_IMPACT_EXPONENT: 2.2, // How much health affects growth
    STRESS_ACCUMULATION_RATE: 0.015, // How quickly stress accumulates
    RECOVERY_RATE: 0.018, // How quickly health recovers in optimal conditions
    DECAY_RATE: 0.028, // How quickly health decays in poor conditions
  },

  // Visual parameters
  VISUAL: {
    LOTTIE_BASELINE_SKIP: 0.08, // Skip first 8% of animation (soil pop-in)
    LOTTIE_END_TRIM: 0.04, // Skip trailing frames where the mature plant disappears
    LOTTIE_MAX_VISUAL_PROGRESS: 0.985, // Cap visual progress so 100% stays on the mature plant
    HEALTH_STRESS_FILTERS: {
      MIN_SATURATE: 0.18,
      MIN_BRIGHTNESS: 0.6,
      MIN_CONTRAST: 0.88,
      MAX_GRAYSCALE: 0.75,
    },
    FRUIT_HEALTH_GATE: 0.45, // Below this health, cap progress to prevent healthy-looking fruit
    FRUIT_PROGRESS_CAP: 0.68,
  },

  // Gamification parameters
  GAMIFICATION: {
    XP_REQUIREMENTS: [0, 100, 250, 500, 1000, 2000], // XP needed for each level
    FARMER_LEVELS: ['Novice', 'Apprentice', 'Gardener', 'Expert', 'Master Farmer'],
    DAILY_CHECKIN_XP: 10,
    OPTIMAL_CONDITIONS_XP_PER_DAY: 5,
    MILESTONE_XP: {
      GERMINATION: 50,
      FIRST_LEAF: 75,
      FLOWERING: 100,
      FIRST_FRUIT: 150,
      HARVEST: 500,
    },
    COINS_PER_HARVEST: 100,
    STREAK_BONUS_MULTIPLIER: 1.1, // 10% bonus per streak day
  },

  // Disease probabilities (per day, 0-1)
  DISEASES: {
    EARLY_BLIGHT: {
      BASE_PROBABILITY: 0.001,
      LIGHT_FACTOR: -0.5, // Lower light increases risk
      HUMIDITY_FACTOR: 0.3, // Higher humidity increases risk
    },
    BLOSSOM_END_ROT: {
      BASE_PROBABILITY: 0.002,
      WATER_IRREGULARITY_FACTOR: 0.4,
      PH_FACTOR: 0.3,
    },
  },

  // Audio settings
  AUDIO: {
    DEFAULT_VOLUME: 0.5,
    AMBIENT_VOLUME: 0.3,
    EFFECTS_VOLUME: 0.6,
    MUSIC_VOLUME: 0.2,
  },

  // Day/night cycle
  DAY_NIGHT: {
    CYCLE_DURATION_SECONDS: 120, // 2 minutes for full day/night cycle
    DAY_START_HOUR: 6,
    DAY_END_HOUR: 20,
    NIGHT_TEMP_REDUCTION: 8, // Degrees C cooler at night
  },
};

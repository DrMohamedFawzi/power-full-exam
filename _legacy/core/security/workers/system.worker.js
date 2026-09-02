/**
 * ============================================================
 *  AEGIS-X EDGE-AI PROCTORING ENGINE — system.worker.js
 *  Isolated Web Worker for OS, Browser & Behavioral Integrity
 *  © 2026 Aegis-X. All rights reserved.
 * ============================================================
 */

self.onmessage = async function (e) {
  const { type, payload } = e.data;

  switch (type) {
    case 'INIT_SYSTEM_WORKER':
      initSystemEngine(payload);
      break;
    case 'PROCESS_KEYSTROKE_BATCH':
      processKeystrokeBatch(payload);
      break;
    case 'EVALUATE_SYSTEM_INTEGRITY':
      evaluateSystemIntegrity(payload);
      break;
    default:
      console.warn('[SystemWorker] Unknown message type:', type);
  }
};

let systemState = {
  initialized: false,
  keystrokeHistory: [],
  pasteThresholdMs: 3.0, // Velocity below 3ms per character indicates paste/bot injection
  minHumanIkiMs: 30.0
};

function initSystemEngine(config = {}) {
  systemState.pasteThresholdMs = config.pasteThresholdMs || 3.0;
  systemState.initialized = true;

  self.postMessage({
    type: 'SYSTEM_WORKER_READY',
    payload: { status: 'initialized' }
  });
}

/**
 * Keystroke Dynamics Analysis using Performance API timestamps
 */
function processKeystrokeBatch(payload) {
  if (!systemState.initialized) return;

  const { keystrokes, textLengthAdded } = payload;
  if (!keystrokes || keystrokes.length === 0) return;

  let totalDuration = 0;
  const ikiList = [];

  for (let i = 1; i < keystrokes.length; i++) {
    const iki = keystrokes[i].timestamp - keystrokes[i - 1].timestamp;
    if (iki >= 0 && iki < 5000) {
      ikiList.push(iki);
      totalDuration += iki;
    }
  }

  const averageIki = ikiList.length > 0 ? totalDuration / ikiList.length : 100;

  // Calculate Variance & Typing Entropy
  let variance = 0;
  if (ikiList.length > 1) {
    const sumSquareDiff = ikiList.reduce((acc, val) => acc + Math.pow(val - averageIki, 2), 0);
    variance = sumSquareDiff / (ikiList.length - 1);
  }
  const stdDev = Math.sqrt(variance);

  // Anomaly Detection: Mass Paste or Bot Injection
  const isVelocitySuspicious = (textLengthAdded > 5 && (totalDuration / textLengthAdded) < systemState.pasteThresholdMs);
  const isZeroEntropy = (keystrokes.length > 8 && stdDev < 1.5); // Robotic uniform timing

  const isPasteDetected = isVelocitySuspicious || isZeroEntropy;

  self.postMessage({
    type: 'KEYSTROKE_ANALYSIS_RESULT',
    payload: {
      averageIkiMs: parseFloat(averageIki.toFixed(2)),
      stdDevMs: parseFloat(stdDev.toFixed(2)),
      isPasteDetected: isPasteDetected,
      characterCount: textLengthAdded || keystrokes.length,
      severity: isPasteDetected ? 'high' : 'info'
    }
  });
}

/**
 * Evaluate Screen, Visibility & Event Trust Integrity
 */
function evaluateSystemIntegrity(payload) {
  const { isExtendedScreen, isDocumentVisible, isMouseTrusted, isFocus, timestamp } = payload;

  const violations = [];

  if (isExtendedScreen) {
    violations.push({ type: 'dual_monitor_detected', details: 'Secondary screen or display projector attached.' });
  }

  if (!isDocumentVisible || !isFocus) {
    violations.push({ type: 'tab_switch_or_blur', details: 'Exam window lost focus or tab switched.' });
  }

  if (isMouseTrusted === false) {
    violations.push({ type: 'untrusted_input_bot', details: 'Synthetic mouse event or remote automation bot detected.' });
  }

  let severity = 'info';
  if (violations.some(v => v.type === 'dual_monitor_detected' || v.type === 'untrusted_input_bot')) {
    severity = 'high';
  } else if (violations.length > 0) {
    severity = 'medium';
  }

  self.postMessage({
    type: 'SYSTEM_INTEGRITY_RESULT',
    payload: {
      timestamp: timestamp || Date.now(),
      violations: violations,
      isIntegrityViolated: violations.length > 0,
      severity: severity
    }
  });
}

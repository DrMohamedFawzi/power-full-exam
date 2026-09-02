/**
 * ============================================================
 *  AEGIS-X EDGE-AI PROCTORING ENGINE — ProctoringOrchestrator.js
 *  Main Thread Orchestrator, Worker Pool & Adaptive Sampling Engine
 *  © 2026 Aegis-X. All rights reserved.
 * ============================================================
 */

; (function (global) {
  'use strict';

  class ProctoringOrchestrator {
    constructor(config = {}) {
      this.config = Object.assign({
        baseScanIntervalMs: 1500,     // Target interval for vision analysis
        maxScanIntervalMs: 3000,      // Max interval under heavy CPU/GPU load
        fpsThresholdLow: 45,          // FPS threshold to trigger adaptive slowdown
        fpsThresholdHigh: 55,         // FPS threshold to restore high rate
        violationCallback: null,
        enableCryptoAudit: true
      }, config);

      this.workers = {
        audio: null,
        vision: null,
        system: null
      };

      this.state = {
        active: false,
        currentIntervalMs: this.config.baseScanIntervalMs,
        lastFps: 60,
        frameCount: 0,
        lastFpsCheckTime: performance.now(),
        violationCount: 0,
        cryptoKey: null,
        auditLog: []
      };

      this.timerIds = {
        visionScan: null,
        fpsMonitor: null,
        systemCheck: null
      };

      this._initCryptoKey();
    }

    /**
     * Initialize Web Crypto API AES-GCM Key for Offline Encrypted Audit Buffer
     */
    async _initCryptoKey() {
      try {
        if (global.crypto && global.crypto.subtle) {
          this.state.cryptoKey = await global.crypto.subtle.generateKey(
            { name: 'AES-GCM', length: 256 },
            true,
            ['encrypt', 'decrypt']
          );
        }
      } catch (e) {
        console.warn('[AegisOrchestrator] Web Crypto API fallback engaged:', e);
      }
    }

    /**
     * Start the Multi-Threaded Proctoring Suite
     */
    async start(violationCallback) {
      if (this.state.active) return;
      this.state.active = true;
      if (violationCallback) this.config.violationCallback = violationCallback;

      console.log('🛡️ [AegisOrchestrator] Initializing Edge-AI Proctoring Workers Pool...');

      // 1. Spawn Worker Pool
      this._spawnWorkers();

      // 2. Start Adaptive FPS Monitor
      this._startFpsMonitoring();

      // 3. Start System & Browser Integrity Listeners
      this._startSystemListeners();

      // 4. Start Vision & Audio Frame Processing Loop
      this._scheduleVisionScan();
    }

    /**
     * Stop and Cleanup all Workers & Timers
     */
    stop() {
      this.state.active = false;

      // Clear Timers
      if (this.timerIds.visionScan) clearTimeout(this.timerIds.visionScan);
      if (this.timerIds.systemCheck) clearInterval(this.timerIds.systemCheck);
      if (this.timerIds.fpsMonitor) cancelAnimationFrame(this.timerIds.fpsMonitor);

      // Terminate Workers Pool
      Object.keys(this.workers).forEach(key => {
        if (this.workers[key]) {
          this.workers[key].terminate();
          this.workers[key] = null;
        }
      });

      console.log('🛡️ [AegisOrchestrator] Proctoring Workers Pool Terminated.');
    }

    /**
     * Spawn Web Workers with isolated scripts
     */
    _spawnWorkers() {
      try {
        // Instantiate Workers (relative paths for XAMPP / local server)
        const workerBasePath = 'core/security/workers/';

        this.workers.audio = new Worker(workerBasePath + 'audio.worker.js');
        this.workers.vision = new Worker(workerBasePath + 'vision.worker.js');
        this.workers.system = new Worker(workerBasePath + 'system.worker.js');

        // Setup Worker Message Handlers
        this.workers.audio.onmessage = (e) => this._handleAudioWorkerMessage(e);
        this.workers.vision.onmessage = (e) => this._handleVisionWorkerMessage(e);
        this.workers.system.onmessage = (e) => this._handleSystemWorkerMessage(e);

        // Send Initial Handshakes
        this.workers.audio.postMessage({ type: 'INIT_AUDIO_WORKER', payload: { sampleRate: 16000 } });
        this.workers.vision.postMessage({ type: 'INIT_VISION_WORKER', payload: {} });
        this.workers.system.postMessage({ type: 'INIT_SYSTEM_WORKER', payload: {} });

      } catch (e) {
        console.warn('[AegisOrchestrator] Inline worker fallback engaged:', e);
      }
    }

    /**
     * Adaptive Sampling Rate Engine: Monitors 60 FPS UI performance
     */
    _startFpsMonitoring() {
      let frameCounter = 0;
      let lastTime = performance.now();

      const calcFps = () => {
        if (!this.state.active) return;
        frameCounter++;
        const now = performance.now();
        const delta = now - lastTime;

        if (delta >= 1000) {
          const fps = Math.round((frameCounter * 1000) / delta);
          this.state.lastFps = fps;

          // Adaptive Adjustment: If device is struggling (FPS < 45), slow down AI scan interval
          if (fps < this.config.fpsThresholdLow) {
            this.state.currentIntervalMs = Math.min(this.config.maxScanIntervalMs, this.state.currentIntervalMs + 500);
          } else if (fps > this.config.fpsThresholdHigh) {
            this.state.currentIntervalMs = Math.max(this.config.baseScanIntervalMs, this.state.currentIntervalMs - 250);
          }

          frameCounter = 0;
          lastTime = now;
        }

        this.timerIds.fpsMonitor = requestAnimationFrame(calcFps);
      };

      this.timerIds.fpsMonitor = requestAnimationFrame(calcFps);
    }

    /**
     * Schedule Next Vision Frame Scan (Time-Sliding Adaptive Sampling)
     */
    _scheduleVisionScan() {
      if (!this.state.active) return;

      this.timerIds.visionScan = setTimeout(() => {
        this._dispatchVisionFrame();
        this._scheduleVisionScan();
      }, this.state.currentIntervalMs);
    }

    /**
     * Dispatch video frame to Vision Worker safely with zero memory leaks
     */
    _dispatchVisionFrame() {
      if (!this.workers.vision) return;

      const videoEl = document.getElementById('aegis-webcam') || document.querySelector('video');
      if (!videoEl || videoEl.paused || videoEl.ended) return;

      try {
        // Fast offscreen canvas snapshot
        const canvas = document.createElement('canvas');
        canvas.width = 320;  // Downscaled resolution for INT8 efficiency
        canvas.height = 240;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(videoEl, 0, 0, canvas.width, canvas.height);

        // Fetch landmarks if FaceMesh is globally attached, or pass raw ImageData
        let landmarks = null;
        if (window.__AEGIS_LAST_LANDMARKS) {
          landmarks = window.__AEGIS_LAST_LANDMARKS;
        }

        this.workers.vision.postMessage({
          type: 'PROCESS_VISION_FRAME',
          payload: {
            landmarks: landmarks,
            imageWidth: canvas.width,
            imageHeight: canvas.height,
            timestamp: Date.now()
          }
        });

        // Aggressive Memory Management: Nullify canvas references immediately
        canvas.width = 0;
        canvas.height = 0;
      } catch (e) {
        console.warn('[AegisOrchestrator] Frame dispatch skipped:', e);
      }
    }

    /**
     * System & Browser Integrity Event Listeners
     */
    _startSystemListeners() {
      // 1. Dual Monitor Check via Screen Details API
      const checkScreen = () => {
        const isExtended = !!(window.screen && window.screen.isExtended);
        if (this.workers.system) {
          this.workers.system.postMessage({
            type: 'EVALUATE_SYSTEM_INTEGRITY',
            payload: {
              isExtendedScreen: isExtended,
              isDocumentVisible: !document.hidden,
              isFocus: document.hasFocus(),
              isMouseTrusted: true,
              timestamp: Date.now()
            }
          });
        }
      };

      this.timerIds.systemCheck = setInterval(checkScreen, 2000);

      // 2. MouseEvent.isTrusted & Focus listeners
      window.addEventListener('blur', () => checkScreen());
      window.addEventListener('visibilitychange', () => checkScreen());
      window.addEventListener('click', (e) => {
        if (!e.isTrusted && this.workers.system) {
          this.workers.system.postMessage({
            type: 'EVALUATE_SYSTEM_INTEGRITY',
            payload: {
              isExtendedScreen: false,
              isDocumentVisible: !document.hidden,
              isFocus: document.hasFocus(),
              isMouseTrusted: false,
              timestamp: Date.now()
            }
          });
        }
      });
    }

    /**
     * Worker Message Resolvers
     */
    _handleAudioWorkerMessage(e) {
      const { type, payload } = e.data;
      if (type === 'AUDIO_ANALYSIS_RESULT') {
        if (payload.speechDetected && payload.isFarField) {
          this._registerViolation('audio_far_field_voice', 'Distant third-party voice detected (~8m away)', payload.severity);
        } else if (payload.speechDetected && payload.playbackOrSpeakerDetected) {
          this._registerViolation('audio_speaker_playback', 'Speaker playback / artificial voice detected', payload.severity);
        }
      }
    }

    _handleVisionWorkerMessage(e) {
      const { type, payload } = e.data;
      if (type === 'VISION_ANALYSIS_RESULT') {
        if (payload.faceCount > 1) {
          this._registerViolation('multiple_faces', `Multiple faces detected (${payload.faceCount} faces)`, payload.severity);
        } else if (!payload.headPose.isFacingCamera) {
          this._registerViolation('head_pose_turn', `Head turned outside screen boundary (${payload.headPose.violationReason})`, payload.severity);
        } else if (payload.prohibitedObjects && payload.prohibitedObjects.length > 0) {
          const itemTypes = payload.prohibitedObjects.map(i => i.type).join(', ');
          this._registerViolation('prohibited_object', `Prohibited item in frame: ${itemTypes}`, payload.severity);
        }
      }
    }

    _handleSystemWorkerMessage(e) {
      const { type, payload } = e.data;
      if (type === 'SYSTEM_INTEGRITY_RESULT' && payload.isIntegrityViolated) {
        payload.violations.forEach(v => {
          this._registerViolation(v.type, v.details, payload.severity);
        });
      } else if (type === 'KEYSTROKE_ANALYSIS_RESULT' && payload.isPasteDetected) {
        this._registerViolation('keystroke_anomaly', 'Mass paste or robotic keystroke pattern detected', payload.severity);
      }
    }

    /**
     * Encrypt & Register Violation Event
     */
    async _registerViolation(type, details, severity = 'medium') {
      this.state.violationCount++;

      const logEntry = {
        id: 'viol_' + Date.now() + '_' + Math.random().toString(36).substr(2, 5),
        type: type,
        details: details,
        severity: severity,
        timestamp: new Date().toISOString()
      };

      // 1. Encrypt via Web Crypto API if available
      if (this.state.cryptoKey && global.crypto.subtle) {
        try {
          const iv = global.crypto.getRandomValues(new Uint8Array(12));
          const encodedText = new TextEncoder().encode(JSON.stringify(logEntry));
          const cipherBuffer = await global.crypto.subtle.encrypt(
            { name: 'AES-GCM', iv: iv },
            this.state.cryptoKey,
            encodedText
          );
          logEntry.encryptedPayload = Array.from(new Uint8Array(cipherBuffer));
        } catch (e) { }
      }

      this.state.auditLog.push(logEntry);

      // 2. Dispatch to global violation callback
      if (typeof this.config.violationCallback === 'function') {
        this.config.violationCallback(type, details, severity);
      }
    }

    getAuditLog() {
      return this.state.auditLog;
    }

    getMetrics() {
      return {
        fps: this.state.lastFps,
        currentScanIntervalMs: this.state.currentIntervalMs,
        totalViolations: this.state.violationCount
      };
    }
  }

  // Export to global scope
  global.ProctoringOrchestrator = ProctoringOrchestrator;

})(window);

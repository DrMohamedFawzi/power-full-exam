/**
 * Aegis-X Audio Proctoring — speech-focused VAD (not raw loudness).
 *
 * Ignores fans, typing, doors, and broadband noise by requiring:
 *  1. Energy concentrated in the human speech band (~300–3400 Hz)
 *  2. Zero-crossing rate typical of voiced/unvoiced speech
 *  3. Level above a short room-noise baseline
 *  4. Sustained activity (~1s), not a brief spike
 */
export function createAudioMonitor({ onViolation }) {
    let audioCtx = null;
    let microphone = null;
    let analyser = null;
    let stream = null;
    let timer = null;
    let speechRecognizer = null;

    let speechAccumulator = 0;
    let lastViolationTime = 0;
    const AUDIO_COOLDOWN_MS = 8_000;
    const TICK_MS = 80;
    // ~1.0s of continuous speech-like frames before a violation
    const SPEECH_FRAMES_REQUIRED = 13;

    let vadBarEl = null;
    let calibrationFrames = 0;
    const CALIBRATION_N = 25; // ~2s at 80ms
    let roomSpeechBase = 0;
    let roomSpeechSum = 0;

    // Web Speech API: only treat as "confirmed speech" when we get transcripts
    let lastSpeechApiHitAt = 0;
    const SPEECH_API_FRESH_MS = 2_500;

    function resumeAudioContext() {
        if (audioCtx && audioCtx.state === 'suspended') {
            audioCtx.resume().catch(() => {});
        }
    }

    function attachUnlockers() {
        ['click', 'keydown', 'mousemove', 'touchstart', 'scroll'].forEach((evt) => {
            window.addEventListener(evt, resumeAudioContext, { passive: true });
        });
    }

    function initSpeechKeywordDetector() {
        const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
        if (!SpeechRecognition) return;

        try {
            speechRecognizer = new SpeechRecognition();
            speechRecognizer.continuous = true;
            speechRecognizer.interimResults = true;
            speechRecognizer.lang = 'ar-SA';

            const cheatingKeywords = [
                'ما هو', 'ما هي', 'ما إجابة', 'ما اجابة', 'حل السؤال',
                'جاوب', 'ابحث عن', 'اختر', 'السؤال الثاني', 'السؤال الثالث',
                'كم الناتج', 'ما الحل',
            ];

            speechRecognizer.onresult = (event) => {
                for (let i = event.resultIndex; i < event.results.length; ++i) {
                    const transcript = event.results[i][0].transcript.trim();
                    if (!transcript) continue;

                    // Any non-empty transcript = confirmed human speech (not ambient noise)
                    lastSpeechApiHitAt = Date.now();

                    const matched = cheatingKeywords.find((kw) => transcript.includes(kw));
                    if (matched) {
                        const now = Date.now();
                        if (now - lastViolationTime > AUDIO_COOLDOWN_MS) {
                            lastViolationTime = now;
                            onViolation(
                                'audio_keyword_cheating',
                                `⚠️ تم رصد كلام بشري وصياغة سؤال/طلب غش: "${transcript}"`,
                            );
                        }
                    }
                }
            };

            speechRecognizer.onerror = () => {};
            speechRecognizer.onend = () => {
                setTimeout(() => {
                    try {
                        speechRecognizer?.start();
                    } catch {
                        /* ignore restart races */
                    }
                }, 2000);
            };

            speechRecognizer.start();
        } catch (err) {
            console.warn('[Aegis Audio] SpeechRecognition warning:', err);
        }
    }

    /**
     * Map Hz → FFT bin for the current AudioContext sample rate.
     */
    function hzToBin(hz, sampleRate, fftSize) {
        return Math.max(0, Math.min(Math.floor((hz * fftSize) / sampleRate), (fftSize / 2) - 1));
    }

    /**
     * Zero-crossing rate on uint8 time-domain samples (centered at 128).
     * Human speech typically lands roughly in [0.02, 0.35] at mic sample rates.
     */
    function zeroCrossingRate(timeData) {
        let crossings = 0;
        for (let i = 1; i < timeData.length; i++) {
            const a = timeData[i - 1] - 128;
            const b = timeData[i] - 128;
            if ((a >= 0 && b < 0) || (a < 0 && b >= 0)) {
                crossings += 1;
            }
        }
        return crossings / timeData.length;
    }

    /**
     * Band energies from frequency bins. Speech lives in mid band;
     * rumble/hiss dominate ambient noise.
     */
    function bandEnergies(freqData, sampleRate, fftSize) {
        const speechLo = hzToBin(300, sampleRate, fftSize);
        const speechHi = hzToBin(3400, sampleRate, fftSize);
        const rumbleHi = hzToBin(200, sampleRate, fftSize);
        const hissLo = hzToBin(4500, sampleRate, fftSize);

        let speech = 0;
        let rumble = 0;
        let hiss = 0;
        let total = 0;

        for (let i = 0; i < freqData.length; i++) {
            const v = freqData[i];
            total += v;
            if (i < rumbleHi) {
                rumble += v;
            } else if (i >= speechLo && i <= speechHi) {
                speech += v;
            } else if (i >= hissLo) {
                hiss += v;
            }
        }

        const speechBins = Math.max(1, speechHi - speechLo + 1);
        const rumbleBins = Math.max(1, rumbleHi);
        const hissBins = Math.max(1, freqData.length - hissLo);

        return {
            speechAvg: speech / speechBins,
            rumbleAvg: rumble / rumbleBins,
            hissAvg: hiss / hissBins,
            totalAvg: total / Math.max(1, freqData.length),
            speechShare: total > 0 ? speech / total : 0,
        };
    }

    /**
     * True when the frame looks like near-field human speech, not just sound.
     */
    function isSpeechLikeFrame(bands, zcr, speechAboveBase) {
        const zcrOk = zcr >= 0.02 && zcr <= 0.38;
        // Voice energy should dominate rumble and hiss
        const formantOk =
            bands.speechAvg > bands.rumbleAvg * 1.35 &&
            bands.speechAvg > bands.hissAvg * 1.15 &&
            bands.speechShare >= 0.28;
        // Must rise clearly above calibrated room speech-band baseline
        const loudEnough = speechAboveBase >= 10;

        return zcrOk && formantOk && loudEnough;
    }

    return {
        async start() {
            try {
                try {
                    stream = await navigator.mediaDevices.getUserMedia({
                        audio: {
                            echoCancellation: true,
                            noiseSuppression: true,
                            autoGainControl: false,
                        },
                    });
                } catch {
                    stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                }

                audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                attachUnlockers();
                resumeAudioContext();

                microphone = audioCtx.createMediaStreamSource(stream);

                analyser = audioCtx.createAnalyser();
                // Larger FFT → finer speech-band resolution
                analyser.fftSize = 1024;
                analyser.smoothingTimeConstant = 0.45;

                microphone.connect(analyser);

                const freqData = new Uint8Array(analyser.frequencyBinCount);
                const timeData = new Uint8Array(analyser.fftSize);

                initSpeechKeywordDetector();

                timer = window.setInterval(() => {
                    if (!analyser || !audioCtx) return;

                    resumeAudioContext();

                    analyser.getByteFrequencyData(freqData);
                    analyser.getByteTimeDomainData(timeData);

                    const bands = bandEnergies(freqData, audioCtx.sampleRate, analyser.fftSize);
                    const zcr = zeroCrossingRate(timeData);

                    // --- Room calibration (first ~2s) ---
                    if (calibrationFrames < CALIBRATION_N) {
                        roomSpeechSum += bands.speechAvg;
                        calibrationFrames += 1;
                        if (calibrationFrames === CALIBRATION_N) {
                            roomSpeechBase = roomSpeechSum / CALIBRATION_N;
                            console.log(
                                '[Aegis Audio] Room speech-band baseline:',
                                roomSpeechBase.toFixed(2),
                            );
                        }
                        if (vadBarEl || (vadBarEl = document.getElementById('audio-vad-bar'))) {
                            vadBarEl.style.width = '8%';
                            vadBarEl.className = 'bg-success h-full transition-all duration-75';
                        }
                        return;
                    }

                    // Slow drift compensation when the room is quiet
                    if (bands.speechAvg < roomSpeechBase * 1.4) {
                        roomSpeechBase = roomSpeechBase * 0.995 + bands.speechAvg * 0.005;
                    }

                    const speechAboveBase = Math.max(0, bands.speechAvg - roomSpeechBase);
                    const speechLike = isSpeechLikeFrame(bands, zcr, speechAboveBase);

                    // UI meter tracks speech likelihood, not raw loudness
                    let volumePct = 0;
                    if (speechLike) {
                        volumePct = Math.min(100, Math.round((speechAboveBase / 40) * 100));
                    } else if (speechAboveBase > 4) {
                        // Mild activity / noise — show low so user sees the mic is live
                        volumePct = Math.min(25, Math.round((speechAboveBase / 60) * 100));
                    }

                    if (!vadBarEl) vadBarEl = document.getElementById('audio-vad-bar');
                    if (vadBarEl) {
                        vadBarEl.style.width = volumePct + '%';
                        if (speechLike && volumePct >= 45) {
                            vadBarEl.className = 'bg-error h-full transition-all duration-75';
                        } else if (speechLike) {
                            vadBarEl.className = 'bg-warning h-full transition-all duration-75';
                        } else {
                            vadBarEl.className = 'bg-success h-full transition-all duration-75';
                        }
                    }

                    const now = Date.now();
                    const speechApiConfirms = now - lastSpeechApiHitAt < SPEECH_API_FRESH_MS;

                    // Count speech-like frames. A fresh Web Speech transcript accelerates
                    // the accumulator (confirmed words), but ambient noise never qualifies
                    // because speechLike already failed the band/ZCR gates.
                    if (speechLike) {
                        speechAccumulator += speechApiConfirms ? 2 : 1;
                        if (
                            speechAccumulator >= SPEECH_FRAMES_REQUIRED &&
                            now - lastViolationTime > AUDIO_COOLDOWN_MS
                        ) {
                            lastViolationTime = now;
                            speechAccumulator = 0;
                            onViolation(
                                'audio_violation',
                                '⚠️ تم رصد كلام بشري مسموع بالقرب من الجهاز!',
                            );
                        }
                    } else {
                        speechAccumulator = Math.max(0, speechAccumulator - 2);
                    }
                }, TICK_MS);

                console.log('[Aegis Audio] Speech-focused VAD active (band + ZCR + baseline).');
            } catch (err) {
                console.warn('[Aegis Audio] Mic init error:', err);
                onViolation('microphone_denied', 'تعذّر فتح الميكروفون أو تم رفض الإذن من المتصفح');
            }
        },

        stop() {
            if (timer) clearInterval(timer);
            if (speechRecognizer) {
                try {
                    speechRecognizer.stop();
                } catch {
                    /* ignore */
                }
                speechRecognizer = null;
            }
            stream?.getTracks().forEach((track) => track.stop());
            audioCtx?.close().catch(() => {});
        },
    };
}

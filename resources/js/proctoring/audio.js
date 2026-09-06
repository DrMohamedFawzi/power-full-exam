/**
 * Aegis-X Audio Proctoring Engine v5.2 - Hyper-Sensitive Near-Field VAD
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
    const AUDIO_COOLDOWN_MS = 5_000;

    let vadBarEl = null;

    function resumeAudioContext() {
        if (audioCtx && audioCtx.state === 'suspended') {
            audioCtx.resume().then(() => {
                console.log('[Aegis Audio] AudioContext resumed.');
            }).catch(() => {});
        }
    }

    function attachUnlockers() {
        ['click', 'keydown', 'mousemove', 'touchstart', 'scroll'].forEach(evt => {
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
                'كم الناتج', 'ما الحل'
            ];

            speechRecognizer.onresult = (event) => {
                for (let i = event.resultIndex; i < event.results.length; ++i) {
                    const transcript = event.results[i][0].transcript.trim();
                    const matched = cheatingKeywords.find(kw => transcript.includes(kw));
                    if (matched) {
                        const now = Date.now();
                        if (now - lastViolationTime > AUDIO_COOLDOWN_MS) {
                            lastViolationTime = now;
                            onViolation(
                                'audio_keyword_cheating',
                                `⚠️ تم رصد كلام بشري وصياغة سؤال/طلب غش: "${transcript}"`
                            );
                        }
                    }
                }
            };

            speechRecognizer.onerror = () => {};
            speechRecognizer.onend = () => {
                setTimeout(() => {
                    try { speechRecognizer?.start(); } catch {}
                }, 2000);
            };

            speechRecognizer.start();
        } catch (err) {
            console.warn('[Aegis Audio] SpeechRecognition warning:', err);
        }
    }

    return {
        async start() {
            try {
                try {
                    stream = await navigator.mediaDevices.getUserMedia({
                        audio: { echoCancellation: true, noiseSuppression: true, autoGainControl: false }
                    });
                } catch (e) {
                    stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                }

                audioCtx = new (window.AudioContext || window.webkitAudioContext)();
                attachUnlockers();
                resumeAudioContext();

                microphone = audioCtx.createMediaStreamSource(stream);

                analyser = audioCtx.createAnalyser();
                analyser.fftSize = 256;
                analyser.smoothingTimeConstant = 0.2;

                microphone.connect(analyser);

                const dataArray = new Uint8Array(analyser.frequencyBinCount);
                const timeData = new Uint8Array(analyser.fftSize);

                initSpeechKeywordDetector();

                // Responsive 60ms Audio VAD Loop
                timer = window.setInterval(() => {
                    if (!analyser) return;

                    resumeAudioContext();

                    // 1. Calculate Peak Waveform Displacement
                    analyser.getByteTimeDomainData(timeData);
                    let maxDev = 0;
                    for (let i = 0; i < timeData.length; i++) {
                        const dev = Math.abs(timeData[i] - 128);
                        if (dev > maxDev) maxDev = dev;
                    }

                    // 2. Frequency Energy
                    analyser.getByteFrequencyData(dataArray);
                    let freqSum = 0;
                    for (let i = 0; i < dataArray.length; i++) {
                        freqSum += dataArray[i];
                    }
                    const avgFreq = freqSum / dataArray.length;

                    // Hyper-Sensitive Volume Scaling (animates immediately when speaking)
                    let volumePct = 0;
                    if (maxDev >= 2) {
                        volumePct = Math.min(100, Math.round((maxDev / 8) * 100));
                    }

                    // Live UI Meter Update on every tick (60ms)
                    if (!vadBarEl) vadBarEl = document.getElementById('audio-vad-bar');
                    if (vadBarEl) {
                        vadBarEl.style.width = volumePct + '%';
                        if (volumePct >= 50) {
                            vadBarEl.className = 'bg-error h-full transition-all duration-75';
                        } else if (volumePct >= 20) {
                            vadBarEl.className = 'bg-warning h-full transition-all duration-75';
                        } else {
                            vadBarEl.className = 'bg-success h-full transition-all duration-75';
                        }
                    }

                    // Speech Detection Condition (volumePct >= 18 and human voice frequencies)
                    const isSpeaking = volumePct >= 18 && avgFreq >= 5;
                    const now = Date.now();

                    if (isSpeaking) {
                        speechAccumulator += 1;
                        if (speechAccumulator >= 3 && (now - lastViolationTime > AUDIO_COOLDOWN_MS)) {
                            lastViolationTime = now;
                            speechAccumulator = 0;
                            onViolation(
                                'audio_violation',
                                `⚠️ تم رصد كلام بشري مسموع بالقرب من الجهاز!`
                            );
                        }
                    } else {
                        speechAccumulator = Math.max(0, speechAccumulator - 1);
                    }
                }, 60);

                console.log('[Aegis Audio] Hyper-Sensitive Audio Monitor v5.2 active (60ms).');

            } catch (err) {
                console.warn('[Aegis Audio] Mic init error:', err);
                onViolation('microphone_denied', 'تعذّر فتح الميكروفون أو تم رفض الإذن من المتصفح');
            }
        },

        stop() {
            if (timer) clearInterval(timer);
            if (speechRecognizer) {
                try { speechRecognizer.stop(); } catch {}
                speechRecognizer = null;
            }
            stream?.getTracks().forEach((track) => track.stop());
            audioCtx?.close().catch(() => {});
        }
    };
}

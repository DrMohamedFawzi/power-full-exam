export function createVisionMonitor({ onViolation, onFaceMatch, onModelsLoaded, approvedDescriptor }) {
    let stream = null;
    let video = null;
    let timer = null;
    let modelsLoaded = false;
    let isCooldown = false;
    const COOLDOWN_MS = 3000;
    const MODELS_URL = 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api/model';
    const MATCH_THRESHOLD = 0.6; // euclidean distance threshold for face match

    // Cached DOM Elements
    let pipWidget = null;

    // Parse approvedDescriptor into Float32Array for comparison
    let approvedDesc = null;
    if (approvedDescriptor) {
        try {
            const arr = Array.isArray(approvedDescriptor) ? approvedDescriptor : JSON.parse(approvedDescriptor);
            approvedDesc = new Float32Array(arr);
        } catch {
            approvedDesc = null;
        }
    }

    function triggerViolation(type, reason) {
        if (isCooldown) return;
        isCooldown = true;
        onViolation(type, reason);
        setTimeout(() => { isCooldown = false; }, COOLDOWN_MS);
    }

    async function loadModels() {
        if (modelsLoaded) return true;
        if (typeof faceapi === 'undefined') {
            console.error('[Aegis Vision] face-api.js not loaded!');
            return false;
        }
        try {
            const modelsToLoad = [
                faceapi.nets.tinyFaceDetector.loadFromUri(MODELS_URL),
                faceapi.nets.faceLandmark68Net.loadFromUri(MODELS_URL),
            ];

            // Load face recognition model only when identity matching is needed
            if (approvedDesc) {
                modelsToLoad.push(faceapi.nets.faceRecognitionNet.loadFromUri(MODELS_URL));
            }

            await Promise.all(modelsToLoad);
            modelsLoaded = true;
            console.log('[Aegis Vision] AI models loaded' + (approvedDesc ? ' (with recognition).' : '.'));
            onModelsLoaded?.();
            return true;
        } catch (e) {
            console.error('[Aegis Vision] Failed to load AI models:', e);
            return false;
        }
    }

    function euclideanDistance(desc1, desc2) {
        if (!desc1 || !desc2 || desc1.length !== desc2.length) return Infinity;
        let sum = 0;
        for (let i = 0; i < desc1.length; i++) {
            const diff = desc1[i] - desc2[i];
            sum += diff * diff;
        }
        return Math.sqrt(sum);
    }

    async function runDetectionLoop(pipCanvas, pipCtx) {
        if (!video || video.readyState < 2 || !modelsLoaded) return;

        // Build detection chain — add descriptor computation when identity matching is active
        let detectionChain = faceapi
            .detectAllFaces(video, new faceapi.TinyFaceDetectorOptions({ inputSize: 224, scoreThreshold: 0.4 }))
            .withFaceLandmarks();

        if (approvedDesc) {
            detectionChain = detectionChain.withFaceDescriptors();
        }

        const detections = await detectionChain;

        pipCtx.clearRect(0, 0, pipCanvas.width, pipCanvas.height);

        const safeCenterX = pipCanvas.width / 2;
        const safeCenterY = pipCanvas.height / 2;
        const safeRadius = pipCanvas.width * 0.36;

        if (!pipWidget) pipWidget = document.getElementById('aegis-vision-deterrent');

        // No face detected
        if (!detections || detections.length === 0) {
            onFaceMatch?.(false);

            pipCtx.save();
            pipCtx.beginPath();
            pipCtx.arc(safeCenterX, safeCenterY, safeRadius, 0, 2 * Math.PI);
            pipCtx.strokeStyle = '#ef4444';
            pipCtx.lineWidth = 2.5;
            pipCtx.setLineDash([4, 4]);
            pipCtx.stroke();
            pipCtx.restore();

            if (pipWidget) pipWidget.style.borderColor = '#ef4444';
            triggerViolation('face_missing', 'لم يتم العثور على وجه أمام الكاميرا');
            return;
        }

        // Multiple faces detected
        if (detections.length > 1) {
            triggerViolation('multiple_faces', 'تم كشف أكثر من شخص أمام الكاميرا!');
        }

        const detection = detections[0];
        const box = detection.detection.box;

        const scaleX = pipCanvas.width / video.videoWidth;
        const scaleY = pipCanvas.height / video.videoHeight;

        const scaledBox = {
            x: box.x * scaleX,
            y: box.y * scaleY,
            width: box.width * scaleX,
            height: box.height * scaleY,
        };

        const faceCenterX = scaledBox.x + (scaledBox.width / 2);
        const faceCenterY = scaledBox.y + (scaledBox.height / 2);

        const distance = Math.sqrt(
            Math.pow(faceCenterX - safeCenterX, 2) +
            Math.pow(faceCenterY - safeCenterY, 2)
        );

        const isOutside = distance > safeRadius;

        pipCtx.save();
        pipCtx.beginPath();
        pipCtx.arc(safeCenterX, safeCenterY, safeRadius, 0, 2 * Math.PI);
        pipCtx.strokeStyle = isOutside ? '#eab308' : '#10b981';
        pipCtx.lineWidth = 2.5;
        pipCtx.setLineDash([4, 4]);
        pipCtx.stroke();
        pipCtx.restore();

        // Fast Green 68-Landmark Drawing
        const landmarks = detection.landmarks.positions;
        if (landmarks && landmarks.length > 0) {
            pipCtx.fillStyle = '#10b981';
            for (let i = 0; i < landmarks.length; i += 2) { // Render half points for speed
                const pt = landmarks[i];
                pipCtx.beginPath();
                pipCtx.arc(pt.x * scaleX, pt.y * scaleY, 1.8, 0, 2 * Math.PI);
                pipCtx.fill();
            }
        }

        if (pipWidget) pipWidget.style.borderColor = isOutside ? '#eab308' : '#10b981';

        if (isOutside) {
            onFaceMatch?.(false);
            triggerViolation('head_motion', 'حركة خارج النطاق المسموح به (الدائرة). يرجى العودة لمنتصف الكاميرا!');
        } else if (approvedDesc && detection.descriptor) {
            // Real identity match using face descriptor comparison
            const dist = euclideanDistance(detection.descriptor, approvedDesc);
            const isIdentityMatch = dist < MATCH_THRESHOLD;
            onFaceMatch?.(isIdentityMatch);

            if (!isIdentityMatch) {
                triggerViolation('identity_mismatch', `عدم تطابق الهوية مع الصورة المعتمدة (المسافة: ${dist.toFixed(2)})`);
            }
        } else {
            // No approved descriptor — position-based match only (sandbox mode)
            onFaceMatch?.(true);
        }
    }

    return {
        async start() {
            try {
                const ok = await loadModels();
                if (!ok) return;

                stream = await navigator.mediaDevices.getUserMedia({
                    video: { width: 320, height: 240, facingMode: 'user' }
                });

                const pipVideo = document.getElementById('aegis-pip-video');
                if (pipVideo) {
                    pipVideo.srcObject = stream;
                    pipVideo.play().catch(() => {});
                }

                const preflightVideo = document.getElementById('preflight-live-video');
                if (preflightVideo) {
                    preflightVideo.srcObject = stream;
                    preflightVideo.play().catch(() => {});
                }

                video = document.createElement('video');
                video.srcObject = stream;
                video.muted = true;
                video.playsInline = true;
                video.width = 320;
                video.height = 240;
                await new Promise((resolve) => {
                    video.onloadeddata = resolve;
                    video.play().catch(() => {});
                });

                const pipCanvas = document.getElementById('aegis-pip-canvas');
                if (pipCanvas) {
                    pipCanvas.width = 144;
                    pipCanvas.height = 144;
                }
                const pipCtx = pipCanvas ? pipCanvas.getContext('2d', { willReadFrequently: true }) : null;
                if (!pipCtx) return;

                let isDetecting = false;
                // High-precision 400ms AI loop with concurrency guard
                timer = window.setInterval(async () => {
                    if (isDetecting) return;
                    isDetecting = true;
                    try {
                        await runDetectionLoop(pipCanvas, pipCtx);
                    } catch (e) {
                        console.warn('[Aegis Vision] Detection frame error:', e);
                    } finally {
                        isDetecting = false;
                    }
                }, 400);

                console.log('[Aegis Vision] High-precision Vision monitor active (400ms).');

            } catch (e) {
                console.error('[Aegis Vision] start() error:', e);
                onViolation('camera_denied', 'تعذّر الوصول لكاميرا الامتحان للمراقبة الحية');
            }
        },

        stop() {
            if (timer) clearInterval(timer);
            stream?.getTracks().forEach((track) => track.stop());
            console.log('[Aegis Vision] Vision monitor stopped.');
        }
    };
}

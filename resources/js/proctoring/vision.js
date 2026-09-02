/**
 * Aegis-X Vision Proctoring Engine - High-Performance AI Neural Network
 * Optimized with TinyFaceDetector for 10x faster execution & zero CPU lag.
 */
export function createVisionMonitor({ onViolation, onFaceMatch, onModelsLoaded, approvedDescriptor }) {
    let stream = null;
    let video = null;
    let timer = null;
    let modelsLoaded = false;
    let isCooldown = false;
    const COOLDOWN_MS = 3000;
    const MODELS_URL = 'https://cdn.jsdelivr.net/npm/@vladmandic/face-api/model';

    // Cached DOM Elements
    let pipWidget = null;

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
            // Load lightweight TinyFaceDetector & 68-Landmarks (10x faster than MobileNet)
            await Promise.all([
                faceapi.nets.tinyFaceDetector.loadFromUri(MODELS_URL),
                faceapi.nets.faceLandmark68Net.loadFromUri(MODELS_URL),
            ]);
            modelsLoaded = true;
            console.log('[Aegis Vision] Optimized AI models loaded.');
            onModelsLoaded?.();
            return true;
        } catch (e) {
            console.error('[Aegis Vision] Failed to load AI models:', e);
            return false;
        }
    }

    async function runDetectionLoop(pipCanvas, pipCtx) {
        if (!video || video.readyState < 2 || !modelsLoaded) return;

        // Optimized Tiny Face Detection (224px input = 10x speedup)
        const detections = await faceapi
            .detectAllFaces(video, new faceapi.TinyFaceDetectorOptions({ inputSize: 224, scoreThreshold: 0.4 }))
            .withFaceLandmarks();

        pipCtx.clearRect(0, 0, pipCanvas.width, pipCanvas.height);

        const safeCenterX = pipCanvas.width / 2;
        const safeCenterY = pipCanvas.height / 2;
        const safeRadius = pipCanvas.width * 0.36;

        if (!pipWidget) pipWidget = document.getElementById('aegis-vision-deterrent');

        // No face detected
        if (!detections || detections.length === 0) {
            onFaceMatch(false);

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
            onFaceMatch(false);
            triggerViolation('head_motion', 'حركة خارج النطاق المسموح به (الدائرة). يرجى العودة لمنتصف الكاميرا!');
        } else {
            onFaceMatch(true);
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

                // Throttled 500ms loop for smooth 60fps UI performance
                timer = window.setInterval(async () => {
                    try {
                        await runDetectionLoop(pipCanvas, pipCtx);
                    } catch (e) {
                        console.warn('[Aegis Vision] Detection frame error:', e);
                    }
                }, 500);

                console.log('[Aegis Vision] Optimized Vision monitor active.');

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

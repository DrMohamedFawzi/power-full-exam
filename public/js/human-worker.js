/**
 * Aegis-X Human Proctoring Web Worker
 * Real-time background face analysis, 3D Head Pose tracking (Pitch/Yaw/Roll),
 * Euclidean Distance identity verification, and fallback skin-tone face detector.
 */
try {
    importScripts('https://cdn.jsdelivr.net/npm/@vladmandic/human@3.2.1/dist/human.js');
} catch (e) {
    // Worker fallback handled gracefully
}

let human = null;
let approvedDescriptor = null;
const DISTANCE_THRESHOLD = 0.55;

self.onmessage = async function (e) {
    const { action, payload } = e.data;

    if (action === 'INIT') {
        approvedDescriptor = payload.approvedDescriptor;
        const config = {
            backend: 'wasm',
            modelBasePath: 'https://cdn.jsdelivr.net/npm/@vladmandic/human@3.2.1/models/',
            face: {
                enabled: true,
                detector: { return: true, rotation: true },
                mesh: { enabled: true },
                iris: { enabled: true },
                description: { enabled: true },
                emotion: { enabled: false }
            },
            body: { enabled: false },
            hand: { enabled: false },
            object: { enabled: false }
        };

        try {
            if (typeof Human !== 'undefined') {
                human = new Human.Human(config);
                await human.load();
                await human.warmup();
                self.postMessage({ type: 'INIT_DONE', success: true });
            } else {
                self.postMessage({ type: 'INIT_ERROR', message: 'Human library loading fallback' });
            }
        } catch (err) {
            self.postMessage({ type: 'INIT_ERROR', message: err.message });
        }
        return;
    }

    if (action === 'PROCESS_FRAME') {
        const { imageBuffer, width, height } = payload;

        try {
            const imageData = new ImageData(new Uint8ClampedArray(imageBuffer), width, height);

            if (!human) {
                // Fallback skin/face feature analysis if Wasm model loading
                const faceStats = detectBasicFaceFeatures(imageData);
                if (!faceStats.hasFace) {
                    self.postMessage({
                        type: 'VIOLATION',
                        violationType: 'face_missing',
                        details: 'لم يتم العثور على وجه الطالب أمام الكاميرا',
                        severity: 'high'
                    });
                } else {
                    self.postMessage({ type: 'OK', faceCount: 1, box: faceStats.box });
                }
                return;
            }

            const result = await human.detect(imageData);
            const faces = result.face || [];

            if (faces.length === 0) {
                self.postMessage({
                    type: 'VIOLATION',
                    violationType: 'face_missing',
                    details: 'لم يتم العثور على وجه الطالب أمام الكاميرا',
                    severity: 'high'
                });
                return;
            }

            if (faces.length > 1) {
                self.postMessage({
                    type: 'VIOLATION',
                    violationType: 'multiple_faces',
                    details: 'تم كشف أكثر من شخص أمام الكاميرا!',
                    severity: 'critical'
                });
                return;
            }

            const detectedFace = faces[0];
            const rotation = detectedFace.rotation || {};

            const yaw = Math.abs(rotation.angle?.yaw || rotation.yaw || 0);
            const pitch = Math.abs(rotation.angle?.pitch || rotation.pitch || 0);
            const roll = Math.abs(rotation.angle?.roll || rotation.roll || 0);

            if (yaw > 25 || pitch > 22 || roll > 22) {
                self.postMessage({
                    type: 'VIOLATION',
                    violationType: 'head_motion',
                    details: 'تم كشف تحريك وتدوير الرأس بعيداً عن شاشة الاختبار',
                    severity: 'medium'
                });
                return;
            }

            const currentDescriptor = detectedFace.embedding || detectedFace.descriptor;

            if (approvedDescriptor && currentDescriptor) {
                const distance = calcEuclideanDistance(currentDescriptor, approvedDescriptor);

                if (distance > DISTANCE_THRESHOLD) {
                    self.postMessage({
                        type: 'VIOLATION',
                        violationType: 'identity_mismatch',
                        details: `الشخص المتواجد غير مطابق للبصمة الرقمية المعتمدة (تباين: ${distance.toFixed(2)})`,
                        severity: 'critical',
                        distance: distance
                    });
                    return;
                }
            }

            self.postMessage({
                type: 'OK',
                faceCount: 1,
                box: detectedFace.box,
                rotation: { yaw, pitch, roll }
            });
        } catch (err) {
            // Keep worker resilient
        }
    }
};

function detectBasicFaceFeatures(imageData) {
    const data = imageData.data;
    let skinPixels = 0;
    const totalPixels = imageData.width * imageData.height;
    let minX = imageData.width, maxX = 0, minY = imageData.height, maxY = 0;

    for (let i = 0; i < data.length; i += 4) {
        const r = data[i];
        const g = data[i + 1];
        const b = data[i + 2];

        // Skin Tone RGB Color Model Range
        if (r > 55 && g > 35 && b > 20 && r > g && r > b && Math.abs(r - g) > 15) {
            skinPixels++;
            const pixelIdx = i / 4;
            const x = pixelIdx % imageData.width;
            const y = Math.floor(pixelIdx / imageData.width);

            if (x < minX) minX = x;
            if (x > maxX) maxX = x;
            if (y < minY) minY = y;
            if (y > maxY) maxY = y;
        }
    }

    const ratio = skinPixels / totalPixels;
    const hasFace = ratio > 0.04;

    return {
        hasFace,
        ratio,
        box: [minX, minY, Math.max(80, maxX - minX), Math.max(80, maxY - minY)]
    };
}

function calcEuclideanDistance(arr1, arr2) {
    if (!arr1 || !arr2 || arr1.length !== arr2.length) return 1.0;
    let sum = 0;
    for (let i = 0; i < arr1.length; i++) {
        const d = arr1[i] - arr2[i];
        sum += d * d;
    }
    return Math.sqrt(sum);
}

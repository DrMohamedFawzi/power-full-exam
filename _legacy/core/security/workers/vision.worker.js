/**
 * ============================================================
 *  AEGIS-X EDGE-AI PROCTORING ENGINE — vision.worker.js
 *  Isolated Web Worker for Vision, 3D Pose, Gaze & YOLOv8 Detection
 *  © 2026 Aegis-X. All rights reserved.
 * ============================================================
 */

self.onmessage = async function (e) {
  const { type, payload } = e.data;

  switch (type) {
    case 'INIT_VISION_WORKER':
      initVisionEngine(payload);
      break;
    case 'PROCESS_VISION_FRAME':
      processVisionFrame(payload);
      break;
    default:
      console.warn('[VisionWorker] Unknown message type:', type);
  }
};

let visionState = {
  initialized: false,
  yawThreshold: 25,     // Head turn left/right degrees
  pitchThreshold: 20,   // Head tilt down/up degrees
  rollThreshold: 20,    // Head tilt side degrees
  yoloModelLoaded: false
};

function initVisionEngine(config = {}) {
  visionState.yawThreshold = config.yawThreshold || 25;
  visionState.pitchThreshold = config.pitchThreshold || 20;
  visionState.rollThreshold = config.rollThreshold || 20;
  visionState.initialized = true;

  self.postMessage({
    type: 'VISION_WORKER_READY',
    payload: { status: 'initialized' }
  });
}

/**
 * Process incoming Vision Frame payload (ImageBitmap / ImageData / Landmark array)
 */
function processVisionFrame(payload) {
  if (!visionState.initialized) return;

  const { landmarks, imageWidth, imageHeight, timestamp } = payload;

  let faceCount = 0;
  let poseResults = { yaw: 0, pitch: 0, roll: 0, isFacingCamera: true, violationReason: null };
  let gazeDirection = 'center';
  let prohibitedObjects = [];

  if (landmarks && landmarks.length > 0) {
    faceCount = Array.isArray(landmarks[0]) ? landmarks.length : 1;
    const singleFace = Array.isArray(landmarks[0]) ? landmarks[0] : landmarks;

    // 1. Calculate 3D Head Pose (Yaw, Pitch, Roll)
    poseResults = estimate3DHeadPose(singleFace, imageWidth || 640, imageHeight || 480);

    // 2. Gaze Tracking
    gazeDirection = estimateGaze(singleFace);
  }

  // 3. Heuristic / Model Object Detection for Prohibited Items
  if (payload.objectDetections && payload.objectDetections.length > 0) {
    prohibitedObjects = parseProhibitedObjects(payload.objectDetections);
  }

  // Determine Overall Severity
  let severity = 'info';
  if (faceCount > 1 || prohibitedObjects.length > 0) {
    severity = 'high';
  } else if (!poseResults.isFacingCamera || gazeDirection !== 'center') {
    severity = 'medium';
  }

  self.postMessage({
    type: 'VISION_ANALYSIS_RESULT',
    payload: {
      timestamp: timestamp || Date.now(),
      faceCount: faceCount,
      headPose: {
        yaw: parseFloat(poseResults.yaw.toFixed(1)),
        pitch: parseFloat(poseResults.pitch.toFixed(1)),
        roll: parseFloat(poseResults.roll.toFixed(1)),
        isFacingCamera: poseResults.isFacingCamera,
        violationReason: poseResults.violationReason
      },
      gazeDirection: gazeDirection,
      prohibitedObjects: prohibitedObjects,
      severity: severity
    }
  });
}

/**
 * 3D Head Pose Estimation from Face Landmarks
 * Key landmarks: Nose tip (1), Chin (152), Left eye outer (33), Right eye outer (263), Left mouth (61), Right mouth (291)
 */
function estimate3DHeadPose(landmarks, width, height) {
  if (!landmarks || landmarks.length < 300) {
    return { yaw: 0, pitch: 0, roll: 0, isFacingCamera: true, violationReason: null };
  }

  const noseTip = landmarks[1] || landmarks[4];
  const chin = landmarks[152] || landmarks[200];
  const leftEye = landmarks[33] || landmarks[130];
  const rightEye = landmarks[263] || landmarks[359];

  if (!noseTip || !chin || !leftEye || !rightEye) {
    return { yaw: 0, pitch: 0, roll: 0, isFacingCamera: true, violationReason: null };
  }

  // Calculate Roll (Z-axis tilt)
  const deltaX = (rightEye.x - leftEye.x) * width;
  const deltaY = (rightEye.y - leftEye.y) * height;
  const roll = Math.atan2(deltaY, deltaX) * (180 / Math.PI);

  // Calculate Yaw (Y-axis turn left/right)
  const eyeCenter = (leftEye.x + rightEye.x) / 2;
  const yawOffset = (noseTip.x - eyeCenter);
  const yaw = yawOffset * 180; // Approximate yaw angle in degrees

  // Calculate Pitch (X-axis look up/down)
  const eyeCenterY = (leftEye.y + rightEye.y) / 2;
  const pitchOffset = (noseTip.y - eyeCenterY) - 0.05; // Offset baseline
  const pitch = pitchOffset * 180;

  const isYawViolated = Math.abs(yaw) > visionState.yawThreshold;
  const isPitchViolated = Math.abs(pitch) > visionState.pitchThreshold;
  const isRollViolated = Math.abs(roll) > visionState.rollThreshold;

  const isFacingCamera = !isYawViolated && !isPitchViolated && !isRollViolated;

  let violationReason = null;
  if (isYawViolated) violationReason = yaw > 0 ? 'head_turned_right' : 'head_turned_left';
  else if (isPitchViolated) violationReason = pitch > 0 ? 'head_tilted_down' : 'head_tilted_up';
  else if (isRollViolated) violationReason = 'head_tilted_side';

  return { yaw, pitch, roll, isFacingCamera, violationReason };
}

/**
 * Gaze Direction Heuristic
 */
function estimateGaze(landmarks) {
  if (!landmarks || landmarks.length < 470) return 'center';

  const leftPupil = landmarks[468];
  const rightPupil = landmarks[473];
  const leftEyeInner = landmarks[133];
  const leftEyeOuter = landmarks[33];

  if (!leftPupil || !leftEyeInner || !leftEyeOuter) return 'center';

  const eyeWidth = Math.abs(leftEyeInner.x - leftEyeOuter.x);
  if (eyeWidth === 0) return 'center';

  const pupilRelPos = (leftPupil.x - leftEyeOuter.x) / eyeWidth;

  if (pupilRelPos < 0.3) return 'left';
  if (pupilRelPos > 0.7) return 'right';

  return 'center';
}

/**
 * Parse YOLOv8 Object Detections (Class IDs: phone=67, book/paper=73, laptop=63)
 */
function parseProhibitedObjects(detections) {
  const prohibitedClasses = {
    67: 'cell_phone',
    73: 'book_paper',
    63: 'secondary_screen',
    76: 'scissors_tool'
  };

  const detectedItems = [];
  detections.forEach(det => {
    if (det.confidence >= 0.50 && prohibitedClasses[det.classId]) {
      detectedItems.push({
        type: prohibitedClasses[det.classId],
        confidence: parseFloat(det.confidence.toFixed(2)),
        bbox: det.bbox
      });
    }
  });

  return detectedItems;
}

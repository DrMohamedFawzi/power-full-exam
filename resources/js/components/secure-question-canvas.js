/**
 * Secure Question Canvas Renderer & Anti-Camera Spotlight Blur Lens Engine
 * Renders question text on a crisp High-DPI canvas with a white frosted glass blur overlay.
 * The student moves a magnifying lens (radial spotlight mask) across the question to reveal it.
 */

let animationFrameId = null;

export function renderQuestionCanvas(canvas, prompt, studentName, sessionCode) {
    if (!canvas || !prompt) return;

    const ctx = canvas.getContext('2d');
    const dpr = window.devicePixelRatio || 1;
    const rect = canvas.getBoundingClientRect();

    canvas.width = (rect.width || 600) * dpr;
    canvas.height = Math.max(rect.height || 220, 200) * dpr;
    ctx.scale(dpr, dpr);

    const width = rect.width || 600;
    const height = Math.max(rect.height || 220, 200);

    // Background Surface (Crisp Light Background)
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(0, 0, width, height);

    // Security Watermark Background Pattern
    ctx.save();
    ctx.fillStyle = 'rgba(15, 23, 42, 0.05)';
    ctx.font = 'bold 12px Cairo, sans-serif';
    ctx.rotate(-0.12);
    const watermarkText = `AEGIS-X LENS SECURE • ${studentName || 'STUDENT'} • ID: ${sessionCode || 'PROCTOR'} • ${new Date().toLocaleTimeString('ar-EG')}`;
    for (let y = -50; y < height + 100; y += 45) {
        for (let x = -50; x < width + 150; x += 320) {
            ctx.fillText(watermarkText, x, y);
        }
    }
    ctx.restore();

    // Render Question Prompt in Dark Ink with Right-to-Left Arabic Auto Wrapping
    ctx.fillStyle = '#0f172a';
    ctx.font = 'bold 18px Cairo, sans-serif';
    ctx.textAlign = 'right';
    ctx.textBaseline = 'top';

    const paddingX = 24;
    const maxWidth = width - (paddingX * 2);
    const words = prompt.split(' ');
    let line = '';
    let y = 24;
    const lineHeight = 34;

    for (let n = 0; n < words.length; n++) {
        const testLine = line + words[n] + ' ';
        const metrics = ctx.measureText(testLine);
        const testWidth = metrics.width;

        if (testWidth > maxWidth && n > 0) {
            ctx.fillText(line, width - paddingX, y);
            line = words[n] + ' ';
            y += lineHeight;
        } else {
            line = testLine;
        }
    }
    ctx.fillText(line, width - paddingX, y);

    // Initialize/Update the Magnifying Lens Spotlight Overlay
    const parentContainerId = canvas.parentElement ? canvas.parentElement.id : 'question-container';
    initAntiCameraShield(parentContainerId);
}

/**
 * Initializes the White Frosted Glass Blur Overlay with a Moving Magnifying Lens
 */
export function initAntiCameraShield(containerId = 'question-container') {
    const container = document.getElementById(containerId);
    if (!container) return;

    let overlay = document.getElementById('aegis-unified-mask');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.id = 'aegis-unified-mask';
        container.appendChild(overlay);
    }

    let targetX = container.offsetWidth / 2;
    let targetY = container.offsetHeight / 2;
    let currentX = targetX;
    let currentY = targetY;

    overlay.style.cssText = `
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        pointer-events: none;
        z-index: 30;
        background: rgba(255, 255, 255, 0.88);
        backdrop-filter: blur(14px);
        -webkit-backdrop-filter: blur(14px);
        mask-image: radial-gradient(circle 95px at var(--x, 50%) var(--y, 50%), transparent 0%, black 100%);
        -webkit-mask-image: radial-gradient(circle 95px at var(--x, 50%) var(--y, 50%), transparent 0%, black 100%);
        border-radius: 1rem;
        transition: opacity 0.3s ease;
    `;

    function updateTargetPos(clientX, clientY) {
        const rect = container.getBoundingClientRect();
        targetX = clientX - rect.left;
        targetY = clientY - rect.top;
    }

    container.onmousemove = (e) => updateTargetPos(e.clientX, e.clientY);
    container.ontouchstart = (e) => { if (e.touches.length > 0) updateTargetPos(e.touches[0].clientX, e.touches[0].clientY); };
    container.ontouchmove = (e) => { if (e.touches.length > 0) updateTargetPos(e.touches[0].clientX, e.touches[0].clientY); };

    if (animationFrameId) cancelAnimationFrame(animationFrameId);

    function updateSpotlight() {
        currentX += (targetX - currentX) * 0.35;
        currentY += (targetY - currentY) * 0.35;

        overlay.style.setProperty('--x', `${currentX}px`);
        overlay.style.setProperty('--y', `${currentY}px`);

        animationFrameId = requestAnimationFrame(updateSpotlight);
    }
    updateSpotlight();
}

/**
 * Audio Violation Beep Player
 */
export function playViolationBeep() {
    try {
        const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        const osc = audioCtx.createOscillator();
        const gain = audioCtx.createGain();

        osc.type = 'sawtooth';
        osc.frequency.setValueAtTime(880, audioCtx.currentTime);
        gain.gain.setValueAtTime(0.3, audioCtx.currentTime);

        osc.connect(gain);
        gain.connect(audioCtx.destination);

        osc.start();
        osc.stop(audioCtx.currentTime + 0.35);
    } catch {
        // Fallback silently if audio context unavailable
    }
}

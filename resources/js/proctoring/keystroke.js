/**
 * Keystroke-dynamics monitor. Collects the intervals (ms) between consecutive
 * keydown events and hands a batch to `onSample` every WINDOW_MS — it does
 * NOT decide anomaly itself; that analysis (mean/std vs. the session's own
 * baseline) happens server-side in `Proctoring\Actions\AnalyzeKeystrokeDynamics`,
 * ported from the legacy Python worker.
 */
export function createKeystrokeMonitor({ onSample }) {
    const WINDOW_MS = 20_000;
    const MIN_KEYS = 5;

    let lastKeyAt = null;
    let intervals = [];
    let timer = null;

    const handleKeydown = () => {
        const now = performance.now();

        if (lastKeyAt !== null) {
            intervals.push(now - lastKeyAt);
        }

        lastKeyAt = now;
    };

    const flush = () => {
        if (intervals.length >= MIN_KEYS) {
            onSample([...intervals]);
        }

        intervals = [];
    };

    return {
        start() {
            document.addEventListener('keydown', handleKeydown);
            timer = window.setInterval(flush, WINDOW_MS);
        },
        stop() {
            document.removeEventListener('keydown', handleKeydown);
            if (timer) {
                window.clearInterval(timer);
            }
        },
    };
}

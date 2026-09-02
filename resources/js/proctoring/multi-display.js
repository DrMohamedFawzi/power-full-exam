/**
 * Multi-monitor monitor. Uses the Window Management API where available
 * (`getScreenDetails`) and falls back to a coarse `window.screen.isExtended`
 * check. Both are behind a permission prompt or unsupported in some
 * browsers — this degrades to "unknown" (no violation) rather than guessing.
 */
export function createMultiDisplayMonitor({ onViolation }) {
    const POLL_MS = 10_000;
    let timer = null;

    const check = async () => {
        try {
            if (typeof window.screen?.isExtended === 'boolean') {
                if (window.screen.isExtended) {
                    onViolation('multi_monitor');
                }

                return;
            }

            if (typeof window.getScreenDetails === 'function') {
                const details = await window.getScreenDetails();

                if (details.screens.length > 1) {
                    onViolation('multi_monitor');
                }
            }
        } catch {
            // Permission denied or unsupported — silently skip, no false positives.
        }
    };

    return {
        start() {
            check();
            timer = window.setInterval(check, POLL_MS);
        },
        stop() {
            if (timer) {
                window.clearInterval(timer);
            }
        },
    };
}

/**
 * Fullscreen monitor. Requests fullscreen on start (best-effort — some
 * browsers require a user gesture, so the runner also offers a manual
 * "enter fullscreen" button) and reports `fullscreen_exit` whenever the
 * student leaves it.
 */
export function createFullscreenMonitor({ onViolation }) {
    let armed = false;

    const handleChange = () => {
        if (armed && document.fullscreenElement == null) {
            onViolation('fullscreen_exit');
        }
    };

    return {
        async start() {
            document.addEventListener('fullscreenchange', handleChange);

            try {
                await document.documentElement.requestFullscreen?.();
            } catch {
                // Requires a user gesture in some browsers — the runner UI offers a button.
            }

            armed = true;
        },
        stop() {
            armed = false;
            document.removeEventListener('fullscreenchange', handleChange);
        },
        async enter() {
            try {
                await document.documentElement.requestFullscreen?.();
            } catch {
                // Ignored — button remains available for the student to retry.
            }
        },
        isFullscreen() {
            return document.fullscreenElement != null;
        },
    };
}

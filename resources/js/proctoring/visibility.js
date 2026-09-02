/**
 * Tab-switch / window-blur monitor. Fires `tab_switch` when the document goes
 * hidden (the student switched tabs, minimized, or locked the screen) and
 * `window_blur` when the browser window itself loses focus without the page
 * becoming hidden (e.g. alt-tabbing to another app on the same screen).
 */
export function createVisibilityMonitor({ onViolation }) {
    const handleVisibility = () => {
        if (document.hidden) {
            onViolation('tab_switch');
        }
    };

    const handleBlur = () => {
        if (!document.hidden) {
            onViolation('window_blur');
        }
    };

    return {
        start() {
            document.addEventListener('visibilitychange', handleVisibility);
            window.addEventListener('blur', handleBlur);
        },
        stop() {
            document.removeEventListener('visibilitychange', handleVisibility);
            window.removeEventListener('blur', handleBlur);
        },
    };
}

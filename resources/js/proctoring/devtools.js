/**
 * DevTools monitor: catches the obvious keyboard shortcuts immediately
 * (`devtools_shortcut`), and heuristically detects an already-open panel by
 * watching for a sustained, large gap between outer and inner window
 * dimensions — the classic docked-panel tell (`devtools_opened`). Heuristics
 * only; there is no reliable cross-browser API for this.
 */
export function createDevtoolsMonitor({ onViolation }) {
    const THRESHOLD_PX = 160;
    const POLL_MS = 1500;

    let timer = null;
    let wasOpen = false;

    const handleKeydown = (event) => {
        const key = event.key?.toUpperCase();
        const isF12 = key === 'F12';
        const isCtrlShiftTool = (event.ctrlKey || event.metaKey) && event.shiftKey && ['I', 'J', 'C'].includes(key);
        const isViewSource = (event.ctrlKey || event.metaKey) && key === 'U';

        if (isF12 || isCtrlShiftTool || isViewSource) {
            event.preventDefault();
            onViolation('devtools_shortcut');
        }
    };

    const poll = () => {
        const widthGap = window.outerWidth - window.innerWidth;
        const heightGap = window.outerHeight - window.innerHeight;
        const isOpen = widthGap > THRESHOLD_PX || heightGap > THRESHOLD_PX;

        if (isOpen && !wasOpen) {
            onViolation('devtools_opened');
        }

        wasOpen = isOpen;
    };

    return {
        start() {
            document.addEventListener('keydown', handleKeydown);
            timer = window.setInterval(poll, POLL_MS);
        },
        stop() {
            document.removeEventListener('keydown', handleKeydown);
            if (timer) {
                window.clearInterval(timer);
            }
        },
    };
}

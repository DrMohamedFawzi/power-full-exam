/**
 * Anti-Screenshot & Anti-Copy Protection Monitor
 * Detects PrintScreen key, Win+Shift+S (Snipping Tool), Ctrl+C / Ctrl+V,
 * clears clipboard data immediately, and triggers screenshot_attempt violation.
 */
export function createClipboardMonitor({ onViolation }) {
    const handleCopy = (e) => {
        e.preventDefault();
        if (navigator.clipboard) {
            navigator.clipboard.writeText('⚠️ المحتوى محمي بموجب أنظمة Aegis-X ولا يمكن نسخه.').catch(() => {});
        }
        onViolation('copy_attempt', 'محاولة نسخ نصوص الامتحان محظورة');
    };

    const handlePaste = (e) => {
        e.preventDefault();
        onViolation('paste_attempt', 'محاولة إلصاق نصوص خارجية محظورة');
    };

    const handleKeydown = (event) => {
        const isPrintScreen = event.key === 'PrintScreen' || event.keyCode === 44;
        const isSnippingShortcut = (event.metaKey || event.ctrlKey || event.shiftKey)
            && ['s', 'S', 'c', 'C', 'v', 'V'].includes(event.key);

        if (isPrintScreen || (event.shiftKey && (event.metaKey || event.key === 'S' || event.key === 's'))) {
            event.preventDefault();
            event.stopPropagation();

            if (navigator.clipboard) {
                navigator.clipboard.writeText('').catch(() => {});
            }

            // Immediately blur page to prevent screen capture
            const blurOverlay = document.getElementById('exam-blur-overlay');
            if (blurOverlay) blurOverlay.style.display = 'flex';

            onViolation('screenshot_attempt', 'محاولة التقاط صورة لشاشة الامتحان محظورة');
        }
    };

    const handleKeyup = (event) => {
        if (event.key === 'PrintScreen' || event.keyCode === 44) {
            if (navigator.clipboard) {
                navigator.clipboard.writeText('').catch(() => {});
            }
        }
    };

    return {
        start() {
            document.addEventListener('copy', handleCopy);
            document.addEventListener('cut', handleCopy);
            document.addEventListener('paste', handlePaste);
            document.addEventListener('keydown', handleKeydown, true);
            document.addEventListener('keyup', handleKeyup, true);
        },
        stop() {
            document.removeEventListener('copy', handleCopy);
            document.removeEventListener('cut', handleCopy);
            document.removeEventListener('paste', handlePaste);
            document.removeEventListener('keydown', handleKeydown, true);
            document.removeEventListener('keyup', handleKeyup, true);
        },
    };
}

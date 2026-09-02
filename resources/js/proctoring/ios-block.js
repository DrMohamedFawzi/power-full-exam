/**
 * iOS Block & Anti-Scraping protection.
 * Blocks Apple iOS devices (iPads / iPhones) during exams and protects Canvas prototypes from external scrapers.
 */
export function createIosBlockMonitor({ onViolation }) {
    return {
        start() {
            const isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) ||
                (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);

            if (isIOS) {
                onViolation('ios_device_blocked', 'محاولة دخول من جهاز iOS غير مدعوم لدواعي أمنية');
            }

            // Anti-Canvas extraction protection
            try {
                const origToDataURL = HTMLCanvasElement.prototype.toDataURL;
                HTMLCanvasElement.prototype.toDataURL = function (...args) {
                    if (this.id && (this.id.includes('aegis') || this.id.includes('video') || this.id.includes('profile'))) {
                        return origToDataURL.apply(this, args);
                    }
                    return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII=';
                };
            } catch {
                // Keep resilient
            }
        },
        stop() {},
    };
}

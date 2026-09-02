/**
 * Builds a stable-ish device fingerprint before the student starts a session,
 * so `StartExamSession` can bind (or recognise) the device. Best-effort only:
 * every field degrades to null rather than blocking submission.
 */
async function hashCanvas() {
    try {
        const canvas = document.createElement('canvas');
        const ctx = canvas.getContext('2d');
        ctx.textBaseline = 'top';
        ctx.font = '14px Arial';
        ctx.fillText('aegis-x-fingerprint', 2, 2);

        const data = canvas.toDataURL();
        const digest = await crypto.subtle.digest('SHA-256', new TextEncoder().encode(data));

        return Array.from(new Uint8Array(digest)).map((b) => b.toString(16).padStart(2, '0')).join('').slice(0, 32);
    } catch {
        return null;
    }
}

export default () => ({
    deviceHash: '',
    screenResolution: '',
    timezone: '',

    async init() {
        this.screenResolution = `${window.screen.width}x${window.screen.height}`;
        this.timezone = Intl.DateTimeFormat().resolvedOptions().timeZone ?? '';

        const canvasHash = await hashCanvas();
        const seed = [navigator.userAgent, this.screenResolution, this.timezone, canvasHash].join('|');
        const digest = await crypto.subtle.digest('SHA-256', new TextEncoder().encode(seed));

        this.deviceHash = Array.from(new Uint8Array(digest)).map((b) => b.toString(16).padStart(2, '0')).join('');
    },
});

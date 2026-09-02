import { request } from '../lib/http';

const POLL_MS = 5000;

/** Polls the teacher's per-exam monitor JSON endpoint. No websockets. */
export default (dataUrl) => ({
    sessions: [],
    loading: true,
    timer: null,

    init() {
        this.poll();
        this.timer = setInterval(() => this.poll(), POLL_MS);
    },

    destroy() {
        if (this.timer) {
            clearInterval(this.timer);
        }
    },

    async poll() {
        try {
            const payload = await request(dataUrl);
            this.sessions = payload.sessions;
        } catch {
            // Transient network blip — keep showing the last known snapshot.
        } finally {
            this.loading = false;
        }
    },
});

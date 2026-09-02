/**
 * Tracks online/offline transitions. Does not raise violations itself — the
 * server derives `connection_lost` from accumulated offline time reported
 * through heartbeats (see student.sessions.heartbeat). This module just gives
 * the runner an accurate, low-latency status for its offline banner and for
 * how long to accumulate before the next heartbeat.
 */
export function createConnectivityMonitor({ onStatusChange }) {
    const handleOnline = () => onStatusChange(true);
    const handleOffline = () => onStatusChange(false);

    return {
        start() {
            window.addEventListener('online', handleOnline);
            window.addEventListener('offline', handleOffline);
        },
        stop() {
            window.removeEventListener('online', handleOnline);
            window.removeEventListener('offline', handleOffline);
        },
        isOnline() {
            return navigator.onLine;
        },
    };
}

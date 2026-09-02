/**
 * Thin fetch wrapper: attaches the CSRF token, asks for JSON, and turns a
 * non-2xx response into a rejected promise carrying the parsed payload.
 */

const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';

export async function request(url, { method = 'GET', body = null, keepalive = false, signal } = {}) {
    const response = await fetch(url, {
        method,
        keepalive,
        signal,
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: body === null ? null : JSON.stringify(body),
    });

    const payload = response.status === 204 ? null : await response.json().catch(() => null);

    if (!response.ok) {
        const error = new Error(payload?.message ?? `HTTP ${response.status}`);
        error.status = response.status;
        error.payload = payload;
        throw error;
    }

    return payload;
}

/**
 * Fire-and-forget POST that survives page unload — used for proctoring signals
 * so a student closing the tab still reports the final event.
 */
export function beacon(url, body) {
    const formData = new FormData();
    formData.append('payload', JSON.stringify(body));
    formData.append('_token', csrfToken());

    if (navigator.sendBeacon?.(url, formData)) {
        return true;
    }

    request(url, { method: 'POST', body, keepalive: true }).catch(() => {});

    return false;
}

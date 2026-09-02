const listeners = new Set();
let nextId = 1;

/** Push a transient message. Types map to DaisyUI alert tones. */
export function toast(message, type = 'info', timeout = 4000) {
    const entry = { id: nextId++, message, type, timeout };
    listeners.forEach((listener) => listener(entry));

    return entry.id;
}

export function onToast(listener) {
    listeners.add(listener);

    return () => listeners.delete(listener);
}

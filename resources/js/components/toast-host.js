import { onToast } from '../lib/toast';

export default () => ({
    toasts: [],

    init() {
        onToast((entry) => this.push(entry));
    },

    push(entry) {
        this.toasts.push(entry);

        if (entry.timeout > 0) {
            setTimeout(() => this.dismiss(entry.id), entry.timeout);
        }
    },

    dismiss(id) {
        this.toasts = this.toasts.filter((toast) => toast.id !== id);
    },
});

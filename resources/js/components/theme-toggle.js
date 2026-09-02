const STORAGE_KEY = 'aegis-theme';

export default () => ({
    dark: false,

    init() {
        const stored = localStorage.getItem(STORAGE_KEY);
        this.dark = stored ? stored === 'aegis-dark' : window.matchMedia('(prefers-color-scheme: dark)').matches;
        this.apply();
    },

    apply() {
        const theme = this.dark ? 'aegis-dark' : 'aegis';
        document.documentElement.dataset.theme = theme;
        localStorage.setItem(STORAGE_KEY, theme);
    },
});

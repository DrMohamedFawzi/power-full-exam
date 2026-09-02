/**
 * Alpine components are auto-registered by filename:
 *
 *     resources/js/components/exam-runner.js  →  x-data="examRunner"
 *
 * Drop a file in this directory that default-exports an Alpine data factory and
 * it is wired up — no central registry to edit, so parallel work never collides.
 * Inline `x-data="{ open: false }"` in Blade remains fine for trivial state.
 */
const modules = import.meta.glob('./*.js', { eager: true });

const toCamelCase = (name) => name.replace(/-([a-z])/g, (_, letter) => letter.toUpperCase());

export default function registerComponents(Alpine) {
    for (const [path, module] of Object.entries(modules)) {
        const name = path.replace(/^\.\//, '').replace(/\.js$/, '');

        if (name === 'index' || typeof module.default !== 'function') {
            continue;
        }

        Alpine.data(toCamelCase(name), module.default);
    }
}

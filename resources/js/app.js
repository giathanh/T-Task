import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

async function mountVuePage(id, loader) {
    const el = document.getElementById(id);

    if (!el) {
        return;
    }

    const props = window.__INITIAL_PROPS__?.[id] ?? {};

    const [{ createApp }, { default: component }] = await Promise.all([
        import('vue'),
        loader(),
    ]);

    createApp(component, props).mount(el);
}

mountVuePage('project-show', () => import('./pages/ProjectShow.vue'));

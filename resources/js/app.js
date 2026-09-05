import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

async function mountVuePage(id, loader) {
    const el = document.getElementById(id);

    if (!el) {
        return;
    }

    const props = window.__INITIAL_PROPS__?.[id] ?? {};

    const { createApp } = await import('vue');
    const { default: component } = await loader();

    createApp(component, props).mount(el);
}

mountVuePage('app', () => import('./App.vue'));
mountVuePage('project-show', () => import('./pages/ProjectShow.vue'));

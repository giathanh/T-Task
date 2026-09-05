---
paths:
  - resources/css/app.css
---

# Css

## Material Design 3 tokens live in app.css; project uses Tailwind v4
This project uses Tailwind CSS v4 (the `@tailwindcss/vite` plugin, no `tailwind.config.js`/`postcss.config.js`). Do not run `laravel/breeze`'s Blade installer again without re-checking `vite.config.js` and `package.json` — `breeze:install` overwrites `vite.config.js` (dropping the Vue plugin and font loading) and downgrades Tailwind to v3 (adds `tailwind.config.js`, `postcss.config.js`, `@tailwindcss/forms`, `autoprefixer`).

`resources/css/app.css` defines the full Material Design 3 baseline color-role system (seed #6750A4) as CSS custom properties on `:root`, overridden under `@media (prefers-color-scheme: dark)`, then mapped into Tailwind `@theme` tokens (`--color-primary`, `--color-surface-container-*`, etc.) plus MD3 elevation shadows. Use these utilities (`bg-surface-container-lowest`, `text-on-surface-variant`, `shadow-elevation-1`, ...) for any new UI instead of raw Tailwind grays/indigos, so dark mode keeps working for free.

The floating-label MD3 text field is `<x-md3-text-field name="..." type="..." :label="__('...')" />` (resources/views/components/md3-text-field.blade.php) — it auto-wires `old()`, `$errors`, and the error-state border/label color. Prefer it over the plain `<x-text-input>`/`<x-input-label>` pair for any new form.

---
paths:
  - 'resources/**'
---

# Resources

## Liquid glass surface system
UI uses a "liquid glass" style layered on the MD3 tokens. resources/css/app.css defines `--glass-*` custom properties (light on :root, dark in the media query) and four classes:
- `.glass` — translucent panel (cards, popovers, stat tiles); brings its own 1px border + shadow + backdrop-blur.
- `.glass-strong` — higher opacity / stronger blur for dense content (dropdown & modal panels, auth card).
- `.glass-card` — `.glass` + `border-radius:1.5rem`.
- `.glass-bar` — edge-to-edge chrome (sticky top nav, page header); bottom border only.
- `.glass-scrim` — modal overlay (translucent + slight blur).
- `.glass-button` — tinted translucent button (lit top edge, curved inner shadow, hover specular sweep). Pair with an accent modifier `.glass-button--primary` / `.glass-button--error` / `.glass-button--secondary` (the last is used for the active tab in `<x-project-nav>`, whose container is `.glass-strong`); keep layout utilities (`h-10`, `rounded-full`, `px-*`, `text-on-primary`/`text-on-error`) on the element. `<x-primary-button>` / `<x-danger-button>` already use it. Do NOT also put `bg-primary`/`bg-error`/`shadow-elevation-*`/`hover:shadow-*` on it. `<x-secondary-button>` stays plain `.glass`.
Fallbacks: opaque `surface-container-lowest` / accent tint under `@supports not (backdrop-filter)` and `prefers-reduced-transparency`; hover sweep drops under `prefers-reduced-motion`.
`body` paints a fixed radial-gradient mesh (primary/tertiary/secondary) that the glass refracts — do NOT put an opaque `bg-*` on the layout wrapper or it hides the mesh. For new panels prefer `glass`/`glass-card` over `bg-surface-container-lowest shadow-elevation-1`. Don't stack `ring-1`/`border`/`shadow-elevation-*` on a `.glass*` element — it already has them.

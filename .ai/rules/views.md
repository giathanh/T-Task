---
paths:
  - 'resources/views/**'
---

# Views

## Project screens: sub-nav bar + wiki Markdown rendering
Every project-scoped screen puts `<x-project-nav :project="$project" active="<key>" />` inside the header slot, below the h2 (see resources/views/projects/show.blade.php). `:project` accepts the model or the `['id' => ...]` array. Tabs: overview, activity, issues, wiki, calendar — activity/calendar have no routes yet and render as disabled spans; wire them up as their routes land.

Wiki page bodies are Markdown. Render only via `App\Support\Markdown::toHtml()` (GithubFlavoredMarkdownConverter with html_input=escape + allow_unsafe_links=false) and output with `{!! !!}` inside a `.wiki-content` wrapper — that class (defined in resources/css/app.css) restyles the raw tags Tailwind preflight strips. Never pass user Markdown straight to another converter or to `{!! !!}` without this class.

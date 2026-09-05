<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreWikiPageRequest;
use App\Http\Requests\UpdateWikiPageRequest;
use App\Models\Project;
use App\Models\WikiPage;
use App\Support\Markdown;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;

class WikiController extends Controller
{
    /**
     * List every wiki page belonging to the project.
     */
    public function index(Project $project): View
    {
        Gate::authorize('viewAny', [WikiPage::class, $project]);

        $pages = $project->wikiPages()
            ->with('editor:id,name')
            ->orderBy('title')
            ->get(['id', 'title', 'slug', 'updated_at', 'updated_by']);

        return view('projects.wiki.index', [
            'project' => $project,
            'pages' => $pages,
        ]);
    }

    /**
     * Show the form for creating a new wiki page.
     */
    public function create(Project $project): View
    {
        Gate::authorize('create', [WikiPage::class, $project]);

        return view('projects.wiki.create', [
            'project' => $project,
        ]);
    }

    /**
     * Store a newly created wiki page.
     */
    public function store(StoreWikiPageRequest $request, Project $project): RedirectResponse
    {
        $validated = $request->validated();

        $page = $project->wikiPages()->create([
            'title' => $validated['title'],
            'slug' => $this->uniqueSlug($project, $validated['title']),
            'content' => $validated['content'] ?? null,
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('projects.wiki.show', [$project, $page])
            ->with('status', 'wiki-page-created');
    }

    /**
     * Display a single wiki page with its rendered Markdown body.
     */
    public function show(Project $project, WikiPage $wikiPage): View
    {
        Gate::authorize('view', $wikiPage);

        $wikiPage->load(['creator:id,name', 'editor:id,name']);

        return view('projects.wiki.show', [
            'project' => $project,
            'page' => $wikiPage,
            'contentHtml' => Markdown::toHtml($wikiPage->content),
        ]);
    }

    /**
     * Show the form for editing an existing wiki page.
     */
    public function edit(Project $project, WikiPage $wikiPage): View
    {
        Gate::authorize('update', $wikiPage);

        return view('projects.wiki.edit', [
            'project' => $project,
            'page' => $wikiPage,
        ]);
    }

    /**
     * Update the given wiki page.
     */
    public function update(UpdateWikiPageRequest $request, Project $project, WikiPage $wikiPage): RedirectResponse
    {
        $validated = $request->validated();

        $wikiPage->update([
            'title' => $validated['title'],
            'slug' => $wikiPage->title === $validated['title']
                ? $wikiPage->slug
                : $this->uniqueSlug($project, $validated['title'], $wikiPage),
            'content' => $validated['content'] ?? null,
            'updated_by' => $request->user()->id,
        ]);

        return redirect()
            ->route('projects.wiki.show', [$project, $wikiPage])
            ->with('status', 'wiki-page-updated');
    }

    /**
     * Delete the given wiki page.
     */
    public function destroy(Project $project, WikiPage $wikiPage): RedirectResponse
    {
        Gate::authorize('delete', $wikiPage);

        $wikiPage->delete();

        return redirect()
            ->route('projects.wiki.index', $project)
            ->with('status', 'wiki-page-deleted');
    }

    /**
     * Build a project-unique slug from the given title.
     */
    private function uniqueSlug(Project $project, string $title, ?WikiPage $ignore = null): string
    {
        $base = Str::slug($title) ?: 'trang';
        $slug = $base;
        $suffix = 2;

        while (
            $project->wikiPages()
                ->where('slug', $slug)
                ->when($ignore, fn ($query) => $query->whereKeyNot($ignore->id))
                ->exists()
        ) {
            $slug = $base.'-'.$suffix++;
        }

        return $slug;
    }
}

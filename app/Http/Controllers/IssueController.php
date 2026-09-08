<?php

namespace App\Http\Controllers;

use App\Enums\IssuePriority;
use App\Enums\IssueSeverity;
use App\Enums\IssueStatus;
use App\Enums\IssueType;
use App\Http\Requests\StoreIssueRequest;
use App\Http\Requests\UpdateIssueRequest;
use App\Models\Issue;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class IssueController extends Controller
{
    /**
     * List the project's issues with optional type, status, assignee and title filters.
     */
    public function index(Request $request, Project $project): View
    {
        Gate::authorize('viewAny', [Issue::class, $project]);

        $filters = $request->validate([
            'type' => ['nullable', Rule::enum(IssueType::class)],
            'status' => ['nullable', Rule::enum(IssueStatus::class)],
            'assignee' => ['nullable', 'integer'],
            'q' => ['nullable', 'string', 'max:255'],
        ]);

        $hierarchy = $project->issues()
            ->when($filters['type'] ?? null, fn ($query, $type) => $query->where('type', $type))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['assignee'] ?? null, fn ($query, $assigneeId) => $query->where('assignee_id', $assigneeId))
            ->when($request->filled('q'), fn ($query) => $query->whereLike('title', '%'.$filters['q'].'%'))
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get(['id', 'parent_id']);

        $groups = $this->issueGroups($hierarchy);
        $page = LengthAwarePaginator::resolveCurrentPage();
        $depths = $groups->forPage($page, 20)->reduce(fn (array $depths, array $group): array => $depths + $group, []);
        $pageIssues = $project->issues()
            ->whereKey(array_keys($depths))
            ->with('assignee:id,name')
            ->get(['id', 'project_id', 'parent_id', 'title', 'type', 'status', 'priority', 'assignee_id', 'due_date', 'percent_done'])
            ->keyBy('id');
        $issues = (new LengthAwarePaginator(
            new EloquentCollection(array_map(fn (int $id): Issue => $pageIssues[$id], array_keys($depths))),
            $groups->count(),
            20,
            $page,
            ['path' => $request->url()],
        ))->withQueryString();

        $members = $project->members()
            ->orderBy('name')
            ->get(['users.id', 'users.name'])
            ->map(fn (User $member) => ['id' => $member->id, 'name' => $member->name]);

        return view('issues.index', [
            'project' => $project->only(['id', 'name']),
            'issues' => $issues,
            'issueCount' => $hierarchy->count(),
            'depths' => $depths,
            'members' => $members,
            'filters' => $filters,
            'newIssueUrl' => route('issues.create', $project),
        ]);
    }

    /**
     * Keep each matching issue's descendants together, tolerating missing parents and cycles.
     *
     * @param  Collection<int, Issue>  $hierarchy
     * @return Collection<int, array<int, int>>
     */
    private function issueGroups(Collection $hierarchy): Collection
    {
        $byId = $hierarchy->keyBy('id');
        $children = $hierarchy->groupBy('parent_id');
        $roots = $hierarchy->filter(fn (Issue $issue): bool => ! $byId->has($issue->parent_id));
        $visited = [];
        $groups = collect();

        foreach ($roots->concat($hierarchy) as $root) {
            if (isset($visited[$root->id])) {
                continue;
            }

            $group = [];
            $stack = [[$root->id, 0]];

            while ($stack !== []) {
                [$id, $depth] = array_pop($stack);

                if (isset($visited[$id])) {
                    continue;
                }

                $visited[$id] = true;
                $group[$id] = $depth;

                foreach ($children->get($id, collect())->reverse() as $child) {
                    $stack[] = [$child->id, $depth + 1];
                }
            }

            $groups->push($group);
        }

        return $groups;
    }

    /**
     * Show the form for creating a new issue within the project.
     */
    public function create(Project $project): View
    {
        Gate::authorize('create', [Issue::class, $project]);

        $members = $project->members()
            ->orderBy('name')
            ->get(['users.id', 'users.name'])
            ->map(fn (User $member) => ['id' => $member->id, 'name' => $member->name]);

        $parentOptions = $project->issues()
            ->orderBy('title')
            ->get(['id', 'title', 'type']);

        return view('issues.create', [
            'project' => $project->only(['id', 'name']),
            'members' => $members,
            'parentOptions' => $parentOptions,
            'trackerOptions' => IssueType::cases(),
            'statusOptions' => IssueStatus::cases(),
            'priorityOptions' => IssuePriority::cases(),
            'severityOptions' => IssueSeverity::cases(),
        ]);
    }

    /**
     * Show a single issue with its people, scheduling and related issues.
     */
    public function show(Project $project, Issue $issue): View
    {
        Gate::authorize('view', $issue);

        $issue->load([
            'assignee:id,name',
            'reporter:id,name',
            'parent:id,title,type',
            'children' => fn ($query) => $query->orderBy('title')->select(['id', 'parent_id', 'title', 'type', 'status', 'percent_done']),
            'watchers:id,name',
            'attachments.uploader:id,name',
        ]);

        $notes = $issue->notes()->with('author:id,name')->orderByDesc('id')->paginate(20, ['*'], 'notes_page')->fragment('notes');

        return view('issues.show', [
            'project' => $project->only(['id', 'name']),
            'issue' => $issue,
            'notes' => $notes,
        ]);
    }

    /**
     * Show the form for editing the given issue.
     */
    public function edit(Project $project, Issue $issue): View
    {
        Gate::authorize('update', $issue);

        $issue->load('watchers:id');

        $members = $project->members()
            ->orderBy('name')
            ->get(['users.id', 'users.name'])
            ->map(fn (User $member) => ['id' => $member->id, 'name' => $member->name]);

        $parentOptions = $project->issues()
            ->whereKeyNot($issue->id)
            ->orderBy('title')
            ->get(['id', 'title', 'type']);

        return view('issues.edit', [
            'project' => $project->only(['id', 'name']),
            'issue' => $issue,
            'members' => $members,
            'parentOptions' => $parentOptions,
            'trackerOptions' => IssueType::cases(),
            'statusOptions' => IssueStatus::cases(),
            'priorityOptions' => IssuePriority::cases(),
            'severityOptions' => IssueSeverity::cases(),
        ]);
    }

    /**
     * Update the given issue.
     */
    public function update(UpdateIssueRequest $request, Project $project, Issue $issue): RedirectResponse
    {
        $validated = $request->validated();

        $issue->update([
            ...Arr::except($validated, ['watchers', 'attachments', 'is_private', 'percent_done', 'severity']),
            'severity' => $validated['type'] === IssueType::Bug->value ? ($validated['severity'] ?? null) : null,
            'percent_done' => $validated['percent_done'] ?? 0,
            'is_private' => $request->boolean('is_private'),
        ]);

        $issue->watchers()->sync($validated['watchers'] ?? []);

        foreach ($request->file('attachments', []) as $file) {
            $path = $file->store('issue-attachments/'.$issue->id);

            $issue->attachments()->create([
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'uploaded_by' => $request->user()->id,
            ]);
        }

        return redirect()
            ->route('issues.show', [$project, $issue])
            ->with('status', 'issue-updated');
    }

    /**
     * Store a newly created issue in the project.
     */
    public function store(StoreIssueRequest $request, Project $project): RedirectResponse
    {
        $validated = $request->validated();

        $issue = $project->issues()->create([
            ...Arr::except($validated, ['watchers', 'attachments', 'is_private', 'percent_done', 'severity']),
            'severity' => $validated['type'] === IssueType::Bug->value ? ($validated['severity'] ?? null) : null,
            'percent_done' => $validated['percent_done'] ?? 0,
            'is_private' => $request->boolean('is_private'),
            'created_by' => $request->user()->id,
        ]);

        if (! empty($validated['watchers'])) {
            $issue->watchers()->sync($validated['watchers']);
        }

        foreach ($request->file('attachments', []) as $file) {
            $path = $file->store('issue-attachments/'.$issue->id);

            $issue->attachments()->create([
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'uploaded_by' => $request->user()->id,
            ]);
        }

        return redirect()->route('projects.show', $project)->with('status', 'issue-created');
    }
}

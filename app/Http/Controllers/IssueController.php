<?php

namespace App\Http\Controllers;

use App\Enums\IssuePriority;
use App\Enums\IssueSeverity;
use App\Enums\IssueStatus;
use App\Enums\IssueType;
use App\Http\Requests\StoreIssueRequest;
use App\Models\Issue;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
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

        $issues = $project->issues()
            ->with('assignee:id,name')
            ->when($filters['type'] ?? null, fn ($query, $type) => $query->where('type', $type))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['assignee'] ?? null, fn ($query, $assigneeId) => $query->where('assignee_id', $assigneeId))
            ->when($filters['q'] ?? null, fn ($query, $term) => $query->where('title', 'like', '%'.$term.'%'))
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $members = $project->members()
            ->orderBy('name')
            ->get(['users.id', 'users.name'])
            ->map(fn (User $member) => ['id' => $member->id, 'name' => $member->name]);

        return view('issues.index', [
            'project' => $project->only(['id', 'name']),
            'issues' => $issues,
            'members' => $members,
            'filters' => $filters,
            'newIssueUrl' => route('issues.create', $project),
        ]);
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
     * Store a newly created issue in the project.
     */
    public function store(StoreIssueRequest $request, Project $project): RedirectResponse
    {
        $validated = $request->validated();

        $issue = $project->issues()->create([
            ...Arr::except($validated, ['watchers', 'attachments', 'is_private', 'percent_done']),
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

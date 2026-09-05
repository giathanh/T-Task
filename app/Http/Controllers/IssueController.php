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
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class IssueController extends Controller
{
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

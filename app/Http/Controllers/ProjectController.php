<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ProjectController extends Controller
{
    /**
     * Display the project's info screen: profile, issue counts, and members.
     */
    public function show(Request $request, Project $project): View
    {
        Gate::authorize('view', $project);

        $project->load('creator');

        $members = $project->members()
            ->orderBy('name')
            ->get(['users.id', 'users.name', 'users.email'])
            ->map(fn (User $member) => [
                'id' => $member->id,
                'name' => $member->name,
                'email' => $member->email,
                'role' => $member->pivot->role,
            ]);

        return view('projects.show', [
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'description' => $project->description,
                'status' => $project->status->value,
                'dueDate' => $project->due_date?->toDateString(),
                'createdAt' => $project->created_at->toDateString(),
                'creator' => $project->creator?->only(['id', 'name']),
            ],
            'issueStats' => $project->issueStats(),
            'members' => $members->values()->all(),
            'currentUserRole' => $members->firstWhere('id', $request->user()->id)['role'] ?? null,
            'newIssueUrl' => route('issues.create', $project),
        ]);
    }
}

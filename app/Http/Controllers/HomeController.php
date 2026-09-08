<?php

namespace App\Http\Controllers;

use App\Enums\IssueStatus;
use App\Models\Issue;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::enum(IssueStatus::class)],
            'project' => ['nullable', 'integer', 'min:1'],
        ]);

        $assigned = Issue::query()
            ->where('assignee_id', $request->user()->id)
            ->whereHas('project.members', fn ($query) => $query->whereKey($request->user()->id));

        $today = today();
        $stats = [
            'total' => (clone $assigned)->count(),
            'in_progress' => (clone $assigned)->where('status', IssueStatus::InProgress)->count(),
            'overdue' => (clone $assigned)->where('status', '!=', IssueStatus::Done)->where('due_date', '<', $today)->count(),
            'done' => (clone $assigned)->where('status', IssueStatus::Done)->count(),
        ];

        $issues = $assigned
            ->select(['id', 'project_id', 'title', 'type', 'status', 'priority', 'due_date', 'percent_done'])
            ->with('project:id,name')
            ->when(isset($filters['q']) && $filters['q'] !== '', fn ($query) => $query->where('title', 'like', '%'.$filters['q'].'%'))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['project'] ?? null, fn ($query, $project) => $query->where('project_id', $project))
            ->orderByRaw('CASE WHEN status = ? THEN 1 ELSE 0 END', [IssueStatus::Done->value])
            ->orderByRaw('CASE WHEN due_date IS NULL THEN 1 ELSE 0 END')
            ->orderBy('due_date')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('dashboard', [
            'issues' => $issues,
            'stats' => $stats,
            'filters' => $filters,
            'today' => $today,
            'projects' => $request->user()->projects()->orderBy('name')->get(['projects.id', 'projects.name']),
        ]);
    }
}

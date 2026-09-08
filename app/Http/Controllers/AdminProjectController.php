<?php

namespace App\Http\Controllers;

use App\Enums\ProjectRole;
use App\Http\Requests\SaveAdminProjectRequest;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminProjectController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['search' => ['nullable', 'string', 'max:255']]);
        $search = $filters['search'] ?? '';
        $projects = Project::query()->withCount('members')
            ->where('name', 'like', '%'.$search.'%')->orderBy('name')->orderBy('id')
            ->paginate(15)->withQueryString();

        return view('admin.projects.index', compact('projects', 'search'));
    }

    public function create(): View
    {
        return view('admin.projects.form', ['project' => new Project]);
    }

    public function store(SaveAdminProjectRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $project = Project::create([...$request->validated(), 'created_by' => $request->user()->id]);
            $project->members()->attach($request->user(), ['role' => ProjectRole::Admin->value]);
        });

        return to_route('admin.projects.index')->with('status', 'Đã tạo dự án.');
    }

    public function edit(Project $project): View
    {
        return view('admin.projects.form', compact('project'));
    }

    public function update(SaveAdminProjectRequest $request, Project $project): RedirectResponse
    {
        $project->update($request->validated());

        return to_route('admin.projects.index')->with('status', 'Đã cập nhật dự án.');
    }
}

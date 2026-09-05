<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ActivityController extends Controller
{
    public function __invoke(Request $request, Project $project): View
    {
        Gate::authorize('view', $project);

        $filters = $request->validate([
            'type' => ['nullable', 'in:task,bug,wiki'],
        ]);
        $type = $filters['type'] ?? null;

        $issues = $project->issues()->select(['id', 'title', 'type', 'created_by', 'created_at'])
            ->selectRaw('NULL as slug')->toBase();
        $wiki = $project->wikiPages()->select(['id', 'title'])
            ->selectRaw("'wiki' as type")
            ->addSelect(['created_by', 'created_at', 'slug'])->toBase();
        $query = DB::query()->fromSub($issues->unionAll($wiki), 'activity');
        $counts = (clone $query)->selectRaw('type, count(*) as total')->groupBy('type')->pluck('total', 'type');

        $activities = $query->leftJoin('users', 'users.id', '=', 'activity.created_by')
            ->select(['activity.*', 'users.name as author'])
            ->when($type, fn ($query) => $query->where('activity.type', $type))
            ->orderByDesc('activity.created_at')->orderBy('activity.type')->orderByDesc('activity.id')
            ->paginate(20)->withQueryString();

        $activities->through(function (object $activity) use ($project): object {
            $activity->date = Carbon::parse($activity->created_at);
            $activity->url = $activity->type === 'wiki'
                ? route('projects.wiki.show', [$project, $activity->slug])
                : route('issues.show', [$project, $activity->id]);

            return $activity;
        });

        return view('projects.activity', compact('project', 'activities', 'counts', 'type'));
    }
}

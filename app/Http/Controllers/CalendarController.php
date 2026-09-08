<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CalendarController extends Controller
{
    public function __invoke(Request $request, Project $project): View
    {
        Gate::authorize('view', $project);

        $filters = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
        ]);
        $month = isset($filters['month'])
            ? CarbonImmutable::createFromFormat('!Y-m', $filters['month'])
            : CarbonImmutable::today()->startOfMonth();
        $start = $month->startOfWeek();
        $end = $month->endOfMonth()->endOfWeek();
        $range = [$start->toDateString(), $end->toDateString()];

        $issues = $project->issues()
            ->where(function (Builder $query) use ($range): void {
                $query->whereBetween('start_date', $range)->orWhereBetween('due_date', $range);
            })
            ->orderBy('id')
            ->get(['id', 'project_id', 'title', 'type', 'status', 'start_date', 'due_date']);

        $events = [];
        foreach ($issues as $issue) {
            foreach (['start_date' => 'Bắt đầu', 'due_date' => 'Đến hạn'] as $field => $label) {
                $date = $issue->{$field}?->toDateString();
                if ($date !== null && $date >= $range[0] && $date <= $range[1]) {
                    $events[$date][] = ['issue' => $issue, 'label' => $label];
                }
            }
        }

        $days = [];
        for ($day = $start; $day <= $end; $day = $day->addDay()) {
            $days[] = $day;
        }

        return view('projects.calendar', compact('project', 'month', 'days', 'events'));
    }
}

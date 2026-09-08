<?php

namespace Tests\Feature;

use App\Enums\IssueStatus;
use App\Models\Issue;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_home_only_displays_assignments_in_accessible_projects(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $project->members()->attach($user, ['role' => 'member']);
        $task = Issue::factory()->for($project)->task()->status(IssueStatus::Open)->create(['assignee_id' => $user->id, 'title' => '<script>alert(1)</script>']);
        $bug = Issue::factory()->for($project)->bug()->status(IssueStatus::InProgress)->create(['assignee_id' => $user->id]);
        $other = Issue::factory()->for($project)->create(['assignee_id' => User::factory()]);
        $unassigned = Issue::factory()->for($project)->create();
        $inaccessible = Issue::factory()->create(['assignee_id' => $user->id]);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertSee($task->title)
            ->assertDontSee($task->title, false)
            ->assertSee($bug->title)
            ->assertSee(route('issues.show', [$project, $task]))
            ->assertDontSee($other->title)
            ->assertDontSee($unassigned->title)
            ->assertDontSee($inaccessible->title)
            ->assertViewHas('stats', fn ($stats) => $stats['total'] === 2);
    }

    public function test_summary_and_order_distinguish_overdue_today_undated_and_completed_work(): void
    {
        $this->travelTo(now()->setDate(2026, 9, 5)->setTime(14, 0));
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $project->members()->attach($user, ['role' => 'member']);
        $factory = Issue::factory()->for($project)->state(['assignee_id' => $user->id]);
        $undated = $factory->status(IssueStatus::Open)->create(['title' => 'Undated work']);
        $done = $factory->status(IssueStatus::Done)->create(['title' => 'Completed work', 'due_date' => '2026-09-01']);
        $today = $factory->status(IssueStatus::InProgress)->create(['title' => 'Due today', 'due_date' => '2026-09-05']);
        $late = $factory->status(IssueStatus::Open)->create(['title' => 'Overdue work', 'due_date' => '2026-09-04']);

        $this->actingAs($user)->get(route('dashboard'))
            ->assertSeeInOrder([$late->title, $today->title, $undated->title, $done->title])
            ->assertViewHas('stats', ['total' => 4, 'in_progress' => 1, 'overdue' => 1, 'done' => 1]);
    }

    public function test_filters_combine_and_keep_overall_summary(): void
    {
        $user = User::factory()->create();
        $projects = Project::factory()->count(2)->create();
        foreach ($projects as $project) {
            $project->members()->attach($user, ['role' => 'member']);
        }
        $factory = Issue::factory()->state(['assignee_id' => $user->id]);
        $match = $factory->for($projects[0])->status(IssueStatus::Open)->create(['title' => 'Search target']);
        $factory->for($projects[0])->status(IssueStatus::Done)->create(['title' => 'Search completed']);
        $factory->for($projects[0])->status(IssueStatus::Open)->create(['title' => 'Different title']);
        $factory->for($projects[1])->status(IssueStatus::Open)->create(['title' => 'Search elsewhere']);

        $this->actingAs($user)->get(route('dashboard', ['q' => 'Search', 'status' => 'open', 'project' => $projects[0]->id]))
            ->assertSee($match->title)
            ->assertDontSee('Search completed')
            ->assertDontSee('Different title')
            ->assertDontSee('Search elsewhere')
            ->assertViewHas('stats', fn ($stats) => $stats['total'] === 4);
    }

    public function test_empty_home_explains_assignments(): void
    {
        $this->actingAs(User::factory()->create())->get(route('dashboard'))
            ->assertSee('Bạn chưa được giao công việc nào')
            ->assertViewHas('stats', ['total' => 0, 'in_progress' => 0, 'overdue' => 0, 'done' => 0]);
    }

    public function test_unmatched_filter_shows_filtered_empty_state(): void
    {
        $this->actingAs(User::factory()->create())->get(route('dashboard', ['q' => 'missing']))
            ->assertSee('Không tìm thấy công việc phù hợp')
            ->assertSee('Xoá lọc');
    }

    public function test_invalid_filters_return_validation_errors(): void
    {
        $this->actingAs(User::factory()->create())->from(route('dashboard'))
            ->get(route('dashboard', ['status' => 'invalid', 'project' => -1, 'q' => str_repeat('a', 256)]))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHasErrors(['status', 'project', 'q']);
    }

    public function test_assignments_are_paginated_and_filters_are_preserved(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $project->members()->attach($user, ['role' => 'member']);
        $issues = Issue::factory()->for($project)->status(IssueStatus::Open)->count(16)->create(['assignee_id' => $user->id]);

        $this->actingAs($user)->get(route('dashboard', ['status' => 'open', 'page' => 2]))
            ->assertSee($issues->first()->title)
            ->assertDontSee($issues->last()->title)
            ->assertViewHas('issues', fn ($page) => $page->total() === 16 && $page->count() === 1 && str_contains($page->previousPageUrl(), 'status=open'));
    }
}

<?php

namespace Tests\Feature;

use App\Models\Issue;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalendarControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('projects.calendar', Project::factory()->create()))->assertRedirect(route('login'));
    }

    public function test_non_members_cannot_view_calendar(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('projects.calendar', Project::factory()->create()))->assertForbidden();
    }

    public function test_calendar_shows_start_and_due_milestones_with_safe_links_and_project_isolation(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $project->members()->attach($user, ['role' => 'member']);
        $task = Issue::factory()->task()->for($project)->create(['title' => '<script>task</script>', 'start_date' => '2026-09-01', 'due_date' => '2026-09-30']);
        $bug = Issue::factory()->bug()->for($project)->create(['start_date' => '2026-08-31', 'due_date' => '2026-10-04']);
        $undated = Issue::factory()->for($project)->create();
        $outside = Issue::factory()->for($project)->create(['start_date' => '2026-08-30', 'due_date' => '2026-10-05']);
        $other = Issue::factory()->create(['due_date' => '2026-09-15']);

        $this->actingAs($user)->get(route('projects.calendar', ['project' => $project, 'month' => '2026-09']))
            ->assertSee($task->title)->assertDontSee($task->title, false)
            ->assertSee($bug->title)->assertSee(route('issues.show', [$project, $task]))
            ->assertDontSee($undated->title)->assertDontSee($outside->title)->assertDontSee($other->title)
            ->assertViewHas('events', fn ($events): bool => $events['2026-09-01'][0]['issue']->is($task)
                && $events['2026-09-01'][0]['label'] === 'Bắt đầu'
                && $events['2026-09-30'][0]['label'] === 'Đến hạn'
                && $events['2026-08-31'][0]['issue']->is($bug)
                && $events['2026-10-04'][0]['issue']->is($bug)
            );
    }

    public function test_calendar_defaults_to_current_month_and_links_across_years(): void
    {
        $this->travelTo(now()->setDate(2026, 1, 15));
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $project->members()->attach($user, ['role' => 'member']);

        $this->actingAs($user)->get(route('projects.calendar', $project))
            ->assertSee('Tháng 01 / 2026')
            ->assertSee(route('projects.calendar', ['project' => $project, 'month' => '2025-12']))
            ->assertSee(route('projects.calendar', ['project' => $project, 'month' => '2026-02']))
            ->assertSee('aria-current="date"', false)
            ->assertSee('Chưa có mốc công việc trong khoảng lịch này.');
    }

    public function test_leap_day_and_single_date_issues_appear_and_same_day_keeps_both_milestones(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $project->members()->attach($user, ['role' => 'member']);
        $startOnly = Issue::factory()->for($project)->create(['start_date' => '2028-02-01']);
        $dueOnly = Issue::factory()->for($project)->create(['due_date' => '2028-02-29']);
        $sameDay = Issue::factory()->for($project)->create(['start_date' => '2028-02-15', 'due_date' => '2028-02-15']);

        $this->actingAs($user)->get(route('projects.calendar', ['project' => $project, 'month' => '2028-02']))
            ->assertSee($startOnly->title)->assertSee($dueOnly->title)->assertSee($sameDay->title)
            ->assertViewHas('events', fn ($events): bool => count($events['2028-02-15']) === 2)
            ->assertViewHas('days', fn ($days): bool => count($days) === 35 && $days[0]->toDateString() === '2028-01-31' && $days[34]->toDateString() === '2028-03-05');
    }

    public function test_invalid_month_is_rejected(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $project->members()->attach($user, ['role' => 'member']);

        $this->actingAs($user)->getJson(route('projects.calendar', ['project' => $project, 'month' => '2026-13']))
            ->assertUnprocessable()->assertJsonValidationErrors('month');
    }

    public function test_overview_links_to_calendar(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $project->members()->attach($user, ['role' => 'member']);

        $this->actingAs($user)->get(route('projects.show', $project))->assertSee(route('projects.calendar', $project));
    }
}

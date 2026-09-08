<?php

namespace Tests\Feature;

use App\Models\Issue;
use App\Models\Project;
use App\Models\User;
use App\Models\WikiPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('projects.activity', Project::factory()->create()))->assertRedirect(route('login'));
    }

    public function test_non_members_cannot_view_activity(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('projects.activity', Project::factory()->create()))->assertForbidden();
    }

    public function test_members_see_only_project_content_in_newest_first_order_and_safe_links(): void
    {
        $user = User::factory()->create(['name' => '<script>author</script>']);
        $project = Project::factory()->create();
        $project->members()->attach($user, ['role' => 'member']);
        $task = Issue::factory()->task()->for($project)->create(['title' => '<script>task</script>', 'created_by' => $user->id, 'created_at' => '2026-09-01 09:00:00']);
        $wiki = WikiPage::factory()->for($project)->create(['title' => 'Latest wiki', 'created_at' => '2026-09-02 09:00:00']);
        $other = Issue::factory()->create(['title' => 'Other project issue']);

        $this->actingAs($user)->get(route('projects.activity', $project))
            ->assertSeeInOrder([$wiki->title, $task->title])
            ->assertSee(route('issues.show', [$project, $task]))
            ->assertSee(route('projects.wiki.show', [$project, $wiki]))
            ->assertSee($user->name)->assertDontSee($user->name, false)
            ->assertDontSee($task->title, false)->assertDontSee($other->title)
            ->assertSee('Người tạo chưa xác định');
    }

    public function test_type_filters_keep_project_totals_and_exclude_other_types(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $project->members()->attach($user, ['role' => 'member']);
        $task = Issue::factory()->task()->for($project)->create();
        $bug = Issue::factory()->bug()->for($project)->create();
        $wiki = WikiPage::factory()->for($project)->create();

        $this->actingAs($user)->get(route('projects.activity', ['project' => $project, 'type' => 'bug']))
            ->assertSee($bug->title)->assertDontSee($task->title)->assertDontSee($wiki->title)
            ->assertViewHas('counts', fn ($counts): bool => $counts->sum() === 3);
    }

    public function test_empty_wiki_filter_offers_creation_link(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $project->members()->attach($user, ['role' => 'member']);

        $this->actingAs($user)->get(route('projects.activity', ['project' => $project, 'type' => 'wiki']))
            ->assertSee('Chưa có hoạt động Wiki')->assertSee(route('projects.wiki.create', $project));
    }

    public function test_invalid_type_is_rejected(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $project->members()->attach($user, ['role' => 'member']);

        $this->actingAs($user)->getJson(route('projects.activity', ['project' => $project, 'type' => 'invalid']))
            ->assertUnprocessable()->assertJsonValidationErrors('type');
    }

    public function test_pagination_retains_type_and_uses_stable_order_for_equal_timestamps(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $project->members()->attach($user, ['role' => 'member']);
        $issues = Issue::factory()->task()->for($project)->count(21)->create(['created_at' => '2026-09-01 09:00:00']);

        $this->actingAs($user)->get(route('projects.activity', ['project' => $project, 'type' => 'task']))
            ->assertSee($issues->last()->title)->assertDontSee($issues->first()->title)
            ->assertViewHas('activities', fn ($activities): bool => str_contains($activities->nextPageUrl(), 'type=task'));
        $this->get(route('projects.activity', ['project' => $project, 'type' => 'task', 'page' => 2]))
            ->assertSee($issues->first()->title)->assertDontSee($issues->last()->title);
    }

    public function test_overview_links_to_activity(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $project->members()->attach($user, ['role' => 'member']);

        $this->actingAs($user)->get(route('projects.show', $project))->assertSee(route('projects.activity', $project));
    }
}

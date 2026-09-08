<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_header_lists_only_memberships_on_pages_outside_home(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['name' => 'Member project']);
        $other = Project::factory()->create(['name' => 'Hidden project']);
        $removed = Project::factory()->create(['name' => 'Former project']);
        $project->members()->attach($user, ['role' => 'member']);
        $removed->members()->attach($user, ['role' => 'member']);
        $removed->members()->detach($user);

        $this->actingAs($user)->get(route('profile.edit'))
            ->assertSee('Dự án của bạn')
            ->assertSee($project->name)
            ->assertSee(route('projects.show', $project))
            ->assertDontSee($other->name)
            ->assertDontSee($removed->name);
    }

    public function test_header_shows_empty_membership_message(): void
    {
        $this->actingAs(User::factory()->create())->get(route('profile.edit'))
            ->assertSee('Bạn chưa tham gia dự án nào.');
    }

    public function test_project_page_marks_current_project_and_escapes_its_name(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create(['name' => '<script>alert(1)</script>']);
        $project->members()->attach($user, ['role' => 'member']);

        $this->actingAs($user)->get(route('projects.show', $project))
            ->assertSee('aria-current="page"', false)
            ->assertSee($project->name)
            ->assertDontSee($project->name, false);
    }
}

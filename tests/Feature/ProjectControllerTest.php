<?php

namespace Tests\Feature;

use App\Enums\ProjectRole;
use App\Models\Issue;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $project = Project::factory()->create();

        $response = $this->get(route('projects.show', $project));

        $response->assertRedirect(route('login'));
    }

    public function test_user_who_is_not_a_project_member_is_forbidden(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        $response = $this->actingAs($user)->get(route('projects.show', $project));

        $response->assertForbidden();
    }

    public function test_unknown_project_returns_404(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/projects/999999');

        $response->assertNotFound();
    }

    public function test_member_can_view_project_info_with_issue_counts_and_members(): void
    {
        $member = User::factory()->create(['name' => 'Nguyen Van A']);
        $leader = User::factory()->create(['name' => 'Tran Van B']);
        $project = Project::factory()->create(['name' => 'Website Redesign']);

        $project->members()->attach($member, ['role' => ProjectRole::Member->value]);
        $project->members()->attach($leader, ['role' => ProjectRole::Leader->value]);

        Issue::factory()->for($project)->task()->count(3)->create();
        Issue::factory()->for($project)->bug()->count(1)->create();

        $response = $this->actingAs($member)->get(route('projects.show', $project));

        $response->assertOk();
        $response->assertSee('Website Redesign');
        $response->assertViewHas('project', fn (array $data): bool => $data['name'] === 'Website Redesign');
        $response->assertViewHas('issueStats', fn (array $stats): bool => $stats['task']['total'] === 3 && $stats['bug']['total'] === 1);
        $response->assertViewHas('currentUserRole', 'member');
        $response->assertViewHas('members', function (array $members) use ($leader): bool {
            return collect($members)->contains(
                fn (array $member): bool => $member['id'] === $leader->id && $member['role'] === 'leader'
            );
        });
    }
}

<?php

namespace Tests\Feature;

use App\Enums\ProjectRole;
use App\Models\Project;
use App\Models\User;
use App\Models\WikiPage;
use App\Policies\WikiPagePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class WikiPagePolicyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{ProjectRole}>
     */
    public static function projectRoles(): array
    {
        return collect(ProjectRole::cases())
            ->mapWithKeys(fn (ProjectRole $role): array => [$role->value => [$role]])
            ->all();
    }

    /**
     * @return array<string, array{ProjectRole}>
     */
    public static function rolesThatCannotDelete(): array
    {
        return ['member' => [ProjectRole::Member]];
    }

    /**
     * @return array<string, array{ProjectRole}>
     */
    public static function rolesThatCanDelete(): array
    {
        return [
            'admin' => [ProjectRole::Admin],
            'leader' => [ProjectRole::Leader],
        ];
    }

    #[DataProvider('projectRoles')]
    public function test_allows_view_and_create_and_update_for_a_member_with_any_role(ProjectRole $role): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $project->members()->attach($user, ['role' => $role->value]);
        $page = WikiPage::factory()->for($project)->create();

        $policy = new WikiPagePolicy;

        $this->assertTrue($policy->viewAny($user, $project));
        $this->assertTrue($policy->view($user, $page));
        $this->assertTrue($policy->create($user, $project));
        $this->assertTrue($policy->update($user, $page));
    }

    public function test_forbids_every_ability_for_a_user_who_is_not_a_project_member(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $page = WikiPage::factory()->for($project)->create();

        $policy = new WikiPagePolicy;

        $this->assertFalse($policy->viewAny($user, $project));
        $this->assertFalse($policy->view($user, $page));
        $this->assertFalse($policy->create($user, $project));
        $this->assertFalse($policy->update($user, $page));
        $this->assertFalse($policy->delete($user, $page));
    }

    #[DataProvider('rolesThatCanDelete')]
    public function test_allows_delete_for_admins_and_leaders(ProjectRole $role): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $project->members()->attach($user, ['role' => $role->value]);
        $page = WikiPage::factory()->for($project)->create();

        $this->assertTrue((new WikiPagePolicy)->delete($user, $page));
    }

    #[DataProvider('rolesThatCannotDelete')]
    public function test_forbids_delete_for_plain_members(ProjectRole $role): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $project->members()->attach($user, ['role' => $role->value]);
        $page = WikiPage::factory()->for($project)->create();

        $this->assertFalse((new WikiPagePolicy)->delete($user, $page));
    }
}

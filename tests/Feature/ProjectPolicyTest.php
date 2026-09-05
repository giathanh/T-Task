<?php

namespace Tests\Feature;

use App\Enums\ProjectRole;
use App\Models\Project;
use App\Models\User;
use App\Policies\ProjectPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProjectPolicyTest extends TestCase
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

    #[DataProvider('projectRoles')]
    public function test_allows_view_for_a_member_with_any_role(ProjectRole $role): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();
        $project->members()->attach($user, ['role' => $role->value]);

        $this->assertTrue((new ProjectPolicy)->view($user, $project));
    }

    public function test_forbids_view_for_a_user_who_is_not_a_project_member(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        $this->assertFalse((new ProjectPolicy)->view($user, $project));
    }
}

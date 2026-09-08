<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminManagementTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{string, string}> */
    public static function adminEndpoints(): array
    {
        return [
            'project list' => ['GET', '/admin/projects'],
            'project create form' => ['GET', '/admin/projects/create'],
            'project store' => ['POST', '/admin/projects'],
            'project edit form' => ['GET', '/admin/projects/1/edit'],
            'project update' => ['PUT', '/admin/projects/1'],
            'user list' => ['GET', '/admin/users'],
            'user create form' => ['GET', '/admin/users/create'],
            'user store' => ['POST', '/admin/users'],
            'user edit form' => ['GET', '/admin/users/1/edit'],
            'user update' => ['PUT', '/admin/users/1'],
        ];
    }

    #[DataProvider('adminEndpoints')]
    public function test_guests_are_redirected_to_login(string $method, string $uri): void
    {
        $this->call($method, $uri)->assertRedirect(route('login'));
    }

    #[DataProvider('adminEndpoints')]
    public function test_project_admin_cannot_access_system_administration(string $method, string $uri): void
    {
        $user = User::factory()->create(['id' => 1]);
        $project = Project::factory()->create(['id' => 1]);
        $project->members()->attach($user, ['role' => 'admin']);

        $this->actingAs($user)->call($method, $uri)->assertForbidden();
    }

    public function test_admin_has_grouped_navigation_and_can_open_all_forms(): void
    {
        $admin = User::factory()->admin()->create();
        $project = Project::factory()->create();
        $this->actingAs($admin)->get(route('dashboard'))
            ->assertSee('Quản trị')->assertSee(route('admin.projects.index'))->assertSee(route('admin.users.index'));

        $this->get(route('admin.projects.create'))->assertSee('Thêm dự án');
        $this->get(route('admin.projects.edit', $project))->assertSee($project->name);
        $this->get(route('admin.users.create'))->assertSee('Thêm người dùng');
        $this->get(route('admin.users.edit', $admin))->assertSee($admin->email);
    }

    public function test_regular_user_does_not_see_admin_navigation(): void
    {
        $this->actingAs(User::factory()->create())->get(route('dashboard'))
            ->assertDontSee(route('admin.projects.index'))->assertDontSee(route('admin.users.index'));
    }

    public function test_admin_can_search_projects_and_names_are_escaped(): void
    {
        $project = Project::factory()->create(['name' => '<script>Target</script>']);
        Project::factory()->create(['name' => 'Unrelated project']);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.projects.index', ['search' => 'Target']))
            ->assertSee($project->name)->assertDontSee($project->name, false)->assertDontSee('Unrelated project');
    }

    public function test_admin_can_search_users_by_email_and_paginate(): void
    {
        User::factory()->count(16)->create(['name' => 'Matching person']);
        $target = User::factory()->create(['email' => 'target@example.com']);
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.users.index', ['search' => 'target@example.com']))
            ->assertSee($target->email)->assertDontSee('Matching person');
        $this->get(route('admin.users.index', ['search' => 'Matching']))
            ->assertViewHas('users', fn ($users): bool => $users->count() === 15 && $users->total() === 16)
            ->assertSee('search=Matching', false);
    }

    public function test_admin_creates_project_with_membership_and_updates_it(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->post(route('admin.projects.store'), [
            'name' => 'New project', 'description' => 'Description', 'status' => 'planning', 'due_date' => '2026-12-01', 'created_by' => 999,
        ])->assertRedirect(route('admin.projects.index'));
        $project = Project::where('name', 'New project')->firstOrFail();
        $this->assertSame($admin->id, $project->created_by);
        $this->assertDatabaseHas('project_user', ['project_id' => $project->id, 'user_id' => $admin->id, 'role' => 'admin']);

        $this->put(route('admin.projects.update', $project), [
            'name' => 'Updated project', 'status' => 'archived', 'due_date' => null,
        ])->assertRedirect(route('admin.projects.index'));
        $this->assertDatabaseHas('projects', ['id' => $project->id, 'name' => 'Updated project', 'status' => 'archived', 'due_date' => null]);
    }

    public function test_invalid_project_does_not_persist(): void
    {
        $this->actingAs(User::factory()->admin()->create())->post(route('admin.projects.store'), [
            'name' => '', 'status' => 'invalid', 'due_date' => 'not-a-date',
        ])->assertSessionHasErrors(['name', 'status', 'due_date']);
        $this->assertDatabaseCount('projects', 0);
    }

    public function test_admin_creates_user_with_hashed_password_and_admin_role(): void
    {
        $this->actingAs(User::factory()->admin()->create())->post(route('admin.users.store'), [
            'name' => 'New admin', 'email' => 'new@example.com', 'password' => 'secure-password',
            'password_confirmation' => 'secure-password', 'is_admin' => 1, 'email_verified_at' => now(),
        ])->assertRedirect(route('admin.users.index'));

        $user = User::where('email', 'new@example.com')->firstOrFail();
        $this->assertTrue($user->is_admin);
        $this->assertTrue(Hash::check('secure-password', $user->password));
        $this->assertNull($user->email_verified_at);
    }

    public function test_admin_updates_user_without_replacing_blank_password_and_resets_email_verification(): void
    {
        $user = User::factory()->admin()->create();
        $password = $user->password;

        $this->actingAs(User::factory()->admin()->create())->put(route('admin.users.update', $user), [
            'name' => 'Updated user', 'email' => 'changed@example.com', 'password' => '', 'is_admin' => 0,
        ])->assertRedirect(route('admin.users.index'));

        $user->refresh();
        $this->assertSame('Updated user', $user->name);
        $this->assertSame('changed@example.com', $user->email);
        $this->assertSame($password, $user->password);
        $this->assertFalse($user->is_admin);
        $this->assertNull($user->email_verified_at);
    }

    public function test_admin_can_set_new_password_and_keep_unchanged_email(): void
    {
        $user = User::factory()->create();
        $this->actingAs(User::factory()->admin()->create())->put(route('admin.users.update', $user), [
            'name' => $user->name, 'email' => $user->email, 'is_admin' => 0,
            'password' => 'replacement-password', 'password_confirmation' => 'replacement-password',
        ])->assertRedirect(route('admin.users.index'));

        $this->assertTrue(Hash::check('replacement-password', $user->fresh()->password));
        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_invalid_user_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => '', 'email' => $admin->email, 'password' => 'short',
            'password_confirmation' => 'different', 'is_admin' => 'invalid',
        ])->assertSessionHasErrors(['name', 'email', 'password', 'is_admin']);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_admin_cannot_remove_own_admin_role(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin)->put(route('admin.users.update', $admin), [
            'name' => $admin->name, 'email' => $admin->email, 'is_admin' => 0,
        ])->assertSessionHasErrors(['is_admin' => 'Bạn không thể tự gỡ quyền quản trị của mình.']);
        $this->assertTrue($admin->fresh()->is_admin);
    }

    public function test_registration_cannot_grant_admin_role(): void
    {
        $this->post(route('register'), [
            'name' => 'Member', 'email' => 'member@example.com', 'password' => 'secure-password',
            'password_confirmation' => 'secure-password', 'is_admin' => 1,
        ])->assertRedirect(route('dashboard', absolute: false));
        $this->assertFalse(User::where('email', 'member@example.com')->firstOrFail()->is_admin);
    }

    public function test_command_grants_admin_to_existing_user_only(): void
    {
        $user = User::factory()->create();
        $this->artisan('app:make-admin', ['email' => $user->email])->assertSuccessful();
        $this->assertTrue($user->fresh()->is_admin);
        $this->artisan('app:make-admin', ['email' => 'missing@example.com'])->assertFailed();
        $this->assertDatabaseCount('users', 1);
    }
}

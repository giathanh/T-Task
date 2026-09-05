<?php

namespace Tests\Feature;

use App\Enums\ProjectRole;
use App\Models\Issue;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class IssueControllerTest extends TestCase
{
    use RefreshDatabase;

    private function memberOf(Project $project, ProjectRole $role = ProjectRole::Member): User
    {
        $user = User::factory()->create();
        $project->members()->attach($user, ['role' => $role->value]);

        return $user;
    }

    public function test_guest_is_redirected_to_login_when_viewing_create_form(): void
    {
        $project = Project::factory()->create();

        $response = $this->get(route('issues.create', $project));

        $response->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_to_login_when_storing_issue(): void
    {
        $project = Project::factory()->create();

        $response = $this->post(route('issues.store', $project));

        $response->assertRedirect(route('login'));
    }

    public function test_user_who_is_not_a_project_member_is_forbidden_from_viewing_create_form(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        $response = $this->actingAs($user)->get(route('issues.create', $project));

        $response->assertForbidden();
    }

    public function test_user_who_is_not_a_project_member_is_forbidden_from_storing_issue(): void
    {
        $user = User::factory()->create();
        $project = Project::factory()->create();

        $response = $this->actingAs($user)->post(route('issues.store', $project), [
            'title' => 'Unauthorized attempt',
            'type' => 'task',
            'status' => 'open',
            'priority' => 'normal',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('issues', ['title' => 'Unauthorized attempt']);
    }

    public function test_member_can_view_create_form(): void
    {
        $project = Project::factory()->create(['name' => 'Website Redesign']);
        $member = $this->memberOf($project);

        $response = $this->actingAs($member)->get(route('issues.create', $project));

        $response->assertOk();
        $response->assertSee('Website Redesign');
    }

    public function test_empty_payload_fails_validation_for_required_fields(): void
    {
        $project = Project::factory()->create();
        $member = $this->memberOf($project);

        $response = $this->actingAs($member)->post(route('issues.store', $project), []);

        $response->assertSessionHasErrors(['title', 'type', 'status', 'priority']);
    }

    public function test_severity_is_required_when_tracker_is_bug(): void
    {
        $project = Project::factory()->create();
        $member = $this->memberOf($project);

        $response = $this->actingAs($member)->post(route('issues.store', $project), [
            'title' => 'Login button is unresponsive',
            'type' => 'bug',
            'status' => 'open',
            'priority' => 'normal',
        ]);

        $response->assertSessionHasErrors('severity');
    }

    public function test_due_date_before_start_date_fails_validation(): void
    {
        $project = Project::factory()->create();
        $member = $this->memberOf($project);

        $response = $this->actingAs($member)->post(route('issues.store', $project), [
            'title' => 'Redesign checkout flow',
            'type' => 'task',
            'status' => 'open',
            'priority' => 'normal',
            'start_date' => '2026-09-19',
            'due_date' => '2026-09-08',
        ]);

        $response->assertSessionHasErrors('due_date');
    }

    public function test_assignee_outside_the_project_fails_validation(): void
    {
        $project = Project::factory()->create();
        $member = $this->memberOf($project);
        $outsider = User::factory()->create();

        $response = $this->actingAs($member)->post(route('issues.store', $project), [
            'title' => 'Redesign checkout flow',
            'type' => 'task',
            'status' => 'open',
            'priority' => 'normal',
            'assignee_id' => $outsider->id,
        ]);

        $response->assertSessionHasErrors('assignee_id');
    }

    public function test_parent_issue_from_another_project_fails_validation(): void
    {
        $project = Project::factory()->create();
        $member = $this->memberOf($project);
        $otherProjectIssue = Issue::factory()->create();

        $response = $this->actingAs($member)->post(route('issues.store', $project), [
            'title' => 'Redesign checkout flow',
            'type' => 'task',
            'status' => 'open',
            'priority' => 'normal',
            'parent_id' => $otherProjectIssue->id,
        ]);

        $response->assertSessionHasErrors('parent_id');
    }

    public function test_valid_payload_creates_issue_and_redirects_to_project(): void
    {
        $project = Project::factory()->create();
        $member = $this->memberOf($project);
        $assignee = $this->memberOf($project);

        $response = $this->actingAs($member)->post(route('issues.store', $project), [
            'title' => 'Redesign checkout flow',
            'description' => 'Reduce checkout from 4 steps to 2.',
            'type' => 'task',
            'status' => 'open',
            'priority' => 'high',
            'start_date' => '2026-09-08',
            'due_date' => '2026-09-19',
            'assignee_id' => $assignee->id,
            'estimated_hours' => '12.5',
            'category' => 'Payments',
        ]);

        $response->assertRedirect(route('projects.show', $project));

        $this->assertDatabaseHas('issues', [
            'project_id' => $project->id,
            'title' => 'Redesign checkout flow',
            'type' => 'task',
            'status' => 'open',
            'priority' => 'high',
            'assignee_id' => $assignee->id,
            'created_by' => $member->id,
            'percent_done' => 0,
            'is_private' => false,
        ]);
    }

    public function test_percent_done_is_saved_when_provided(): void
    {
        $project = Project::factory()->create();
        $member = $this->memberOf($project);

        $this->actingAs($member)->post(route('issues.store', $project), [
            'title' => 'Redesign checkout flow',
            'type' => 'task',
            'status' => 'in_progress',
            'priority' => 'normal',
            'percent_done' => 40,
        ]);

        $this->assertDatabaseHas('issues', [
            'title' => 'Redesign checkout flow',
            'percent_done' => 40,
        ]);
    }

    public function test_watchers_are_attached_to_the_created_issue(): void
    {
        $project = Project::factory()->create();
        $member = $this->memberOf($project);
        $watcher = $this->memberOf($project);

        $this->actingAs($member)->post(route('issues.store', $project), [
            'title' => 'Redesign checkout flow',
            'type' => 'task',
            'status' => 'open',
            'priority' => 'normal',
            'watchers' => [$watcher->id],
        ]);

        $issue = Issue::firstWhere('title', 'Redesign checkout flow');

        $this->assertTrue($issue->watchers()->whereKey($watcher->id)->exists());
    }

    public function test_uploaded_attachment_is_stored_and_linked_to_the_created_issue(): void
    {
        Storage::fake('local');

        $project = Project::factory()->create();
        $member = $this->memberOf($project);
        $file = UploadedFile::fake()->create('spec.pdf', 100, 'application/pdf');

        $this->actingAs($member)->post(route('issues.store', $project), [
            'title' => 'Redesign checkout flow',
            'type' => 'task',
            'status' => 'open',
            'priority' => 'normal',
            'attachments' => [$file],
        ]);

        $issue = Issue::firstWhere('title', 'Redesign checkout flow');

        $this->assertCount(1, $issue->attachments);
        $this->assertSame('spec.pdf', $issue->attachments->first()->original_name);
        Storage::disk('local')->assertExists($issue->attachments->first()->path);
    }
}

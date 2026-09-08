<?php

namespace Tests\Feature;

use App\Enums\IssueStatus;
use App\Enums\ProjectRole;
use App\Models\Issue;
use App\Models\IssueNote;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\TestWith;
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

    public function test_guest_is_redirected_to_login_from_the_issue_index(): void
    {
        $project = Project::factory()->create();

        $this->get(route('issues.index', $project))
            ->assertRedirect(route('login'));
    }

    public function test_non_member_is_forbidden_from_listing_issues(): void
    {
        $project = Project::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('issues.index', $project))
            ->assertForbidden();
    }

    public function test_member_sees_only_this_projects_issues(): void
    {
        $project = Project::factory()->create();
        $member = $this->memberOf($project);
        Issue::factory()->for($project)->create(['title' => 'Broken login form']);
        Issue::factory()->create(['title' => 'Issue from another project']);

        $this->actingAs($member)
            ->get(route('issues.index', $project))
            ->assertOk()
            ->assertSee('Broken login form')
            ->assertDontSee('Issue from another project');
    }

    #[TestWith(['low', 'hover:bg-on-surface/8'])]
    #[TestWith(['normal', 'hover:bg-on-surface/8'])]
    #[TestWith(['high', 'bg-priority-high-container hover:bg-priority-high-container-hover'])]
    #[TestWith(['urgent', 'bg-priority-urgent-container hover:bg-priority-urgent-container-hover'])]
    public function test_issue_row_background_matches_its_priority(string $priority, string $rowClass): void
    {
        $project = Project::factory()->create();
        $member = $this->memberOf($project);
        Issue::factory()->for($project)->create(['priority' => $priority]);

        $this->actingAs($member)
            ->get(route('issues.index', $project))
            ->assertOk()
            ->assertSee('<tr class="transition-colors '.$rowClass.'">', false);
    }

    #[TestWith([0])]
    #[TestWith([40])]
    #[TestWith([100])]
    public function test_issue_list_displays_percent_done_in_a_separate_column(int $percentDone): void
    {
        $project = Project::factory()->create();
        $member = $this->memberOf($project);
        Issue::factory()->for($project)->create(['percent_done' => $percentDone]);

        $response = $this->actingAs($member)->get(route('issues.index', $project));

        $response->assertOk()->assertSee('% Done');
        $this->assertMatchesRegularExpression('/<td\b[^>]*>\s*'.$percentDone.'%\s*<\/td>/', $response->getContent());
    }

    public function test_status_filter_limits_the_listed_issues(): void
    {
        $project = Project::factory()->create();
        $member = $this->memberOf($project);
        Issue::factory()->for($project)->status(IssueStatus::Open)->create(['title' => 'Still open task']);
        Issue::factory()->for($project)->status(IssueStatus::Done)->create(['title' => 'Finished task']);

        $this->actingAs($member)
            ->get(route('issues.index', [$project, 'status' => 'open']))
            ->assertOk()
            ->assertSee('Still open task')
            ->assertDontSee('Finished task');
    }

    public function test_title_search_filters_the_listed_issues(): void
    {
        $project = Project::factory()->create();
        $member = $this->memberOf($project);
        Issue::factory()->for($project)->create(['title' => 'Checkout crashes on Safari']);
        Issue::factory()->for($project)->create(['title' => 'Update onboarding copy']);

        $this->actingAs($member)
            ->get(route('issues.index', [$project, 'q' => 'checkout']))
            ->assertOk()
            ->assertSee('Checkout crashes on Safari')
            ->assertDontSee('Update onboarding copy');
    }

    public function test_title_search_accepts_zero(): void
    {
        $project = Project::factory()->create();
        $member = $this->memberOf($project);
        Issue::factory()->for($project)->create(['title' => 'Fix error 0']);
        Issue::factory()->for($project)->create(['title' => 'Unrelated work']);

        $this->actingAs($member)->get(route('issues.index', [$project, 'q' => '0']))
            ->assertSee('Fix error 0')
            ->assertDontSee('Unrelated work')
            ->assertViewHas('issues', fn ($issues) => $issues->total() === 1);
    }

    public function test_issue_pagination_is_stable_when_creation_times_match(): void
    {
        $project = Project::factory()->create();
        $member = $this->memberOf($project);
        $issues = Issue::factory()->for($project)->count(21)->create(['created_at' => now()->startOfDay()]);

        $this->actingAs($member)->get(route('issues.index', [$project, 'page' => 2]))
            ->assertViewHas('issues', fn ($page) => $page->modelKeys() === [$issues->first()->id]);
    }

    public function test_invalid_status_filter_fails_validation(): void
    {
        $project = Project::factory()->create();
        $member = $this->memberOf($project);

        $this->actingAs($member)
            ->get(route('issues.index', [$project, 'status' => 'not-a-status']))
            ->assertSessionHasErrors('status');
    }

    public function test_guest_is_redirected_to_login_from_the_issue_detail(): void
    {
        $issue = Issue::factory()->create();

        $this->get(route('issues.show', [$issue->project, $issue]))
            ->assertRedirect(route('login'));
    }

    public function test_non_member_is_forbidden_from_viewing_an_issue(): void
    {
        $issue = Issue::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('issues.show', [$issue->project, $issue]))
            ->assertForbidden();
    }

    public function test_member_sees_the_issue_detail(): void
    {
        $project = Project::factory()->create();
        $member = $this->memberOf($project);
        $assignee = $this->memberOf($project);
        $issue = Issue::factory()->for($project)->create([
            'title' => 'Checkout crashes on Safari',
            'description' => 'Reproduces on every Safari 17 build.',
            'assignee_id' => $assignee->id,
            'percent_done' => 30,
        ]);

        $this->actingAs($member)
            ->get(route('issues.show', [$project, $issue]))
            ->assertOk()
            ->assertSee('Checkout crashes on Safari')
            ->assertSee('Reproduces on every Safari 17 build.')
            ->assertSee($assignee->name)
            ->assertSee('30%')
            ->assertSee('href="'.route('issues.index', $project).'"', false)
            ->assertDontSee('← Tất cả issue');
    }

    public function test_issue_detail_places_watchers_beside_subtasks_and_note_form_after_notes(): void
    {
        $project = Project::factory()->create();
        $member = $this->memberOf($project);
        $firstWatcher = $this->memberOf($project);
        $secondWatcher = $this->memberOf($project);
        $issue = Issue::factory()->for($project)->create();
        Issue::factory()->for($project)->create([
            'parent_id' => $issue->id,
            'title' => 'Prepare release checklist',
        ]);
        $issue->watchers()->sync([$firstWatcher->id, $secondWatcher->id]);
        $note = IssueNote::factory()->for($issue)->for($member, 'author')->create([
            'body' => 'Ready for final review.',
        ]);

        $response = $this->actingAs($member)->get(route('issues.show', [$project, $issue]));

        $response
            ->assertSee($firstWatcher->name.', '.$secondWatcher->name)
            ->assertSeeInOrder([
                'id="subtasks"',
                'id="watchers"',
                $note->body,
                'id="new-note"',
            ], false);
    }

    public function test_issue_scoped_to_another_project_is_not_found(): void
    {
        $project = Project::factory()->create();
        $member = $this->memberOf($project);
        $otherIssue = Issue::factory()->create();

        $this->actingAs($member)
            ->get(route('issues.show', [$project, $otherIssue]))
            ->assertNotFound();
    }

    public function test_non_member_is_forbidden_from_editing_an_issue(): void
    {
        $issue = Issue::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('issues.edit', [$issue->project, $issue]))
            ->assertForbidden();
    }

    public function test_member_can_view_the_edit_form(): void
    {
        $project = Project::factory()->create();
        $member = $this->memberOf($project);
        $issue = Issue::factory()->for($project)->create(['title' => 'Broken export button']);

        $this->actingAs($member)
            ->get(route('issues.edit', [$project, $issue]))
            ->assertOk()
            ->assertSee('Broken export button');
    }

    public function test_non_member_is_forbidden_from_updating_an_issue(): void
    {
        $issue = Issue::factory()->for(Project::factory())->create(['title' => 'Original title']);

        $this->actingAs(User::factory()->create())
            ->put(route('issues.update', [$issue->project, $issue]), [
                'title' => 'Hijacked title',
                'type' => 'task',
                'status' => 'open',
                'priority' => 'normal',
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('issues', ['id' => $issue->id, 'title' => 'Original title']);
    }

    public function test_member_can_update_an_issue_and_is_redirected_to_the_detail(): void
    {
        $project = Project::factory()->create();
        $member = $this->memberOf($project);
        $assignee = $this->memberOf($project);
        $issue = Issue::factory()->for($project)->status(IssueStatus::Open)->create([
            'title' => 'Old title',
            'percent_done' => 0,
        ]);

        $response = $this->actingAs($member)->put(route('issues.update', [$project, $issue]), [
            'title' => 'New title',
            'description' => 'Updated details.',
            'type' => 'task',
            'status' => 'in_progress',
            'priority' => 'high',
            'assignee_id' => $assignee->id,
            'percent_done' => 60,
        ]);

        $response->assertRedirect(route('issues.show', [$project, $issue]));

        $this->assertDatabaseHas('issues', [
            'id' => $issue->id,
            'title' => 'New title',
            'description' => 'Updated details.',
            'status' => 'in_progress',
            'priority' => 'high',
            'assignee_id' => $assignee->id,
            'percent_done' => 60,
        ]);
    }

    public function test_update_syncs_watchers(): void
    {
        $project = Project::factory()->create();
        $member = $this->memberOf($project);
        $keptWatcher = $this->memberOf($project);
        $droppedWatcher = $this->memberOf($project);
        $issue = Issue::factory()->for($project)->task()->create();
        $issue->watchers()->sync([$keptWatcher->id, $droppedWatcher->id]);

        $this->actingAs($member)->put(route('issues.update', [$project, $issue]), [
            'title' => $issue->title,
            'type' => $issue->type->value,
            'status' => $issue->status->value,
            'priority' => $issue->priority->value,
            'watchers' => [$keptWatcher->id],
        ]);

        $this->assertTrue($issue->watchers()->whereKey($keptWatcher->id)->exists());
        $this->assertFalse($issue->watchers()->whereKey($droppedWatcher->id)->exists());
    }

    public function test_update_clears_severity_when_tracker_changes_from_bug_to_task(): void
    {
        $project = Project::factory()->create();
        $member = $this->memberOf($project);
        $issue = Issue::factory()->for($project)->bug()->create();

        $this->actingAs($member)->put(route('issues.update', [$project, $issue]), [
            'title' => $issue->title,
            'type' => 'task',
            'status' => $issue->status->value,
            'priority' => $issue->priority->value,
        ]);

        $this->assertDatabaseHas('issues', ['id' => $issue->id, 'severity' => null]);
    }

    public function test_update_rejects_an_issue_set_as_its_own_parent(): void
    {
        $project = Project::factory()->create();
        $member = $this->memberOf($project);
        $issue = Issue::factory()->for($project)->task()->create();

        $this->actingAs($member)->put(route('issues.update', [$project, $issue]), [
            'title' => $issue->title,
            'type' => 'task',
            'status' => $issue->status->value,
            'priority' => $issue->priority->value,
            'parent_id' => $issue->id,
        ])->assertSessionHasErrors('parent_id');
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

    public function test_task_creation_discards_bug_severity_and_accepts_due_date_without_start_date(): void
    {
        $project = Project::factory()->create();
        $member = $this->memberOf($project);

        $this->actingAs($member)->post(route('issues.store', $project), [
            'title' => 'Task with deadline',
            'type' => 'task',
            'status' => 'open',
            'priority' => 'normal',
            'severity' => 'critical',
            'start_date' => null,
            'due_date' => '2026-09-19',
        ])->assertRedirect(route('projects.show', $project))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('issues', [
            'project_id' => $project->id,
            'title' => 'Task with deadline',
            'severity' => null,
            'start_date' => null,
            'due_date' => '2026-09-19 00:00:00',
        ]);
    }

    public function test_issue_update_accepts_due_date_without_start_date(): void
    {
        $project = Project::factory()->create();
        $member = $this->memberOf($project);
        $issue = Issue::factory()->for($project)->task()->create();

        $this->actingAs($member)->put(route('issues.update', [$project, $issue]), [
            'title' => $issue->title,
            'type' => 'task',
            'status' => 'open',
            'priority' => 'normal',
            'due_date' => '2026-09-19',
        ])->assertRedirect(route('issues.show', [$project, $issue]))->assertSessionHasNoErrors();

        $this->assertSame('2026-09-19', $issue->fresh()->due_date->toDateString());
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

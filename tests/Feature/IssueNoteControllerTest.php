<?php

namespace Tests\Feature;

use App\Enums\ProjectRole;
use App\Models\Issue;
use App\Models\IssueNote;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class IssueNoteControllerTest extends TestCase
{
    use RefreshDatabase;

    private function memberOf(Project $project): User
    {
        $user = User::factory()->create();
        $project->members()->attach($user, ['role' => ProjectRole::Member->value]);

        return $user;
    }

    public function test_member_can_add_multiple_notes_without_replacing_existing_notes(): void
    {
        $issue = Issue::factory()->create();
        $member = $this->memberOf($issue->project);
        $existing = IssueNote::factory()->for($issue)->create();

        $this->actingAs($member)->post(route('issues.notes.store', [$issue->project, $issue]), [
            'body' => "Đã kiểm tra lỗi.\nCần bổ sung log.",
            'created_by' => $existing->created_by,
            'issue_id' => 999999,
        ])->assertRedirect(route('issues.show', [$issue->project, $issue]).'#notes')
            ->assertSessionHas('status', 'issue-note-created');

        $this->assertDatabaseCount('issue_notes', 2);
        $this->assertModelExists($existing);
        $this->assertDatabaseHas('issue_notes', [
            'issue_id' => $issue->id,
            'created_by' => $member->id,
            'body' => "Đã kiểm tra lỗi.\nCần bổ sung log.",
        ]);
    }

    public function test_guest_cannot_add_a_note(): void
    {
        $issue = Issue::factory()->create();

        $this->post(route('issues.notes.store', [$issue->project, $issue]), ['body' => 'Note'])
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('issue_notes', 0);
    }

    public function test_non_member_cannot_add_or_read_notes(): void
    {
        $issue = Issue::factory()->create();
        $note = IssueNote::factory()->for($issue)->create();

        $this->actingAs(User::factory()->create())
            ->post(route('issues.notes.store', [$issue->project, $issue]), ['body' => 'Note'])
            ->assertForbidden();
        $this->get(route('issues.show', [$issue->project, $issue]))->assertForbidden()->assertDontSee($note->body);

        $this->assertDatabaseCount('issue_notes', 1);
    }

    public function test_issue_from_another_project_cannot_receive_a_note(): void
    {
        $project = Project::factory()->create();
        $member = $this->memberOf($project);
        $issue = Issue::factory()->create();

        $this->actingAs($member)->post(route('issues.notes.store', [$project, $issue]), ['body' => 'Note'])
            ->assertNotFound();

        $this->assertDatabaseCount('issue_notes', 0);
    }

    #[TestWith([null, 'Vui lòng nhập nội dung ghi chú.'])]
    #[TestWith(['   ', 'Vui lòng nhập nội dung ghi chú.'])]
    #[TestWith([['invalid'], 'Nội dung ghi chú phải là văn bản.'])]
    public function test_invalid_note_is_rejected(mixed $body, string $message): void
    {
        $issue = Issue::factory()->create();
        $member = $this->memberOf($issue->project);

        $this->actingAs($member)->from(route('issues.show', [$issue->project, $issue]))
            ->post(route('issues.notes.store', [$issue->project, $issue]), ['body' => $body])
            ->assertSessionHasErrors(['body' => $message]);

        $this->assertDatabaseCount('issue_notes', 0);
    }

    public function test_oversized_note_is_rejected_and_input_is_preserved(): void
    {
        $issue = Issue::factory()->create();
        $member = $this->memberOf($issue->project);
        $body = str_repeat('a', 10001);

        $this->actingAs($member)->post(route('issues.notes.store', [$issue->project, $issue]), ['body' => $body])
            ->assertSessionHasErrors(['body' => 'Ghi chú không được vượt quá 10.000 ký tự.'])
            ->assertSessionHasInput('body', $body);

        $this->assertDatabaseCount('issue_notes', 0);
    }

    public function test_detail_shows_only_this_issues_notes_newest_first_and_escapes_html(): void
    {
        $issue = Issue::factory()->create();
        $member = $this->memberOf($issue->project);
        IssueNote::factory()->for($issue)->for($member, 'author')->create(['body' => 'Older note']);
        IssueNote::factory()->for($issue)->for($member, 'author')->create(['body' => '<script>alert("x")</script>']);
        IssueNote::factory()->create(['body' => 'Unrelated note']);

        $this->actingAs($member)->get(route('issues.show', [$issue->project, $issue]))
            ->assertSeeInOrder(['<script>alert("x")</script>', 'Older note'])
            ->assertDontSee('<script>alert("x")</script>', false)
            ->assertSee($member->name)
            ->assertDontSee('Unrelated note');
    }

    public function test_notes_are_paginated_in_stable_order(): void
    {
        $issue = Issue::factory()->create();
        $member = $this->memberOf($issue->project);
        $notes = IssueNote::factory()->count(21)->for($issue)->for($member, 'author')->create();

        $this->actingAs($member)->get(route('issues.show', [$issue->project, $issue]))
            ->assertSee($notes->last()->body)->assertDontSee($notes->first()->body);
        $this->get(route('issues.show', [$issue->project, $issue, 'notes_page' => 2]))
            ->assertSee($notes->first()->body)->assertDontSee($notes->last()->body);
    }

    public function test_detail_displays_empty_notes_state(): void
    {
        $issue = Issue::factory()->create();
        $member = $this->memberOf($issue->project);

        $this->actingAs($member)->get(route('issues.show', [$issue->project, $issue]))
            ->assertSee('Issue này chưa có ghi chú.')->assertSee('Thêm ghi chú');
    }

    public function test_notes_survive_author_deletion_and_display_a_fallback_name(): void
    {
        $issue = Issue::factory()->create();
        $member = $this->memberOf($issue->project);
        $note = IssueNote::factory()->for($issue)->create();
        $note->author->delete();

        $this->actingAs($member)->get(route('issues.show', [$issue->project, $issue]))
            ->assertSee($note->body)->assertSee('Người dùng đã xóa');
    }
}

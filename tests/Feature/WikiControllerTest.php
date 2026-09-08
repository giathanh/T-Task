<?php

namespace Tests\Feature;

use App\Enums\ProjectRole;
use App\Models\Project;
use App\Models\User;
use App\Models\WikiPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WikiControllerTest extends TestCase
{
    use RefreshDatabase;

    private function memberOf(Project $project, ProjectRole $role = ProjectRole::Member): User
    {
        $user = User::factory()->create();
        $project->members()->attach($user, ['role' => $role->value]);

        return $user;
    }

    public function test_guest_is_redirected_to_login_from_the_wiki_index(): void
    {
        $project = Project::factory()->create();

        $this->get(route('projects.wiki.index', $project))
            ->assertRedirect(route('login'));
    }

    public function test_non_member_is_forbidden_from_listing_wiki_pages(): void
    {
        $project = Project::factory()->create();

        $this->actingAs(User::factory()->create())
            ->get(route('projects.wiki.index', $project))
            ->assertForbidden();
    }

    public function test_non_member_is_forbidden_from_viewing_a_wiki_page(): void
    {
        $project = Project::factory()->create();
        $page = WikiPage::factory()->for($project)->create();

        $this->actingAs(User::factory()->create())
            ->get(route('projects.wiki.show', [$project, $page]))
            ->assertForbidden();
    }

    public function test_non_member_is_forbidden_from_storing_a_wiki_page(): void
    {
        $project = Project::factory()->create();

        $this->actingAs(User::factory()->create())
            ->post(route('projects.wiki.store', $project), ['title' => 'Nope'])
            ->assertForbidden();

        $this->assertDatabaseMissing('wiki_pages', ['title' => 'Nope']);
    }

    public function test_wiki_page_from_another_project_returns_404(): void
    {
        $project = Project::factory()->create();
        $member = $this->memberOf($project);
        $foreignPage = WikiPage::factory()->create();

        $this->actingAs($member)
            ->get(route('projects.wiki.show', [$project, $foreignPage]))
            ->assertNotFound();
    }

    public function test_member_sees_the_wiki_index_with_its_pages(): void
    {
        $project = Project::factory()->create();
        $member = $this->memberOf($project);
        WikiPage::factory()->for($project)->create(['title' => 'Hướng dẫn cài đặt']);

        $this->actingAs($member)
            ->get(route('projects.wiki.index', $project))
            ->assertOk()
            ->assertSee('Hướng dẫn cài đặt');
    }

    public function test_wiki_pages_are_paginated_with_total_and_navigation(): void
    {
        $project = Project::factory()->create();
        $member = $this->memberOf($project);
        WikiPage::factory()->for($project)->count(21)
            ->sequence(fn ($sequence) => ['title' => sprintf('Page %02d', $sequence->index)])
            ->create();
        WikiPage::factory()->create(['title' => 'Foreign page']);

        $this->actingAs($member)->get(route('projects.wiki.index', [$project, 'page' => 2]))
            ->assertSee('Các trang (21)')
            ->assertSee('Page 20')
            ->assertDontSee('Page 00')
            ->assertDontSee('Foreign page')
            ->assertViewHas('pages', fn ($pages) => $pages->total() === 21 && $pages->count() === 1 && $pages->previousPageUrl() !== null);
    }

    public function test_member_can_open_the_create_form(): void
    {
        $project = Project::factory()->create(['name' => 'Website Redesign']);
        $member = $this->memberOf($project);

        $this->actingAs($member)
            ->get(route('projects.wiki.create', $project))
            ->assertOk()
            ->assertSee('Website Redesign');
    }

    public function test_storing_without_a_title_fails_validation(): void
    {
        $project = Project::factory()->create();
        $member = $this->memberOf($project);

        $this->actingAs($member)
            ->post(route('projects.wiki.store', $project), ['content' => 'body'])
            ->assertSessionHasErrors('title');
    }

    public function test_storing_a_duplicate_title_in_the_same_project_fails_validation(): void
    {
        $project = Project::factory()->create();
        $member = $this->memberOf($project);
        WikiPage::factory()->for($project)->create(['title' => 'Trang chủ']);

        $this->actingAs($member)
            ->post(route('projects.wiki.store', $project), ['title' => 'Trang chủ'])
            ->assertSessionHasErrors(['title' => 'Dự án đã có một trang wiki với tiêu đề này.']);
    }

    public function test_valid_payload_creates_the_page_and_redirects_to_it(): void
    {
        $project = Project::factory()->create();
        $member = $this->memberOf($project);

        $response = $this->actingAs($member)->post(route('projects.wiki.store', $project), [
            'title' => 'Quy tắc làm việc',
            'content' => '# Quy tắc',
        ]);

        $page = WikiPage::firstWhere('title', 'Quy tắc làm việc');

        $response->assertRedirect(route('projects.wiki.show', [$project, $page]));

        $this->assertDatabaseHas('wiki_pages', [
            'project_id' => $project->id,
            'title' => 'Quy tắc làm việc',
            'slug' => 'quy-tac-lam-viec',
            'content' => '# Quy tắc',
            'created_by' => $member->id,
            'updated_by' => $member->id,
        ]);
    }

    public function test_a_second_page_whose_title_slugifies_the_same_gets_a_distinct_slug(): void
    {
        $project = Project::factory()->create();
        $member = $this->memberOf($project);
        WikiPage::factory()->for($project)->create(['title' => 'Ghi chú', 'slug' => 'ghi-chu']);

        $this->actingAs($member)->post(route('projects.wiki.store', $project), [
            'title' => 'Ghi  chú!',
        ]);

        $this->assertDatabaseHas('wiki_pages', [
            'project_id' => $project->id,
            'title' => 'Ghi  chú!',
            'slug' => 'ghi-chu-2',
        ]);
    }

    public function test_show_renders_markdown_and_escapes_raw_html(): void
    {
        $project = Project::factory()->create();
        $member = $this->memberOf($project);
        $page = WikiPage::factory()->for($project)->create([
            'content' => "## Bảo mật\n\n<script>alert('xss')</script>",
        ]);

        $response = $this->actingAs($member)->get(route('projects.wiki.show', [$project, $page]));

        $response->assertOk();
        $response->assertSee('<h2>Bảo mật</h2>', false);
        $response->assertDontSee("<script>alert('xss')</script>", false);
    }

    public function test_member_can_update_a_page_and_the_slug_follows_the_new_title(): void
    {
        $project = Project::factory()->create();
        $author = $this->memberOf($project);
        $editor = $this->memberOf($project);
        $page = WikiPage::factory()->for($project)->create([
            'title' => 'Bản nháp',
            'slug' => 'ban-nhap',
            'created_by' => $author->id,
            'updated_by' => $author->id,
        ]);

        $response = $this->actingAs($editor)->put(route('projects.wiki.update', [$project, $page]), [
            'title' => 'Tài liệu chính thức',
            'content' => 'Nội dung mới',
        ]);

        $response->assertRedirect(route('projects.wiki.show', [$project, $page->fresh()]));

        $this->assertDatabaseHas('wiki_pages', [
            'id' => $page->id,
            'title' => 'Tài liệu chính thức',
            'slug' => 'tai-lieu-chinh-thuc',
            'content' => 'Nội dung mới',
            'created_by' => $author->id,
            'updated_by' => $editor->id,
        ]);
    }

    public function test_plain_member_cannot_delete_a_page(): void
    {
        $project = Project::factory()->create();
        $member = $this->memberOf($project);
        $page = WikiPage::factory()->for($project)->create();

        $this->actingAs($member)
            ->delete(route('projects.wiki.destroy', [$project, $page]))
            ->assertForbidden();

        $this->assertModelExists($page);
    }

    public function test_leader_can_delete_a_page_and_is_redirected_to_the_index(): void
    {
        $project = Project::factory()->create();
        $leader = $this->memberOf($project, ProjectRole::Leader);
        $page = WikiPage::factory()->for($project)->create();

        $this->actingAs($leader)
            ->delete(route('projects.wiki.destroy', [$project, $page]))
            ->assertRedirect(route('projects.wiki.index', $project));

        $this->assertDatabaseMissing('wiki_pages', ['id' => $page->id]);
    }
}

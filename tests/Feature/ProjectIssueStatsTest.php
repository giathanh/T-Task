<?php

namespace Tests\Feature;

use App\Enums\IssueStatus;
use App\Models\Issue;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectIssueStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_counts_issues_by_type_and_status(): void
    {
        $project = Project::factory()->create();

        Issue::factory()->for($project)->task()->status(IssueStatus::Open)->count(2)->create();
        Issue::factory()->for($project)->task()->status(IssueStatus::Done)->create();
        Issue::factory()->for($project)->bug()->status(IssueStatus::InProgress)->create();

        $stats = $project->issueStats();

        $this->assertSame([
            'total' => 3,
            'by_status' => ['open' => 2, 'in_progress' => 0, 'done' => 1],
        ], $stats['task']);

        $this->assertSame([
            'total' => 1,
            'by_status' => ['open' => 0, 'in_progress' => 1, 'done' => 0],
        ], $stats['bug']);
    }

    public function test_returns_zero_counts_when_project_has_no_issues(): void
    {
        $project = Project::factory()->create();

        $stats = $project->issueStats();

        $this->assertSame(0, $stats['task']['total']);
        $this->assertSame(0, $stats['bug']['total']);
    }
}

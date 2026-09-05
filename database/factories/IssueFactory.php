<?php

namespace Database\Factories;

use App\Enums\IssuePriority;
use App\Enums\IssueSeverity;
use App\Enums\IssueStatus;
use App\Enums\IssueType;
use App\Models\Issue;
use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Issue>
 */
class IssueFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'type' => fake()->randomElement(IssueType::cases()),
            'title' => fake()->sentence(6),
            'description' => fake()->optional()->paragraph(),
            'status' => fake()->randomElement(IssueStatus::cases()),
            'severity' => null,
            'assignee_id' => null,
            'created_by' => null,
            'priority' => IssuePriority::Normal,
            'start_date' => null,
            'due_date' => null,
            'percent_done' => 0,
            'estimated_hours' => null,
            'category' => null,
            'is_private' => false,
            'parent_id' => null,
        ];
    }

    public function task(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => IssueType::Task,
            'severity' => null,
        ]);
    }

    public function bug(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => IssueType::Bug,
            'severity' => fake()->randomElement(IssueSeverity::cases()),
        ]);
    }

    public function status(IssueStatus $status): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $status,
        ]);
    }
}

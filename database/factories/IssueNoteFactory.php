<?php

namespace Database\Factories;

use App\Models\Issue;
use App\Models\IssueNote;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<IssueNote> */
class IssueNoteFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'issue_id' => Issue::factory(),
            'created_by' => User::factory(),
            'body' => fake()->paragraph(),
        ];
    }
}

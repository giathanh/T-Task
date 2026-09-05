<?php

namespace Database\Factories;

use App\Models\Project;
use App\Models\WikiPage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<WikiPage>
 */
class WikiPageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->unique()->sentence(4);

        return [
            'project_id' => Project::factory(),
            'title' => $title,
            'slug' => Str::slug($title),
            'content' => fake()->optional()->paragraphs(3, true),
            'created_by' => null,
            'updated_by' => null,
        ];
    }
}

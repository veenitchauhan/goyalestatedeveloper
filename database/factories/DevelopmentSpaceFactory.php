<?php

namespace Database\Factories;

use App\Models\ContentEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

class DevelopmentSpaceFactory extends Factory
{
    public function definition(): array
    {
        return ['content_entry_id' => ContentEntry::factory()->state(['type' => 'development']), 'kind' => 'tower', 'name' => fake()->unique()->word(), 'details' => [], 'is_public' => false, 'status' => 'Available'];
    }
}

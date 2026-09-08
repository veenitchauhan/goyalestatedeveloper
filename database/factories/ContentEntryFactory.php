<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ContentEntryFactory extends Factory
{
    public function definition(): array
    {
        return ['type' => 'project', 'slug' => fake()->unique()->slug(), 'title' => fake()->sentence(), 'status' => 'draft'];
    }
}

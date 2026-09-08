<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class SeoPageFactory extends Factory
{
    public function definition(): array
    {
        return ['path' => '/'.fake()->unique()->slug(), 'data' => [], 'version' => 1];
    }
}

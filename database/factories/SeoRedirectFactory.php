<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class SeoRedirectFactory extends Factory
{
    public function definition(): array
    {
        return ['source' => '/'.fake()->unique()->slug(), 'destination' => '/', 'status' => 301];
    }
}

<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class MediaFactory extends Factory
{
    public function definition(): array
    {
        return ['title' => fake()->sentence(3), 'original_name' => 'image.png', 'original_path' => 'media/test.png', 'mime' => 'image/png', 'category' => 'Company', 'alt' => 'Test illustration'];
    }
}

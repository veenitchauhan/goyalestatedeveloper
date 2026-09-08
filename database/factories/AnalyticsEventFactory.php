<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class AnalyticsEventFactory extends Factory
{
    public function definition(): array
    {
        return ['visitor_session' => fake()->uuid(), 'event' => 'page_view', 'path' => '/', 'attribution' => []];
    }
}

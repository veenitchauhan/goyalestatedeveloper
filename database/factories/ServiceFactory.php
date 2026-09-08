<?php

namespace Database\Factories;

use App\Models\BusinessUnit;
use App\Models\ContentEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceFactory extends Factory
{
    public function definition(): array
    {
        return ['content_entry_id' => ContentEntry::factory()->state(['type' => 'service']), 'business_unit_id' => BusinessUnit::factory(), 'delivery_scope' => fake()->paragraph()];
    }
}

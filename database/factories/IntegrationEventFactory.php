<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class IntegrationEventFactory extends Factory
{
    public function definition(): array
    {
        return ['external_id' => fake()->uuid(), 'channel' => 'call', 'payload_hash' => hash('sha256', fake()->uuid())];
    }
}

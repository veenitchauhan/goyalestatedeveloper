<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class EnquiryFactory extends Factory
{
    public function definition(): array
    {
        return ['name' => fake()->name(), 'email' => fake()->safeEmail(), 'type' => 'Construction', 'location' => fake()->city(), 'message' => fake()->paragraph(), 'consented_at' => now(), 'consent_version' => 'enquiry-v1', 'status' => 'new'];
    }
}

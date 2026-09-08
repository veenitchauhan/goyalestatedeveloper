<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CandidateFactory extends Factory
{
    public function definition(): array
    {
        return ['job_title' => 'General application', 'name' => fake()->name(), 'email' => fake()->safeEmail(), 'phone' => '1234567890', 'profile' => [], 'resume_path' => 'resumes/test.pdf', 'consented_at' => now(), 'status' => 'New', 'version' => 1];
    }
}

<?php

namespace Database\Factories;

use App\Models\Enquiry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EnquiryNoteFactory extends Factory
{
    public function definition(): array
    {
        return ['enquiry_id' => Enquiry::factory(), 'user_id' => User::factory(), 'body' => fake()->sentence(), 'changes' => []];
    }
}

<?php

namespace Database\Factories;

use App\Models\ContentEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

class TeamMemberFactory extends Factory
{
    public function definition(): array
    {
        return ['content_entry_id' => ContentEntry::factory()->state(['type' => 'team_member']), 'designation' => 'Engineer'];
    }
}

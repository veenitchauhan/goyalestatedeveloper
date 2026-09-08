<?php

namespace Database\Factories;

use App\Models\ContentEntry;
use App\Models\TeamMember;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeStoryFactory extends Factory
{
    public function definition(): array
    {
        return ['content_entry_id' => ContentEntry::factory()->state(['type' => 'employee_story']), 'team_member_id' => TeamMember::factory()];
    }
}

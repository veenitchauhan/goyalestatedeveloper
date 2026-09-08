<?php

namespace Database\Factories;

use App\Models\ContentEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompanyMilestoneFactory extends Factory
{
    public function definition(): array
    {
        return ['content_entry_id' => ContentEntry::factory()->state(['type' => 'company_milestone']), 'occurred_on' => '2026-01-01', 'timeline' => 'company'];
    }
}

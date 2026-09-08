<?php

namespace Database\Factories;

use App\Models\ContentEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompanyPageFactory extends Factory
{
    public function definition(): array
    {
        return ['content_entry_id' => ContentEntry::factory()->state(['type' => 'company_page']), 'topic' => 'story'];
    }
}

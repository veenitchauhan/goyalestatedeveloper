<?php

namespace Database\Seeders;

use App\Models\ContentEntry;
use App\Services\ContentPublisher;
use Illuminate\Database\Seeder;

class CorporateContentSeeder extends Seeder
{
    public function run(): void
    {
        $outlines = [
            'company_page' => ['our-story' => ['Our story', ['topic' => 'story']], 'our-approach' => ['Our approach', ['topic' => 'approach']], 'quality' => ['Quality from foundation to finish', ['topic' => 'quality']], 'safety' => ['Safety is built into every project', ['topic' => 'safety']], 'sustainability' => ['Sustainability', ['topic' => 'sustainability']], 'future-vision' => ['Building towards what’s next', ['topic' => 'future-vision']]],
            'business_unit' => ['construction' => ['Construction', []], 'infrastructure' => ['Infrastructure', []], 'project-delivery' => ['Project delivery', []]],
            'capability' => ['engineering-planning' => ['Engineering & planning', []], 'construction-execution' => ['Construction execution', []], 'project-management' => ['Project management', []], 'procurement-coordination' => ['Procurement & coordination', []], 'quality' => ['Quality', []], 'safety' => ['HSE / safety', []]],
        ];
        foreach ($outlines as $type => $pages) {
            foreach ($pages as $slug => [$title, $facts]) {
                $entry = ContentEntry::firstOrCreate(['type' => $type, 'slug' => $slug], ['title' => $title]);
                if (! $entry->revisions()->exists()) {
                    app(ContentPublisher::class)->save($entry, ['type' => $type, 'slug' => $slug, 'title' => $title, 'summary' => '', 'body' => '', 'facts' => $facts, 'order' => 0, 'featured' => false, 'seo_title' => '', 'seo_description' => '', 'source_note' => 'Draft outline from the supplied product specification. Add verified company content before approval.'], 0);
                }
            }
        }
    }
}

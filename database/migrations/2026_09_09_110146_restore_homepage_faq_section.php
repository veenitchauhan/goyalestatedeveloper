<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $section = json_decode(<<<'JSON'
{
  "id": "homepage-faq",
  "nav": "",
  "enabled": true,
  "order": 70,
  "eyebrow": "INSIGHTS & KNOWLEDGE",
  "title": "A closer look\nat construction.",
  "text": "Project stories, construction knowledge and perspectives on the work behind the built environment.",
  "items": [
    {
      "title": "What is the company’s current focus?",
      "text": "Construction, infrastructure and project execution, with a current focus on the Tricity region."
    },
    {
      "title": "Is real estate an active business?",
      "text": "Development and real estate are part of the long-term vision. They are not presented as an active property-sales business."
    },
    {
      "title": "How can I discuss a project?",
      "text": "Use the enquiry form to share the project type, location and scope of your requirement."
    }
  ],
  "featured_title": "Ideas, insights & answers"
}
JSON, true, 512, JSON_THROW_ON_ERROR);
        DB::transaction(function () use ($section): void {
            foreach (['homepages' => 'content', 'content_revisions' => 'payload'] as $table => $column) {
                $query = DB::table($table);
                if ($table === 'content_revisions') {
                    $query->whereIn('content_entry_id', DB::table('content_entries')->where('type', 'homepage')->select('id'));
                }
                foreach ($query->get() as $record) {
                    $payload = json_decode($record->{$column}, true);
                    if (in_array('homepage-faq', array_column($payload['sections'] ?? [], 'id'))) {
                        continue;
                    }
                    $payload['sections'][] = $section;
                    usort($payload['sections'], fn (array $first, array $second): int => $first['order'] <=> $second['order']);
                    DB::table($table)->where('id', $record->id)->update([$column => json_encode($payload, JSON_THROW_ON_ERROR)]);
                }
            }
        });
    }

    public function down(): void
    {
        DB::transaction(function (): void {
            foreach (['homepages' => 'content', 'content_revisions' => 'payload'] as $table => $column) {
                $query = DB::table($table);
                if ($table === 'content_revisions') {
                    $query->whereIn('content_entry_id', DB::table('content_entries')->where('type', 'homepage')->select('id'));
                }
                foreach ($query->get() as $record) {
                    $payload = json_decode($record->{$column}, true);
                    $payload['sections'] = array_values(array_filter($payload['sections'] ?? [], fn (array $section): bool => $section['id'] !== 'homepage-faq'));
                    DB::table($table)->where('id', $record->id)->update([$column => json_encode($payload, JSON_THROW_ON_ERROR)]);
                }
            }
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $types = ['service', 'equipment', 'capability', 'business_unit', 'article'];
            $ids = DB::table('content_entries')->whereIn('type', $types)->pluck('id')->all();
            $removedPath = static fn (string $url): bool => (bool) preg_match('~^/(business|capabilities|insights)(/|$)~', parse_url($url, PHP_URL_PATH) ?: '');
            foreach (DB::table('content_entries')->whereIn('type', ['menu', 'cta', 'page'])->get() as $entry) {
                $revision = DB::table('content_revisions')->where('content_entry_id', $entry->id)->orderByDesc('version')->first();
                $payload = $revision ? json_decode($revision->payload, true) : [];
                if ($removedPath($payload['url'] ?? '') || ($entry->type === 'page' && in_array($entry->slug, ['business', 'capabilities', 'insights']))) {
                    $ids[] = $entry->id;
                }
            }
            DB::table('content_entries')->whereIn('id', $ids)->update(['published_revision_id' => null, 'scheduled_revision_id' => null]);
            foreach ($types as $type) {
                DB::table('content_entries')->where('type', $type)->delete();
            }
            DB::table('content_entries')->whereIn('id', $ids)->delete();
            $clean = static function (array $payload) use ($ids): array {
                if (isset($payload['sections'])) {
                    $payload['sections'] = array_values(array_filter($payload['sections'], fn (array $section): bool => ! in_array($section['id'], ['business', 'capabilities', 'insights'])));
                }
                foreach (['related_ids', 'cta_ids'] as $key) {
                    if (isset($payload[$key])) {
                        $payload[$key] = array_values(array_diff($payload[$key], $ids));
                    }
                }
                foreach (['service_ids', 'equipment_ids'] as $key) {
                    if (isset($payload[$key])) {
                        $payload[$key] = [];
                    }
                }

                return $payload;
            };
            foreach (DB::table('content_revisions')->get() as $revision) {
                DB::table('content_revisions')->where('id', $revision->id)->update(['payload' => json_encode($clean(json_decode($revision->payload, true)), JSON_THROW_ON_ERROR)]);
            }
            foreach (DB::table('homepages')->get() as $home) {
                DB::table('homepages')->where('id', $home->id)->update(['content' => json_encode($clean(json_decode($home->content, true)), JSON_THROW_ON_ERROR)]);
            }
            foreach (DB::table('seo_pages')->get() as $page) {
                if ($removedPath($page->path)) {
                    DB::table('seo_pages')->where('id', $page->id)->delete();
                }
            }
            foreach (DB::table('seo_redirects')->get() as $redirect) {
                if ($removedPath($redirect->source) || $removedPath($redirect->destination)) {
                    DB::table('seo_redirects')->where('id', $redirect->id)->delete();
                }
            }
        });
    }

    public function down(): void
    {
        // Deleted editorial content cannot be reconstructed by rolling back a migration.
    }
};

<?php

namespace App\Console\Commands;

use App\Models\Candidate;
use App\Models\ContentEntry;
use App\Models\Enquiry;
use App\Models\Media;
use App\Models\SiteSetting;
use App\Services\ContentPublisher;
use Database\Seeders\DemoContentSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DemoContentCommand extends Command
{
    protected $signature = 'demo:content {--remove : Archive demo pages and media and delete only registered synthetic enquiries/candidates}';

    protected $description = 'Create or remove the explicitly labelled local CMS demo dataset';

    public function handle(): int
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->error('Demo content commands run only in local development.');

            return self::FAILURE;
        }
        if (! $this->option('remove')) {
            return $this->call('db:seed', ['--class' => DemoContentSeeder::class, '--force' => true]);
        }
        $setting = SiteSetting::where('key', 'demo_content')->first();
        if (! $setting) {
            $this->info('No registered demo dataset.');

            return self::SUCCESS;
        }
        DB::transaction(function () use ($setting): void {
            $data = $setting->data;
            foreach (ContentEntry::whereIn('id', $data['entry_ids'])->where('status', '!=', 'archived')->get() as $entry) {
                app(ContentPublisher::class)->transition($entry, 'archive', (int) $entry->revisions()->max('version'));
            }
            Media::whereIn('id', $data['media_ids'])->update(['archived_at' => now(), 'is_public' => false, 'publication_status' => 'draft']);
            Enquiry::whereIn('id', $data['enquiry_ids'])->delete();
            Candidate::whereIn('id', $data['candidate_ids'])->delete();
            SiteSetting::updateOrCreate(['key' => 'future_developments'], ['data' => $data['previous_developments'] ?? ['enabled' => false, 'version' => 0]]);
            $setting->delete();
        });
        $this->info('Demo pages/media archived; registered synthetic enquiries and candidates removed. Existing content preserved.');

        return self::SUCCESS;
    }
}

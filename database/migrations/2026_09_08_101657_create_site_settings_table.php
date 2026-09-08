<?php

use App\Models\ContentEntry;
use App\Models\SiteSetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->json('data');
            $table->timestamps();
        });
        $data = SiteSetting::defaults();
        SiteSetting::create(['key' => 'global', 'data' => $data]);
        $entry = ContentEntry::create(['type' => 'settings', 'slug' => 'global', 'title' => 'Website settings', 'status' => 'published']);
        $revision = $entry->revisions()->create(['version' => 1, 'payload' => $data]);
        $entry->update(['published_revision_id' => $revision->id, 'published_at' => now()]);
    }

    public function down(): void
    {
        $entry = ContentEntry::where('type', 'settings')->where('slug', 'global')->first();
        if ($entry) {
            $entry->update(['published_revision_id' => null, 'scheduled_revision_id' => null]);
            $entry->delete();
        }
        Schema::dropIfExists('site_settings');
    }
};

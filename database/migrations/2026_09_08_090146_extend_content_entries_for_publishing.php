<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_entries', function (Blueprint $table) {
            $table->foreignId('published_revision_id')->nullable()->constrained('content_revisions')->nullOnDelete();
            $table->foreignId('scheduled_revision_id')->nullable()->constrained('content_revisions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('content_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('published_revision_id');
            $table->dropConstrainedForeignId('scheduled_revision_id');
        });
    }
};

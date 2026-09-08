<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media', function (Blueprint $table) {
            $table->string('publication_status')->default('draft');
            $table->timestamp('archived_at')->nullable();
            $table->string('document_category')->nullable();
        });
        DB::table('media')->where('is_public', true)->update(['publication_status' => 'published']);
    }

    public function down(): void
    {
        Schema::table('media', fn (Blueprint $table) => $table->dropColumn(['publication_status', 'archived_at', 'document_category']));
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enquiries', function (Blueprint $table) {
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('content_entry_id')->nullable()->constrained('content_entries')->nullOnDelete();
            $table->json('details')->nullable();
            $table->json('attribution')->nullable();
            $table->timestamp('follow_up_at')->nullable()->index();
            $table->string('qualification', 20)->default('pending');
            $table->unsignedInteger('version')->default(1);
            $table->timestamp('notified_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('enquiries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assigned_to');
            $table->dropConstrainedForeignId('content_entry_id');
            $table->dropColumn(['details', 'attribution', 'follow_up_at', 'qualification', 'version', 'notified_at']);
        });
    }
};

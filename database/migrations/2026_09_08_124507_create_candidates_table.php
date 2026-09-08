<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_entry_id')->nullable()->constrained()->nullOnDelete();
            $table->string('job_title');
            $table->string('name', 150);
            $table->string('email');
            $table->string('phone', 40);
            $table->json('profile');
            $table->string('resume_path');
            $table->string('status', 30)->default('New')->index();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->text('internal_notes')->nullable();
            $table->timestamp('interview_at')->nullable();
            $table->text('interview_notes')->nullable();
            $table->timestamp('consented_at');
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidates');
    }
};

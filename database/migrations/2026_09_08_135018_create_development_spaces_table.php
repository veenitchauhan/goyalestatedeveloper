<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('development_spaces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_entry_id')->constrained()->restrictOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('development_spaces')->restrictOnDelete();
            $table->string('kind', 20);
            $table->string('name', 100);
            $table->string('status', 20)->default('Available');
            $table->boolean('is_public')->default(false);
            $table->json('details');
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
            $table->unique(['content_entry_id', 'parent_id', 'kind', 'name'], 'development_space_name_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('development_spaces');
    }
};

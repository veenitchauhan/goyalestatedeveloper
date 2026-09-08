<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_entry_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('topic', 40)->index();
            $table->timestamps();
        });
        Schema::create('business_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_entry_id')->unique()->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_entry_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('business_unit_id')->constrained()->restrictOnDelete();
            $table->text('delivery_scope');
            $table->text('requirements')->nullable();
            $table->timestamps();
        });
        Schema::create('capabilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_entry_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('focus_area')->nullable();
            $table->text('approach')->nullable();
            $table->timestamps();
        });
        Schema::create('equipment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_entry_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('category')->index();
            $table->string('model')->nullable();
            $table->string('manufacturer')->nullable();
            $table->string('capacity')->nullable();
            $table->text('application')->nullable();
            $table->unsignedInteger('quantity')->nullable();
            $table->string('operating_status')->nullable();
            $table->timestamps();
        });
        Schema::create('team_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_entry_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('designation');
            $table->string('department')->nullable()->index();
            $table->text('experience')->nullable();
            $table->unsignedSmallInteger('joining_year')->nullable();
            $table->timestamps();
        });
        Schema::create('company_milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_entry_id')->unique()->constrained()->cascadeOnDelete();
            $table->date('occurred_on')->index();
            $table->string('timeline', 30)->index();
            $table->timestamps();
        });
        Schema::create('employee_stories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_entry_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('team_member_id')->constrained()->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['employee_stories', 'company_milestones', 'team_members', 'equipment', 'capabilities', 'services', 'business_units', 'company_pages'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};

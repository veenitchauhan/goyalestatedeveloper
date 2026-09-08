<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enquiries', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('email', 254);
            $table->string('phone', 25)->nullable();
            $table->string('type', 80);
            $table->string('location', 150);
            $table->text('message');
            $table->timestamp('consented_at');
            $table->string('consent_version');
            $table->string('status')->default('new')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enquiries');
    }
};

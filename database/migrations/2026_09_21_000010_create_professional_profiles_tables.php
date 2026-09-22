<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('professional_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('headline')->nullable();
            $table->text('summary')->nullable();
            $table->string('location')->nullable();
            $table->string('website_url')->nullable();
            $table->string('github_url')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->json('skills')->nullable();
            $table->string('visibility')->default('public');
            $table->string('availability')->default('not_looking');
            $table->timestamps();
        });

        Schema::create('profile_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('professional_profile_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('title');
            $table->string('organization')->nullable();
            $table->text('description')->nullable();
            $table->date('started_at')->nullable();
            $table->date('ended_at')->nullable();
            $table->boolean('is_current')->default(false);
            $table->timestamps();
            $table->index(['professional_profile_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_entries');
        Schema::dropIfExists('professional_profiles');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('posted_by')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->string('company_name');
            $table->text('description');
            $table->string('location')->nullable();
            $table->string('workplace_type');
            $table->string('employment_type');
            $table->decimal('salary_min', 12, 2)->nullable();
            $table->decimal('salary_max', 12, 2)->nullable();
            $table->char('currency', 3)->default('USD');
            $table->string('application_url')->nullable();
            $table->string('status')->default('open');
            $table->date('expires_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'workplace_type', 'employment_type']);
        });

        Schema::create('job_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('job_listing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('applicant_id')->constrained('users')->cascadeOnDelete();
            $table->text('cover_letter');
            $table->string('resume_url')->nullable();
            $table->string('status')->default('submitted');
            $table->timestamps();
            $table->unique(['job_listing_id', 'applicant_id']);
        });

        Schema::create('saved_jobs', function (Blueprint $table) {
            $table->foreignId('job_listing_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['job_listing_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_jobs');
        Schema::dropIfExists('job_applications');
        Schema::dropIfExists('job_listings');
    }
};
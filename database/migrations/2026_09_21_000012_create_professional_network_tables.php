<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requester_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('recipient_id')->constrained('users')->cascadeOnDelete();
            $table->string('status')->default('pending');
            $table->timestamps();
            $table->index(['recipient_id', 'status']);
            $table->unique(['requester_id', 'recipient_id']);
        });

        Schema::create('endorsements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('endorser_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('endorsed_user_id')->constrained('users')->cascadeOnDelete();
            $table->string('skill');
            $table->timestamps();
            $table->unique(['endorser_id', 'endorsed_user_id', 'skill']);
        });

        Schema::create('recommendations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('recommender_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('recommended_user_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->string('status')->default('pending');
            $table->timestamps();
            $table->unique(['recommender_id', 'recommended_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recommendations');
        Schema::dropIfExists('endorsements');
        Schema::dropIfExists('connections');
    }
};
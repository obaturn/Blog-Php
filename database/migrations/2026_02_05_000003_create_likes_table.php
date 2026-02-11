<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates likes table for post engagement.
     * Unique constraint ensures idempotent like operations.
     */
    public function up(): void
    {
        Schema::create('likes', function (Blueprint $table) {
            $table->id();
            
            // User who liked the post
            $table->foreignId('user_id')
                ->constrained()
                ->onDelete('cascade');
            
            // Post that was liked
            $table->foreignId('post_id')
                ->constrained()
                ->onDelete('cascade');
            
            $table->timestamps();
            
            // Unique constraint: A user can only like a post once (idempotency)
            $table->unique(['user_id', 'post_id']);
            
            // Indexes for performance
            $table->index('user_id');
            $table->index('post_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('likes');
    }
};

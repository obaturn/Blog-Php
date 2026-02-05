<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates the follows table for social connections between users.
     * Implements many-to-many self-referential relationship.
     */
    public function up(): void
    {
        Schema::create('follows', function (Blueprint $table) {
            $table->id();
            
            // User who is following someone
            $table->foreignId('follower_id')
                ->constrained('users')
                ->onDelete('cascade');
            
            // User who is being followed
            $table->foreignId('following_id')
                ->constrained('users')
                ->onDelete('cascade');
            
            $table->timestamps();
            
            // Ensure a user can only follow another user once (idempotency)
            $table->unique(['follower_id', 'following_id']);
            
            // Indexes for performance
            $table->index('follower_id');
            $table->index('following_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('follows');
    }
};

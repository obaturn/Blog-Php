<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates comments table with soft deletes.
     */
    public function up(): void
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            
            // Post being commented on
            $table->foreignId('post_id')
                ->constrained()
                ->onDelete('cascade');
            
            // User who made the comment
            $table->foreignId('user_id')
                ->constrained()
                ->onDelete('cascade');
            
            // Comment content
            $table->text('body');
            
            $table->timestamps();
            
            // Soft deletes for comment moderation
            $table->softDeletes();
            
            // Indexes for performance
            $table->index('post_id');
            $table->index('user_id');
            $table->index('created_at');
            $table->index('deleted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};

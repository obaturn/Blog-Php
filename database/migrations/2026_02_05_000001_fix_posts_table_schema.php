<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Fixes typo in 'tittle' column and adds proper fields for production.
     */
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            // Add user_id foreign key for post ownership
            $table->foreignId('user_id')->after('id')->constrained()->onDelete('cascade');
            
            // Rename 'tittle' to 'title'
            $table->renameColumn('tittle', 'title');
            
            // Rename 'image' to 'image_url' for clarity (stores URL, not binary)
            $table->renameColumn('image', 'image_url');
            
            // Add indexes for performance
            $table->index('user_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropIndex(['user_id']);
            $table->dropIndex(['created_at']);
            $table->dropColumn('user_id');
            
            $table->renameColumn('title', 'tittle');
            $table->renameColumn('image_url', 'image');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            // Composite index for feed queries (user's posts ordered by date)
            $table->index(['user_id', 'created_at'], 'posts_user_created_index');
            
            // Index for sorting by likes_count (trending/popular posts)
            $table->index('likes_count', 'posts_likes_count_index');
            
            // Index for published status + date (active content)
            $table->index(['is_published', 'created_at'], 'posts_published_date_index');
        });

        Schema::table('comments', function (Blueprint $table) {
            // Composite index for fetching recent comments on a post
            $table->index(['post_id', 'created_at'], 'comments_post_created_index');
            
            // Index for user's comments
            $table->index(['user_id', 'created_at'], 'comments_user_created_index');
        });

        Schema::table('likes', function (Blueprint $table) {
            // Composite unique index (prevent duplicate likes)
            $table->unique(['user_id', 'post_id'], 'likes_user_post_unique');
        });

        Schema::table('follows', function (Blueprint $table) {
            // Composite unique index (prevent duplicate follows)
            $table->unique(['follower_id', 'following_id'], 'follows_follower_following_unique');
            
            // Index for getting followers of a user
            $table->index(['following_id', 'created_at'], 'follows_following_date_index');
            
            // Index for getting who a user follows
            $table->index(['follower_id', 'created_at'], 'follows_follower_date_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex('posts_user_created_index');
            $table->dropIndex('posts_likes_count_index');
            $table->dropIndex('posts_published_date_index');
        });

        Schema::table('comments', function (Blueprint $table) {
            $table->dropIndex('comments_post_created_index');
            $table->dropIndex('comments_user_created_index');
        });

        Schema::table('likes', function (Blueprint $table) {
            $table->dropUnique('likes_user_post_unique');
        });

        Schema::table('follows', function (Blueprint $table) {
            $table->dropUnique('follows_follower_following_unique');
            $table->dropIndex('follows_following_date_index');
            $table->dropIndex('follows_follower_date_index');
        });
    }
};

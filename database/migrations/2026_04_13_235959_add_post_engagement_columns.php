<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add the materialized engagement columns expected by the app.
     */
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            if (!Schema::hasColumn('posts', 'likes_count')) {
                $table->unsignedInteger('likes_count')->default(0)->after('content');
            }

            if (!Schema::hasColumn('posts', 'comments_count')) {
                $table->unsignedInteger('comments_count')->default(0)->after('likes_count');
            }

            if (!Schema::hasColumn('posts', 'is_published')) {
                $table->boolean('is_published')->default(true)->after('comments_count');
            }
        });
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            if (Schema::hasColumn('posts', 'is_published')) {
                $table->dropColumn('is_published');
            }

            if (Schema::hasColumn('posts', 'comments_count')) {
                $table->dropColumn('comments_count');
            }

            if (Schema::hasColumn('posts', 'likes_count')) {
                $table->dropColumn('likes_count');
            }
        });
    }
};

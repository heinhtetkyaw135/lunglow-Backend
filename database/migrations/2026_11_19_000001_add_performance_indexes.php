<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->index(['role', 'status'], 'users_role_status_idx');
            $table->index('created_at', 'users_created_at_idx');
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->index('created_at', 'posts_created_at_idx');
        });

        Schema::table('blogs', function (Blueprint $table) {
            $table->index('created_at', 'blogs_created_at_idx');
        });

        Schema::table('comments', function (Blueprint $table) {
            $table->index('created_at', 'comments_created_at_idx');
        });

        Schema::table('reports', function (Blueprint $table) {
            $table->index(['status', 'created_at'], 'reports_status_created_idx');
        });

        Schema::table('user_requests', function (Blueprint $table) {
            $table->index(['status', 'created_at'], 'user_requests_status_created_idx');
        });

        Schema::table('user_notifications', function (Blueprint $table) {
            $table->index(['user_id', 'created_at'], 'user_notifs_user_created_idx');
            $table->index(['user_id', 'read_at'], 'user_notifs_user_read_idx');
        });

        Schema::table('challenges', function (Blueprint $table) {
            $table->index(['active', 'created_at'], 'challenges_active_created_idx');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_role_status_idx');
            $table->dropIndex('users_created_at_idx');
        });
        Schema::table('posts', fn(Blueprint $t) => $t->dropIndex('posts_created_at_idx'));
        Schema::table('blogs', fn(Blueprint $t) => $t->dropIndex('blogs_created_at_idx'));
        Schema::table('comments', fn(Blueprint $t) => $t->dropIndex('comments_created_at_idx'));
        Schema::table('reports', fn(Blueprint $t) => $t->dropIndex('reports_status_created_idx'));
        Schema::table('user_requests', fn(Blueprint $t) => $t->dropIndex('user_requests_status_created_idx'));
        Schema::table('user_notifications', function (Blueprint $table) {
            $table->dropIndex('user_notifs_user_created_idx');
            $table->dropIndex('user_notifs_user_read_idx');
        });
        Schema::table('challenges', fn(Blueprint $t) => $t->dropIndex('challenges_active_created_idx'));
    }
};

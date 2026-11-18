<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('user_notifications')) {
            return;
        }

        Schema::table('user_notifications', function (Blueprint $table) {
            if (!Schema::hasColumn('user_notifications', 'read_at')) {
                $table->timestamp('read_at')->nullable()->after('fetched_at');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('user_notifications') || !Schema::hasColumn('user_notifications', 'read_at')) {
            return;
        }

        Schema::table('user_notifications', function (Blueprint $table) {
            $table->dropColumn('read_at');
        });
    }
};

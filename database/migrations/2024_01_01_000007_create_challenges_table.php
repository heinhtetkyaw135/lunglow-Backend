<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('challenges', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('details')->nullable();
            $table->string('reward')->nullable();
            $table->unsignedInteger('days')->default(7);
            $table->boolean('active')->default(true);
            $table->string('emoji')->default('🎯');
            $table->string('cover')->nullable();
            $table->timestamps();
        });

        // Per-user progress on a challenge.
        Schema::create('challenge_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('challenge_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('progress')->default(0);
            $table->date('last_log_date')->nullable();
            $table->timestamps();

            $table->unique(['challenge_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('challenge_user');
        Schema::dropIfExists('challenges');
    }
};

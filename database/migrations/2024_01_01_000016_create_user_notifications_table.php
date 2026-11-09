<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Delivered notifications. The SPA polls /api/notifications/due;
        // unfetched rows are returned once, then marked fetched_at.
        Schema::create('user_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('kind')->default('info');      // peak-craving | info | ...
            $table->string('title');
            $table->text('body');
            $table->json('meta')->nullable();             // {day_index, slot_index}
            $table->timestamp('window_start_at')->nullable();
            $table->timestamp('fetched_at')->nullable();  // set when SPA picked it up
            $table->timestamps();

            $table->index(['user_id', 'fetched_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_notifications');
    }
};

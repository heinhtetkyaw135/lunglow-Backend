<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Each row is one craving event. The 7x6 heatmap grid derives each
        // cell via MAX(intensity) for that (day, slot).
        Schema::create('craving_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('day_index');   // 0 = Sun .. 6 = Sat
            $table->unsignedTinyInteger('slot_index');  // 0..5 time blocks
            $table->unsignedTinyInteger('intensity')->default(0); // 0 Low, 1 Moderate, 2 Peak
            $table->timestamp('occurred_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'day_index', 'slot_index']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('craving_logs');
    }
};

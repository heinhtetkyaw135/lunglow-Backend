<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Created/updated when a user marks a heatmap cell as Peak Craving.
        // The alert fires a notification at the START of that weekly window.
        Schema::create('peak_craving_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('day_index');   // 0 = Sun .. 6 = Sat
            $table->unsignedTinyInteger('slot_index');  // 0..5 (6-9AM .. 9-12PM)
            $table->boolean('active')->default(true);
            $table->timestamp('last_sent_at')->nullable(); // window start last delivered
            $table->timestamps();

            $table->unique(['user_id', 'day_index', 'slot_index']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('peak_craving_alerts');
    }
};

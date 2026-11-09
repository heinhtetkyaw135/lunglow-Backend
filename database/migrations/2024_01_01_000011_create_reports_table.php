<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reporter_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('target_type');           // post | blog | comment | profile-image
            $table->string('target_id')->nullable(); // string cast — frontend uses string ids
            $table->string('parent_id')->nullable();
            $table->string('target_author')->nullable();
            $table->string('reason')->nullable();
            $table->string('note')->nullable();
            $table->text('excerpt')->nullable();
            $table->string('status')->default('pending'); // pending | resolved | dismissed
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};

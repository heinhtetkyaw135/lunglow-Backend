<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // "requests" is a reserved-ish name; use user_requests for clarity.
        Schema::create('user_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('category')->nullable();   // New Feature | Challenge Idea | Account | Bug
            $table->string('subject')->nullable();
            $table->text('message')->nullable();
            $table->string('status')->default('pending'); // pending | in-progress | resolved
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_requests');
    }
};

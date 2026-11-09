<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blogs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('category')->default('Tips & Tricks');
            $table->string('author_role')->default('member'); // admin | member
            $table->json('images')->nullable();               // array of image paths
            $table->text('content')->nullable();
            $table->unsignedInteger('likes')->default(0);
            $table->timestamps();
        });

        Schema::create('blog_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blog_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['blog_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_likes');
        Schema::dropIfExists('blogs');
    }
};

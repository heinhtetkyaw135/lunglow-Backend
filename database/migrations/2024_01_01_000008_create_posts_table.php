<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('post_type')->default('tip');      // tip | progress | milestone
            $table->string('tip_category')->nullable();
            $table->string('title')->nullable();
            $table->text('content')->nullable();
            $table->string('image')->nullable();
            $table->string('badge_key')->nullable();
            $table->string('badge_custom')->nullable();
            $table->json('stats')->nullable();                // [{value,label|labelKey}]
            $table->json('reaction_counts')->nullable();      // {fire,clap,heart} baseline
            $table->timestamps();
        });

        // Unique per-user reaction so toggles stay consistent across sessions.
        Schema::create('post_reactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type');                            // fire | clap | heart
            $table->timestamps();

            $table->unique(['post_id', 'user_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('post_reactions');
        Schema::dropIfExists('posts');
    }
};

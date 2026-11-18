<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Idempotent: only create if missing (the earlier migration may have been
        // recorded as "Ran" without actually creating the table, or it was dropped).
        if (!Schema::hasTable('user_requests')) {
            Schema::create('user_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('category')->nullable();
                $table->string('subject')->nullable();
                $table->text('message')->nullable();
                $table->string('status')->default('pending');
                $table->timestamps();
            });
        }

        // Migrate any rows from the legacy `requests` table so nothing is lost.
        if (Schema::hasTable('requests')) {
            $existing = DB::table('user_requests')->count();
            if ($existing === 0) {
                $legacy = DB::table('requests')->get();
                foreach ($legacy as $row) {
                    DB::table('user_requests')->insert([
                        'user_id'    => $row->user_id,
                        'category'   => $row->category,
                        'subject'    => $row->subject,
                        'message'    => $row->message,
                        'status'     => $row->status ?? 'pending',
                        'created_at' => $row->created_at,
                        'updated_at' => $row->updated_at,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_requests');
    }
};

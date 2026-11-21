<?php

namespace Database\Seeders;

use App\Models\Blog;
use App\Models\Challenge;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ---- Admin ----
        $admin = User::updateOrCreate(
            ['email' => 'admin@lunglow.dev'],
            [
                'name' => 'Dr. Aye Chan (Admin)',
                'password' => Hash::make('Admin@2410'),
                'role' => 'admin',
                'status' => 'active',
                'bio' => 'Lung health researcher & Lunglow moderator. Every clean day counts.',
            ]
        );
        $admin->settings()->firstOrCreate([], ['daily_limit' => 20, 'cig_price' => 0.50]);
        foreach (['morning' => '08:00', 'peak' => '13:00', 'evening' => '21:00'] as $key => $time) {
            $admin->reminders()->firstOrCreate(['key' => $key], ['time' => $time, 'enabled' => true]);
        }

        // ---- Starter challenges ----
        Challenge::updateOrCreate(['title' => '7-Day Clean Challenge'], [
            'description' => 'Stay completely smoke-free for 7 consecutive days. Log a clean day every day this week to unlock your badge.',
            'details' => 'Log one clean day per 24 hours — you can only tap "Log Clean Day" once a day. Finish all 7 days to earn the Gold Lung Badge.',
            'reward' => 'Gold Lung Badge',
            'days' => 7,
            'active' => true,
            'emoji' => '🏆',
        ]);
        Challenge::updateOrCreate(['title' => 'Weekend Zero-Smoke Sprint'], [
            'description' => 'Keep Saturday and Sunday completely clean. Weekend triggers are the hardest — beat them for a shield badge.',
            'details' => 'This sprint only counts clean days logged on Saturday and Sunday (2 total). Complete both days to earn the Streak Shield badge.',
            'reward' => 'Streak Shield',
            'days' => 2,
            'active' => true,
            'emoji' => '⚡',
        ]);
        Challenge::updateOrCreate(['title' => 'Morning Breath Warrior'], [
            'description' => 'Complete the 3-minute breathing exercise every morning for 5 days straight — no morning cigarette.',
            'details' => 'Finish a 3-minute breathing session before noon, then log the day here. Complete 5 mornings to earn the Breath Master Badge.',
            'reward' => 'Breath Master Badge',
            'days' => 5,
            'active' => true,
            'emoji' => '🫁',
        ]);

        // ---- Official starter blog guide ----
        Blog::updateOrCreate(
            ['title' => 'How Deep Breathing Repairs Lung Capacity in the First 14 Days'],
            [
                'user_id' => $admin->id,
                'category' => 'Official Guide',
                'author_role' => 'admin',
                'images' => [],
                'content' => 'When you reduce your daily cigarette intake and practice 3-minute diaphragmatic breathing, residual carbon monoxide clears from your bloodstream within 12 to 24 hours. By day 14, bronchial tubes relax and cilia begin regrowing, measurably improving oxygen uptake during brisk walks.',
                'likes' => 0,
            ]
        );

        // ---- 10 Burmese Member Users (password: 12345678) + 20 Blog Feed Posts ----
        $this->call(BurmeseMembersBlogSeeder::class);
    }
}

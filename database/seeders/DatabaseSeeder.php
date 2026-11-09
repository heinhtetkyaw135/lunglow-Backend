<?php

namespace Database\Seeders;

use App\Models\Challenge;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ---- Admin ----
        $admin = User::firstOrCreate(
            ['email' => 'admin@lunglow.dev'],
            [
                'name' => 'Dr. Aye Chan (Admin)',
                'password' => Hash::make('admin123'),
                'role' => 'admin',
                'status' => 'active',
                'bio' => 'Lung health researcher & Lunglow moderator. Every clean day counts.',
            ]
        );
        $admin->settings()->firstOrCreate([], ['daily_limit' => 20, 'cig_price' => 0.50]);

        // ---- Demo member ----
        $member = User::firstOrCreate(
            ['email' => 'heinhtet@lunglow.dev'],
            [
                'name' => 'Hein Htet Kyaw',
                'password' => Hash::make('password'),
                'role' => 'member',
                'status' => 'active',
                'bio' => 'Two weeks into the journey — swapping smoke breaks for breathing breaks.',
            ]
        );
        $member->settings()->firstOrCreate([], ['daily_limit' => 20, 'cig_price' => 0.50]);
        foreach (['morning' => '08:00', 'peak' => '13:00', 'evening' => '21:00'] as $key => $time) {
            $member->reminders()->firstOrCreate(['key' => $key], ['time' => $time, 'enabled' => true]);
        }

        // ---- Starter challenges ----
        Challenge::firstOrCreate(['title' => '7-Day Clean Challenge'], [
            'description' => 'Stay completely smoke-free for 7 consecutive days. Log a clean day every day this week to unlock your badge.',
            'details' => 'Log one clean day per 24 hours — you can only tap "Log Clean Day" once a day. Finish all 7 days to earn the Gold Lung Badge.',
            'reward' => 'Gold Lung Badge',
            'days' => 7,
            'active' => true,
            'emoji' => '🏆',
        ]);
        Challenge::firstOrCreate(['title' => 'Weekend Zero-Smoke Sprint'], [
            'description' => 'Keep Saturday and Sunday completely clean. Weekend triggers are the hardest — beat them for a shield badge.',
            'details' => 'This sprint only counts clean days logged on Saturday and Sunday (2 total). Complete both days to earn the Streak Shield badge.',
            'reward' => 'Streak Shield',
            'days' => 2,
            'active' => true,
            'emoji' => '⚡',
        ]);

        // ---- Welcome community post ----
        if (Post::count() === 0) {
            $member->posts()->create([
                'post_type' => 'tip',
                'tip_category' => 'Tips & Tricks',
                'title' => 'Welcome to the Lunglow Community',
                'content' => 'Share your quit-smoking wins, tips, and milestones here. Every clean day makes your lung glow!',
                'reaction_counts' => ['fire' => 3, 'clap' => 5, 'heart' => 7],
            ]);
        }
    }
}

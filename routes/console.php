<?php

use App\Models\PeakCravingAlert;
use App\Models\UserNotification;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Fire peak-craving window-start notifications every minute (scheduler fallback;
// the SPA's /notifications/due poll is the primary trigger).
Schedule::call(function () {
    $grace = (int) config('services.peak_craving.grace_minutes', 10);
    PeakCravingAlert::where('active', true)->get()->each(function ($alert) use ($grace) {
        $windowStart = $alert->currentWindowStart();
        $now = now();
        if (
            $now->between($windowStart, $windowStart->copy()->addMinutes($grace))
            && (!$alert->last_sent_at || $alert->last_sent_at->lt($windowStart))
        ) {
            UserNotification::create([
                'user_id' => $alert->user_id,
                'kind' => 'peak-craving',
                'title' => 'Peak Craving Window',
                'body' => 'Your peak craving window is starting — stay strong!',
                'meta' => ['day_index' => $alert->day_index, 'slot_index' => $alert->slot_index],
                'window_start_at' => $windowStart,
            ]);
            $alert->update(['last_sent_at' => $windowStart]);
        }
    });
})->everyMinute();

<?php

namespace App\Console\Commands;

use App\Models\PeakCravingAlert;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Console\Command;

class TestPeakCravingAlert extends Command
{
    protected $signature = 'notifications:test-peak {userId} {--day=} {--slot=}';

    protected $description = 'Arm a peak-craving alert and immediately deliver it (test helper).';

    public function handle(): int
    {
        $user = User::findOrFail((int) $this->argument('userId'));
        $day = $this->option('day') !== null ? (int) $this->option('day') : now()->dayOfWeek;
        $slot = $this->option('slot') !== null ? (int) $this->option('slot') : intdiv(now()->hour, 3);

        $alert = PeakCravingAlert::updateOrCreate(
            ['user_id' => $user->id, 'day_index' => $day, 'slot_index' => $slot],
            ['active' => true]
        );

        // Force a "just started" window so delivery is due now.
        $windowStart = now()->subMinutes(2);
        $alert->update(['last_sent_at' => $windowStart->copy()->subWeek()]);

        UserNotification::create([
            'user_id' => $user->id,
            'kind' => 'peak-craving',
            'title' => 'Peak Craving Window',
            'body' => 'Your peak craving window is starting — stay strong!',
            'meta' => ['day_index' => $day, 'slot_index' => $slot],
            'window_start_at' => $windowStart,
        ]);

        $this->info("Armed day={$day} slot={$slot} and queued a test notification for user {$user->id}.");
        return self::SUCCESS;
    }
}

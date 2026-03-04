<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\TrialEndingNotification;
use App\Notifications\TrialExpiredNotification;
use App\Notifications\TrialLastDayNotification;
use Illuminate\Console\Command;

class TrialReminderCommand extends Command
{
    protected $signature = 'trial:reminders';
    protected $description = 'Send trial reminder notifications to users approaching trial expiry';

    public function handle(): int
    {
        $sent = 0;

        // 2 days before expiry
        foreach (User::whereDate('trial_ends_at', today()->addDays(2))
            ->whereDoesntHave('subscriptions')
            ->cursor() as $user) {
            $user->notify(new TrialEndingNotification);
            $sent++;
        }

        // 1 day before expiry
        foreach (User::whereDate('trial_ends_at', today()->addDays(1))
            ->whereDoesntHave('subscriptions')
            ->cursor() as $user) {
            $user->notify(new TrialLastDayNotification);
            $sent++;
        }

        // Day of expiry
        foreach (User::whereDate('trial_ends_at', today())
            ->whereDoesntHave('subscriptions')
            ->cursor() as $user) {
            $user->notify(new TrialExpiredNotification);
            $sent++;
        }

        $this->info("Sent {$sent} trial reminder(s).");

        return self::SUCCESS;
    }
}

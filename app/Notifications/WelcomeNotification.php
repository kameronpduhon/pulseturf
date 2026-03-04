<?php

namespace App\Notifications;

use App\Models\Business;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Business $business) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $competitorCount = $this->business->competitors()->count();

        return (new MailMessage)
            ->subject('Welcome to PulseTurf!')
            ->greeting("Hi {$notifiable->name},")
            ->line("Your business **{$this->business->name}** is all set up.")
            ->line("We're tracking {$competitorCount} " . ($competitorCount === 1 ? 'competitor' : 'competitors') . ' for you.')
            ->line('Your first intelligence briefing arrives Monday at 7 AM.')
            ->action('View Your Dashboard', route('home'));
    }
}

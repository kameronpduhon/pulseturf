<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TrialLastDayNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Tomorrow is your last day on PulseTurf')
            ->greeting("Hi {$notifiable->name},")
            ->line('**This is your last chance** to keep the competitive intelligence you have built up.')
            ->line('Tomorrow your trial ends and you will lose access to:')
            ->line('- **Weekly digests** — AI-powered summaries of your competitive landscape')
            ->line('- **Competitor tracking** — stay ahead of rivals in your market')
            ->line('- **Review monitoring** — never miss what patients are saying')
            ->line('Your data stays intact — subscribe now and pick up right where you left off.')
            ->action('Subscribe Now', route('settings', ['tab' => 'billing']));
    }
}

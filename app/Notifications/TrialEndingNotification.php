<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TrialEndingNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your PulseTurf trial ends in 2 days')
            ->greeting("Hi {$notifiable->name},")
            ->line('Your free trial ends in **2 days**. After that, you will lose access to:')
            ->line('- Weekly AI-powered competitive intelligence digests')
            ->line('- Competitor review tracking and monitoring')
            ->line('- Automated Google review scraping')
            ->line('Choose a plan now to keep your competitive edge uninterrupted.')
            ->action('Choose a Plan', route('billing'));
    }
}

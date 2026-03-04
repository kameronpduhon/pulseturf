<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionCancelledNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('PulseTurf: Subscription cancelled')
            ->greeting("Hi {$notifiable->name},")
            ->line('Your PulseTurf subscription has been cancelled.')
            ->line('You will continue to have full access until the end of your current billing period.')
            ->line('Your data — including your business profile, competitor list, and review history — will be preserved if you decide to come back.')
            ->action('Resubscribe', route('billing'));
    }
}

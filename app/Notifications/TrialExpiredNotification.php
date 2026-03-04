<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TrialExpiredNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your PulseTurf trial has ended')
            ->greeting("Hi {$notifiable->name},")
            ->line('Your PulseTurf trial has ended and your account is currently paused.')
            ->line('The good news: **all your data is still saved**. Your business profile, competitor list, and review history are waiting for you.')
            ->line('Reactivate anytime and you will be back up to speed in minutes — no setup required.')
            ->action('Reactivate Now', route('billing'));
    }
}

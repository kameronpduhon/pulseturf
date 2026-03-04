<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentFailedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('PulseTurf: Payment failed')
            ->greeting("Hi {$notifiable->name},")
            ->line("We weren't able to process your payment for PulseTurf.")
            ->line('Please update your payment method to avoid any interruption to your service.')
            ->action('Update Payment Method', route('settings', ['tab' => 'billing']));
    }
}

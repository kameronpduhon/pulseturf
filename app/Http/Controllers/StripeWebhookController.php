<?php

namespace App\Http\Controllers;

use App\Notifications\PaymentFailedNotification;
use App\Notifications\SubscriptionCancelledNotification;
use Laravel\Cashier\Http\Controllers\WebhookController;

class StripeWebhookController extends WebhookController
{
    public function handleInvoicePaymentFailed(array $payload): \Symfony\Component\HttpFoundation\Response
    {
        $user = $this->getUserByStripeId(
            $payload['data']['object']['customer'] ?? null
        );

        if ($user) {
            $user->notify(new PaymentFailedNotification);
        }

        return $this->successMethod();
    }

    public function handleCustomerSubscriptionDeleted(array $payload): \Symfony\Component\HttpFoundation\Response
    {
        $response = parent::handleCustomerSubscriptionDeleted($payload);

        $user = $this->getUserByStripeId(
            $payload['data']['object']['customer'] ?? null
        );

        if ($user) {
            $user->notify(new SubscriptionCancelledNotification);
        }

        return $response;
    }
}

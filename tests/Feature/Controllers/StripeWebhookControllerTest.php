<?php

namespace Tests\Feature\Controllers;

use App\Http\Controllers\StripeWebhookController;
use App\Models\User;
use App\Notifications\PaymentFailedNotification;
use App\Notifications\SubscriptionCancelledNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Tests for StripeWebhookController.
 *
 * We test the handler methods directly (bypassing Stripe signature
 * verification which requires a live secret and a real request body digest).
 * Cashier's WebhookController::handleWebhook() does the signature check;
 * our custom handlers are called after that verification passes, so testing
 * them in isolation gives us the coverage we need without involving Stripe.
 */
class StripeWebhookControllerTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function controller(): StripeWebhookController
    {
        return new StripeWebhookController;
    }

    /**
     * Build a minimal invoice.payment_failed payload.
     */
    private function invoicePaymentFailedPayload(string $customerId): array
    {
        return [
            'type' => 'invoice.payment_failed',
            'data' => [
                'object' => [
                    'id'       => 'in_test_123',
                    'customer' => $customerId,
                ],
            ],
        ];
    }

    /**
     * Build a minimal customer.subscription.deleted payload.
     * The subscription must have a stripe_id that matches a DB record for
     * parent::handleCustomerSubscriptionDeleted() to cancel it.
     */
    private function subscriptionDeletedPayload(string $customerId, string $subscriptionStripeId): array
    {
        return [
            'type' => 'customer.subscription.deleted',
            'data' => [
                'object' => [
                    'id'       => $subscriptionStripeId,
                    'customer' => $customerId,
                ],
            ],
        ];
    }

    // -------------------------------------------------------------------------
    // handleInvoicePaymentFailed
    // -------------------------------------------------------------------------

    public function test_handle_invoice_payment_failed_sends_notification_to_matching_user(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'trial_ends_at' => null,
            'stripe_id'     => 'cus_test_payment_failed',
        ]);

        $payload = $this->invoicePaymentFailedPayload('cus_test_payment_failed');

        $response = $this->controller()->handleInvoicePaymentFailed($payload);

        $this->assertEquals(200, $response->getStatusCode());
        Notification::assertSentTo($user, PaymentFailedNotification::class);
    }

    public function test_handle_invoice_payment_failed_sends_notification_only_once(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'trial_ends_at' => null,
            'stripe_id'     => 'cus_test_once',
        ]);

        $payload = $this->invoicePaymentFailedPayload('cus_test_once');

        $this->controller()->handleInvoicePaymentFailed($payload);

        Notification::assertSentToTimes($user, PaymentFailedNotification::class, 1);
    }

    public function test_handle_invoice_payment_failed_does_not_notify_when_customer_id_is_unknown(): void
    {
        Notification::fake();

        $payload = $this->invoicePaymentFailedPayload('cus_nonexistent_xyz');

        $response = $this->controller()->handleInvoicePaymentFailed($payload);

        $this->assertEquals(200, $response->getStatusCode());
        Notification::assertNothingSent();
    }

    public function test_handle_invoice_payment_failed_does_not_notify_when_customer_key_is_missing(): void
    {
        Notification::fake();

        // Payload missing the customer key entirely
        $payload = [
            'type' => 'invoice.payment_failed',
            'data' => [
                'object' => [
                    'id' => 'in_test_no_customer',
                ],
            ],
        ];

        $response = $this->controller()->handleInvoicePaymentFailed($payload);

        $this->assertEquals(200, $response->getStatusCode());
        Notification::assertNothingSent();
    }

    public function test_handle_invoice_payment_failed_does_not_cross_notify_other_users(): void
    {
        Notification::fake();

        $targetUser = User::factory()->create([
            'trial_ends_at' => null,
            'stripe_id'     => 'cus_target',
        ]);

        $otherUser = User::factory()->create([
            'trial_ends_at' => null,
            'stripe_id'     => 'cus_other',
        ]);

        $payload = $this->invoicePaymentFailedPayload('cus_target');

        $this->controller()->handleInvoicePaymentFailed($payload);

        Notification::assertSentTo($targetUser, PaymentFailedNotification::class);
        Notification::assertNotSentTo($otherUser, PaymentFailedNotification::class);
    }

    // -------------------------------------------------------------------------
    // handleCustomerSubscriptionDeleted
    // -------------------------------------------------------------------------

    public function test_handle_customer_subscription_deleted_sends_notification_to_matching_user(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'trial_ends_at' => null,
            'stripe_id'     => 'cus_test_cancelled',
        ]);

        // Create the subscription record so Cashier's parent handler can cancel it.
        $user->subscriptions()->create([
            'type'         => 'default',
            'stripe_id'    => 'sub_cancelled_123',
            'stripe_status' => 'active',
            'stripe_price' => 'price_starter_monthly',
        ]);

        $payload = $this->subscriptionDeletedPayload('cus_test_cancelled', 'sub_cancelled_123');

        $response = $this->controller()->handleCustomerSubscriptionDeleted($payload);

        $this->assertEquals(200, $response->getStatusCode());
        Notification::assertSentTo($user, SubscriptionCancelledNotification::class);
    }

    public function test_handle_customer_subscription_deleted_does_not_notify_when_customer_id_is_unknown(): void
    {
        Notification::fake();

        $payload = $this->subscriptionDeletedPayload('cus_nonexistent_abc', 'sub_no_match');

        $response = $this->controller()->handleCustomerSubscriptionDeleted($payload);

        $this->assertEquals(200, $response->getStatusCode());
        Notification::assertNothingSent();
    }

    public function test_handle_customer_subscription_deleted_does_not_notify_when_customer_id_is_null(): void
    {
        Notification::fake();

        // Stripe always sends the 'customer' key in its payload, even when it
        // is null. The parent handler accesses it directly (not null-safe), so
        // we must include the key; passing null exercises the "no billable found" path.
        $payload = [
            'type' => 'customer.subscription.deleted',
            'data' => [
                'object' => [
                    'id'       => 'sub_no_customer',
                    'customer' => null,
                ],
            ],
        ];

        $response = $this->controller()->handleCustomerSubscriptionDeleted($payload);

        $this->assertEquals(200, $response->getStatusCode());
        Notification::assertNothingSent();
    }

    public function test_handle_customer_subscription_deleted_does_not_cross_notify_other_users(): void
    {
        Notification::fake();

        $targetUser = User::factory()->create([
            'trial_ends_at' => null,
            'stripe_id'     => 'cus_target_del',
        ]);

        $otherUser = User::factory()->create([
            'trial_ends_at' => null,
            'stripe_id'     => 'cus_other_del',
        ]);

        $targetUser->subscriptions()->create([
            'type'         => 'default',
            'stripe_id'    => 'sub_target_del',
            'stripe_status' => 'active',
            'stripe_price' => 'price_starter_monthly',
        ]);

        $payload = $this->subscriptionDeletedPayload('cus_target_del', 'sub_target_del');

        $this->controller()->handleCustomerSubscriptionDeleted($payload);

        Notification::assertSentTo($targetUser, SubscriptionCancelledNotification::class);
        Notification::assertNotSentTo($otherUser, SubscriptionCancelledNotification::class);
    }

    public function test_handle_customer_subscription_deleted_marks_subscription_as_cancelled_in_db(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'trial_ends_at' => null,
            'stripe_id'     => 'cus_mark_cancelled',
        ]);

        $user->subscriptions()->create([
            'type'         => 'default',
            'stripe_id'    => 'sub_mark_cancelled',
            'stripe_status' => 'active',
            'stripe_price' => 'price_starter_monthly',
        ]);

        $payload = $this->subscriptionDeletedPayload('cus_mark_cancelled', 'sub_mark_cancelled');

        $this->controller()->handleCustomerSubscriptionDeleted($payload);

        // Cashier's parent handler sets stripe_status = 'canceled' (American spelling).
        $this->assertDatabaseHas('subscriptions', [
            'stripe_id'    => 'sub_mark_cancelled',
            'stripe_status' => 'canceled',
        ]);
    }
}

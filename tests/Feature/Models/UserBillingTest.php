<?php

namespace Tests\Feature\Models;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for billing-related methods on the User model.
 *
 * Covers: isSubscribedOrTrial(), competitorLimit().
 * Uses Cashier's database approach to create subscription records without
 * hitting the Stripe API.
 */
class UserBillingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Set deterministic test price IDs so config lookups work regardless
        // of what is in .env during testing.
        config([
            'services.stripe.prices.pro_monthly' => 'price_pro_m',
            'services.stripe.prices.pro_annual'  => 'price_pro_a',
            'services.stripe.prices.starter_monthly' => 'price_starter_m',
            'services.stripe.prices.starter_annual'  => 'price_starter_a',
        ]);
    }

    // -------------------------------------------------------------------------
    // isSubscribedOrTrial()
    // -------------------------------------------------------------------------

    public function test_is_subscribed_or_trial_returns_true_when_on_trial(): void
    {
        $user = User::factory()->create([
            'trial_ends_at' => now()->addDays(7),
        ]);

        $this->assertTrue($user->isSubscribedOrTrial());
    }

    public function test_is_subscribed_or_trial_returns_false_when_trial_just_expired(): void
    {
        $user = User::factory()->create([
            'trial_ends_at' => now()->subSecond(),
        ]);

        $this->assertFalse($user->isSubscribedOrTrial());
    }

    public function test_is_subscribed_or_trial_returns_false_when_expired_and_no_subscription(): void
    {
        $user = User::factory()->expired()->create();

        $this->assertFalse($user->isSubscribedOrTrial());
    }

    public function test_is_subscribed_or_trial_returns_false_when_no_trial_and_no_subscription(): void
    {
        $user = User::factory()->create(['trial_ends_at' => null]);

        $this->assertFalse($user->isSubscribedOrTrial());
    }

    public function test_is_subscribed_or_trial_returns_true_when_subscribed_with_active_subscription(): void
    {
        $user = User::factory()->create(['trial_ends_at' => null]);

        $user->subscriptions()->create([
            'type'         => 'default',
            'stripe_id'    => 'sub_test_active',
            'stripe_status' => 'active',
            'stripe_price' => 'price_starter_m',
        ]);

        $this->assertTrue($user->isSubscribedOrTrial());
    }

    public function test_is_subscribed_or_trial_returns_true_when_subscription_on_grace_period(): void
    {
        $user = User::factory()->create(['trial_ends_at' => null]);

        // A subscription with ends_at in the future is considered on grace period (still valid).
        $user->subscriptions()->create([
            'type'         => 'default',
            'stripe_id'    => 'sub_test_grace',
            'stripe_status' => 'active',
            'stripe_price' => 'price_starter_m',
            'ends_at'      => now()->addDays(5),
        ]);

        $this->assertTrue($user->isSubscribedOrTrial());
    }

    public function test_is_subscribed_or_trial_returns_true_when_both_trial_and_subscription_exist(): void
    {
        // Edge case: trial still running AND has a subscription.
        $user = User::factory()->create([
            'trial_ends_at' => now()->addDays(3),
        ]);

        $user->subscriptions()->create([
            'type'         => 'default',
            'stripe_id'    => 'sub_test_both',
            'stripe_status' => 'active',
            'stripe_price' => 'price_starter_m',
        ]);

        $this->assertTrue($user->isSubscribedOrTrial());
    }

    // -------------------------------------------------------------------------
    // competitorLimit()
    // -------------------------------------------------------------------------

    public function test_competitor_limit_returns_1_for_trial_user_with_no_subscription(): void
    {
        $user = User::factory()->create([
            'trial_ends_at' => now()->addDays(7),
        ]);

        $this->assertSame(1, $user->competitorLimit());
    }

    public function test_competitor_limit_returns_1_for_user_with_no_subscription(): void
    {
        $user = User::factory()->create(['trial_ends_at' => null]);

        $this->assertSame(1, $user->competitorLimit());
    }

    public function test_competitor_limit_returns_1_for_starter_monthly_plan(): void
    {
        $user = User::factory()->create(['trial_ends_at' => null]);

        $user->subscriptions()->create([
            'type'         => 'default',
            'stripe_id'    => 'sub_starter_m',
            'stripe_status' => 'active',
            'stripe_price' => 'price_starter_m',
        ]);

        $this->assertSame(1, $user->competitorLimit());
    }

    public function test_competitor_limit_returns_1_for_starter_annual_plan(): void
    {
        $user = User::factory()->create(['trial_ends_at' => null]);

        $user->subscriptions()->create([
            'type'         => 'default',
            'stripe_id'    => 'sub_starter_a',
            'stripe_status' => 'active',
            'stripe_price' => 'price_starter_a',
        ]);

        $this->assertSame(1, $user->competitorLimit());
    }

    public function test_competitor_limit_returns_3_for_pro_monthly_plan(): void
    {
        $user = User::factory()->create(['trial_ends_at' => null]);

        $user->subscriptions()->create([
            'type'         => 'default',
            'stripe_id'    => 'sub_pro_m',
            'stripe_status' => 'active',
            'stripe_price' => 'price_pro_m',
        ]);

        $this->assertSame(3, $user->competitorLimit());
    }

    public function test_competitor_limit_returns_3_for_pro_annual_plan(): void
    {
        $user = User::factory()->create(['trial_ends_at' => null]);

        $user->subscriptions()->create([
            'type'         => 'default',
            'stripe_id'    => 'sub_pro_a',
            'stripe_status' => 'active',
            'stripe_price' => 'price_pro_a',
        ]);

        $this->assertSame(3, $user->competitorLimit());
    }

    public function test_competitor_limit_returns_1_for_unknown_price_id(): void
    {
        $user = User::factory()->create(['trial_ends_at' => null]);

        $user->subscriptions()->create([
            'type'         => 'default',
            'stripe_id'    => 'sub_unknown',
            'stripe_status' => 'active',
            'stripe_price' => 'price_unknown_xyz',
        ]);

        $this->assertSame(1, $user->competitorLimit());
    }
}

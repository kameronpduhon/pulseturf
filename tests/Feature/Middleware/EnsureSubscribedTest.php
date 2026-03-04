<?php

namespace Tests\Feature\Middleware;

use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for the EnsureSubscribed middleware.
 *
 * The middleware allows access when the user is on an active trial
 * OR has an active Cashier subscription. Otherwise it redirects to /billing.
 * Authentication and email verification are handled by upstream middleware.
 */
class EnsureSubscribedTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Create a user with an active business so setup.complete middleware passes,
     * then create a Cashier subscription record directly in the database.
     */
    private function makeSubscribedUser(string $stripePrice = 'price_starter_monthly'): User
    {
        $user = User::factory()->create(['trial_ends_at' => null]);
        Business::factory()->active()->create(['user_id' => $user->id]);

        $user->subscriptions()->create([
            'type'         => 'default',
            'stripe_id'    => 'sub_test_' . $user->id,
            'stripe_status' => 'active',
            'stripe_price' => $stripePrice,
        ]);

        return $user;
    }

    /**
     * Create a user on an active trial with an active business.
     */
    private function makeTrialUser(): User
    {
        $user = User::factory()->create([
            'trial_ends_at' => now()->addDays(7),
        ]);
        Business::factory()->active()->create(['user_id' => $user->id]);

        return $user;
    }

    /**
     * Create a user whose trial has expired and has no subscription.
     */
    private function makeExpiredUser(): User
    {
        $user = User::factory()->expired()->create();
        Business::factory()->active()->create(['user_id' => $user->id]);

        return $user;
    }

    // -------------------------------------------------------------------------
    // Trial users
    // -------------------------------------------------------------------------

    public function test_trial_user_can_access_home(): void
    {
        $user = $this->makeTrialUser();

        $response = $this->actingAs($user)->get('/home');

        $response->assertOk();
    }

    // -------------------------------------------------------------------------
    // Subscribed users
    // -------------------------------------------------------------------------

    public function test_subscribed_user_can_access_home(): void
    {
        $user = $this->makeSubscribedUser();

        $response = $this->actingAs($user)->get('/home');

        $response->assertOk();
    }

    public function test_subscribed_user_can_access_profile(): void
    {
        $user = $this->makeSubscribedUser();

        $response = $this->actingAs($user)->get('/profile');

        $response->assertOk();
    }

    // -------------------------------------------------------------------------
    // Expired trial — no subscription
    // -------------------------------------------------------------------------

    public function test_expired_trial_user_is_redirected_to_billing(): void
    {
        $user = $this->makeExpiredUser();

        $response = $this->actingAs($user)->get('/home');

        $response->assertRedirect(route('billing'));
    }

    public function test_expired_trial_user_is_redirected_from_profile_to_billing(): void
    {
        $user = $this->makeExpiredUser();

        $response = $this->actingAs($user)->get('/profile');

        $response->assertRedirect(route('billing'));
    }

    // -------------------------------------------------------------------------
    // User with null trial_ends_at and no subscription (never had a trial)
    // -------------------------------------------------------------------------

    public function test_user_with_no_trial_and_no_subscription_is_redirected_to_billing(): void
    {
        $user = User::factory()->create(['trial_ends_at' => null]);
        Business::factory()->active()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get('/home');

        $response->assertRedirect(route('billing'));
    }

    // -------------------------------------------------------------------------
    // Cancelled subscription still in grace period counts as subscribed
    // -------------------------------------------------------------------------

    public function test_user_with_cancelled_subscription_on_grace_period_can_access_home(): void
    {
        $user = User::factory()->create(['trial_ends_at' => null]);
        Business::factory()->active()->create(['user_id' => $user->id]);

        // Grace period: cancelled but ends_at is in the future
        $user->subscriptions()->create([
            'type'         => 'default',
            'stripe_id'    => 'sub_grace_' . $user->id,
            'stripe_status' => 'active',
            'stripe_price' => 'price_starter_monthly',
            'ends_at'      => now()->addDays(10),
        ]);

        $response = $this->actingAs($user)->get('/home');

        $response->assertOk();
    }
}

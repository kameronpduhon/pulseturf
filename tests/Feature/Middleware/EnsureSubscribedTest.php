<?php

namespace Tests\Feature\Middleware;

use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Stripe\SetupIntent;
use Tests\TestCase;

/**
 * Tests for the EnsureSubscribed middleware.
 *
 * The middleware allows access when the user is on an active trial
 * OR has an active Cashier subscription. Otherwise it redirects to
 * /settings?tab=billing (the settings page, billing tab).
 * Authentication and email verification are handled by upstream middleware.
 */
class EnsureSubscribedTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Create a fake SetupIntent so mount() doesn't hit the Stripe network.
     */
    private function fakeSetupIntent(): SetupIntent
    {
        return SetupIntent::constructFrom([
            'id'            => 'seti_test_fake',
            'object'        => 'setup_intent',
            'client_secret' => 'seti_test_fake_secret',
            'status'        => 'requires_payment_method',
        ]);
    }

    /**
     * Create a partial mock of a user that stubs createSetupIntent().
     * Use this when the test will actually render SettingsPage (assertOk on /settings).
     */
    private function mockUser(User $user): \Mockery\MockInterface
    {
        $mock = \Mockery::mock($user)->makePartial();
        $mock->shouldReceive('createSetupIntent')->andReturn($this->fakeSetupIntent());

        return $mock;
    }

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

    public function test_subscribed_user_can_access_settings(): void
    {
        // /settings calls createSetupIntent() in mount() — use a partial mock.
        $user = $this->makeSubscribedUser();
        $user->refresh();
        $mock = $this->mockUser($user);

        $response = $this->actingAs($mock)->get('/settings');

        $response->assertOk();
    }

    // -------------------------------------------------------------------------
    // Expired trial — no subscription
    // -------------------------------------------------------------------------

    public function test_expired_trial_user_is_redirected_to_settings_billing_tab(): void
    {
        $user = $this->makeExpiredUser();

        $response = $this->actingAs($user)->get('/home');

        $response->assertRedirect(route('settings', ['tab' => 'billing']));
    }

    public function test_expired_trial_user_can_still_access_settings(): void
    {
        // /settings is NOT behind the subscribed middleware, so expired users
        // can reach it to subscribe (that is its purpose).
        // Use a partial mock to avoid hitting the Stripe API in mount().
        $user = $this->makeExpiredUser();
        $user->refresh();
        $mock = $this->mockUser($user);

        $response = $this->actingAs($mock)->get('/settings');

        $response->assertOk();
    }

    // -------------------------------------------------------------------------
    // User with null trial_ends_at and no subscription (never had a trial)
    // -------------------------------------------------------------------------

    public function test_user_with_no_trial_and_no_subscription_is_redirected_to_settings_billing_tab(): void
    {
        $user = User::factory()->create(['trial_ends_at' => null]);
        Business::factory()->active()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get('/home');

        $response->assertRedirect(route('settings', ['tab' => 'billing']));
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

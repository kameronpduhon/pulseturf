<?php

namespace Tests\Feature\Routes;

use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Stripe\SetupIntent;
use Tests\TestCase;

/**
 * Tests for /settings (billing tab) route access control and the interaction
 * between setup.complete and subscribed middleware on /home.
 *
 * Route middleware stack (from routes/web.php):
 *   /settings — auth, verified, setup.complete  (NOT subscribed — intentional)
 *   /home     — auth, verified, setup.complete, subscribed
 *   /billing  — legacy redirect to /settings?tab=billing
 *
 * Because /settings renders the SettingsPage Livewire component, which calls
 * $user->createSetupIntent() in mount(), tests that must reach a 200 response
 * use a Mockery partial mock that stubs out that one Stripe API call.
 * Tests that assert a redirect (auth, setup, subscription guards) never reach
 * the component render phase, so they use plain User models.
 */
class BillingRoutesTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Create a fake \Stripe\SetupIntent so mount() doesn't hit the network.
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
     * Create a user with an active business, returning a Mockery partial mock
     * that stubs createSetupIntent(). Use this for tests that assert assertOk()
     * on /settings (the component render phase must succeed).
     */
    private function mockUserWithActiveBusiness(array $userAttributes = []): \Mockery\MockInterface
    {
        $user = User::factory()->create($userAttributes);
        Business::factory()->active()->create(['user_id' => $user->id]);
        $user->refresh();

        $mock = \Mockery::mock($user)->makePartial();
        $mock->shouldReceive('createSetupIntent')->andReturn($this->fakeSetupIntent());

        return $mock;
    }

    /**
     * Create a plain user with an active business (for redirect assertions —
     * the component is never rendered, so createSetupIntent is not called).
     */
    private function userWithActiveBusiness(array $userAttributes = []): User
    {
        $user = User::factory()->create($userAttributes);
        Business::factory()->active()->create(['user_id' => $user->id]);

        return $user;
    }

    /**
     * Create a mocked user with an active business and an active Cashier subscription.
     */
    private function mockSubscribedUserWithBusiness(): \Mockery\MockInterface
    {
        $mock = $this->mockUserWithActiveBusiness(['trial_ends_at' => null]);

        $mock->subscriptions()->create([
            'type'         => 'default',
            'stripe_id'    => 'sub_routes_test_' . $mock->id,
            'stripe_status' => 'active',
            'stripe_price' => 'price_starter_monthly',
        ]);

        return $mock;
    }

    /**
     * Create a plain subscribed user (for /home tests which never render SettingsPage).
     */
    private function subscribedUserWithBusiness(): User
    {
        $user = $this->userWithActiveBusiness(['trial_ends_at' => null]);

        $user->subscriptions()->create([
            'type'         => 'default',
            'stripe_id'    => 'sub_home_routes_' . $user->id,
            'stripe_status' => 'active',
            'stripe_price' => 'price_starter_monthly',
        ]);

        return $user;
    }

    // -------------------------------------------------------------------------
    // /billing legacy redirect
    // -------------------------------------------------------------------------

    public function test_billing_url_redirects_to_settings_billing_tab(): void
    {
        $user = $this->userWithActiveBusiness([
            'trial_ends_at' => now()->addDays(7),
        ]);

        $response = $this->actingAs($user)->get('/billing');

        $response->assertRedirect('/settings?tab=billing');
    }

    public function test_billing_redirect_requires_authentication(): void
    {
        $response = $this->get('/billing');

        $response->assertRedirect(route('login'));
    }

    // -------------------------------------------------------------------------
    // /settings — authentication
    // -------------------------------------------------------------------------

    public function test_settings_requires_authentication(): void
    {
        $response = $this->get('/settings');

        $response->assertRedirect(route('login'));
    }

    public function test_settings_requires_verified_email(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get('/settings');

        $response->assertRedirect(route('verification.notice'));
    }

    // -------------------------------------------------------------------------
    // /settings — setup.complete (redirects fire before render — no mock needed)
    // -------------------------------------------------------------------------

    public function test_settings_requires_setup_complete_redirects_without_active_business(): void
    {
        $user = User::factory()->create();
        // No business at all

        $response = $this->actingAs($user)->get('/settings');

        $response->assertRedirect(route('setup'));
    }

    public function test_settings_requires_setup_complete_redirects_with_pending_setup_business(): void
    {
        $user = User::factory()->create();
        Business::factory()->pendingSetup()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get('/settings');

        $response->assertRedirect(route('setup'));
    }

    // -------------------------------------------------------------------------
    // /settings — NOT gated by subscribed middleware
    // -------------------------------------------------------------------------

    public function test_settings_is_accessible_to_trial_user(): void
    {
        // Trial users must be able to reach /settings to subscribe.
        $user = $this->mockUserWithActiveBusiness([
            'trial_ends_at' => now()->addDays(7),
        ]);

        $response = $this->actingAs($user)->get('/settings');

        $response->assertOk();
    }

    public function test_settings_is_accessible_to_expired_trial_user(): void
    {
        // Expired users must be able to reach /settings — it is the recovery path.
        $user = $this->mockUserWithActiveBusiness([
            'trial_ends_at' => now()->subDays(1),
        ]);

        $response = $this->actingAs($user)->get('/settings');

        $response->assertOk();
    }

    public function test_settings_is_accessible_to_user_with_no_trial_and_no_subscription(): void
    {
        $user = $this->mockUserWithActiveBusiness(['trial_ends_at' => null]);

        $response = $this->actingAs($user)->get('/settings');

        $response->assertOk();
    }

    public function test_settings_is_accessible_to_subscribed_user(): void
    {
        $user = $this->mockSubscribedUserWithBusiness();

        $response = $this->actingAs($user)->get('/settings');

        $response->assertOk();
    }

    // -------------------------------------------------------------------------
    // /home — subscribed middleware interaction
    // -------------------------------------------------------------------------

    public function test_home_is_accessible_to_trial_user_with_active_business(): void
    {
        $user = $this->userWithActiveBusiness([
            'trial_ends_at' => now()->addDays(7),
        ]);

        $response = $this->actingAs($user)->get('/home');

        $response->assertOk();
    }

    public function test_home_is_accessible_to_subscribed_user_with_active_business(): void
    {
        $user = $this->subscribedUserWithBusiness();

        $response = $this->actingAs($user)->get('/home');

        $response->assertOk();
    }

    public function test_home_redirects_expired_non_subscribed_user_to_settings_billing_tab(): void
    {
        $user = $this->userWithActiveBusiness([
            'trial_ends_at' => now()->subDays(1),
        ]);

        $response = $this->actingAs($user)->get('/home');

        $response->assertRedirect(route('settings', ['tab' => 'billing']));
    }

    public function test_home_redirects_user_with_no_trial_and_no_subscription_to_settings_billing_tab(): void
    {
        $user = $this->userWithActiveBusiness(['trial_ends_at' => null]);

        $response = $this->actingAs($user)->get('/home');

        $response->assertRedirect(route('settings', ['tab' => 'billing']));
    }

    public function test_home_requires_setup_complete_before_subscribed_check(): void
    {
        // Without an active business, setup.complete fires first → redirects to /setup,
        // not /settings. This confirms middleware ordering.
        $user = User::factory()->create(['trial_ends_at' => null]);
        // No business

        $response = $this->actingAs($user)->get('/home');

        $response->assertRedirect(route('setup'));
    }

    public function test_home_requires_authentication_before_all_other_checks(): void
    {
        $response = $this->get('/home');

        $response->assertRedirect(route('login'));
    }

    public function test_home_requires_verified_email(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get('/home');

        $response->assertRedirect(route('verification.notice'));
    }

    // -------------------------------------------------------------------------
    // /settings named route resolution
    // -------------------------------------------------------------------------

    public function test_settings_route_is_named_settings(): void
    {
        $this->assertEquals('/settings', route('settings', [], false));
    }

    public function test_settings_billing_tab_url(): void
    {
        $this->assertEquals('/settings?tab=billing', route('settings', ['tab' => 'billing'], false));
    }
}

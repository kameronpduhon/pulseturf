<?php

namespace Tests\Feature\Middleware;

use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Stripe\SetupIntent;
use Tests\TestCase;

/**
 * Tests for the EnsureSetupComplete middleware.
 *
 * The middleware redirects to /setup when the authenticated user
 * does not have an active business. It uses a null-safe operator
 * so unauthenticated requests are handled gracefully.
 */
class EnsureSetupCompleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_active_business_passes_through_middleware(): void
    {
        $user = User::factory()->create();
        Business::factory()->active()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get('/home');

        // Email is verified by factory default, setup is complete
        $response->assertOk();
    }

    public function test_user_without_any_business_is_redirected_to_setup(): void
    {
        $user = User::factory()->create();
        // No business created

        $response = $this->actingAs($user)->get('/home');

        $response->assertRedirect(route('setup'));
    }

    public function test_user_with_pending_setup_business_is_redirected_to_setup(): void
    {
        $user = User::factory()->create();
        Business::factory()->pendingSetup()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get('/home');

        $response->assertRedirect(route('setup'));
    }

    public function test_unauthenticated_request_is_redirected_to_login(): void
    {
        // No user — the null-safe operator on $request->user()?->hasActiveBusiness()
        // returns null (falsy), but the auth middleware runs first and redirects to login.
        $response = $this->get('/home');

        $response->assertRedirect(route('login'));
    }

    public function test_middleware_allows_settings_access_with_active_business(): void
    {
        // /settings calls createSetupIntent() in mount() — stub it to avoid Stripe API call.
        $user = User::factory()->create();
        Business::factory()->active()->create(['user_id' => $user->id]);
        $user->refresh();

        $fakeIntent = SetupIntent::constructFrom([
            'id'            => 'seti_test_fake',
            'object'        => 'setup_intent',
            'client_secret' => 'seti_test_fake_secret',
            'status'        => 'requires_payment_method',
        ]);

        $mock = \Mockery::mock($user)->makePartial();
        $mock->shouldReceive('createSetupIntent')->andReturn($fakeIntent);

        $response = $this->actingAs($mock)->get('/settings');

        $response->assertOk();
    }

    public function test_middleware_blocks_settings_access_without_active_business(): void
    {
        $user = User::factory()->create();
        // No active business — redirect fires before mount(), no Stripe mock needed.

        $response = $this->actingAs($user)->get('/settings');

        $response->assertRedirect(route('setup'));
    }
}

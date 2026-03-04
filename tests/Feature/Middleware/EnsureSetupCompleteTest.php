<?php

namespace Tests\Feature\Middleware;

use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_middleware_allows_profile_access_with_active_business(): void
    {
        $user = User::factory()->create();
        Business::factory()->active()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get('/profile');

        $response->assertOk();
    }

    public function test_middleware_blocks_profile_access_without_active_business(): void
    {
        $user = User::factory()->create();
        // No active business

        $response = $this->actingAs($user)->get('/profile');

        $response->assertRedirect(route('setup'));
    }
}

<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for legacy route redirects introduced in Phase 6.
 *
 * /profile  → /settings           (backwards compat for old Breeze profile link)
 * /billing  → /settings?tab=billing (backwards compat for old billing link)
 *
 * Both routes require auth + verified middleware, matching the /settings route
 * they redirect to. Unauthenticated requests are bounced to login first.
 */
class RouteRedirectTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // /profile redirects
    // -------------------------------------------------------------------------

    public function test_profile_route_redirects_authenticated_user_to_settings(): void
    {
        $user = User::factory()->create();
        Business::factory()->active()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get('/profile');

        $response->assertRedirect('/settings');
    }

    public function test_profile_route_redirects_guest_to_login(): void
    {
        $response = $this->get('/profile');

        $response->assertRedirect(route('login'));
    }

    public function test_profile_route_redirects_unverified_user_away_from_settings(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get('/profile');

        // The verified middleware intercepts before the redirect fires.
        // The user does not reach /settings.
        $response->assertRedirectContains('verify');
    }

    // -------------------------------------------------------------------------
    // /billing redirects
    // -------------------------------------------------------------------------

    public function test_billing_route_redirects_authenticated_user_to_settings_billing_tab(): void
    {
        $user = User::factory()->create();
        Business::factory()->active()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get('/billing');

        $response->assertRedirect('/settings?tab=billing');
    }

    public function test_billing_route_redirects_guest_to_login(): void
    {
        $response = $this->get('/billing');

        $response->assertRedirect(route('login'));
    }

    public function test_billing_route_redirects_unverified_user_away_from_settings(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get('/billing');

        // Same as /profile — verified middleware fires before the redirect.
        $response->assertRedirectContains('verify');
    }

    // -------------------------------------------------------------------------
    // Verify the target routes actually exist and are named correctly
    // -------------------------------------------------------------------------

    public function test_settings_route_is_named_correctly(): void
    {
        $this->assertEquals('/settings', route('settings', [], false));
    }

    public function test_settings_billing_tab_url_is_correct(): void
    {
        $this->assertEquals(
            '/settings?tab=billing',
            route('settings', ['tab' => 'billing'], false),
        );
    }
}

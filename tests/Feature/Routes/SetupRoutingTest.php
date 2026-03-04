<?php

namespace Tests\Feature\Routes;

use App\Models\Business;
use App\Models\Competitor;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for route-level access control and redirects.
 *
 * Routes under test:
 *   GET /setup   — auth + verified required; redirects to /home if already set up
 *   GET /home    — auth + verified + setup.complete required
 *   GET /dashboard — redirects to /home
 */
class SetupRoutingTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // /setup
    // -------------------------------------------------------------------------

    public function test_setup_requires_authentication(): void
    {
        $response = $this->get('/setup');

        $response->assertRedirect(route('login'));
    }

    public function test_setup_requires_verified_email(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get('/setup');

        $response->assertRedirect(route('verification.notice'));
    }

    public function test_setup_is_accessible_to_authenticated_verified_user_without_business(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/setup');

        $response->assertOk();
    }

    public function test_setup_is_accessible_to_user_with_pending_setup_business(): void
    {
        $user = User::factory()->create();
        Business::factory()->pendingSetup()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get('/setup');

        $response->assertOk();
    }

    public function test_setup_redirects_to_home_when_user_has_active_business(): void
    {
        // The SetupWizard mount() calls $this->redirect(route('home')) which Livewire
        // converts into a real HTTP redirect response when accessed via a standard GET.
        $user = User::factory()->create();
        Business::factory()->active()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get('/setup');

        $response->assertRedirect(route('home'));
    }

    // -------------------------------------------------------------------------
    // /home
    // -------------------------------------------------------------------------

    public function test_home_requires_authentication(): void
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

    public function test_home_requires_setup_complete_redirects_to_setup_without_active_business(): void
    {
        $user = User::factory()->create();
        // No business

        $response = $this->actingAs($user)->get('/home');

        $response->assertRedirect(route('setup'));
    }

    public function test_home_requires_setup_complete_redirects_with_pending_setup_business(): void
    {
        $user = User::factory()->create();
        Business::factory()->pendingSetup()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get('/home');

        $response->assertRedirect(route('setup'));
    }

    public function test_home_is_accessible_to_user_with_active_business(): void
    {
        $user = User::factory()->create();
        Business::factory()->active()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get('/home');

        $response->assertOk();
    }

    // -------------------------------------------------------------------------
    // /dashboard
    // -------------------------------------------------------------------------

    public function test_dashboard_redirects_to_home(): void
    {
        $user = User::factory()->create();
        Business::factory()->active()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect('/home');
    }

    public function test_dashboard_requires_authentication(): void
    {
        $response = $this->get('/dashboard');

        // The auth middleware on /dashboard fires before the redirect to /home,
        // so unauthenticated requests are redirected to login.
        $response->assertRedirect(route('login'));
    }

    // -------------------------------------------------------------------------
    // /profile (also protected by setup.complete)
    // -------------------------------------------------------------------------

    public function test_profile_redirects_to_setup_without_active_business(): void
    {
        $user = User::factory()->create();
        // No active business

        $response = $this->actingAs($user)->get('/profile');

        $response->assertRedirect(route('setup'));
    }

    public function test_profile_is_accessible_with_active_business(): void
    {
        $user = User::factory()->create();
        Business::factory()->active()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get('/profile');

        $response->assertOk();
    }
}

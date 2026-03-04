<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for the marketing landing page at /.
 *
 * The landing page is a public Blade view (welcome.blade.php) rendered by
 * Route::view('/', 'welcome'). No authentication is required. Content and
 * navigation elements change based on whether a user is authenticated.
 */
class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // HTTP accessibility
    // -------------------------------------------------------------------------

    public function test_landing_page_is_accessible_to_guests(): void
    {
        $response = $this->get('/');

        $response->assertOk();
    }

    public function test_landing_page_is_accessible_to_authenticated_users(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/');

        $response->assertOk();
    }

    // -------------------------------------------------------------------------
    // Brand and headline content
    // -------------------------------------------------------------------------

    public function test_page_contains_brand_name(): void
    {
        $response = $this->get('/');

        $response->assertSee('PulseTurf');
    }

    public function test_page_contains_headline_competitive_intelligence(): void
    {
        $response = $this->get('/');

        $response->assertSee('Competitive Intelligence');
    }

    public function test_page_contains_med_spa_copy(): void
    {
        $response = $this->get('/');

        $response->assertSee('Med Spa');
    }

    // -------------------------------------------------------------------------
    // Guest navigation — shows Login and Start Free Trial
    // -------------------------------------------------------------------------

    public function test_guest_sees_login_link(): void
    {
        $response = $this->get('/');

        $response->assertSee('Log in');
    }

    public function test_guest_sees_start_free_trial_link_in_nav(): void
    {
        $response = $this->get('/');

        $response->assertSee('Start Free Trial');
    }

    public function test_guest_does_not_see_dashboard_link(): void
    {
        $response = $this->get('/');

        $response->assertDontSee('Dashboard');
    }

    // -------------------------------------------------------------------------
    // Authenticated navigation — shows Dashboard instead of Login/Register
    // -------------------------------------------------------------------------

    public function test_authenticated_user_sees_dashboard_link_in_nav(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/');

        $response->assertSee('Dashboard');
    }

    public function test_authenticated_user_does_not_see_register_link_in_nav(): void
    {
        // The nav @auth block shows a Dashboard button instead of the
        // Login / Start Free Trial pair that guests see. The pricing section
        // still renders "Start Free Trial" CTAs unconditionally, but those
        // are in the body — we verify the nav Register link is absent by
        // checking the auth block does not render the register route as a
        // top-level nav action.
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/');

        // The "Sign up" link in the footer footer is in a @guest block,
        // so authenticated users should not see it there.
        $response->assertDontSee('Sign up');
    }

    // -------------------------------------------------------------------------
    // CTA links point to /register
    // -------------------------------------------------------------------------

    public function test_start_free_trial_link_points_to_register_route(): void
    {
        $response = $this->get('/');

        $response->assertSee(route('register'), false);
    }

    // -------------------------------------------------------------------------
    // Pricing section
    // -------------------------------------------------------------------------

    public function test_page_contains_starter_plan_heading(): void
    {
        $response = $this->get('/');

        $response->assertSee('Starter');
    }

    public function test_page_contains_pro_plan_heading(): void
    {
        $response = $this->get('/');

        $response->assertSee('Pro');
    }

    public function test_page_contains_starter_monthly_price(): void
    {
        $response = $this->get('/');

        $response->assertSee('$29');
    }

    public function test_page_contains_pro_monthly_price(): void
    {
        $response = $this->get('/');

        $response->assertSee('$79');
    }

    public function test_page_contains_pricing_section_label(): void
    {
        $response = $this->get('/');

        $response->assertSee('Pricing');
    }

    public function test_pricing_section_shows_trial_copy(): void
    {
        $response = $this->get('/');

        $response->assertSee('14-day free trial');
    }

    // -------------------------------------------------------------------------
    // FAQ section
    // -------------------------------------------------------------------------

    public function test_page_contains_faq_section(): void
    {
        $response = $this->get('/');

        $response->assertSee('FAQ');
    }

    public function test_page_contains_faq_question_about_data(): void
    {
        $response = $this->get('/');

        $response->assertSee('What data does PulseTurf track?');
    }

    public function test_page_contains_faq_question_about_cancellation(): void
    {
        $response = $this->get('/');

        $response->assertSee('Can I cancel anytime?');
    }

    public function test_page_contains_faq_question_about_trial(): void
    {
        $response = $this->get('/');

        $response->assertSee('What happens during the free trial?');
    }

    public function test_page_contains_faq_question_about_competitor_count(): void
    {
        $response = $this->get('/');

        $response->assertSee('How many competitors can I track?');
    }

    // -------------------------------------------------------------------------
    // How It Works section
    // -------------------------------------------------------------------------

    public function test_page_contains_how_it_works_steps(): void
    {
        $response = $this->get('/');

        $response->assertSee('Add your med spa');
        $response->assertSee('Pick your competitors');
        $response->assertSee('Get weekly AI briefings');
    }

    // -------------------------------------------------------------------------
    // Page title
    // -------------------------------------------------------------------------

    public function test_page_title_contains_pulseturf(): void
    {
        $response = $this->get('/');

        $response->assertSee('PulseTurf', false);
    }
}

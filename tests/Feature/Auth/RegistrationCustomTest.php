<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Volt\Volt;
use Tests\TestCase;

/**
 * Tests for PulseTurf's registration customizations:
 *   - trial_ends_at set to 14 days from now on registration
 *   - timezone stored from the request
 *   - redirect to /setup after registration (not /dashboard)
 *   - default timezone is America/Chicago when none provided
 */
class RegistrationCustomTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_sets_trial_ends_at_to_14_days_from_now(): void
    {
        $component = Volt::test('pages.auth.register')
            ->set('name', 'Jane Doe')
            ->set('email', 'jane@example.com')
            ->set('password', 'password')
            ->set('password_confirmation', 'password')
            ->set('timezone', 'America/New_York');

        $component->call('register');

        $user = User::where('email', 'jane@example.com')->firstOrFail();

        $this->assertNotNull($user->trial_ends_at);
        $this->assertTrue($user->trial_ends_at->isFuture());
        // Should be approximately 14 days — allow ±1 minute for test timing
        $this->assertEqualsWithDelta(14, now()->diffInDays($user->trial_ends_at), 1);
    }

    public function test_registration_stores_timezone_from_request(): void
    {
        $component = Volt::test('pages.auth.register')
            ->set('name', 'Jane Doe')
            ->set('email', 'jane@example.com')
            ->set('password', 'password')
            ->set('password_confirmation', 'password')
            ->set('timezone', 'America/Los_Angeles');

        $component->call('register');

        $user = User::where('email', 'jane@example.com')->firstOrFail();

        $this->assertEquals('America/Los_Angeles', $user->timezone);
    }

    public function test_registration_redirects_to_setup_not_dashboard(): void
    {
        $component = Volt::test('pages.auth.register')
            ->set('name', 'Jane Doe')
            ->set('email', 'jane@example.com')
            ->set('password', 'password')
            ->set('password_confirmation', 'password')
            ->set('timezone', 'America/Chicago');

        $component->call('register');

        $component->assertRedirect(route('setup', absolute: false));
    }

    public function test_registration_uses_default_timezone_of_america_chicago_when_none_provided(): void
    {
        // The component defaults $timezone to 'America/Chicago' without any JS interaction
        $component = Volt::test('pages.auth.register')
            ->set('name', 'Jane Doe')
            ->set('email', 'jane@example.com')
            ->set('password', 'password')
            ->set('password_confirmation', 'password');
        // Note: timezone is NOT explicitly set — relying on the component's default

        $component->call('register');

        $user = User::where('email', 'jane@example.com')->firstOrFail();

        $this->assertEquals('America/Chicago', $user->timezone);
    }

    public function test_registration_stores_eastern_timezone(): void
    {
        $component = Volt::test('pages.auth.register')
            ->set('name', 'John Smith')
            ->set('email', 'john@example.com')
            ->set('password', 'password')
            ->set('password_confirmation', 'password')
            ->set('timezone', 'America/New_York');

        $component->call('register');

        $user = User::where('email', 'john@example.com')->firstOrFail();

        $this->assertEquals('America/New_York', $user->timezone);
    }

    public function test_registration_fails_with_invalid_timezone(): void
    {
        $component = Volt::test('pages.auth.register')
            ->set('name', 'Jane Doe')
            ->set('email', 'jane@example.com')
            ->set('password', 'password')
            ->set('password_confirmation', 'password')
            ->set('timezone', 'Not/ATimezone');

        $component->call('register');

        $component->assertHasErrors(['timezone']);
        $this->assertDatabaseMissing('users', ['email' => 'jane@example.com']);
    }

    public function test_registration_creates_user_and_authenticates(): void
    {
        $component = Volt::test('pages.auth.register')
            ->set('name', 'Jane Doe')
            ->set('email', 'jane@example.com')
            ->set('password', 'password')
            ->set('password_confirmation', 'password')
            ->set('timezone', 'America/Denver');

        $component->call('register');

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'jane@example.com',
            'name' => 'Jane Doe',
        ]);
    }
}

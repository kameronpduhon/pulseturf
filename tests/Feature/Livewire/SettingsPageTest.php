<?php

namespace Tests\Feature\Livewire;

use App\Livewire\SettingsPage;
use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Stripe\SetupIntent;
use Tests\TestCase;

/**
 * Tests for the unified SettingsPage Livewire component.
 *
 * SettingsPage::mount() always calls $user->createSetupIntent() which hits
 * the Stripe API directly (cURL). All tests that instantiate the component
 * must use a partial Mockery mock of the User model to stub that method.
 *
 * Route-level access tests (auth, verified, setup.complete) go through the
 * HTTP layer; component-level tests use Livewire::actingAs() directly.
 */
class SettingsPageTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

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
     * Create a real User in the database, attach an active Business, then wrap
     * in a partial Mockery mock that stubs createSetupIntent().
     *
     * The underlying User record is persisted, so all Eloquent/Cashier calls
     * (subscriptions, hasActiveBusiness, etc.) hit the real database.
     */
    private function makeSettingsUser(array $userAttributes = []): \Mockery\MockInterface
    {
        $user = User::factory()->create(array_merge([
            'trial_ends_at' => now()->addDays(7),
        ], $userAttributes));

        Business::factory()->active()->create(['user_id' => $user->id]);

        $user->refresh();

        $mock = \Mockery::mock($user)->makePartial();
        $mock->shouldReceive('createSetupIntent')
            ->andReturn($this->fakeSetupIntent());

        return $mock;
    }

    // -------------------------------------------------------------------------
    // Route accessibility — HTTP layer (no Stripe mock needed for redirects)
    // -------------------------------------------------------------------------

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/settings');

        $response->assertRedirect(route('login'));
    }

    public function test_unverified_user_is_redirected_away(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->get('/settings');

        // Laravel's verified middleware sends unverified users to the
        // email verification notice page, not login.
        $response->assertRedirectContains('verify');
    }

    public function test_user_without_active_business_is_redirected_to_setup(): void
    {
        // No business created — setup.complete middleware fires and redirects.
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/settings');

        $response->assertRedirect(route('setup'));
    }

    public function test_trial_expired_user_can_access_settings(): void
    {
        // /settings is NOT behind the subscribed middleware so expired users
        // can reach it to subscribe. We must stub createSetupIntent().
        $user = $this->makeSettingsUser(['trial_ends_at' => now()->subDays(1)]);

        $response = $this->actingAs($user)->get('/settings');

        $response->assertOk();
    }

    public function test_active_trial_user_can_access_settings(): void
    {
        $user = $this->makeSettingsUser(['trial_ends_at' => now()->addDays(7)]);

        $response = $this->actingAs($user)->get('/settings');

        $response->assertOk();
    }

    public function test_subscribed_user_can_access_settings(): void
    {
        $user = $this->makeSettingsUser(['trial_ends_at' => null]);

        $user->subscriptions()->create([
            'type'          => 'default',
            'stripe_id'     => 'sub_settings_access_test',
            'stripe_status' => 'active',
            'stripe_price'  => 'price_starter_test',
        ]);

        $response = $this->actingAs($user)->get('/settings');

        $response->assertOk();
    }

    // -------------------------------------------------------------------------
    // mount() — tab initialisation from query parameter
    // -------------------------------------------------------------------------

    public function test_default_tab_is_account(): void
    {
        $user = $this->makeSettingsUser();

        Livewire::actingAs($user)
            ->test(SettingsPage::class)
            ->assertSet('activeTab', 'account');
    }

    public function test_tab_query_param_billing_sets_billing_tab(): void
    {
        $user = $this->makeSettingsUser();

        Livewire::actingAs($user)
            ->test(SettingsPage::class, ['tab' => 'billing'])
            ->assertSet('activeTab', 'billing');
    }

    public function test_invalid_tab_query_param_defaults_to_account(): void
    {
        $user = $this->makeSettingsUser();

        Livewire::actingAs($user)
            ->test(SettingsPage::class, ['tab' => 'invalid_tab'])
            ->assertSet('activeTab', 'account');
    }

    public function test_mount_populates_name_from_user(): void
    {
        $user = $this->makeSettingsUser(['name' => 'Jane Doe']);

        Livewire::actingAs($user)
            ->test(SettingsPage::class)
            ->assertSet('name', 'Jane Doe');
    }

    public function test_mount_populates_email_from_user(): void
    {
        $user = $this->makeSettingsUser(['email' => 'jane@example.com']);

        Livewire::actingAs($user)
            ->test(SettingsPage::class)
            ->assertSet('email', 'jane@example.com');
    }

    public function test_mount_populates_timezone_from_user(): void
    {
        $user = $this->makeSettingsUser(['timezone' => 'America/Los_Angeles']);

        Livewire::actingAs($user)
            ->test(SettingsPage::class)
            ->assertSet('timezone', 'America/Los_Angeles');
    }

    // -------------------------------------------------------------------------
    // switchTab() — tab state and event dispatch
    // -------------------------------------------------------------------------

    public function test_switch_tab_to_billing_changes_active_tab(): void
    {
        $user = $this->makeSettingsUser();

        Livewire::actingAs($user)
            ->test(SettingsPage::class)
            ->call('switchTab', 'billing')
            ->assertSet('activeTab', 'billing');
    }

    public function test_switch_tab_to_account_changes_active_tab(): void
    {
        $user = $this->makeSettingsUser();

        Livewire::actingAs($user)
            ->test(SettingsPage::class, ['tab' => 'billing'])
            ->call('switchTab', 'account')
            ->assertSet('activeTab', 'account');
    }

    public function test_switch_tab_to_billing_dispatches_init_stripe_elements_event(): void
    {
        $user = $this->makeSettingsUser();

        Livewire::actingAs($user)
            ->test(SettingsPage::class)
            ->call('switchTab', 'billing')
            ->assertDispatched('init-stripe-elements');
    }

    public function test_switch_tab_to_account_does_not_dispatch_init_stripe_elements_event(): void
    {
        $user = $this->makeSettingsUser();

        Livewire::actingAs($user)
            ->test(SettingsPage::class, ['tab' => 'billing'])
            ->call('switchTab', 'account')
            ->assertNotDispatched('init-stripe-elements');
    }

    public function test_switch_tab_with_invalid_name_is_ignored(): void
    {
        $user = $this->makeSettingsUser();

        Livewire::actingAs($user)
            ->test(SettingsPage::class)
            ->call('switchTab', 'hacking')
            ->assertSet('activeTab', 'account'); // unchanged
    }

    // -------------------------------------------------------------------------
    // updateProfile() — happy path
    // -------------------------------------------------------------------------

    public function test_can_update_name_successfully(): void
    {
        $user = $this->makeSettingsUser(['name' => 'Old Name']);

        Livewire::actingAs($user)
            ->test(SettingsPage::class)
            ->set('name', 'New Name')
            ->call('updateProfile')
            ->assertHasNoErrors('name')
            ->assertSet('profileSaved', true);

        $this->assertDatabaseHas('users', [
            'id'   => $user->id,
            'name' => 'New Name',
        ]);
    }

    public function test_can_update_email_successfully(): void
    {
        $user = $this->makeSettingsUser(['email' => 'old@example.com']);

        Livewire::actingAs($user)
            ->test(SettingsPage::class)
            ->set('email', 'new@example.com')
            ->call('updateProfile')
            ->assertHasNoErrors('email')
            ->assertSet('profileSaved', true);

        $this->assertDatabaseHas('users', [
            'id'    => $user->id,
            'email' => 'new@example.com',
        ]);
    }

    public function test_email_change_nullifies_email_verified_at(): void
    {
        $user = $this->makeSettingsUser(['email' => 'original@example.com']);

        Livewire::actingAs($user)
            ->test(SettingsPage::class)
            ->set('email', 'changed@example.com')
            ->call('updateProfile')
            ->assertHasNoErrors();

        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_unchanged_email_does_not_nullify_email_verified_at(): void
    {
        $user = $this->makeSettingsUser();
        $originalEmail = $user->email;

        Livewire::actingAs($user)
            ->test(SettingsPage::class)
            ->set('email', $originalEmail)
            ->call('updateProfile')
            ->assertHasNoErrors();

        $this->assertNotNull($user->fresh()->email_verified_at);
    }

    public function test_update_profile_dispatches_profile_updated_event(): void
    {
        $user = $this->makeSettingsUser(['name' => 'Test User']);

        Livewire::actingAs($user)
            ->test(SettingsPage::class)
            ->set('name', 'Test User')
            ->call('updateProfile')
            ->assertDispatched('profile-updated');
    }

    // -------------------------------------------------------------------------
    // updateProfile() — validation
    // -------------------------------------------------------------------------

    public function test_update_profile_requires_name(): void
    {
        $user = $this->makeSettingsUser();

        Livewire::actingAs($user)
            ->test(SettingsPage::class)
            ->set('name', '')
            ->call('updateProfile')
            ->assertHasErrors(['name' => 'required']);
    }

    public function test_update_profile_requires_email(): void
    {
        $user = $this->makeSettingsUser();

        Livewire::actingAs($user)
            ->test(SettingsPage::class)
            ->set('email', '')
            ->call('updateProfile')
            ->assertHasErrors(['email' => 'required']);
    }

    public function test_update_profile_rejects_invalid_email_format(): void
    {
        $user = $this->makeSettingsUser();

        Livewire::actingAs($user)
            ->test(SettingsPage::class)
            ->set('email', 'not-an-email')
            ->call('updateProfile')
            ->assertHasErrors(['email' => 'email']);
    }

    public function test_update_profile_rejects_duplicate_email_for_other_user(): void
    {
        $existingUser = User::factory()->create(['email' => 'taken@example.com']);
        $user = $this->makeSettingsUser(['email' => 'mine@example.com']);

        Livewire::actingAs($user)
            ->test(SettingsPage::class)
            ->set('email', 'taken@example.com')
            ->call('updateProfile')
            ->assertHasErrors(['email' => 'unique']);
    }

    public function test_update_profile_allows_same_email_for_same_user(): void
    {
        $user = $this->makeSettingsUser(['email' => 'same@example.com']);

        Livewire::actingAs($user)
            ->test(SettingsPage::class)
            ->set('email', 'same@example.com')
            ->call('updateProfile')
            ->assertHasNoErrors('email');
    }

    // -------------------------------------------------------------------------
    // updateTimezone() — happy path and validation
    // -------------------------------------------------------------------------

    public function test_can_update_timezone_successfully(): void
    {
        $user = $this->makeSettingsUser(['timezone' => 'America/Chicago']);

        Livewire::actingAs($user)
            ->test(SettingsPage::class)
            ->set('timezone', 'America/New_York')
            ->call('updateTimezone')
            ->assertHasNoErrors('timezone')
            ->assertSet('timezoneSaved', true);

        $this->assertDatabaseHas('users', [
            'id'       => $user->id,
            'timezone' => 'America/New_York',
        ]);
    }

    public function test_update_timezone_rejects_invalid_timezone(): void
    {
        $user = $this->makeSettingsUser();

        Livewire::actingAs($user)
            ->test(SettingsPage::class)
            ->set('timezone', 'NotAReal/Timezone')
            ->call('updateTimezone')
            ->assertHasErrors('timezone');
    }

    public function test_update_timezone_accepts_valid_php_timezone_identifiers(): void
    {
        $user = $this->makeSettingsUser();

        Livewire::actingAs($user)
            ->test(SettingsPage::class)
            ->set('timezone', 'Pacific/Honolulu')
            ->call('updateTimezone')
            ->assertHasNoErrors('timezone');
    }

    // -------------------------------------------------------------------------
    // updatePassword() — happy path
    // -------------------------------------------------------------------------

    public function test_can_change_password_with_correct_current_password(): void
    {
        $user = $this->makeSettingsUser();

        Livewire::actingAs($user)
            ->test(SettingsPage::class)
            ->set('currentPassword', 'password')
            ->set('newPassword', 'newSecurePassword1')
            ->set('newPasswordConfirmation', 'newSecurePassword1')
            ->call('updatePassword')
            ->assertHasNoErrors()
            ->assertSet('passwordSaved', true);
    }

    public function test_password_fields_are_cleared_after_successful_change(): void
    {
        $user = $this->makeSettingsUser();

        Livewire::actingAs($user)
            ->test(SettingsPage::class)
            ->set('currentPassword', 'password')
            ->set('newPassword', 'newSecurePassword1')
            ->set('newPasswordConfirmation', 'newSecurePassword1')
            ->call('updatePassword')
            ->assertSet('currentPassword', '')
            ->assertSet('newPassword', '')
            ->assertSet('newPasswordConfirmation', '');
    }

    // -------------------------------------------------------------------------
    // updatePassword() — validation
    // -------------------------------------------------------------------------

    public function test_update_password_rejects_wrong_current_password(): void
    {
        $user = $this->makeSettingsUser();

        Livewire::actingAs($user)
            ->test(SettingsPage::class)
            ->set('currentPassword', 'wrong-password')
            ->set('newPassword', 'newSecurePassword1')
            ->set('newPasswordConfirmation', 'newSecurePassword1')
            ->call('updatePassword')
            ->assertHasErrors('currentPassword');
    }

    public function test_update_password_rejects_new_password_shorter_than_8_characters(): void
    {
        $user = $this->makeSettingsUser();

        Livewire::actingAs($user)
            ->test(SettingsPage::class)
            ->set('currentPassword', 'password')
            ->set('newPassword', 'short')
            ->set('newPasswordConfirmation', 'short')
            ->call('updatePassword')
            ->assertHasErrors(['newPassword' => 'min']);
    }

    public function test_update_password_rejects_mismatched_confirmation(): void
    {
        $user = $this->makeSettingsUser();

        Livewire::actingAs($user)
            ->test(SettingsPage::class)
            ->set('currentPassword', 'password')
            ->set('newPassword', 'newSecurePassword1')
            ->set('newPasswordConfirmation', 'differentPassword2')
            ->call('updatePassword')
            ->assertHasErrors('newPassword');
    }

    public function test_update_password_requires_current_password_field(): void
    {
        $user = $this->makeSettingsUser();

        Livewire::actingAs($user)
            ->test(SettingsPage::class)
            ->set('currentPassword', '')
            ->set('newPassword', 'newSecurePassword1')
            ->set('newPasswordConfirmation', 'newSecurePassword1')
            ->call('updatePassword')
            ->assertHasErrors(['currentPassword' => 'required']);
    }

    // -------------------------------------------------------------------------
    // Billing tab — rendering for different user states
    // -------------------------------------------------------------------------

    public function test_billing_tab_renders_for_trial_user(): void
    {
        $user = $this->makeSettingsUser([
            'trial_ends_at' => now()->addDays(5),
        ]);

        Livewire::actingAs($user)
            ->test(SettingsPage::class, ['tab' => 'billing'])
            ->assertSee('Trial')
            ->assertOk();
    }

    public function test_billing_tab_shows_days_remaining_for_trial_user(): void
    {
        $user = $this->makeSettingsUser([
            'trial_ends_at' => now()->addDays(5),
        ]);

        Livewire::actingAs($user)
            ->test(SettingsPage::class, ['tab' => 'billing'])
            ->assertSee('days left');
    }

    public function test_billing_tab_shows_trial_ended_for_expired_user(): void
    {
        $user = $this->makeSettingsUser([
            'trial_ends_at' => now()->subDays(1),
        ]);

        Livewire::actingAs($user)
            ->test(SettingsPage::class, ['tab' => 'billing'])
            ->assertSee('Trial Ended');
    }

    public function test_billing_tab_shows_choose_your_plan_for_trial_user(): void
    {
        $user = $this->makeSettingsUser([
            'trial_ends_at' => now()->addDays(7),
        ]);

        Livewire::actingAs($user)
            ->test(SettingsPage::class, ['tab' => 'billing'])
            ->assertSee('Choose Your Plan');
    }

    public function test_billing_tab_renders_for_subscribed_user(): void
    {
        $user = $this->makeSettingsUser(['trial_ends_at' => null]);

        $user->subscriptions()->create([
            'type'          => 'default',
            'stripe_id'     => 'sub_render_test',
            'stripe_status' => 'active',
            'stripe_price'  => config('services.stripe.prices.starter_monthly', 'price_starter_test'),
        ]);

        Livewire::actingAs($user)
            ->test(SettingsPage::class, ['tab' => 'billing'])
            ->assertSee('Current Plan')
            ->assertOk();
    }

    public function test_billing_tab_shows_active_badge_for_subscribed_user(): void
    {
        $user = $this->makeSettingsUser(['trial_ends_at' => null]);

        $user->subscriptions()->create([
            'type'          => 'default',
            'stripe_id'     => 'sub_active_badge',
            'stripe_status' => 'active',
            'stripe_price'  => config('services.stripe.prices.starter_monthly', 'price_starter_test'),
        ]);

        Livewire::actingAs($user)
            ->test(SettingsPage::class, ['tab' => 'billing'])
            ->assertSee('Active');
    }

    public function test_billing_tab_renders_for_expired_trial_user(): void
    {
        $user = $this->makeSettingsUser([
            'trial_ends_at' => now()->subDays(3),
        ]);

        Livewire::actingAs($user)
            ->test(SettingsPage::class, ['tab' => 'billing'])
            ->assertSee('Trial Ended')
            ->assertOk();
    }

    // -------------------------------------------------------------------------
    // Billing tab — plan pre-selection from active subscription
    // -------------------------------------------------------------------------

    public function test_mount_pre_selects_active_subscription_plan(): void
    {
        config(['services.stripe.prices.pro_monthly' => 'price_pro_m_settings_test']);

        $user = $this->makeSettingsUser(['trial_ends_at' => null]);

        $user->subscriptions()->create([
            'type'          => 'default',
            'stripe_id'     => 'sub_preselect',
            'stripe_status' => 'active',
            'stripe_price'  => 'price_pro_m_settings_test',
        ]);

        Livewire::actingAs($user)
            ->test(SettingsPage::class)
            ->assertSet('selectedPlan', 'pro_monthly');
    }

    public function test_mount_defaults_selected_plan_to_starter_monthly_when_no_subscription(): void
    {
        $user = $this->makeSettingsUser();

        Livewire::actingAs($user)
            ->test(SettingsPage::class)
            ->assertSet('selectedPlan', 'starter_monthly');
    }

    // -------------------------------------------------------------------------
    // subscribe() — guard when price ID not configured
    // -------------------------------------------------------------------------

    public function test_subscribe_sets_billing_error_when_plan_not_configured(): void
    {
        config(['services.stripe.prices.starter_monthly' => null]);

        $user = $this->makeSettingsUser();

        Livewire::actingAs($user)
            ->test(SettingsPage::class)
            ->set('selectedPlan', 'starter_monthly')
            ->call('subscribe', 'pm_fake')
            ->assertSet('billingError', 'Please select a valid plan.')
            ->assertSet('processing', false);
    }

    // -------------------------------------------------------------------------
    // swapPlan() — guard when price ID not configured
    // -------------------------------------------------------------------------

    public function test_swap_plan_sets_billing_error_when_plan_not_configured(): void
    {
        config(['services.stripe.prices.pro_monthly' => null]);

        $user = $this->makeSettingsUser(['trial_ends_at' => null]);

        $user->subscriptions()->create([
            'type'          => 'default',
            'stripe_id'     => 'sub_swap_guard_settings',
            'stripe_status' => 'active',
            'stripe_price'  => 'price_starter_test',
        ]);

        Livewire::actingAs($user)
            ->test(SettingsPage::class)
            ->call('swapPlan', 'pro_monthly')
            ->assertSet('billingError', 'Invalid plan selected.')
            ->assertSet('processing', false);
    }

    public function test_swap_plan_is_no_op_when_same_plan_selected(): void
    {
        config(['services.stripe.prices.starter_monthly' => 'price_starter_monthly_test']);

        $user = $this->makeSettingsUser(['trial_ends_at' => null]);

        $user->subscriptions()->create([
            'type'          => 'default',
            'stripe_id'     => 'sub_no_op',
            'stripe_status' => 'active',
            'stripe_price'  => 'price_starter_monthly_test',
        ]);

        // Ensure selectedPlan is already 'starter_monthly' on mount
        $component = Livewire::actingAs($user)
            ->test(SettingsPage::class)
            ->assertSet('selectedPlan', 'starter_monthly');

        // Calling swapPlan with the current plan should be a no-op (no error)
        $component->call('swapPlan', 'starter_monthly')
            ->assertSet('billingError', '');
    }

    // -------------------------------------------------------------------------
    // Initial component state assertions
    // -------------------------------------------------------------------------

    public function test_billing_error_is_empty_on_mount(): void
    {
        $user = $this->makeSettingsUser();

        Livewire::actingAs($user)
            ->test(SettingsPage::class)
            ->assertSet('billingError', '');
    }

    public function test_processing_is_false_on_mount(): void
    {
        $user = $this->makeSettingsUser();

        Livewire::actingAs($user)
            ->test(SettingsPage::class)
            ->assertSet('processing', false);
    }

    public function test_show_cancel_modal_is_false_on_mount(): void
    {
        $user = $this->makeSettingsUser();

        Livewire::actingAs($user)
            ->test(SettingsPage::class)
            ->assertSet('showCancelModal', false);
    }

    public function test_show_update_card_is_false_on_mount(): void
    {
        $user = $this->makeSettingsUser();

        Livewire::actingAs($user)
            ->test(SettingsPage::class)
            ->assertSet('showUpdateCard', false);
    }

    public function test_profile_saved_is_false_on_mount(): void
    {
        $user = $this->makeSettingsUser();

        Livewire::actingAs($user)
            ->test(SettingsPage::class)
            ->assertSet('profileSaved', false);
    }

    public function test_timezone_saved_is_false_on_mount(): void
    {
        $user = $this->makeSettingsUser();

        Livewire::actingAs($user)
            ->test(SettingsPage::class)
            ->assertSet('timezoneSaved', false);
    }

    public function test_password_saved_is_false_on_mount(): void
    {
        $user = $this->makeSettingsUser();

        Livewire::actingAs($user)
            ->test(SettingsPage::class)
            ->assertSet('passwordSaved', false);
    }

    // -------------------------------------------------------------------------
    // View renders core structural elements
    // -------------------------------------------------------------------------

    public function test_view_contains_account_tab_button(): void
    {
        $user = $this->makeSettingsUser();

        Livewire::actingAs($user)
            ->test(SettingsPage::class)
            ->assertSee('Account');
    }

    public function test_view_contains_billing_tab_button(): void
    {
        $user = $this->makeSettingsUser();

        Livewire::actingAs($user)
            ->test(SettingsPage::class)
            ->assertSee('Billing');
    }

    public function test_view_contains_profile_information_card(): void
    {
        $user = $this->makeSettingsUser();

        Livewire::actingAs($user)
            ->test(SettingsPage::class)
            ->assertSee('Profile Information');
    }

    public function test_view_contains_change_password_card(): void
    {
        $user = $this->makeSettingsUser();

        Livewire::actingAs($user)
            ->test(SettingsPage::class)
            ->assertSee('Change Password');
    }

    public function test_view_contains_timezone_card(): void
    {
        $user = $this->makeSettingsUser();

        Livewire::actingAs($user)
            ->test(SettingsPage::class)
            ->assertSee('Timezone');
    }
}

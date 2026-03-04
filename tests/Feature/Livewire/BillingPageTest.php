<?php

namespace Tests\Feature\Livewire;

use App\Livewire\SettingsPage;
use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Stripe\SetupIntent;
use Tests\TestCase;

/**
 * Tests for billing functionality within the SettingsPage Livewire component.
 *
 * SettingsPage::mount() calls $user->createSetupIntent() which hits the Stripe
 * API directly (cURL). We bypass this by creating a partial Mockery mock of the
 * User model and stubbing only that one method to return a fake SetupIntent
 * object, then passing that mock as the authenticated user.
 *
 * Tests intentionally avoid exercising subscribe(), swapPlan(), and
 * updatePaymentMethod() because those require Stripe API calls that cannot
 * be faked without a test Stripe account or Stripe's own test helpers.
 * We test the observable rendering state and the property mutations that
 * are purely server-side (e.g., selectedPlan, billingError, showCancelModal).
 */
class BillingPageTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Create a fake \Stripe\SetupIntent DTO that Livewire can render without
     * hitting the network. The view only accesses $setupIntent->client_secret.
     */
    private function fakeSetupIntent(): SetupIntent
    {
        $intent = SetupIntent::constructFrom([
            'id'            => 'seti_test_fake',
            'object'        => 'setup_intent',
            'client_secret' => 'seti_test_fake_secret',
            'status'        => 'requires_payment_method',
        ]);

        return $intent;
    }

    /**
     * Create a fully-set-up user (active business, setup.complete passes) and
     * return a partial mock of that user that stubs createSetupIntent().
     *
     * The returned mock is stored in the database so all model operations
     * (subscriptions(), hasActiveBusiness(), etc.) work normally.
     */
    private function makeBillingUser(array $userAttributes = []): \Mockery\MockInterface
    {
        $user = User::factory()->create(array_merge([
            'trial_ends_at' => now()->addDays(7),
        ], $userAttributes));

        Business::factory()->active()->create(['user_id' => $user->id]);

        // Reload so relationships are fresh after business creation.
        $user->refresh();

        // Partial mock: all real methods work except createSetupIntent.
        $mock = \Mockery::mock($user)->makePartial();
        $mock->shouldReceive('createSetupIntent')
            ->andReturn($this->fakeSetupIntent());

        return $mock;
    }

    // -------------------------------------------------------------------------
    // Accessibility — who can reach the settings page (billing tab)
    // -------------------------------------------------------------------------

    public function test_trial_user_can_access_billing_page(): void
    {
        $user = $this->makeBillingUser([
            'trial_ends_at' => now()->addDays(7),
        ]);

        Livewire::actingAs($user)
            ->test(SettingsPage::class)
            ->assertOk();
    }

    public function test_expired_trial_user_can_access_billing_page(): void
    {
        // /settings is NOT guarded by the subscribed middleware —
        // expired users must be able to reach it to subscribe.
        $user = $this->makeBillingUser([
            'trial_ends_at' => now()->subDays(1),
        ]);

        Livewire::actingAs($user)
            ->test(SettingsPage::class)
            ->assertOk();
    }

    public function test_subscribed_user_can_access_billing_page(): void
    {
        $user = $this->makeBillingUser(['trial_ends_at' => null]);

        $user->subscriptions()->create([
            'type'         => 'default',
            'stripe_id'    => 'sub_billing_page_test',
            'stripe_status' => 'active',
            'stripe_price' => config('services.stripe.prices.starter_monthly', 'price_starter_monthly'),
        ]);

        Livewire::actingAs($user)
            ->test(SettingsPage::class)
            ->assertOk();
    }

    // -------------------------------------------------------------------------
    // mount() — selectedPlan initialisation from active subscription
    // -------------------------------------------------------------------------

    public function test_mount_sets_selected_plan_from_active_subscription_price_id(): void
    {
        config([
            'services.stripe.prices.pro_monthly' => 'price_pro_m_test',
        ]);

        $user = $this->makeBillingUser(['trial_ends_at' => null]);

        $user->subscriptions()->create([
            'type'         => 'default',
            'stripe_id'    => 'sub_pro_mount',
            'stripe_status' => 'active',
            'stripe_price' => 'price_pro_m_test',
        ]);

        Livewire::actingAs($user)
            ->test(SettingsPage::class)
            ->assertSet('selectedPlan', 'pro_monthly');
    }

    public function test_mount_defaults_to_starter_monthly_when_no_subscription(): void
    {
        $user = $this->makeBillingUser();

        Livewire::actingAs($user)
            ->test(SettingsPage::class)
            ->assertSet('selectedPlan', 'starter_monthly');
    }

    public function test_mount_defaults_to_starter_monthly_when_subscription_price_id_is_unknown(): void
    {
        $user = $this->makeBillingUser(['trial_ends_at' => null]);

        $user->subscriptions()->create([
            'type'         => 'default',
            'stripe_id'    => 'sub_unknown_price',
            'stripe_status' => 'active',
            'stripe_price' => 'price_unknown_xyz',
        ]);

        Livewire::actingAs($user)
            ->test(SettingsPage::class)
            ->assertSet('selectedPlan', 'starter_monthly');
    }

    // -------------------------------------------------------------------------
    // Initial component state
    // -------------------------------------------------------------------------

    public function test_billing_error_is_empty_on_mount(): void
    {
        $user = $this->makeBillingUser();

        Livewire::actingAs($user)
            ->test(SettingsPage::class)
            ->assertSet('billingError', '');
    }

    public function test_processing_is_false_on_mount(): void
    {
        $user = $this->makeBillingUser();

        Livewire::actingAs($user)
            ->test(SettingsPage::class)
            ->assertSet('processing', false);
    }

    public function test_show_cancel_modal_is_false_on_mount(): void
    {
        $user = $this->makeBillingUser();

        Livewire::actingAs($user)
            ->test(SettingsPage::class)
            ->assertSet('showCancelModal', false);
    }

    public function test_show_update_card_is_false_on_mount(): void
    {
        $user = $this->makeBillingUser();

        Livewire::actingAs($user)
            ->test(SettingsPage::class)
            ->assertSet('showUpdateCard', false);
    }

    // -------------------------------------------------------------------------
    // Plan selection (pure UI state, no Stripe call)
    // -------------------------------------------------------------------------

    public function test_selected_plan_can_be_set_to_pro_monthly(): void
    {
        $user = $this->makeBillingUser();

        Livewire::actingAs($user)
            ->test(SettingsPage::class)
            ->set('selectedPlan', 'pro_monthly')
            ->assertSet('selectedPlan', 'pro_monthly');
    }

    public function test_selected_plan_can_be_set_to_pro_annual(): void
    {
        $user = $this->makeBillingUser();

        Livewire::actingAs($user)
            ->test(SettingsPage::class)
            ->set('selectedPlan', 'pro_annual')
            ->assertSet('selectedPlan', 'pro_annual');
    }

    // -------------------------------------------------------------------------
    // subscribe() — invalid plan guard (no Stripe call needed)
    // -------------------------------------------------------------------------

    public function test_subscribe_sets_billing_error_when_plan_price_id_is_not_configured(): void
    {
        // When the config value for a plan is null/missing, subscribe() should
        // set a billingError without attempting to hit Stripe.
        config([
            'services.stripe.prices.starter_monthly' => null,
        ]);

        $user = $this->makeBillingUser();

        Livewire::actingAs($user)
            ->test(SettingsPage::class)
            ->set('selectedPlan', 'starter_monthly')
            ->call('subscribe', 'pm_fake_payment_method')
            ->assertSet('billingError', 'Please select a valid plan.')
            ->assertSet('processing', false);
    }

    // -------------------------------------------------------------------------
    // swapPlan() — invalid plan guard (no Stripe call needed)
    // -------------------------------------------------------------------------

    public function test_swap_plan_sets_billing_error_when_plan_price_id_is_not_configured(): void
    {
        config([
            'services.stripe.prices.pro_monthly' => null,
        ]);

        $user = $this->makeBillingUser(['trial_ends_at' => null]);

        $user->subscriptions()->create([
            'type'         => 'default',
            'stripe_id'    => 'sub_swap_guard',
            'stripe_status' => 'active',
            'stripe_price' => 'price_starter_test',
        ]);

        Livewire::actingAs($user)
            ->test(SettingsPage::class)
            ->call('swapPlan', 'pro_monthly')
            ->assertSet('billingError', 'Invalid plan selected.')
            ->assertSet('processing', false);
    }

    // -------------------------------------------------------------------------
    // Cancel modal toggle (pure state, no Stripe call)
    // -------------------------------------------------------------------------

    public function test_show_cancel_modal_can_be_toggled_on(): void
    {
        $user = $this->makeBillingUser();

        Livewire::actingAs($user)
            ->test(SettingsPage::class)
            ->set('showCancelModal', true)
            ->assertSet('showCancelModal', true);
    }

    // -------------------------------------------------------------------------
    // View renders correct trial/expired state indicators
    // -------------------------------------------------------------------------

    public function test_view_contains_trial_indicator_for_trial_user(): void
    {
        $user = $this->makeBillingUser([
            'trial_ends_at' => now()->addDays(5),
        ]);

        Livewire::actingAs($user)
            ->test(SettingsPage::class)
            ->assertSee('Trial');
    }

    public function test_view_contains_trial_ended_indicator_for_expired_user(): void
    {
        $user = $this->makeBillingUser([
            'trial_ends_at' => now()->subDays(1),
        ]);

        Livewire::actingAs($user)
            ->test(SettingsPage::class)
            ->assertSee('Trial Ended');
    }

    public function test_view_contains_choose_your_plan_heading(): void
    {
        $user = $this->makeBillingUser();

        Livewire::actingAs($user)
            ->test(SettingsPage::class)
            ->assertSee('Choose Your Plan');
    }
}

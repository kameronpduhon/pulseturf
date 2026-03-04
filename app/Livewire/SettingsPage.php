<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Laravel\Cashier\Exceptions\IncompletePayment;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('layouts.app')]
class SettingsPage extends Component
{
    // Tab state
    public string $activeTab = 'account';

    // Account fields
    public string $name = '';
    public string $email = '';
    public string $timezone = 'America/Chicago';
    public string $currentPassword = '';
    public string $newPassword = '';
    public string $newPasswordConfirmation = '';

    // Account status messages
    public bool $profileSaved = false;
    public bool $timezoneSaved = false;
    public bool $passwordSaved = false;

    // Billing state
    public string $selectedPlan = 'starter_monthly';
    public string $billingError = '';
    public bool $processing = false;
    public bool $showCancelModal = false;
    public bool $showUpdateCard = false;
    #[Locked]
    public string $setupIntentClientSecret = '';

    public function mount(?string $tab = null): void
    {
        $user = auth()->user();

        $this->name     = $user->name;
        $this->email    = $user->email;
        $this->timezone = $user->timezone ?? 'America/Chicago';

        // Allow tab selection via query param or route param
        $requestedTab = $tab ?? request()->query('tab');
        if ($requestedTab && in_array($requestedTab, ['account', 'billing'], strict: true)) {
            $this->activeTab = $requestedTab;
        }

        // Billing initialization — create SetupIntent once on mount
        $this->setupIntentClientSecret = $user->createSetupIntent()->client_secret;

        $subscription = $user->subscription('default');

        if ($subscription?->valid()) {
            $currentPriceId = $subscription->stripe_price;
            $planKey = $this->getPlanKeyFromPriceId($currentPriceId);
            if ($planKey) {
                $this->selectedPlan = $planKey;
            }
        }
    }

    public function switchTab(string $tab): void
    {
        if (in_array($tab, ['account', 'billing'], strict: true)) {
            $this->activeTab = $tab;

            if ($tab === 'billing') {
                $this->dispatch('init-stripe-elements');
            }
        }
    }

    // -------------------------------------------------------------------------
    // Account methods
    // -------------------------------------------------------------------------

    public function updateProfile(): void
    {
        $user = auth()->user();

        $validated = $this->validate([
            'name'  => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'lowercase',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
        ]);

        $user->fill($validated);
        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }
        $user->save();

        $this->profileSaved = true;
        $this->dispatch('profile-updated', name: $user->name);
    }

    public function updateTimezone(): void
    {
        $this->validate([
            'timezone' => ['required', 'string', Rule::in(timezone_identifiers_list())],
        ]);

        auth()->user()->update(['timezone' => $this->timezone]);

        $this->timezoneSaved = true;
    }

    public function updatePassword(): void
    {
        $this->validate([
            'currentPassword'         => ['required', 'current_password'],
            'newPassword'             => ['required', 'string', 'min:8', 'confirmed:newPasswordConfirmation'],
            'newPasswordConfirmation' => ['required'],
        ], [
            'newPassword.confirmed'            => 'The new password confirmation does not match.',
            'currentPassword.current_password' => 'The current password is incorrect.',
        ]);

        auth()->user()->update([
            'password' => Hash::make($this->newPassword),
        ]);

        $this->currentPassword         = '';
        $this->newPassword             = '';
        $this->newPasswordConfirmation = '';

        $this->passwordSaved = true;
    }

    // -------------------------------------------------------------------------
    // Billing methods
    // -------------------------------------------------------------------------

    public function subscribe(string $paymentMethodId): void
    {
        $this->billingError = '';
        $this->processing = true;

        try {
            $user = auth()->user();
            $priceId = $this->getPriceId($this->selectedPlan);

            if (! $priceId) {
                $this->billingError = 'Please select a valid plan.';
                $this->processing = false;
                return;
            }

            $user->newSubscription('default', $priceId)->create($paymentMethodId);

            // Clear trial since they've subscribed
            if ($user->trial_ends_at) {
                $user->update(['trial_ends_at' => null]);
            }

            $this->redirect(route('home'));
            return;
        } catch (IncompletePayment $e) {
            $this->billingError = 'Your payment requires additional confirmation. Please try again.';
        } catch (\Exception $e) {
            $this->billingError = 'Payment failed. Please check your card details and try again.';
        } finally {
            $this->processing = false;
        }
    }

    public function swapPlan(string $planKey): void
    {
        if ($planKey === $this->selectedPlan) {
            return;
        }

        $this->billingError = '';
        $this->processing = true;

        try {
            $priceId = $this->getPriceId($planKey);

            if (! $priceId) {
                $this->billingError = 'Invalid plan selected.';
                $this->processing = false;
                return;
            }

            auth()->user()->subscription('default')->swap($priceId);
            $this->selectedPlan = $planKey;
        } catch (\Exception $e) {
            $this->billingError = 'Could not change plan. Please try again.';
        } finally {
            $this->processing = false;
        }
    }

    public function updatePaymentMethod(string $paymentMethodId): void
    {
        $this->billingError = '';
        $this->processing = true;

        try {
            auth()->user()->updateDefaultPaymentMethod($paymentMethodId);
            $this->showUpdateCard = false;
        } catch (\Exception $e) {
            $this->billingError = 'Could not update payment method. Please try again.';
        } finally {
            $this->processing = false;
        }
    }

    public function cancelSubscription(): void
    {
        $this->billingError = '';

        try {
            auth()->user()->subscription('default')->cancel();
            $this->showCancelModal = false;
        } catch (\Exception $e) {
            $this->billingError = 'Could not cancel subscription. Please try again.';
        }
    }

    public function resumeSubscription(): void
    {
        $this->billingError = '';

        try {
            auth()->user()->subscription('default')->resume();
        } catch (\Exception $e) {
            $this->billingError = 'Could not resume subscription. Please try again.';
        }
    }

    public function showUpdateCardForm(): void
    {
        $this->showUpdateCard = true;
        $this->dispatch('showUpdateCard');
    }

    // -------------------------------------------------------------------------
    // Render
    // -------------------------------------------------------------------------

    public function render()
    {
        $user = auth()->user();

        return view('livewire.settings-page', [
            'commonTimezones' => $this->getCommonTimezones(),
            'allTimezones'    => timezone_identifiers_list(),
            'plans'           => $this->getPlans(),
            'stripeKey'       => config('cashier.key'),
            'user'            => $user,
        ]);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function getCommonTimezones(): array
    {
        return [
            'America/New_York',
            'America/Chicago',
            'America/Denver',
            'America/Los_Angeles',
            'America/Phoenix',
            'Pacific/Honolulu',
            'America/Anchorage',
        ];
    }

    private function getPlans(): array
    {
        return [
            'starter_monthly' => [
                'name' => 'Starter',
                'interval' => 'monthly',
                'price' => '$29/mo',
                'competitors' => 1,
                'priceId' => config('services.stripe.prices.starter_monthly'),
                'badge' => null,
            ],
            'starter_annual' => [
                'name' => 'Starter',
                'interval' => 'annual',
                'price' => '$290/yr',
                'competitors' => 1,
                'priceId' => config('services.stripe.prices.starter_annual'),
                'badge' => 'Save $58',
            ],
            'pro_monthly' => [
                'name' => 'Pro',
                'interval' => 'monthly',
                'price' => '$79/mo',
                'competitors' => 3,
                'priceId' => config('services.stripe.prices.pro_monthly'),
                'badge' => 'Most Popular',
            ],
            'pro_annual' => [
                'name' => 'Pro',
                'interval' => 'annual',
                'price' => '$790/yr',
                'competitors' => 3,
                'priceId' => config('services.stripe.prices.pro_annual'),
                'badge' => 'Best Value',
            ],
        ];
    }

    private function getPriceId(string $planKey): ?string
    {
        return config("services.stripe.prices.{$planKey}");
    }

    private function getPlanKeyFromPriceId(string $priceId): ?string
    {
        $prices = config('services.stripe.prices');

        foreach ($prices as $key => $id) {
            if ($id === $priceId) {
                return $key;
            }
        }

        return null;
    }
}

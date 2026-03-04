<?php

namespace App\Livewire;

use Laravel\Cashier\Exceptions\IncompletePayment;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class BillingPage extends Component
{
    public string $selectedPlan = 'starter_monthly';
    public string $billingError = '';
    public bool $processing = false;
    public bool $showCancelModal = false;
    public bool $showUpdateCard = false;
    public string $setupIntentClientSecret = '';

    public function mount(): void
    {
        $user = auth()->user();
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

    public function render()
    {
        $user = auth()->user();

        return view('livewire.billing-page', [
            'plans' => $this->getPlans(),
            'stripeKey' => config('cashier.key'),
            'user' => $user,
        ]);
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

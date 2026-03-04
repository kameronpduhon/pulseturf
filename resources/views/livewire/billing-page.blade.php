<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Billing & Subscription') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Error banner --}}
            @if ($billingError)
                <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                    <p class="text-sm text-red-700">{{ $billingError }}</p>
                </div>
            @endif

            @php
                $user = auth()->user();
                $subscription = $user->subscription('default');
                $isSubscribed = $user->subscribed();
                $isOnTrial = $user->isOnTrial();
                $isOnGracePeriod = $subscription?->onGracePeriod() ?? false;
                $isPastDue = $subscription?->pastDue() ?? false;
            @endphp

            {{-- Past due warning --}}
            @if ($isPastDue)
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <div class="flex">
                        <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-yellow-800">Your payment is past due. Please update your payment method to continue service.</p>
                        </div>
                    </div>
                </div>
            @endif

            @if ($isSubscribed && !$isPastDue)
                {{-- ====================== SUBSCRIBED STATE ====================== --}}

                {{-- Current plan --}}
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Current Plan</h3>

                    @php
                        $currentPlanKey = null;
                        if ($subscription) {
                            foreach ($plans as $key => $plan) {
                                if ($plan['priceId'] === $subscription->stripe_price) {
                                    $currentPlanKey = $key;
                                    break;
                                }
                            }
                        }
                        $currentPlan = $currentPlanKey ? $plans[$currentPlanKey] : null;
                    @endphp

                    @if ($currentPlan)
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-2xl font-bold text-gray-900">{{ $currentPlan['name'] }}</p>
                                <p class="text-sm text-gray-500">{{ $currentPlan['price'] }} &middot; {{ $currentPlan['competitors'] }} {{ $currentPlan['competitors'] === 1 ? 'competitor' : 'competitors' }}</p>
                            </div>
                            <span class="inline-flex items-center rounded-full bg-green-100 px-3 py-1 text-sm font-medium text-green-800">
                                Active
                            </span>
                        </div>
                    @endif

                    @if ($isOnGracePeriod)
                        <div class="mt-4 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                            <p class="text-sm text-yellow-800">
                                Your subscription has been cancelled and will end on <strong>{{ $subscription->ends_at->format('F j, Y') }}</strong>.
                            </p>
                            <button wire:click="resumeSubscription" wire:loading.attr="disabled" class="mt-2 inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                Resume Subscription
                            </button>
                        </div>
                    @endif
                </div>

                {{-- Change plan --}}
                @if (!$isOnGracePeriod)
                    <div class="bg-white shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Change Plan</h3>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            @foreach ($plans as $key => $plan)
                                <button
                                    wire:click="swapPlan('{{ $key }}')"
                                    wire:loading.attr="disabled"
                                    @class([
                                        'relative rounded-lg border-2 p-4 text-left transition-colors',
                                        'border-indigo-600 bg-indigo-50' => $currentPlanKey === $key,
                                        'border-gray-200 hover:border-gray-300' => $currentPlanKey !== $key,
                                        'opacity-50 cursor-not-allowed' => $currentPlanKey === $key,
                                    ])
                                    @if ($currentPlanKey === $key) disabled @endif
                                >
                                    @if ($plan['badge'])
                                        <span class="absolute -top-2.5 right-3 inline-flex items-center rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-medium text-indigo-800">
                                            {{ $plan['badge'] }}
                                        </span>
                                    @endif
                                    <p class="font-semibold text-gray-900">{{ $plan['name'] }} <span class="text-sm font-normal text-gray-500">({{ ucfirst($plan['interval']) }})</span></p>
                                    <p class="text-lg font-bold text-gray-900 mt-1">{{ $plan['price'] }}</p>
                                    <p class="text-xs text-gray-500 mt-1">Up to {{ $plan['competitors'] }} {{ $plan['competitors'] === 1 ? 'competitor' : 'competitors' }}</p>
                                    @if ($currentPlanKey === $key)
                                        <p class="text-xs text-indigo-600 font-medium mt-2">Current Plan</p>
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- Payment method --}}
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Payment Method</h3>

                    @php $pm = $user->defaultPaymentMethod(); @endphp

                    @if ($pm)
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="flex items-center justify-center w-10 h-7 bg-gray-100 rounded text-xs font-bold text-gray-600 uppercase">
                                    {{ $pm->card->brand }}
                                </div>
                                <span class="text-sm text-gray-700">&bull;&bull;&bull;&bull; {{ $pm->card->last4 }}</span>
                                <span class="text-xs text-gray-500">Expires {{ $pm->card->exp_month }}/{{ $pm->card->exp_year }}</span>
                            </div>
                            <button wire:click="$set('showUpdateCard', true)" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                                Update
                            </button>
                        </div>
                    @else
                        <p class="text-sm text-gray-500">No payment method on file.</p>
                        <button wire:click="$set('showUpdateCard', true)" class="mt-2 text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                            Add Payment Method
                        </button>
                    @endif

                    @if ($showUpdateCard)
                        <div class="mt-4 border-t border-gray-200 pt-4">
                            <div id="update-card-element" wire:ignore class="p-3 border border-gray-300 rounded-md"></div>
                            <div class="mt-3 flex gap-3">
                                <button id="update-card-button" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150" wire:loading.attr="disabled">
                                    Save Card
                                </button>
                                <button wire:click="$set('showUpdateCard', false)" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    Cancel
                                </button>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Cancel subscription --}}
                @if (!$isOnGracePeriod)
                    <div class="bg-white shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Cancel Subscription</h3>
                        <p class="text-sm text-gray-600 mb-4">If you cancel, your subscription will remain active until the end of your current billing period.</p>
                        <button wire:click="$set('showCancelModal', true)" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            Cancel Subscription
                        </button>
                    </div>

                    {{-- Cancel confirmation modal --}}
                    @if ($showCancelModal)
                        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="$set('showCancelModal', false)"></div>
                                <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
                                <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
                                    <h3 class="text-lg font-medium text-gray-900" id="modal-title">Cancel Subscription</h3>
                                    <p class="mt-2 text-sm text-gray-500">Are you sure you want to cancel? You'll lose access to competitive intelligence features at the end of your billing period.</p>
                                    <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse gap-3">
                                        <button wire:click="cancelSubscription" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:w-auto sm:text-sm">
                                            Yes, Cancel
                                        </button>
                                        <button wire:click="$set('showCancelModal', false)" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm">
                                            Keep Subscription
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                @endif

            @else
                {{-- ====================== TRIAL / EXPIRED STATE ====================== --}}

                {{-- Trial/expired banner --}}
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    @if ($isOnTrial)
                        @php
                            $daysLeft = (int) now()->diffInDays($user->trial_ends_at, false);
                        @endphp
                        <div class="flex items-center gap-3">
                            <div class="flex-shrink-0">
                                <span class="inline-flex items-center rounded-full bg-amber-100 px-3 py-1 text-sm font-medium text-amber-800">
                                    Trial: {{ $daysLeft }} {{ $daysLeft === 1 ? 'day' : 'days' }} left
                                </span>
                            </div>
                            <p class="text-sm text-gray-600">Choose a plan to continue using PulseTurf after your trial ends.</p>
                        </div>
                    @else
                        <div class="flex items-center gap-3">
                            <div class="flex-shrink-0">
                                <span class="inline-flex items-center rounded-full bg-red-100 px-3 py-1 text-sm font-medium text-red-800">
                                    Trial Ended
                                </span>
                            </div>
                            <p class="text-sm text-gray-600">Your trial has expired. Subscribe to regain access to your competitive intelligence.</p>
                        </div>
                    @endif
                </div>

                {{-- Plan cards --}}
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Choose Your Plan</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        @foreach ($plans as $key => $plan)
                            <button
                                wire:click="$set('selectedPlan', '{{ $key }}')"
                                @class([
                                    'relative rounded-lg border-2 p-4 text-left transition-colors cursor-pointer',
                                    'border-indigo-600 bg-indigo-50 ring-2 ring-indigo-600' => $selectedPlan === $key,
                                    'border-gray-200 hover:border-gray-300' => $selectedPlan !== $key,
                                ])
                            >
                                @if ($plan['badge'])
                                    <span class="absolute -top-2.5 right-3 inline-flex items-center rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-medium text-indigo-800">
                                        {{ $plan['badge'] }}
                                    </span>
                                @endif
                                <p class="font-semibold text-gray-900">{{ $plan['name'] }} <span class="text-sm font-normal text-gray-500">({{ ucfirst($plan['interval']) }})</span></p>
                                <p class="text-lg font-bold text-gray-900 mt-1">{{ $plan['price'] }}</p>
                                <p class="text-xs text-gray-500 mt-1">Up to {{ $plan['competitors'] }} {{ $plan['competitors'] === 1 ? 'competitor' : 'competitors' }}</p>
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- Payment form --}}
                <div class="bg-white shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Payment Details</h3>
                    <div id="card-element" wire:ignore class="p-3 border border-gray-300 rounded-md"></div>
                    <div id="card-errors" class="mt-2 text-sm text-red-600" role="alert"></div>
                    <button id="subscribe-button" wire:loading.attr="disabled" class="mt-4 w-full inline-flex justify-center items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-sm text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50 transition ease-in-out duration-150">
                        <span wire:loading.remove>Subscribe</span>
                        <span wire:loading>Processing...</span>
                    </button>
                </div>
            @endif
        </div>
    </div>

    @assets
    <script src="https://js.stripe.com/v3/"></script>
    @endassets

    @script
    <script>
        const stripe = Stripe(@js($stripeKey));
        const elements = stripe.elements();

        // Subscribe card element (for new subscriptions)
        const cardEl = document.getElementById('card-element');
        let subscribeCard = null;
        if (cardEl) {
            subscribeCard = elements.create('card', {
                style: {
                    base: {
                        fontSize: '16px',
                        color: '#374151',
                        '::placeholder': { color: '#9CA3AF' },
                    },
                },
            });
            subscribeCard.mount('#card-element');

            subscribeCard.on('change', function(event) {
                const errorEl = document.getElementById('card-errors');
                if (errorEl) {
                    errorEl.textContent = event.error ? event.error.message : '';
                }
            });

            const subscribeButton = document.getElementById('subscribe-button');
            if (subscribeButton) {
                subscribeButton.addEventListener('click', async function() {
                    subscribeButton.disabled = true;
                    const { setupIntent, error } = await stripe.confirmCardSetup(
                        @js($setupIntentClientSecret),
                        { payment_method: { card: subscribeCard } }
                    );

                    if (error) {
                        const errorEl = document.getElementById('card-errors');
                        if (errorEl) errorEl.textContent = error.message;
                        subscribeButton.disabled = false;
                    } else {
                        $wire.call('subscribe', setupIntent.payment_method);
                    }
                });
            }
        }

        // Update card element (for existing subscribers)
        $wire.on('showUpdateCard', () => {
            setTimeout(() => {
                const updateCardEl = document.getElementById('update-card-element');
                if (updateCardEl && !updateCardEl.hasChildNodes()) {
                    const updateCard = elements.create('card', {
                        style: {
                            base: {
                                fontSize: '16px',
                                color: '#374151',
                                '::placeholder': { color: '#9CA3AF' },
                            },
                        },
                    });
                    updateCard.mount('#update-card-element');

                    const updateButton = document.getElementById('update-card-button');
                    if (updateButton) {
                        updateButton.addEventListener('click', async function() {
                            updateButton.disabled = true;
                            const { setupIntent, error } = await stripe.confirmCardSetup(
                                @js($setupIntentClientSecret),
                                { payment_method: { card: updateCard } }
                            );

                            if (error) {
                                updateButton.disabled = false;
                                $wire.set('billingError', error.message);
                            } else {
                                $wire.call('updatePaymentMethod', setupIntent.payment_method);
                            }
                        });
                    }
                }
            }, 100);
        });
    </script>
    @endscript
</div>

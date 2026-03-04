<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Home') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @php
                $user     = auth()->user();
                $business = $user->business;
                $tz       = $user->timezone ?? 'America/Chicago';

                // Next Monday at 7 AM in the user's timezone
                $nextMonday = \Carbon\Carbon::now($tz)->next(\Carbon\Carbon::MONDAY)->setTime(7, 0, 0);
            @endphp

            {{-- Business status card --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Your Business</h3>
                    <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">
                        Active
                    </span>
                </div>

                @if ($business)
                    <div class="space-y-2 text-sm text-gray-700">
                        <div class="flex items-center gap-2">
                            <span class="font-semibold text-gray-900 text-base">{{ $business->name }}</span>
                        </div>

                        @if ($business->address)
                            <p class="text-gray-500 text-xs">{{ $business->address }}</p>
                        @endif

                        <div class="flex items-center gap-6 mt-3 pt-3 border-t border-gray-100">
                            @if ($business->google_rating)
                                <div>
                                    <p class="text-xs text-gray-500 uppercase tracking-wide font-medium">Rating</p>
                                    <p class="text-xl font-bold text-gray-900 mt-0.5">{{ $business->google_rating }}
                                        <span class="text-sm font-normal text-gray-500">/ 5.0</span>
                                    </p>
                                </div>
                            @endif

                            @if ($business->google_review_count)
                                <div>
                                    <p class="text-xs text-gray-500 uppercase tracking-wide font-medium">Reviews</p>
                                    <p class="text-xl font-bold text-gray-900 mt-0.5">{{ number_format($business->google_review_count) }}</p>
                                </div>
                            @endif

                            <div>
                                <p class="text-xs text-gray-500 uppercase tracking-wide font-medium">Competitors Tracked</p>
                                <p class="text-xl font-bold text-gray-900 mt-0.5">{{ $business->competitors()->count() }}</p>
                            </div>
                        </div>
                    </div>
                @else
                    <p class="text-sm text-gray-500">No business found. <a href="{{ route('setup') }}" class="text-indigo-600 hover:underline">Complete setup</a>.</p>
                @endif
            </div>

            {{-- Next digest card --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-2">Next Briefing</h3>
                <p class="text-sm text-gray-600">
                    Your next intelligence briefing is scheduled for
                    <span class="font-semibold text-gray-900">{{ $nextMonday->format('l, F j') }}</span>
                    at
                    <span class="font-semibold text-gray-900">7:00 AM {{ $tz }}</span>.
                </p>
            </div>

            {{-- Quick links --}}
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Settings</h3>
                <div class="space-y-2">
                    <a href="{{ route('profile') }}" class="flex items-center gap-2 text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        Profile Settings
                    </a>
                    <a href="{{ route('billing') }}" class="flex items-center gap-2 text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                        </svg>
                        Billing & Subscription
                    </a>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>

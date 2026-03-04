<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Home') }}
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-5">

            @php
                $user     = auth()->user();
                $business = $user->business;
                $tz       = $user->timezone ?? 'America/Chicago';

                // Next Monday at 7 AM in the user's timezone
                $nextMonday = \Carbon\Carbon::now($tz)->next(\Carbon\Carbon::MONDAY)->setTime(7, 0, 0);
            @endphp

            {{-- Business status card --}}
            <div class="bg-white shadow-sm sm:rounded-2xl border border-gray-100 p-6">
                <div class="flex items-center justify-between mb-5">
                    <h3 class="text-base font-semibold text-gray-900">Your Business</h3>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-green-50 border border-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-700">
                        <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                        Active
                    </span>
                </div>

                @if ($business)
                    <div>
                        <p class="font-bold text-gray-900 text-lg leading-tight">{{ $business->name }}</p>

                        @if ($business->address)
                            <p class="text-gray-400 text-xs mt-0.5">{{ $business->address }}</p>
                        @endif

                        <div class="flex items-stretch gap-px mt-5 rounded-xl overflow-hidden border border-gray-100 bg-gray-100">
                            @if ($business->google_rating)
                                <div class="flex-1 bg-white px-4 py-4 text-center">
                                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-1">Rating</p>
                                    <p class="text-2xl font-bold text-gray-900 leading-none">{{ $business->google_rating }}</p>
                                    <p class="text-xs text-gray-400 mt-0.5">out of 5.0</p>
                                </div>
                            @endif

                            @if ($business->google_review_count)
                                <div class="flex-1 bg-white px-4 py-4 text-center">
                                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-1">Reviews</p>
                                    <p class="text-2xl font-bold text-gray-900 leading-none">{{ number_format($business->google_review_count) }}</p>
                                    <p class="text-xs text-gray-400 mt-0.5">on Google</p>
                                </div>
                            @endif

                            <div class="flex-1 bg-white px-4 py-4 text-center">
                                <p class="text-xs font-semibold text-gray-400 uppercase tracking-widest mb-1">Competing</p>
                                <p class="text-2xl font-bold text-gray-900 leading-none">{{ $business->competitors()->count() }}</p>
                                <p class="text-xs text-gray-400 mt-0.5">tracked</p>
                            </div>
                        </div>
                    </div>
                @else
                    <p class="text-sm text-gray-500">No business found. <a href="{{ route('setup') }}" class="text-indigo-600 hover:underline font-medium">Complete setup</a>.</p>
                @endif
            </div>

            {{-- Next digest card --}}
            <div class="bg-white shadow-sm sm:rounded-2xl border border-gray-100 p-6">
                <div class="flex items-center gap-3">
                    <div class="flex-shrink-0 w-10 h-10 rounded-xl bg-indigo-50 border border-indigo-100 flex items-center justify-center">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-semibold text-gray-900">Next Briefing</h3>
                        <p class="text-sm text-gray-500 mt-0.5">
                            <span class="font-semibold text-gray-700">{{ $nextMonday->format('l, F j') }}</span>
                            at <span class="font-semibold text-gray-700">7:00 AM</span>
                            <span class="text-gray-400">({{ $tz }})</span>
                        </p>
                    </div>
                </div>
            </div>

            {{-- Quick links --}}
            <div class="bg-white shadow-sm sm:rounded-2xl border border-gray-100 p-6">
                <h3 class="text-base font-semibold text-gray-900 mb-4">Quick Links</h3>
                <div class="space-y-1">
                    <a href="{{ route('settings') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-gray-50 transition-colors group">
                        <span class="flex-shrink-0 w-8 h-8 rounded-lg bg-gray-100 group-hover:bg-indigo-50 flex items-center justify-center transition-colors">
                            <svg class="h-4 w-4 text-gray-500 group-hover:text-indigo-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </span>
                        <span class="text-sm font-medium text-gray-700 group-hover:text-gray-900 transition-colors">Account Settings</span>
                        <svg class="ml-auto h-4 w-4 text-gray-300 group-hover:text-indigo-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                    <a href="{{ route('settings', ['tab' => 'billing']) }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-gray-50 transition-colors group">
                        <span class="flex-shrink-0 w-8 h-8 rounded-lg bg-gray-100 group-hover:bg-indigo-50 flex items-center justify-center transition-colors">
                            <svg class="h-4 w-4 text-gray-500 group-hover:text-indigo-600 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                            </svg>
                        </span>
                        <span class="text-sm font-medium text-gray-700 group-hover:text-gray-900 transition-colors">Billing &amp; Subscription</span>
                        <svg class="ml-auto h-4 w-4 text-gray-300 group-hover:text-indigo-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>

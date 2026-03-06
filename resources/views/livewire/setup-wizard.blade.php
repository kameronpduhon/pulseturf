<div class="py-12">
    <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">

        {{-- Step progress indicator --}}
        <div class="mb-8">
            <div class="flex items-center justify-between">
                @foreach ([1 => 'Your Business', 2 => 'Competitors', 3 => 'Setting Up', 4 => 'All Set'] as $step => $label)
                    <div class="flex flex-col items-center flex-1">
                        <div class="flex items-center w-full">
                            @if ($step > 1)
                                <div class="flex-1 h-0.5 {{ $currentStep >= $step ? 'bg-indigo-600' : 'bg-gray-300' }}"></div>
                            @endif
                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-semibold
                                {{ $currentStep > $step ? 'bg-indigo-600 text-white' : ($currentStep === $step ? 'bg-indigo-600 text-white ring-4 ring-indigo-100' : 'bg-gray-200 text-gray-500') }}">
                                @if ($currentStep > $step)
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                @else
                                    {{ $step }}
                                @endif
                            </div>
                            @if ($step < 4)
                                <div class="flex-1 h-0.5 {{ $currentStep > $step ? 'bg-indigo-600' : 'bg-gray-300' }}"></div>
                            @endif
                        </div>
                        <span class="mt-2 text-xs font-medium {{ $currentStep === $step ? 'text-indigo-600' : 'text-gray-500' }}">
                            {{ $label }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- ================================================================= --}}
        {{-- STEP 1: Your Business                                              --}}
        {{-- ================================================================= --}}
        @if ($currentStep === 1)
            <div class="bg-white shadow-sm sm:rounded-2xl border border-gray-100">
                <div class="p-6 sm:p-8">
                    <h2 class="text-xl font-semibold text-gray-900 mb-1">Tell us about your business</h2>
                    <p class="text-sm text-gray-500 mb-6">We'll look it up on Google to get your rating and reviews.</p>

                    @if ($foundBusiness)
                        {{-- Confirmation card --}}
                        <div class="rounded-lg border border-green-200 bg-green-50 p-4 mb-6">
                            <div class="flex items-start justify-between">
                                <div>
                                    <p class="font-semibold text-gray-900">{{ $foundBusiness['name'] }}</p>
                                    @if ($foundBusiness['address'])
                                        <p class="text-sm text-gray-600 mt-0.5">{{ $foundBusiness['address'] }}</p>
                                    @endif
                                    <div class="flex items-center gap-4 mt-2 text-sm text-gray-700">
                                        @if ($foundBusiness['rating'])
                                            <span>{{ $foundBusiness['rating'] }} stars</span>
                                        @endif
                                        @if ($foundBusiness['review_count'])
                                            <span>{{ number_format($foundBusiness['review_count']) }} reviews</span>
                                        @endif
                                    </div>
                                </div>
                                <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">
                                    Found
                                </span>
                            </div>
                        </div>

                        <p class="text-sm text-gray-600 mb-4">Is this your business?</p>

                        <div class="flex gap-3">
                            <button
                                wire:click="confirmBusiness"
                                class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                            >
                                Yes, that's my business
                            </button>
                            <button
                                wire:click="resetBusinessSearch"
                                class="inline-flex items-center px-4 py-2 bg-white text-gray-700 text-sm font-medium rounded-md border border-gray-300 hover:bg-gray-50"
                            >
                                Not quite — try again
                            </button>
                        </div>

                    @else
                        {{-- Search form --}}
                        @if ($businessSearchError)
                            <div class="rounded-md bg-red-50 border border-red-200 p-3 mb-4 text-sm text-red-700">
                                {{ $businessSearchError }}
                            </div>
                        @endif

                        <div class="space-y-4">
                            <div>
                                <label for="businessName" class="block text-sm font-medium text-gray-700 mb-1">Business Name</label>
                                <input
                                    wire:model="businessName"
                                    id="businessName"
                                    type="text"
                                    placeholder="Glow Med Spa"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('businessName') border-red-300 @enderror"
                                />
                                @error('businessName')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="businessAddress" class="block text-sm font-medium text-gray-700 mb-1">Street Address</label>
                                <input
                                    wire:model="businessAddress"
                                    id="businessAddress"
                                    type="text"
                                    placeholder="123 Main St"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('businessAddress') border-red-300 @enderror"
                                />
                                @error('businessAddress')
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="grid grid-cols-6 gap-3">
                                <div class="col-span-3">
                                    <label for="businessCity" class="block text-sm font-medium text-gray-700 mb-1">City</label>
                                    <input
                                        wire:model="businessCity"
                                        id="businessCity"
                                        type="text"
                                        placeholder="Austin"
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('businessCity') border-red-300 @enderror"
                                    />
                                    @error('businessCity')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="col-span-1">
                                    <label for="businessState" class="block text-sm font-medium text-gray-700 mb-1">State</label>
                                    <input
                                        wire:model="businessState"
                                        id="businessState"
                                        type="text"
                                        placeholder="TX"
                                        maxlength="2"
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('businessState') border-red-300 @enderror"
                                    />
                                    @error('businessState')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="col-span-2">
                                    <label for="businessZip" class="block text-sm font-medium text-gray-700 mb-1">ZIP</label>
                                    <input
                                        wire:model="businessZip"
                                        id="businessZip"
                                        type="text"
                                        placeholder="78701"
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('businessZip') border-red-300 @enderror"
                                    />
                                    @error('businessZip')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <div class="mt-6">
                            <button
                                wire:click="findBusiness"
                                wire:loading.attr="disabled"
                                wire:target="findBusiness"
                                class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-60 disabled:cursor-not-allowed"
                            >
                                <span wire:loading.remove wire:target="findBusiness">Find My Business</span>
                                <span wire:loading wire:target="findBusiness" class="flex items-center gap-2">
                                    <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                    </svg>
                                    Searching...
                                </span>
                            </button>
                        </div>

                        {{-- URL / Place ID fallback --}}
                        @if ($showUrlFallback)
                            <div class="mt-6 pt-6 border-t border-gray-200">
                                <p class="text-sm font-medium text-gray-700 mb-3">Having trouble? Use your Google Maps Place ID or URL</p>
                                <p class="text-xs text-gray-500 mb-3">
                                    Open Google Maps, search your business, and copy the URL or the Place ID from the share dialog.
                                </p>
                                <div class="flex gap-3">
                                    <input
                                        wire:model="googleMapsUrl"
                                        type="text"
                                        placeholder="ChIJ... or https://maps.google.com/..."
                                        class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    />
                                    <button
                                        wire:click="findBusinessByUrl"
                                        wire:loading.attr="disabled"
                                        wire:target="findBusinessByUrl"
                                        class="inline-flex items-center px-3 py-2 bg-gray-800 text-white text-sm font-medium rounded-md hover:bg-gray-700 disabled:opacity-60"
                                    >
                                        <span wire:loading.remove wire:target="findBusinessByUrl">Look Up</span>
                                        <span wire:loading wire:target="findBusinessByUrl">...</span>
                                    </button>
                                </div>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        @endif

        {{-- ================================================================= --}}
        {{-- STEP 2: Competitors                                                --}}
        {{-- ================================================================= --}}
        @if ($currentStep === 2)
            <div class="bg-white shadow-sm sm:rounded-2xl border border-gray-100">
                <div class="p-6 sm:p-8">
                    <h2 class="text-xl font-semibold text-gray-900 mb-1">Add your competitors</h2>
                    <p class="text-sm text-gray-500 mb-6">
                        Add up to 3 competitors to track. You need at least 1 to continue.
                    </p>

                    {{-- Confirmed competitors list --}}
                    @if (count($competitors) > 0)
                        <div class="mb-6 space-y-2">
                            @foreach ($competitors as $index => $competitor)
                                <div class="flex items-center justify-between rounded-lg border border-gray-200 bg-gray-50 px-4 py-3">
                                    <div>
                                        <p class="font-medium text-gray-900 text-sm">{{ $competitor['name'] }}</p>
                                        @if ($competitor['address'])
                                            <p class="text-xs text-gray-500 mt-0.5">{{ $competitor['address'] }}</p>
                                        @endif
                                        <div class="flex items-center gap-3 mt-1 text-xs text-gray-600">
                                            @if ($competitor['rating'])
                                                <span>{{ $competitor['rating'] }} stars</span>
                                            @endif
                                            @if ($competitor['review_count'])
                                                <span>{{ number_format($competitor['review_count']) }} reviews</span>
                                            @endif
                                        </div>
                                    </div>
                                    <button
                                        wire:click="removeCompetitor({{ $index }})"
                                        class="text-xs text-red-500 hover:text-red-700 font-medium ml-4"
                                    >
                                        Remove
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    {{-- Add competitor form (hidden when at limit) --}}
                    @if (count($competitors) < 3)
                        @if ($foundCompetitor)
                            {{-- Confirmation card for found competitor --}}
                            <div class="rounded-lg border border-green-200 bg-green-50 p-4 mb-6">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <p class="font-semibold text-gray-900">{{ $foundCompetitor['name'] }}</p>
                                        @if ($foundCompetitor['address'])
                                            <p class="text-sm text-gray-600 mt-0.5">{{ $foundCompetitor['address'] }}</p>
                                        @endif
                                        <div class="flex items-center gap-4 mt-2 text-sm text-gray-700">
                                            @if ($foundCompetitor['rating'])
                                                <span>{{ $foundCompetitor['rating'] }} stars</span>
                                            @endif
                                            @if ($foundCompetitor['review_count'])
                                                <span>{{ number_format($foundCompetitor['review_count']) }} reviews</span>
                                            @endif
                                        </div>
                                    </div>
                                    <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">
                                        Found
                                    </span>
                                </div>
                            </div>

                            @if ($competitorSearchError)
                                <div class="rounded-md bg-red-50 border border-red-200 p-3 mb-3 text-sm text-red-700">
                                    {{ $competitorSearchError }}
                                </div>
                            @endif

                            <div class="flex gap-3">
                                <button
                                    wire:click="confirmCompetitor"
                                    class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                >
                                    Add This Competitor
                                </button>
                                <button
                                    wire:click="$set('foundCompetitor', null)"
                                    class="inline-flex items-center px-4 py-2 bg-white text-gray-700 text-sm font-medium rounded-md border border-gray-300 hover:bg-gray-50"
                                >
                                    Not quite — try again
                                </button>
                            </div>

                        @else
                            {{-- Search form --}}
                            @if ($competitorSearchError)
                                <div class="rounded-md bg-red-50 border border-red-200 p-3 mb-4 text-sm text-red-700">
                                    {{ $competitorSearchError }}
                                </div>
                            @endif

                            <div class="space-y-4">
                                <div>
                                    <label for="competitorName" class="block text-sm font-medium text-gray-700 mb-1">Competitor Name</label>
                                    <input
                                        wire:model="competitorName"
                                        id="competitorName"
                                        type="text"
                                        placeholder="Radiance Med Spa"
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('competitorName') border-red-300 @enderror"
                                    />
                                    @error('competitorName')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="competitorAddress" class="block text-sm font-medium text-gray-700 mb-1">Street Address</label>
                                    <input
                                        wire:model="competitorAddress"
                                        id="competitorAddress"
                                        type="text"
                                        placeholder="456 Oak Ave"
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('competitorAddress') border-red-300 @enderror"
                                    />
                                    @error('competitorAddress')
                                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="grid grid-cols-6 gap-3">
                                    <div class="col-span-3">
                                        <label for="competitorCity" class="block text-sm font-medium text-gray-700 mb-1">City</label>
                                        <input
                                            wire:model="competitorCity"
                                            id="competitorCity"
                                            type="text"
                                            placeholder="Austin"
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('competitorCity') border-red-300 @enderror"
                                        />
                                        @error('competitorCity')
                                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div class="col-span-1">
                                        <label for="competitorState" class="block text-sm font-medium text-gray-700 mb-1">State</label>
                                        <input
                                            wire:model="competitorState"
                                            id="competitorState"
                                            type="text"
                                            placeholder="TX"
                                            maxlength="2"
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('competitorState') border-red-300 @enderror"
                                        />
                                        @error('competitorState')
                                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div class="col-span-2">
                                        <label for="competitorZip" class="block text-sm font-medium text-gray-700 mb-1">ZIP</label>
                                        <input
                                            wire:model="competitorZip"
                                            id="competitorZip"
                                            type="text"
                                            placeholder="78701"
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm @error('competitorZip') border-red-300 @enderror"
                                        />
                                        @error('competitorZip')
                                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="mt-6">
                                <button
                                    wire:click="findCompetitor"
                                    wire:loading.attr="disabled"
                                    wire:target="findCompetitor"
                                    class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-60 disabled:cursor-not-allowed"
                                >
                                    <span wire:loading.remove wire:target="findCompetitor">Find Competitor</span>
                                    <span wire:loading wire:target="findCompetitor" class="flex items-center gap-2">
                                        <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                        </svg>
                                        Searching...
                                    </span>
                                </button>
                            </div>

                            {{-- URL / Place ID fallback --}}
                            @if ($showCompetitorUrlFallback)
                                <div class="mt-6 pt-6 border-t border-gray-200">
                                    <p class="text-sm font-medium text-gray-700 mb-3">Having trouble? Use a Google Maps Place ID or URL</p>
                                    <div class="flex gap-3">
                                        <input
                                            wire:model="competitorGoogleMapsUrl"
                                            type="text"
                                            placeholder="ChIJ... or https://maps.google.com/..."
                                            class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                        />
                                        <button
                                            wire:click="findCompetitorByUrl"
                                            wire:loading.attr="disabled"
                                            wire:target="findCompetitorByUrl"
                                            class="inline-flex items-center px-3 py-2 bg-gray-800 text-white text-sm font-medium rounded-md hover:bg-gray-700 disabled:opacity-60"
                                        >
                                            <span wire:loading.remove wire:target="findCompetitorByUrl">Look Up</span>
                                            <span wire:loading wire:target="findCompetitorByUrl">...</span>
                                        </button>
                                    </div>
                                </div>
                            @endif
                        @endif
                    @else
                        <p class="text-sm text-gray-500 italic">You've reached the maximum of 3 competitors.</p>
                    @endif

                    {{-- Continue button --}}
                    @if (count($competitors) >= 1)
                        <div class="mt-8 pt-6 border-t border-gray-200">
                            <button
                                wire:click="proceedToScraping"
                                wire:loading.attr="disabled"
                                wire:target="proceedToScraping"
                                class="inline-flex items-center px-5 py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-60 disabled:cursor-not-allowed"
                            >
                                Continue with {{ count($competitors) }} {{ Str::plural('competitor', count($competitors)) }}
                                <svg class="ml-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        {{-- ================================================================= --}}
        {{-- STEP 3: Scraping In Progress                                       --}}
        {{-- ================================================================= --}}
        @if ($currentStep === 3)
            @php
                $totalItems = count($scrapeStatuses);
                $doneItems = collect($scrapeStatuses)->filter(fn($s) => in_array($s['status'], ['complete', 'failed']))->count();
                $allDone = $totalItems > 0 && $doneItems === $totalItems;
                $progressPct = $totalItems > 0 ? (int)(($doneItems / $totalItems) * 100) : 0;
            @endphp
            <div wire:poll.3s="checkScrapeProgress" class="bg-white shadow-sm sm:rounded-2xl border border-gray-100">
                <div class="p-6 sm:p-8">
                    {{-- Header --}}
                    <div class="text-center mb-8">
                        <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-indigo-50 border border-indigo-100 mb-4">
                            @if ($allDone)
                                <svg class="w-7 h-7 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                            @else
                                <svg class="w-7 h-7 animate-spin text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                </svg>
                            @endif
                        </div>
                        <h2 class="text-xl font-semibold text-gray-900 mb-1">Building your intelligence briefing</h2>
                        <p class="text-sm text-gray-500">We're pulling your Google reviews right now. Hang tight — this takes 30–60 seconds.</p>
                    </div>

                    {{-- Progress bar --}}
                    <div class="mb-6">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-xs font-medium text-gray-500">Progress</span>
                            <span class="text-xs font-semibold text-indigo-600">{{ $progressPct }}%</span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-2">
                            <div
                                class="bg-indigo-600 h-2 rounded-full transition-all duration-700 ease-in-out"
                                style="width: {{ max(8, $progressPct) }}%"
                            ></div>
                        </div>
                    </div>

                    {{-- Steps --}}
                    <div class="space-y-3">
                        {{-- Step: Connecting to Google --}}
                        <div class="flex items-center gap-3 p-3 rounded-xl bg-green-50 border border-green-100">
                            <span class="w-7 h-7 flex items-center justify-center rounded-full bg-green-100 shrink-0">
                                <svg class="w-3.5 h-3.5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                </svg>
                            </span>
                            <div class="text-sm">
                                <span class="font-medium text-gray-800">Connected to Google</span>
                                <span class="ml-1 text-green-600 text-xs font-medium">Done</span>
                            </div>
                        </div>

                        {{-- Per-business scrape statuses --}}
                        @foreach ($scrapeStatuses as $key => $item)
                            <div class="flex items-center gap-3 p-3 rounded-xl
                                {{ $item['status'] === 'complete' ? 'bg-green-50 border border-green-100' : ($item['status'] === 'failed' ? 'bg-yellow-50 border border-yellow-100' : 'bg-indigo-50 border border-indigo-100') }}">

                                @if ($item['status'] === 'complete')
                                    <span class="w-7 h-7 flex items-center justify-center rounded-full bg-green-100 shrink-0">
                                        <svg class="w-3.5 h-3.5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </span>
                                @elseif ($item['status'] === 'failed')
                                    <span class="w-7 h-7 flex items-center justify-center rounded-full bg-yellow-100 shrink-0">
                                        <svg class="w-3.5 h-3.5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M12 3a9 9 0 110 18A9 9 0 0112 3z"/>
                                        </svg>
                                    </span>
                                @else
                                    <span class="w-7 h-7 flex items-center justify-center shrink-0">
                                        <svg class="w-5 h-5 animate-spin text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                        </svg>
                                    </span>
                                @endif

                                <div class="text-sm flex-1 min-w-0">
                                    <span class="font-medium text-gray-800 truncate block">{{ $item['name'] }}</span>
                                    <span class="text-xs
                                        {{ $item['status'] === 'complete' ? 'text-green-600' : ($item['status'] === 'failed' ? 'text-yellow-600' : 'text-indigo-500') }}">
                                        @if ($item['status'] === 'complete')
                                            Reviews scraped ✓
                                        @elseif ($item['status'] === 'failed')
                                            Partial data (will retry weekly)
                                        @else
                                            Fetching reviews from Google...
                                        @endif
                                    </span>
                                </div>
                            </div>
                        @endforeach

                        {{-- Final step: Building briefing --}}
                        <div class="flex items-center gap-3 p-3 rounded-xl
                            {{ $allDone ? 'bg-green-50 border border-green-100' : 'bg-gray-50 border border-gray-200' }}">
                            @if ($allDone)
                                <span class="w-7 h-7 flex items-center justify-center rounded-full bg-green-100 shrink-0">
                                    <svg class="w-3.5 h-3.5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </span>
                            @else
                                <span class="w-7 h-7 flex items-center justify-center rounded-full bg-gray-200 shrink-0">
                                    <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                </span>
                            @endif
                            <div class="text-sm">
                                <span class="font-medium {{ $allDone ? 'text-gray-800' : 'text-gray-400' }}">Schedule your first briefing</span>
                                @if ($allDone)
                                    <span class="ml-1 text-green-600 text-xs font-medium">Done</span>
                                @else
                                    <span class="block text-xs text-gray-400">Waiting for reviews...</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- ================================================================= --}}
        {{-- STEP 4: All Set                                                    --}}
        {{-- ================================================================= --}}
        @if ($currentStep === 4)
            <div class="bg-white shadow-sm sm:rounded-2xl border border-gray-100">
                <div class="p-6 sm:p-8 text-center">
                    <div class="mb-5">
                        <span class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-indigo-50 border border-indigo-100">
                            <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </span>
                    </div>

                    <h2 class="text-2xl font-bold text-gray-900 mb-2">You're all set!</h2>
                    <p class="text-sm text-gray-500 mb-6">Your competitive intelligence is ready to roll.</p>

                    @if ($business)
                        <div class="rounded-lg bg-gray-50 border border-gray-200 p-4 text-left max-w-sm mx-auto mb-6">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Your Summary</p>
                            <div class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Business</span>
                                    <span class="font-medium text-gray-900">{{ $business->name }}</span>
                                </div>
                                @if ($business->google_rating)
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Rating</span>
                                        <span class="font-medium text-gray-900">{{ $business->google_rating }} / 5.0</span>
                                    </div>
                                @endif
                                @if ($business->google_review_count)
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Reviews</span>
                                        <span class="font-medium text-gray-900">{{ number_format($business->google_review_count) }}</span>
                                    </div>
                                @endif
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Competitors tracked</span>
                                    <span class="font-medium text-gray-900">{{ count($competitors) }}</span>
                                </div>
                            </div>
                        </div>
                    @endif

                    <p class="text-sm text-indigo-700 font-medium mb-6">
                        Your first briefing arrives Monday at 7 AM
                    </p>

                    <a
                        href="{{ route('home') }}"
                        wire:navigate
                        class="inline-flex items-center px-5 py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                    >
                        Go to Home
                        <svg class="ml-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>
            </div>
        @endif

    </div>
</div>

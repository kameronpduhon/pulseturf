<div class="py-12">
    <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">

        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Digest History</h1>
            <p class="text-sm text-gray-500 mt-1">Your weekly intelligence briefings, all in one place.</p>
        </div>

        @if ($digests->isEmpty())
            {{-- Empty state --}}
            <div class="bg-white shadow-sm sm:rounded-2xl border border-gray-100 p-10 text-center">
                <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-indigo-50 border border-indigo-100 mb-4">
                    <svg class="w-7 h-7 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h3 class="text-base font-semibold text-gray-900 mb-1">No digests yet</h3>
                <p class="text-sm text-gray-500">Your first briefing will arrive <span class="font-medium text-indigo-600">Monday morning at 7 AM</span>. Check back then!</p>
            </div>
        @else
            <div x-data="{ open: null }" class="space-y-3">
                @foreach ($digests as $digest)
                    <div class="bg-white shadow-sm sm:rounded-2xl border border-gray-100 overflow-hidden">
                        {{-- Accordion header --}}
                        <button
                            @click="open === {{ $digest->id }} ? open = null : open = {{ $digest->id }}"
                            class="w-full flex items-center justify-between p-5 text-left hover:bg-gray-50 transition-colors duration-150"
                        >
                            <div class="flex items-center gap-4 min-w-0">
                                <div class="shrink-0 w-10 h-10 rounded-xl bg-indigo-50 border border-indigo-100 flex items-center justify-center">
                                    <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-gray-900 truncate">{{ $digest->subject_line }}</p>
                                    <p class="text-xs text-gray-500 mt-0.5">
                                        {{ $digest->sent_at ? $digest->sent_at->format('l, F j, Y') : $digest->created_at->format('l, F j, Y') }}
                                    </p>
                                </div>
                            </div>
                            <svg
                                class="w-5 h-5 text-gray-400 shrink-0 ml-4 transition-transform duration-200"
                                :class="{ 'rotate-180': open === {{ $digest->id }} }"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>

                        {{-- Accordion content --}}
                        <div
                            x-show="open === {{ $digest->id }}"
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 -translate-y-1"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100 translate-y-0"
                            x-transition:leave-end="opacity-0 -translate-y-1"
                            class="border-t border-gray-100"
                        >
                            <div class="p-5">
                                @if ($digest->content_json)
                                    @include('partials.digest-web', ['sections' => $digest->content_json])
                                @else
                                    <div class="prose prose-sm max-w-none text-gray-700">
                                        {!! $digest->html_content !!}
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

    </div>
</div>

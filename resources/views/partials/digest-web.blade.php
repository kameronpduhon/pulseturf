@php
    $sections = $sections ?? [];
@endphp

<div class="space-y-6">
    {{-- Performance Snapshot --}}
    @if (!empty($sections['performance_snapshot']))
    <div>
        <h3 class="text-sm font-semibold text-indigo-600 uppercase tracking-wide mb-2">Performance Snapshot</h3>
        <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-4 text-sm text-gray-700 leading-relaxed whitespace-pre-line">{{ $sections['performance_snapshot'] }}</div>
    </div>
    @endif

    {{-- Review Highlights --}}
    @if (!empty($sections['review_highlights']))
    <div>
        <h3 class="text-sm font-semibold text-indigo-600 uppercase tracking-wide mb-2">Review Highlights</h3>
        <div class="border-l-4 border-indigo-500 bg-gray-50 rounded-r-xl pl-4 pr-4 py-3 text-sm text-gray-700 leading-relaxed whitespace-pre-line">{{ $sections['review_highlights'] }}</div>
    </div>
    @endif

    {{-- Competitor Watch --}}
    @if (!empty($sections['competitor_watch']))
    <div>
        <h3 class="text-sm font-semibold text-indigo-600 uppercase tracking-wide mb-2">Competitor Watch</h3>
        <div class="bg-orange-50 border border-orange-200 rounded-xl p-4 text-sm text-gray-700 leading-relaxed whitespace-pre-line">{{ $sections['competitor_watch'] }}</div>
    </div>
    @endif

    {{-- Sentiment Trends --}}
    @if (!empty($sections['sentiment_trends']))
    <div>
        <h3 class="text-sm font-semibold text-indigo-600 uppercase tracking-wide mb-2">Sentiment Trends</h3>
        <div class="text-sm text-gray-700 leading-relaxed whitespace-pre-line">{{ $sections['sentiment_trends'] }}</div>
    </div>
    @endif

    {{-- Action Items --}}
    @if (!empty($sections['action_items']))
    <div>
        <h3 class="text-sm font-semibold text-indigo-600 uppercase tracking-wide mb-2">Action Items</h3>
        <div class="bg-emerald-50 border border-emerald-100 rounded-xl p-4 text-sm text-gray-700 leading-relaxed whitespace-pre-line">{{ $sections['action_items'] }}</div>
    </div>
    @endif

    {{-- Week Ahead --}}
    @if (!empty($sections['week_ahead']))
    <div>
        <h3 class="text-sm font-semibold text-indigo-600 uppercase tracking-wide mb-2">Week Ahead</h3>
        <div class="text-sm text-gray-600 italic leading-relaxed whitespace-pre-line">{{ $sections['week_ahead'] }}</div>
    </div>
    @endif
</div>

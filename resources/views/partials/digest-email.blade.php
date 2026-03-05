@php
    $sections = $sections ?? [];
@endphp

{{-- Performance Snapshot --}}
@if (!empty($sections['performance_snapshot']))
<table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 24px;">
    <tr>
        <td style="padding-bottom: 8px;">
            <h2 style="margin: 0; font-size: 18px; font-weight: 700; color: #4f46e5;">Performance Snapshot</h2>
        </td>
    </tr>
    <tr>
        <td style="background-color: #f5f3ff; border-radius: 8px; padding: 16px; font-size: 14px; color: #374151; line-height: 1.6;">
            {!! nl2br(e($sections['performance_snapshot'])) !!}
        </td>
    </tr>
</table>
@endif

{{-- Review Highlights --}}
@if (!empty($sections['review_highlights']))
<table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 24px;">
    <tr>
        <td style="padding-bottom: 8px;">
            <h2 style="margin: 0; font-size: 18px; font-weight: 700; color: #4f46e5;">Review Highlights</h2>
        </td>
    </tr>
    <tr>
        <td style="border-left: 3px solid #4f46e5; padding: 12px 16px; font-size: 14px; color: #374151; line-height: 1.6; background-color: #fafafa; border-radius: 0 8px 8px 0;">
            {!! nl2br(e($sections['review_highlights'])) !!}
        </td>
    </tr>
</table>
@endif

{{-- Competitor Watch --}}
@if (!empty($sections['competitor_watch']))
<table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 24px;">
    <tr>
        <td style="padding-bottom: 8px;">
            <h2 style="margin: 0; font-size: 18px; font-weight: 700; color: #4f46e5;">Competitor Watch</h2>
        </td>
    </tr>
    <tr>
        <td style="padding: 12px 16px; font-size: 14px; color: #374151; line-height: 1.6; background-color: #fff7ed; border-radius: 8px; border: 1px solid #fed7aa;">
            {!! nl2br(e($sections['competitor_watch'])) !!}
        </td>
    </tr>
</table>
@endif

{{-- Sentiment Trends --}}
@if (!empty($sections['sentiment_trends']))
<table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 24px;">
    <tr>
        <td style="padding-bottom: 8px;">
            <h2 style="margin: 0; font-size: 18px; font-weight: 700; color: #4f46e5;">Sentiment Trends</h2>
        </td>
    </tr>
    <tr>
        <td style="padding: 12px 16px; font-size: 14px; color: #374151; line-height: 1.6;">
            {!! nl2br(e($sections['sentiment_trends'])) !!}
        </td>
    </tr>
</table>
@endif

{{-- Action Items --}}
@if (!empty($sections['action_items']))
<table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 24px;">
    <tr>
        <td style="padding-bottom: 8px;">
            <h2 style="margin: 0; font-size: 18px; font-weight: 700; color: #4f46e5;">Action Items</h2>
        </td>
    </tr>
    <tr>
        <td style="background-color: #ecfdf5; border-radius: 8px; padding: 16px; font-size: 14px; color: #374151; line-height: 1.6;">
            {!! nl2br(e($sections['action_items'])) !!}
        </td>
    </tr>
</table>
@endif

{{-- Week Ahead --}}
@if (!empty($sections['week_ahead']))
<table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom: 24px;">
    <tr>
        <td style="padding-bottom: 8px;">
            <h2 style="margin: 0; font-size: 18px; font-weight: 700; color: #4f46e5;">Week Ahead</h2>
        </td>
    </tr>
    <tr>
        <td style="padding: 12px 16px; font-size: 14px; color: #374151; line-height: 1.6; font-style: italic;">
            {!! nl2br(e($sections['week_ahead'])) !!}
        </td>
    </tr>
</table>
@endif

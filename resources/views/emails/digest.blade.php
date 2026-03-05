<x-mail::message>
# {{ $digest->subject_line }}

@if ($digest->content_json)
@include('partials.digest-email', ['sections' => $digest->content_json])
@else
{!! $digest->html_content !!}
@endif

---

**Was this digest useful?**

<x-mail::button :url="$positiveUrl">
Yes, helpful!
</x-mail::button>

<x-mail::button :url="$negativeUrl" color="secondary">
Not really
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>

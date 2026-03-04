<x-mail::message>
# {{ $digest->subject_line }}

{!! $digest->html_content !!}

---

**Was this digest useful?**

<x-mail::button :url="$positiveUrl">
👍 Yes, helpful!
</x-mail::button>

<x-mail::button :url="$negativeUrl" color="secondary">
👎 Not really
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>

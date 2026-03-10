<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sample Monday Digest | PulseTurf</title>
    <meta name="description" content="Sample PulseTurf Monday Digest preview.">
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-gradient-to-b from-indigo-50 via-violet-50 to-white font-sans text-gray-900 antialiased">
    <main class="mx-auto flex w-full max-w-4xl flex-col gap-8 px-4 py-10 sm:px-6 sm:py-14">
        <header class="space-y-2 text-center">
            <p class="text-3xl font-bold tracking-tight text-indigo-700 sm:text-4xl">PulseTurf</p>
            <p class="text-sm font-medium tracking-wide text-indigo-500 sm:text-base">Know your turf.</p>
        </header>

        <section class="rounded-3xl border border-indigo-100 bg-white p-6 shadow-xl shadow-indigo-100/50 sm:p-8">
            <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
                <div class="space-y-2">
                    <h1 class="text-2xl font-bold tracking-tight text-gray-900 sm:text-3xl">📬 Sample Monday Digest</h1>
                    <p class="text-sm text-gray-600 sm:text-base">
                        {{ $businessName }} &middot; tracking {{ $competitor }} &middot; Week of {{ $weekOf }}
                    </p>
                </div>
                <span class="inline-flex items-center rounded-full bg-indigo-100 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-indigo-700">
                    SAMPLE REPORT
                </span>
            </div>

            @include("partials.digest-web", ["sections" => $sections])
        </section>

        <section class="rounded-3xl border border-indigo-100 bg-indigo-900/95 px-6 py-8 text-center text-white shadow-lg shadow-indigo-200/50 sm:px-10">
            <h2 class="text-2xl font-bold tracking-tight sm:text-3xl">Get this in your inbox every Monday.</h2>
            <p class="mt-3 text-sm text-indigo-100 sm:text-base">Free 14-day trial. No credit card required.</p>
            <a
                href="/"
                class="mt-6 inline-flex items-center justify-center rounded-xl bg-indigo-500 px-6 py-3 text-sm font-semibold text-white transition hover:bg-indigo-400 focus:outline-none focus:ring-2 focus:ring-white/80 focus:ring-offset-2 focus:ring-offset-indigo-900 sm:text-base"
            >
                Start Free Trial &rarr;
            </a>
        </section>

        <footer class="pt-2 text-center text-xs text-gray-500 sm:text-sm">
            &copy; 2026 PulseTurf &middot; pulseturf.com
        </footer>
    </main>
</body>
</html>

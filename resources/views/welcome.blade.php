<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>PulseTurf — Competitive Intelligence for Med Spas</title>
    <meta name="description" content="Know what your competitors are doing before your next patient visit. PulseTurf delivers weekly AI-powered briefings on your med spa's competitive landscape.">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        .font-serif-display {
            font-family: 'DM Serif Display', Georgia, serif;
        }

        /* Hero mesh gradient background */
        .hero-bg {
            background-color: #ffffff;
            background-image:
                radial-gradient(at 20% 20%, rgba(99, 102, 241, 0.08) 0px, transparent 50%),
                radial-gradient(at 80% 10%, rgba(79, 70, 229, 0.06) 0px, transparent 50%),
                radial-gradient(at 60% 80%, rgba(99, 102, 241, 0.05) 0px, transparent 50%);
        }

        /* Subtle dot pattern */
        .dot-pattern {
            background-image: radial-gradient(circle, rgba(79, 70, 229, 0.12) 1px, transparent 1px);
            background-size: 28px 28px;
        }

        /* Nav blur */
        .nav-blur {
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            background-color: rgba(255, 255, 255, 0.88);
        }

        /* Step badge gradient */
        .step-badge {
            background: linear-gradient(135deg, #4F46E5 0%, #6366F1 100%);
        }

        /* Card hover lift */
        .card-lift {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .card-lift:hover {
            transform: translateY(-3px);
            box-shadow: 0 20px 40px -12px rgba(79, 70, 229, 0.15);
        }

        /* Alpine cloak */
        [x-cloak] { display: none !important; }

        /* FAQ accordion via CSS grid trick */
        .faq-content {
            display: grid;
            grid-template-rows: 0fr;
            transition: grid-template-rows 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .faq-content.open {
            grid-template-rows: 1fr;
        }
        .faq-inner {
            overflow: hidden;
        }

        /* Gradient text */
        .gradient-text {
            background: linear-gradient(135deg, #4F46E5 0%, #6366F1 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Hero entrance animations */
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(24px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .fade-up            { animation: fadeUp 0.7s ease both; }
        .fade-up-delay-1    { animation-delay: 0.1s; }
        .fade-up-delay-2    { animation-delay: 0.2s; }
        .fade-up-delay-3    { animation-delay: 0.3s; }
        .fade-up-delay-4    { animation-delay: 0.4s; }

        /* Pro card subtle pulse */
        @keyframes soft-pulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(79, 70, 229, 0.3); }
            50%       { box-shadow: 0 0 0 6px rgba(79, 70, 229, 0); }
        }
        .popular-pulse {
            animation: soft-pulse 3s ease-in-out infinite;
        }

        /* Scroll-triggered reveal */
        .scroll-reveal {
            opacity: 0;
            transform: translateY(20px);
            transition: opacity 0.6s ease, transform 0.6s ease;
        }
        .scroll-reveal.visible {
            opacity: 1;
            transform: translateY(0);
        }
    </style>
</head>
<body class="antialiased font-sans bg-white text-gray-900" x-cloak>

    <!-- ════════════════════════════════════════════════
         STICKY NAV
    ════════════════════════════════════════════════ -->
    <nav class="nav-blur fixed top-0 left-0 right-0 z-50 border-b border-gray-100/80">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">

                <!-- Logo -->
                <a href="/" class="flex items-center gap-2">
                    <div class="w-7 h-7 rounded-md step-badge flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                    <span class="text-xl font-bold text-indigo-600 tracking-tight">PulseTurf</span>
                </a>

                <!-- Nav actions -->
                <div class="flex items-center gap-2 sm:gap-4">
                    @auth
                        <a href="{{ route('home') }}"
                           class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 transition-colors">
                            Dashboard
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                            </svg>
                        </a>
                    @else
                        <a href="{{ route('login') }}"
                           class="text-sm font-medium text-gray-600 hover:text-indigo-600 transition-colors px-3 py-2">
                            Log in
                        </a>
                        <a href="{{ route('register') }}"
                           class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 transition-colors shadow-sm">
                            Start Free Trial
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <!-- ════════════════════════════════════════════════
         HERO
    ════════════════════════════════════════════════ -->
    <section class="hero-bg relative pt-28 pb-24 sm:pt-36 sm:pb-32 overflow-hidden">

        <!-- Dot pattern overlay -->
        <div class="dot-pattern absolute inset-0 opacity-40 pointer-events-none"></div>

        <!-- Ambient glow blobs -->
        <div class="absolute -right-24 top-0 w-96 h-96 rounded-full bg-indigo-50 opacity-70 blur-3xl pointer-events-none"></div>
        <div class="absolute -left-16 bottom-0 w-72 h-72 rounded-full bg-indigo-50 opacity-50 blur-3xl pointer-events-none"></div>

        <div class="relative max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 text-center">

            <!-- Eyebrow -->
            <div class="inline-flex items-center gap-2 px-4 py-1.5 rounded-full bg-indigo-50 border border-indigo-100 text-indigo-700 text-sm font-semibold mb-8 fade-up">
                <span class="w-1.5 h-1.5 rounded-full bg-indigo-500 animate-pulse"></span>
                AI-powered &middot; Weekly briefings &middot; No setup hassle
            </div>

            <!-- Headline -->
            <h1 class="font-serif-display text-4xl sm:text-6xl lg:text-7xl text-gray-900 leading-[1.1] tracking-tight mb-6 fade-up fade-up-delay-1">
                Competitive Intelligence<br>
                <em class="gradient-text not-italic">for Med Spas</em>
            </h1>

            <!-- Subheadline -->
            <p class="text-lg sm:text-xl text-gray-500 max-w-2xl mx-auto mb-10 leading-relaxed fade-up fade-up-delay-2">
                Every Monday morning, receive an AI-crafted briefing on your competitors' Google reviews, ratings, and patient sentiment — so you always know where you stand.
            </p>

            <!-- CTA group -->
            <div class="flex flex-col sm:flex-row items-center justify-center gap-4 fade-up fade-up-delay-3">
                @auth
                    <a href="{{ route('home') }}"
                       class="inline-flex items-center gap-2 px-8 py-4 rounded-xl bg-indigo-600 text-white text-base font-semibold hover:bg-indigo-700 transition-colors shadow-lg shadow-indigo-200 w-full sm:w-auto justify-center">
                        Go to Dashboard
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                        </svg>
                    </a>
                @else
                    <a href="{{ route('register') }}"
                       class="inline-flex items-center gap-2 px-8 py-4 rounded-xl bg-indigo-600 text-white text-base font-semibold hover:bg-indigo-700 active:bg-indigo-800 transition-colors shadow-lg shadow-indigo-200 w-full sm:w-auto justify-center">
                        Start Your Free Trial
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                        </svg>
                    </a>
                    <p class="text-sm text-gray-400 font-medium">14-day free trial &mdash; no credit card required</p>
                @endauth
            </div>

            <!-- Feature strip -->
            <div class="mt-14 flex flex-wrap items-center justify-center gap-x-8 gap-y-3 fade-up fade-up-delay-4">
                @foreach ([
                    'Monitor Google Reviews',
                    'Track Star Ratings',
                    'AI-Powered Analysis',
                    'Weekly Email Digests',
                ] as $feature)
                    <div class="flex items-center gap-2 text-gray-500 text-sm">
                        <svg class="w-4 h-4 text-indigo-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                        </svg>
                        {{ $feature }}
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- ════════════════════════════════════════════════
         HOW IT WORKS
    ════════════════════════════════════════════════ -->
    <section class="py-20 sm:py-28 bg-gray-50 border-t border-gray-100">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="text-center mb-14 scroll-reveal">
                <p class="text-indigo-600 text-sm font-semibold uppercase tracking-widest mb-3">Simple Setup</p>
                <h2 class="font-serif-display text-3xl sm:text-5xl text-gray-900 leading-tight">
                    Up and running in minutes
                </h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8 relative">

                <!-- Connecting line desktop only -->
                <div class="hidden md:block absolute top-10 left-[calc(16.66%+1.5rem)] right-[calc(16.66%+1.5rem)] h-px bg-gradient-to-r from-indigo-200 via-indigo-300 to-indigo-200 z-0"></div>

                <!-- Step 1 -->
                <div class="relative bg-white rounded-2xl p-8 shadow-sm border border-gray-100 card-lift scroll-reveal" style="transition-delay: 0.1s;">
                    <div class="step-badge w-12 h-12 rounded-xl flex items-center justify-center mb-6 text-white text-lg font-bold shadow-md shadow-indigo-200">1</div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">Add your med spa</h3>
                    <p class="text-gray-500 leading-relaxed">
                        Search for your business by name and location. We pull your Google Business profile automatically — no manual entry needed.
                    </p>
                    <div class="mt-6 flex items-center gap-2 text-indigo-600 text-sm font-medium">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" />
                        </svg>
                        Google Business integration
                    </div>
                </div>

                <!-- Step 2 -->
                <div class="relative bg-white rounded-2xl p-8 shadow-sm border border-gray-100 card-lift scroll-reveal" style="transition-delay: 0.2s;">
                    <div class="step-badge w-12 h-12 rounded-xl flex items-center justify-center mb-6 text-white text-lg font-bold shadow-md shadow-indigo-200">2</div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">Pick your competitors</h3>
                    <p class="text-gray-500 leading-relaxed">
                        Select the med spas you want to track. We begin monitoring their reviews, ratings, and patient feedback in real time.
                    </p>
                    <div class="mt-6 flex items-center gap-2 text-indigo-600 text-sm font-medium">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" />
                        </svg>
                        Track up to 3 competitors
                    </div>
                </div>

                <!-- Step 3 -->
                <div class="relative bg-white rounded-2xl p-8 shadow-sm border border-gray-100 card-lift scroll-reveal" style="transition-delay: 0.3s;">
                    <div class="step-badge w-12 h-12 rounded-xl flex items-center justify-center mb-6 text-white text-lg font-bold shadow-md shadow-indigo-200">3</div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-3">Get weekly AI briefings</h3>
                    <p class="text-gray-500 leading-relaxed">
                        Every Monday morning, receive a clear, actionable summary of the competitive landscape — written by AI, delivered to your inbox.
                    </p>
                    <div class="mt-6 flex items-center gap-2 text-indigo-600 text-sm font-medium">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />
                        </svg>
                        Delivered every Monday
                    </div>
                </div>

            </div>
        </div>
    </section>

    <!-- ════════════════════════════════════════════════
         PRICING
    ════════════════════════════════════════════════ -->
    <section class="py-20 sm:py-28 bg-white" x-data="{ annual: false }">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="text-center mb-12 scroll-reveal">
                <p class="text-indigo-600 text-sm font-semibold uppercase tracking-widest mb-3">Pricing</p>
                <h2 class="font-serif-display text-3xl sm:text-5xl text-gray-900 leading-tight mb-4">
                    Simple, transparent pricing
                </h2>
                <p class="text-gray-500 text-lg max-w-xl mx-auto mb-8">
                    Start with a 14-day free trial. No credit card required.
                </p>

                <!-- Billing toggle -->
                <div class="inline-flex items-center bg-gray-100 rounded-full p-1.5">
                    <button
                        @click="annual = false"
                        :class="!annual ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500'"
                        class="px-5 py-2 rounded-full text-sm font-semibold transition-all duration-200">
                        Monthly
                    </button>
                    <button
                        @click="annual = true"
                        :class="annual ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500'"
                        class="px-5 py-2 rounded-full text-sm font-semibold transition-all duration-200 flex items-center gap-2">
                        Annual
                        <span class="text-xs font-bold text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded-full">Save 17%</span>
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 max-w-3xl mx-auto">

                <!-- Starter -->
                <div class="bg-white rounded-2xl p-8 border border-gray-200 card-lift scroll-reveal">
                    <div class="mb-6">
                        <h3 class="text-lg font-bold text-gray-900 mb-1">Starter</h3>
                        <p class="text-gray-500 text-sm">Perfect for solo practitioners</p>
                    </div>
                    <div class="mb-8">
                        <div x-show="!annual" x-cloak class="flex items-end gap-1">
                            <span class="font-serif-display text-5xl text-gray-900">$29</span>
                            <span class="text-gray-400 mb-2">/month</span>
                        </div>
                        <div x-show="annual" x-cloak class="flex items-end gap-1">
                            <span class="font-serif-display text-5xl text-gray-900">$290</span>
                            <span class="text-gray-400 mb-2">/year</span>
                        </div>
                        <p x-show="annual" x-cloak class="text-sm text-indigo-600 font-medium mt-1">That's $24.17/month — save $58</p>
                        <p x-show="!annual" x-cloak class="text-sm text-gray-400 mt-1">&nbsp;</p>
                    </div>
                    <ul class="space-y-3 mb-8">
                        <li class="flex items-center gap-3 text-sm text-gray-600">
                            <svg class="w-4 h-4 text-indigo-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" /></svg>
                            <strong class="text-gray-900">1 competitor</strong> tracked
                        </li>
                        <li class="flex items-center gap-3 text-sm text-gray-600">
                            <svg class="w-4 h-4 text-indigo-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" /></svg>
                            Weekly AI briefings
                        </li>
                        <li class="flex items-center gap-3 text-sm text-gray-600">
                            <svg class="w-4 h-4 text-indigo-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" /></svg>
                            Review monitoring
                        </li>
                        <li class="flex items-center gap-3 text-sm text-gray-600">
                            <svg class="w-4 h-4 text-indigo-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" /></svg>
                            Email delivery
                        </li>
                    </ul>
                    <a href="{{ route('register') }}"
                       class="block w-full text-center px-6 py-3 rounded-xl border-2 border-indigo-600 text-indigo-600 text-sm font-semibold hover:bg-indigo-50 transition-colors">
                        Start Free Trial
                    </a>
                </div>

                <!-- Pro -->
                <div class="bg-indigo-600 rounded-2xl p-8 border border-indigo-500 card-lift popular-pulse scroll-reveal relative overflow-hidden" style="transition-delay: 0.1s;">

                    <!-- Decorative circles -->
                    <div class="absolute top-0 right-0 w-40 h-40 rounded-full bg-white/5 -translate-y-1/2 translate-x-1/2 pointer-events-none"></div>
                    <div class="absolute bottom-0 left-0 w-32 h-32 rounded-full bg-white/5 translate-y-1/2 -translate-x-1/2 pointer-events-none"></div>

                    <!-- Badge -->
                    <div class="absolute top-6 right-6">
                        <span class="inline-flex items-center px-3 py-1 rounded-full bg-white/20 text-white text-xs font-bold tracking-wide uppercase">
                            Most Popular
                        </span>
                    </div>

                    <div class="mb-6 relative">
                        <h3 class="text-lg font-bold text-white mb-1">Pro</h3>
                        <p class="text-indigo-200 text-sm">For growing practices</p>
                    </div>
                    <div class="mb-8 relative">
                        <div x-show="!annual" x-cloak class="flex items-end gap-1">
                            <span class="font-serif-display text-5xl text-white">$79</span>
                            <span class="text-indigo-200 mb-2">/month</span>
                        </div>
                        <div x-show="annual" x-cloak class="flex items-end gap-1">
                            <span class="font-serif-display text-5xl text-white">$790</span>
                            <span class="text-indigo-200 mb-2">/year</span>
                        </div>
                        <p x-show="annual" x-cloak class="text-sm text-indigo-200 mt-1">That's $65.83/month — save $158</p>
                        <p x-show="!annual" x-cloak class="text-sm text-indigo-200 mt-1">&nbsp;</p>
                    </div>
                    <ul class="space-y-3 mb-8 relative">
                        <li class="flex items-center gap-3 text-sm text-indigo-100">
                            <svg class="w-4 h-4 text-indigo-300 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" /></svg>
                            <strong class="text-white">3 competitors</strong> tracked
                        </li>
                        <li class="flex items-center gap-3 text-sm text-indigo-100">
                            <svg class="w-4 h-4 text-indigo-300 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" /></svg>
                            Weekly AI briefings
                        </li>
                        <li class="flex items-center gap-3 text-sm text-indigo-100">
                            <svg class="w-4 h-4 text-indigo-300 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" /></svg>
                            Review monitoring
                        </li>
                        <li class="flex items-center gap-3 text-sm text-indigo-100">
                            <svg class="w-4 h-4 text-indigo-300 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" /></svg>
                            Email delivery
                        </li>
                        <li class="flex items-center gap-3 text-sm text-indigo-100">
                            <svg class="w-4 h-4 text-indigo-300 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" /></svg>
                            <strong class="text-white">Priority support</strong>
                        </li>
                    </ul>
                    <a href="{{ route('register') }}"
                       class="relative block w-full text-center px-6 py-3 rounded-xl bg-white text-indigo-600 text-sm font-bold hover:bg-indigo-50 transition-colors shadow-sm">
                        Start Free Trial
                    </a>
                </div>

            </div>

            <p class="text-center text-sm text-gray-400 mt-8 scroll-reveal">
                Cancel anytime. No contracts, no setup fees.
            </p>
        </div>
    </section>

    <!-- ════════════════════════════════════════════════
         FAQ
    ════════════════════════════════════════════════ -->
    <section class="py-20 sm:py-28 bg-gray-50 border-t border-gray-100"
             x-data="{ open: null }">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">

            <div class="text-center mb-14 scroll-reveal">
                <p class="text-indigo-600 text-sm font-semibold uppercase tracking-widest mb-3">FAQ</p>
                <h2 class="font-serif-display text-3xl sm:text-5xl text-gray-900 leading-tight">
                    Common questions
                </h2>
            </div>

            <div class="space-y-3">

                @php
                $faqs = [
                    [1, 'What data does PulseTurf track?',
                        'PulseTurf monitors Google Business profiles for your med spa and each competitor you add. We track Google review counts, star ratings, new review text, and response activity — giving you a clear picture of how patient sentiment is shifting week over week.'],
                    [2, 'How often do I receive briefings?',
                        "You receive one briefing every Monday morning. Each digest is generated fresh using the past week's review data, giving you an up-to-date snapshot of your competitive landscape to start the week well-informed."],
                    [3, 'Can I cancel anytime?',
                        "Yes, absolutely. There are no contracts, no cancellation fees, and no lock-in periods. You can cancel your subscription at any time from your billing settings. You'll retain access through the end of your billing period."],
                    [4, 'What happens during the free trial?',
                        'Your 14-day free trial gives you full access to every feature — no credit card required to start. You can set up your business, add a competitor, and receive real weekly briefings. If you choose not to subscribe, your account simply expires at the end of the trial with no charges.'],
                    [5, 'How many competitors can I track?',
                        'The Starter plan lets you track 1 competitor, which is ideal for practices focused on their primary rival. The Pro plan expands that to 3 competitors — perfect if you operate in a competitive market with multiple nearby med spas to keep an eye on.'],
                ];
                @endphp

                @foreach ($faqs as [$id, $question, $answer])
                    <div class="bg-white rounded-2xl border border-gray-100 overflow-hidden scroll-reveal" style="transition-delay: {{ ($id - 1) * 0.05 }}s;">
                        <button
                            @click="open = open === {{ $id }} ? null : {{ $id }}"
                            class="w-full flex items-center justify-between gap-4 px-6 py-5 text-left focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-indigo-500">
                            <span class="font-semibold text-gray-900 text-base">{{ $question }}</span>
                            <svg class="w-5 h-5 text-indigo-500 flex-shrink-0 transition-transform duration-200"
                                 :class="{ 'rotate-45': open === {{ $id }} }"
                                 fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                            </svg>
                        </button>
                        <div class="faq-content" :class="{ 'open': open === {{ $id }} }">
                            <div class="faq-inner">
                                <p class="px-6 pb-5 text-gray-500 leading-relaxed text-sm">{{ $answer }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach

            </div>
        </div>
    </section>

    <!-- ════════════════════════════════════════════════
         FINAL CTA
    ════════════════════════════════════════════════ -->
    <section class="py-20 sm:py-28 bg-indigo-600 relative overflow-hidden">
        <div class="absolute top-0 left-0 w-80 h-80 rounded-full bg-white/5 -translate-x-1/2 -translate-y-1/2 pointer-events-none"></div>
        <div class="absolute bottom-0 right-0 w-96 h-96 rounded-full bg-white/5 translate-x-1/3 translate-y-1/3 pointer-events-none"></div>
        <div class="dot-pattern absolute inset-0 opacity-20 pointer-events-none"></div>

        <div class="relative max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 text-center scroll-reveal">
            <h2 class="font-serif-display text-3xl sm:text-5xl text-white leading-tight mb-4">
                Know your competitive landscape.<br>
                <em class="text-indigo-200 not-italic">Every single Monday.</em>
            </h2>
            <p class="text-indigo-200 text-lg mb-10 max-w-xl mx-auto leading-relaxed">
                Join med spa owners who start each week with a clear picture of where they stand — and what their competitors are doing.
            </p>
            @auth
                <a href="{{ route('home') }}"
                   class="inline-flex items-center gap-2 px-8 py-4 rounded-xl bg-white text-indigo-700 text-base font-bold hover:bg-indigo-50 transition-colors shadow-xl">
                    Go to Dashboard
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                    </svg>
                </a>
            @else
                <a href="{{ route('register') }}"
                   class="inline-flex items-center gap-2 px-8 py-4 rounded-xl bg-white text-indigo-700 text-base font-bold hover:bg-indigo-50 transition-colors shadow-xl">
                    Start Your Free Trial
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" />
                    </svg>
                </a>
                <p class="text-indigo-300 text-sm mt-4">14-day free trial &mdash; no credit card required</p>
            @endauth
        </div>
    </section>

    <!-- ════════════════════════════════════════════════
         FOOTER
    ════════════════════════════════════════════════ -->
    <footer class="bg-white border-t border-gray-100 py-10">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4">

                <!-- Brand -->
                <div class="flex items-center gap-2">
                    <div class="w-6 h-6 rounded step-badge flex items-center justify-center">
                        <svg class="w-3 h-3 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                    </div>
                    <span class="text-indigo-600 font-bold">PulseTurf</span>
                    <span class="text-gray-300 select-none">|</span>
                    <span class="text-gray-400 text-sm">&copy; {{ date('Y') }} All rights reserved.</span>
                </div>

                <!-- Links -->
                <div class="flex items-center gap-6 text-sm text-gray-400">
                    <a href="{{ route('login') }}" class="hover:text-indigo-600 transition-colors font-medium">Log in</a>
                    @guest
                        <a href="{{ route('register') }}" class="hover:text-indigo-600 transition-colors font-medium">Sign up</a>
                    @endguest
                </div>

            </div>
        </div>
    </footer>

    <!-- Scroll reveal -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var observer = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('visible');
                    }
                });
            }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });

            document.querySelectorAll('.scroll-reveal').forEach(function (el) {
                observer.observe(el);
            });
        });
    </script>

</body>
</html>

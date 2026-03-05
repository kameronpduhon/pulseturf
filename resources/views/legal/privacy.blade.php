<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Privacy Policy - PulseTurf</title>
    <meta name="description" content="PulseTurf privacy policy. Learn how we collect, use, and protect your data.">

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/favicon.png">
    <link rel="apple-touch-icon" href="/favicon.png">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        .font-serif-display { font-family: 'DM Serif Display', Georgia, serif; }
        .nav-blur { backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); background-color: rgba(255, 255, 255, 0.88); }
        .step-badge { background: linear-gradient(135deg, #4F46E5 0%, #6366F1 100%); }
    </style>
</head>
<body class="antialiased font-sans bg-white text-gray-900">

    <x-marketing-nav />

    <main class="pt-24 pb-20">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">

            <h1 class="font-serif-display text-3xl sm:text-4xl text-gray-900 mb-2">Privacy Policy</h1>
            <p class="text-sm text-gray-400 mb-12">Last updated: March 2026</p>

            <div class="space-y-10">

                <!-- Information We Collect -->
                <section>
                    <h2 class="text-xl font-semibold text-gray-900 mb-3">Information We Collect</h2>
                    <p class="text-gray-600 leading-relaxed mb-3">When you use PulseTurf, we collect the following types of information:</p>
                    <ul class="list-disc pl-6 text-gray-600 space-y-1.5 leading-relaxed">
                        <li><strong class="text-gray-900">Account information</strong> — your name, email address, and timezone, provided during registration.</li>
                        <li><strong class="text-gray-900">Business profile data</strong> — publicly available Google Business information (name, address, ratings, review counts) for your med spa and the competitors you choose to track.</li>
                        <li><strong class="text-gray-900">Usage data</strong> — how you interact with PulseTurf, including login activity and feature usage.</li>
                        <li><strong class="text-gray-900">Payment information</strong> — processed securely by Stripe. We store your Stripe customer ID but never your credit card number or bank details.</li>
                    </ul>
                </section>

                <!-- How We Use Your Information -->
                <section>
                    <h2 class="text-xl font-semibold text-gray-900 mb-3">How We Use Your Information</h2>
                    <p class="text-gray-600 leading-relaxed mb-3">We use the information we collect to:</p>
                    <ul class="list-disc pl-6 text-gray-600 space-y-1.5 leading-relaxed">
                        <li>Provide the PulseTurf service, including scraping public Google reviews and generating your weekly AI-powered digest.</li>
                        <li>Process billing and subscription management through Stripe.</li>
                        <li>Send transactional communications such as weekly digests, trial reminders, and billing notifications.</li>
                        <li>Improve the service based on usage patterns and feedback.</li>
                    </ul>
                </section>

                <!-- Google Business Data -->
                <section>
                    <h2 class="text-xl font-semibold text-gray-900 mb-3">Google Business Data</h2>
                    <p class="text-gray-600 leading-relaxed">
                        PulseTurf accesses only publicly available Google Business information — the same reviews, ratings, and profile details that anyone can see on Google Maps. We do not access any private or non-public business data. This public data is collected via third-party APIs that access publicly visible Google information.
                    </p>
                </section>

                <!-- AI Processing -->
                <section>
                    <h2 class="text-xl font-semibold text-gray-900 mb-3">AI Processing</h2>
                    <p class="text-gray-600 leading-relaxed">
                        We use OpenAI to generate your weekly digest briefings. Business profile data and public review information are sent to OpenAI's API for analysis and content generation. OpenAI's data usage policies apply to this processing. We do not send your personal account information (email, password, or payment details) to OpenAI.
                    </p>
                </section>

                <!-- Payment Processing -->
                <section>
                    <h2 class="text-xl font-semibold text-gray-900 mb-3">Payment Processing</h2>
                    <p class="text-gray-600 leading-relaxed">
                        All payment processing is handled by Stripe. We never store, process, or have access to your full credit card number. We retain only your Stripe customer ID and basic subscription status to manage your account. For details on how Stripe handles your payment information, please refer to <a href="https://stripe.com/privacy" target="_blank" rel="noopener noreferrer" class="text-indigo-600 hover:underline">Stripe's Privacy Policy</a>.
                    </p>
                </section>

                <!-- Email Communications -->
                <section>
                    <h2 class="text-xl font-semibold text-gray-900 mb-3">Email Communications</h2>
                    <p class="text-gray-600 leading-relaxed mb-3">We send the following types of email communications:</p>
                    <ul class="list-disc pl-6 text-gray-600 space-y-1.5 leading-relaxed">
                        <li><strong class="text-gray-900">Weekly digests</strong> — your AI-generated competitive intelligence briefing, delivered every Monday morning.</li>
                        <li><strong class="text-gray-900">Account notifications</strong> — trial reminders, billing alerts, and onboarding messages.</li>
                    </ul>
                    <p class="text-gray-600 leading-relaxed mt-3">
                        Emails are delivered via Resend. You can stop receiving weekly digests by canceling your subscription.
                    </p>
                </section>

                <!-- Cookies and Tracking -->
                <section>
                    <h2 class="text-xl font-semibold text-gray-900 mb-3">Cookies and Tracking</h2>
                    <p class="text-gray-600 leading-relaxed">
                        PulseTurf uses only session cookies that are essential for authentication and security. We do not use third-party tracking cookies, advertising pixels, or analytics services at this time.
                    </p>
                </section>

                <!-- Data Retention -->
                <section>
                    <h2 class="text-xl font-semibold text-gray-900 mb-3">Data Retention</h2>
                    <p class="text-gray-600 leading-relaxed">
                        Your account data is retained for as long as your account is active. Review data is retained for generating historical digests and trend analysis. If you cancel your subscription, your data is retained through the end of your billing period. You may request full account deletion at any time by contacting us.
                    </p>
                </section>

                <!-- Your Rights -->
                <section>
                    <h2 class="text-xl font-semibold text-gray-900 mb-3">Your Rights</h2>
                    <p class="text-gray-600 leading-relaxed mb-3">You have the right to:</p>
                    <ul class="list-disc pl-6 text-gray-600 space-y-1.5 leading-relaxed">
                        <li>Access and update your personal information through your account settings.</li>
                        <li>Request a copy of the data we hold about you.</li>
                        <li>Request deletion of your account and associated data.</li>
                    </ul>
                    <p class="text-gray-600 leading-relaxed mt-3">
                        To exercise any of these rights, please contact us at <a href="mailto:support@pulseturf.com" class="text-indigo-600 hover:underline">support@pulseturf.com</a>.
                    </p>
                </section>

                <!-- Contact Us -->
                <section>
                    <h2 class="text-xl font-semibold text-gray-900 mb-3">Contact Us</h2>
                    <p class="text-gray-600 leading-relaxed">
                        If you have any questions about this Privacy Policy or our data practices, please contact us at <a href="mailto:support@pulseturf.com" class="text-indigo-600 hover:underline">support@pulseturf.com</a>.
                    </p>
                </section>

            </div>
        </div>
    </main>

    <x-marketing-footer />

</body>
</html>

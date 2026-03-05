<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Terms of Service - PulseTurf</title>
    <meta name="description" content="PulseTurf terms of service. Read about the terms and conditions for using PulseTurf.">

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

            <h1 class="font-serif-display text-3xl sm:text-4xl text-gray-900 mb-2">Terms of Service</h1>
            <p class="text-sm text-gray-400 mb-12">Last updated: March 2026</p>

            <div class="space-y-10">

                <!-- Acceptance of Terms -->
                <section>
                    <h2 class="text-xl font-semibold text-gray-900 mb-3">Acceptance of Terms</h2>
                    <p class="text-gray-600 leading-relaxed">
                        By creating an account or using PulseTurf, you agree to be bound by these Terms of Service. If you do not agree to these terms, please do not use the service.
                    </p>
                </section>

                <!-- Description of Service -->
                <section>
                    <h2 class="text-xl font-semibold text-gray-900 mb-3">Description of Service</h2>
                    <p class="text-gray-600 leading-relaxed">
                        PulseTurf is a competitive intelligence service for med spas. We provide weekly AI-generated briefings that analyze publicly available Google Business review data for your med spa and your competitors. These briefings are delivered to your email every Monday morning.
                    </p>
                </section>

                <!-- Accounts -->
                <section>
                    <h2 class="text-xl font-semibold text-gray-900 mb-3">Accounts</h2>
                    <p class="text-gray-600 leading-relaxed mb-3">When creating an account, you agree to:</p>
                    <ul class="list-disc pl-6 text-gray-600 space-y-1.5 leading-relaxed">
                        <li>Provide accurate and complete registration information.</li>
                        <li>Maintain the security of your account credentials.</li>
                        <li>Notify us promptly of any unauthorized access to your account.</li>
                    </ul>
                    <p class="text-gray-600 leading-relaxed mt-3">
                        Each account is associated with one med spa business. You are responsible for all activity that occurs under your account.
                    </p>
                </section>

                <!-- Free Trial -->
                <section>
                    <h2 class="text-xl font-semibold text-gray-900 mb-3">Free Trial</h2>
                    <p class="text-gray-600 leading-relaxed">
                        New accounts receive a 14-day free trial with full access to all features. No credit card is required to start the trial. If you do not subscribe before your trial ends, your account will be deactivated and you will no longer receive weekly briefings. No charges are incurred during or after an unconverted trial.
                    </p>
                </section>

                <!-- Billing and Payments -->
                <section>
                    <h2 class="text-xl font-semibold text-gray-900 mb-3">Billing and Payments</h2>
                    <p class="text-gray-600 leading-relaxed mb-3">Subscription details:</p>
                    <ul class="list-disc pl-6 text-gray-600 space-y-1.5 leading-relaxed">
                        <li>Pricing is as listed on our pricing page, with monthly and annual billing options.</li>
                        <li>All payments are processed securely by Stripe.</li>
                        <li>Subscriptions renew automatically at the end of each billing period.</li>
                        <li>You can cancel your subscription at any time from your billing settings. Access continues through the end of your current billing period.</li>
                        <li>Refunds are not provided for partial billing periods.</li>
                    </ul>
                </section>

                <!-- Acceptable Use -->
                <section>
                    <h2 class="text-xl font-semibold text-gray-900 mb-3">Acceptable Use</h2>
                    <p class="text-gray-600 leading-relaxed mb-3">You agree not to:</p>
                    <ul class="list-disc pl-6 text-gray-600 space-y-1.5 leading-relaxed">
                        <li>Use PulseTurf to harass, defame, or harm any business or individual.</li>
                        <li>Redistribute, resell, or share scraped data or digest content with third parties for commercial purposes.</li>
                        <li>Attempt to reverse engineer, decompile, or extract the underlying algorithms or code of the service.</li>
                        <li>Create multiple accounts to circumvent plan limitations.</li>
                        <li>Use automated tools to access the service outside of normal usage patterns.</li>
                    </ul>
                </section>

                <!-- Intellectual Property -->
                <section>
                    <h2 class="text-xl font-semibold text-gray-900 mb-3">Intellectual Property</h2>
                    <p class="text-gray-600 leading-relaxed">
                        AI-generated digest content is created for your personal business use. The PulseTurf name, logo, design, and underlying technology are proprietary. Google review data analyzed by PulseTurf remains publicly available information and is not owned by PulseTurf.
                    </p>
                </section>

                <!-- Limitation of Liability -->
                <section>
                    <h2 class="text-xl font-semibold text-gray-900 mb-3">Limitation of Liability</h2>
                    <p class="text-gray-600 leading-relaxed">
                        PulseTurf provides analysis of publicly available data for informational purposes only. We do not guarantee the accuracy, completeness, or timeliness of AI-generated insights. PulseTurf is not liable for business decisions made based on digest content. The service is provided "as is" without warranties of any kind, express or implied.
                    </p>
                </section>

                <!-- Changes to Terms -->
                <section>
                    <h2 class="text-xl font-semibold text-gray-900 mb-3">Changes to Terms</h2>
                    <p class="text-gray-600 leading-relaxed">
                        We may update these Terms of Service from time to time. We will notify you of significant changes via email or through the service. Continued use of PulseTurf after changes are posted constitutes acceptance of the updated terms.
                    </p>
                </section>

                <!-- Contact -->
                <section>
                    <h2 class="text-xl font-semibold text-gray-900 mb-3">Contact</h2>
                    <p class="text-gray-600 leading-relaxed">
                        If you have any questions about these Terms of Service, please contact us at <a href="mailto:support@pulseturf.com" class="text-indigo-600 hover:underline">support@pulseturf.com</a>.
                    </p>
                </section>

            </div>
        </div>
    </main>

    <x-marketing-footer />

</body>
</html>

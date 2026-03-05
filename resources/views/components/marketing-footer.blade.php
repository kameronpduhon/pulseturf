<footer class="bg-white border-t border-gray-100 py-12 sm:py-16">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-10 sm:gap-8">

            <!-- Brand -->
            <div class="lg:col-span-2">
                <a href="/" class="flex items-center gap-2 mb-3">
                    <img src="/favicon.png" alt="PulseTurf" class="w-6 h-6 rounded">
                    <span class="text-indigo-600 font-bold text-lg">PulseTurf</span>
                </a>
                <p class="text-gray-400 text-sm leading-relaxed max-w-xs">
                    AI-powered competitive intelligence for med spas. Know where you stand, every Monday.
                </p>
            </div>

            <!-- Product -->
            <div>
                <h4 class="text-xs font-semibold uppercase tracking-widest text-gray-900 mb-4">Product</h4>
                <ul class="space-y-2.5 text-sm">
                    <li><a href="/#how-it-works" class="text-gray-400 hover:text-indigo-600 transition-colors">How It Works</a></li>
                    <li><a href="/#pricing" class="text-gray-400 hover:text-indigo-600 transition-colors">Pricing</a></li>
                    <li><a href="/#faq" class="text-gray-400 hover:text-indigo-600 transition-colors">FAQ</a></li>
                </ul>
            </div>

            <!-- Company -->
            <div>
                <h4 class="text-xs font-semibold uppercase tracking-widest text-gray-900 mb-4">Company</h4>
                <ul class="space-y-2.5 text-sm">
                    <li><a href="{{ route('privacy') }}" class="text-gray-400 hover:text-indigo-600 transition-colors">Privacy Policy</a></li>
                    <li><a href="{{ route('terms') }}" class="text-gray-400 hover:text-indigo-600 transition-colors">Terms of Service</a></li>
                    <li><a href="mailto:support@pulseturf.com" class="text-gray-400 hover:text-indigo-600 transition-colors">Contact Us</a></li>
                    <li><a href="{{ route('login') }}" class="text-gray-400 hover:text-indigo-600 transition-colors">Log in</a></li>
                    @guest
                        <li><a href="{{ route('register') }}" class="text-gray-400 hover:text-indigo-600 transition-colors">Sign up</a></li>
                    @endguest
                </ul>
            </div>

        </div>

        <!-- Bottom bar -->
        <div class="mt-10 pt-6 border-t border-gray-100">
            <p class="text-gray-300 text-sm text-center sm:text-left">&copy; {{ date('Y') }} PulseTurf. All rights reserved.</p>
        </div>
    </div>
</footer>

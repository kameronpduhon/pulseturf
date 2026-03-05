<nav class="nav-blur fixed top-0 left-0 right-0 z-50 border-b border-gray-100/80">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">

            <!-- Logo -->
            <a href="/" class="flex items-center gap-2">
                <img src="/favicon.png" alt="PulseTurf" class="w-7 h-7 rounded-md flex-shrink-0">
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

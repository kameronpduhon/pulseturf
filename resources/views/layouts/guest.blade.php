<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'PulseTurf') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <style>
            .guest-bg {
                background-color: #f9fafb;
                background-image:
                    radial-gradient(at 20% 10%, rgba(99, 102, 241, 0.06) 0px, transparent 50%),
                    radial-gradient(at 80% 90%, rgba(79, 70, 229, 0.05) 0px, transparent 50%);
            }
        </style>
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="guest-bg min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0">

            <!-- Logo link -->
            <div>
                <a href="/" wire:navigate class="block">
                    <x-application-logo />
                </a>
            </div>

            <!-- Card -->
            <div class="w-full sm:max-w-md mt-6 px-6 py-8 bg-white shadow-sm overflow-hidden sm:rounded-2xl border border-gray-100">
                {{ $slot }}
            </div>

            <!-- Back to home -->
            <p class="mt-6 text-sm text-gray-400">
                <a href="/" wire:navigate class="hover:text-indigo-600 transition-colors">
                    &larr; Back to PulseTurf.com
                </a>
            </p>
        </div>
    </body>
</html>

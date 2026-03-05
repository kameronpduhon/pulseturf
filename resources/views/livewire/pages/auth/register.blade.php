<?php

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';
    public string $timezone = 'America/Chicago';

    /**
     * Handle an incoming registration request.
     */
    public function register(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
            'timezone' => ['required', 'string', 'timezone'],
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['trial_ends_at'] = now()->addDays(14);

        event(new Registered($user = User::create($validated)));

        Auth::login($user);

        $this->redirect(route('setup', absolute: false), navigate: true);
    }
}; ?>

<div>
    <!-- Trial messaging -->
    <div class="mb-6 rounded-xl bg-indigo-50 border border-indigo-100 px-4 py-3.5 text-center">
        <p class="text-sm font-semibold text-indigo-700">14-day free trial &mdash; no credit card required</p>
        <p class="text-xs text-indigo-500 mt-0.5">Full access to every feature. Cancel any time.</p>
    </div>

    <form wire:submit="register">
        <!-- Name -->
        <div>
            <x-input-label for="name" :value="__('Name')" />
            <x-text-input wire:model="name" id="name" class="block mt-1 w-full" type="text" name="name" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input wire:model="email" id="email" class="block mt-1 w-full" type="email" name="email" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input wire:model="password" id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />

            <x-text-input wire:model="password_confirmation" id="password_confirmation" class="block mt-1 w-full"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <!-- Timezone (hidden, populated by JS via Livewire) -->
        <input type="hidden" wire:model="timezone" id="timezone">
        <script>
            document.addEventListener('livewire:initialized', () => {
                const tz = Intl.DateTimeFormat().resolvedOptions().timeZone || 'America/Chicago';
                @this.set('timezone', tz);
            });
        </script>

        <div class="flex items-center justify-between mt-6">
            <a class="text-sm text-gray-500 hover:text-indigo-600 transition-colors rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('login') }}" wire:navigate>
                {{ __('Already registered?') }}
            </a>

            <x-primary-button>
                {{ __('Create Account') }}
            </x-primary-button>
        </div>
    </form>

    <p class="text-xs text-gray-400 text-center mt-4">
        By creating an account, you agree to our
        <a href="{{ route('terms') }}" class="text-indigo-600 hover:underline">Terms of Service</a>
        and
        <a href="{{ route('privacy') }}" class="text-indigo-600 hover:underline">Privacy Policy</a>.
    </p>
</div>

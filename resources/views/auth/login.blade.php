<x-guest-layout>
    <h1 class="text-2xl font-medium text-on-surface">{{ __('Welcome back') }}</h1>
    <p class="mt-1 text-sm text-on-surface-variant">{{ __('Log in to continue to your workspace.') }}</p>

    <x-auth-session-status class="mt-6" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-5">
        @csrf

        <x-md3-text-field name="email" type="email" :label="__('Email')" autofocus autocomplete="username" required />

        <x-md3-text-field name="password" type="password" :label="__('Password')" autocomplete="current-password" required />

        <div class="flex items-center justify-between">
            <label for="remember_me" class="inline-flex cursor-pointer items-center gap-2">
                <input id="remember_me" type="checkbox" name="remember"
                    class="h-[18px] w-[18px] rounded-sm border-2 border-outline text-primary accent-primary focus:ring-2 focus:ring-primary/40 focus:ring-offset-0">
                <span class="text-sm text-on-surface-variant">{{ __('Remember me') }}</span>
            </label>

            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="text-sm font-medium text-primary hover:underline">
                    {{ __('Forgot password?') }}
                </a>
            @endif
        </div>

        <x-primary-button class="w-full">
            {{ __('Log in') }}
        </x-primary-button>

        @if (Route::has('register'))
            <p class="text-center text-sm text-on-surface-variant">
                {{ __("Don't have an account?") }}
                <a href="{{ route('register') }}" class="font-medium text-primary hover:underline">{{ __('Sign up') }}</a>
            </p>
        @endif
    </form>
</x-guest-layout>

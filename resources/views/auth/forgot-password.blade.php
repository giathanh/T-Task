<x-guest-layout>
    <h1 class="text-2xl font-medium text-on-surface">{{ __('Reset your password') }}</h1>
    <p class="mt-1 text-sm text-on-surface-variant">
        {{ __('Enter your email and we will send you a link to reset your password.') }}
    </p>

    <x-auth-session-status class="mt-6" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-5">
        @csrf

        <x-md3-text-field name="email" type="email" :label="__('Email')" autofocus required />

        <x-primary-button class="w-full">
            {{ __('Email password reset link') }}
        </x-primary-button>

        <p class="text-center text-sm text-on-surface-variant">
            <a href="{{ route('login') }}" class="font-medium text-primary hover:underline">{{ __('Back to log in') }}</a>
        </p>
    </form>
</x-guest-layout>

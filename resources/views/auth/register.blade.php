<x-guest-layout>
    <h1 class="text-2xl font-medium text-on-surface">{{ __('Create your account') }}</h1>
    <p class="mt-1 text-sm text-on-surface-variant">{{ __('Get started managing your projects and tasks.') }}</p>

    <form method="POST" action="{{ route('register') }}" class="mt-6 space-y-5">
        @csrf

        <x-md3-text-field name="name" type="text" :label="__('Name')" autofocus autocomplete="name" required />

        <x-md3-text-field name="email" type="email" :label="__('Email')" autocomplete="username" required />

        <x-md3-text-field name="password" type="password" :label="__('Password')" autocomplete="new-password" required />

        <x-md3-text-field name="password_confirmation" type="password" :label="__('Confirm password')" autocomplete="new-password" required />

        <x-primary-button class="w-full">
            {{ __('Create account') }}
        </x-primary-button>

        <p class="text-center text-sm text-on-surface-variant">
            {{ __('Already have an account?') }}
            <a href="{{ route('login') }}" class="font-medium text-primary hover:underline">{{ __('Log in') }}</a>
        </p>
    </form>
</x-guest-layout>

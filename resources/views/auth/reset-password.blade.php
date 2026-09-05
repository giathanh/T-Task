<x-guest-layout>
    <h1 class="text-2xl font-medium text-on-surface">{{ __('Set a new password') }}</h1>
    <p class="mt-1 text-sm text-on-surface-variant">{{ __('Choose a new password for your account.') }}</p>

    <form method="POST" action="{{ route('password.store') }}" class="mt-6 space-y-5">
        @csrf

        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <x-md3-text-field name="email" type="email" :label="__('Email')" :value="$request->email" autofocus autocomplete="username" required />

        <x-md3-text-field name="password" type="password" :label="__('Password')" autocomplete="new-password" required />

        <x-md3-text-field name="password_confirmation" type="password" :label="__('Confirm password')" autocomplete="new-password" required />

        <x-primary-button class="w-full">
            {{ __('Reset password') }}
        </x-primary-button>
    </form>
</x-guest-layout>

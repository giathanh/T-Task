<x-guest-layout>
    <h1 class="text-2xl font-medium text-on-surface">{{ __('Confirm your password') }}</h1>
    <p class="mt-1 text-sm text-on-surface-variant">
        {{ __('This is a secure area. Please confirm your password before continuing.') }}
    </p>

    <form method="POST" action="{{ route('password.confirm') }}" class="mt-6 space-y-5">
        @csrf

        <x-md3-text-field name="password" type="password" :label="__('Password')" autofocus autocomplete="current-password" required />

        <x-primary-button class="w-full">
            {{ __('Confirm') }}
        </x-primary-button>
    </form>
</x-guest-layout>

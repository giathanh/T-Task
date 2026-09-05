<x-guest-layout>
    <h1 class="text-2xl font-medium text-on-surface">{{ __('Verify your email') }}</h1>
    <p class="mt-1 text-sm text-on-surface-variant">
        {{ __("Thanks for signing up! Please verify your email by clicking the link we just sent you. Didn't get it? We can send another one.") }}
    </p>

    @if (session('status') == 'verification-link-sent')
        <div class="mt-6 rounded-xl bg-tertiary-container px-4 py-3 text-sm font-medium text-on-tertiary-container">
            {{ __('A new verification link has been sent to the email address you provided during registration.') }}
        </div>
    @endif

    <div class="mt-6 flex items-center justify-between gap-4">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-primary-button>
                {{ __('Resend verification email') }}
            </x-primary-button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-sm font-medium text-on-surface-variant hover:text-on-surface hover:underline">
                {{ __('Log out') }}
            </button>
        </form>
    </div>
</x-guest-layout>

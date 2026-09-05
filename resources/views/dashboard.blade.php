<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-medium leading-tight text-on-surface">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="overflow-hidden rounded-3xl bg-surface-container-lowest shadow-elevation-1">
                <div class="p-6 text-on-surface">
                    {{ __("You're logged in!") }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

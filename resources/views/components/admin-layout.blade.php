@props(['title'])
<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4">
            <p class="text-sm text-on-surface-variant">Quản trị hệ thống</p>
            <h2 class="text-xl font-medium text-on-surface">{{ $title }}</h2>
            <nav aria-label="Quản trị" class="flex flex-wrap gap-2">
                <x-nav-link :href="route('admin.projects.index')" :active="request()->routeIs('admin.projects.*')">Dự án</x-nav-link>
                <x-nav-link :href="route('admin.users.index')" :active="request()->routeIs('admin.users.*')">Người dùng</x-nav-link>
            </nav>
        </div>
    </x-slot>
    <div class="mx-auto flex max-w-7xl flex-col gap-6 px-4 py-8 sm:px-6 lg:px-8">
        @if (session('status'))
            <p role="status" class="rounded-xl bg-secondary-container px-4 py-3 text-on-secondary-container">{{ session('status') }}</p>
        @endif
        {{ $slot }}
    </div>
</x-app-layout>

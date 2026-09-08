<nav x-data="{ open: false }" class="border-b border-outline-variant bg-surface-container-lowest">
    <!-- Primary Navigation Menu -->
    <div class="w-full px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 justify-between">
            <div class="flex items-center gap-8">
                <!-- Logo -->
                <a href="{{ route('dashboard') }}" class="flex shrink-0 items-center gap-2">
                    <x-application-logo class="block h-8 w-auto fill-current text-primary" />
                    <span class="hidden text-base font-medium text-on-surface sm:block">{{ config('app.name', 'T-Task') }}</span>
                </a>

                <!-- Navigation Links -->
                <div class="hidden sm:flex sm:items-center sm:gap-1">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Trang chủ') }}
                    </x-nav-link>
                    <x-dropdown align="left" width="w-72 max-w-[calc(100vw-2rem)]" contentClasses="py-2 bg-surface-container-lowest max-h-80 overflow-y-auto">
                        <x-slot name="trigger">
                            <button type="button" :aria-expanded="open.toString()" aria-controls="desktop-projects" @keydown.escape="open = false" @class([
                                'flex items-center gap-2 rounded-full px-4 py-2 text-sm font-medium transition focus-visible:outline-2 focus-visible:outline-primary',
                                'bg-secondary-container text-on-secondary-container' => request()->route('project') !== null,
                                'text-on-surface-variant hover:bg-on-surface/8' => request()->route('project') === null,
                            ])>
                                Dự án
                                <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="m5 7 5 5 5-5" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" /></svg>
                            </button>
                        </x-slot>
                        <x-slot name="content">
                            <div id="desktop-projects" @keydown.escape.stop="open = false; $root.querySelector('button').focus()">
                                <p class="px-4 py-2 text-xs font-medium text-on-surface-variant">Dự án của bạn</p>
                                @forelse ($navigationProjects as $navigationProject)
                                    <x-dropdown-link :href="route('projects.show', $navigationProject)" :aria-current="request()->route('project')?->id === $navigationProject->id ? 'page' : null" @class(['break-words', 'bg-secondary-container font-medium' => request()->route('project')?->id === $navigationProject->id])>
                                        {{ $navigationProject->name }}
                                    </x-dropdown-link>
                                @empty
                                    <p class="px-4 py-3 text-sm text-on-surface-variant">Bạn chưa tham gia dự án nào.</p>
                                @endforelse
                            </div>
                        </x-slot>
                    </x-dropdown>
                    @can('access-admin')
                        <x-dropdown align="left" width="48">
                            <x-slot name="trigger">
                                <button type="button" :aria-expanded="open.toString()" aria-controls="desktop-admin" @class(['flex items-center gap-2 rounded-full px-4 py-2 text-sm font-medium transition focus-visible:outline-2 focus-visible:outline-primary', 'bg-secondary-container text-on-secondary-container' => request()->routeIs('admin.*'), 'text-on-surface-variant hover:bg-on-surface/8' => ! request()->routeIs('admin.*')])>
                                    Quản trị
                                    <svg class="size-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" aria-hidden="true"><path d="m5 7 5 5 5-5" /></svg>
                                </button>
                            </x-slot>
                            <x-slot name="content">
                                <div id="desktop-admin" @keydown.escape.stop="open = false; $root.querySelector('button').focus()">
                                    <x-dropdown-link :href="route('admin.projects.index')">Quản lý dự án</x-dropdown-link>
                                    <x-dropdown-link :href="route('admin.users.index')">Quản lý người dùng</x-dropdown-link>
                                </div>
                            </x-slot>
                        </x-dropdown>
                    @endcan
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="flex items-center gap-2 rounded-full py-1 pe-3 ps-1 transition hover:bg-on-surface/8 focus:outline-none focus-visible:outline focus-visible:outline-2 focus-visible:outline-primary">
                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-primary-container text-sm font-medium text-on-primary-container">
                                {{ Str::of(Auth::user()->name)->substr(0, 1)->upper() }}
                            </span>
                            <span class="text-sm font-medium text-on-surface">{{ Auth::user()->name }}</span>
                            <svg class="h-4 w-4 fill-current text-on-surface-variant" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Log out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center rounded-full p-2 text-on-surface-variant transition hover:bg-on-surface/8 focus:outline-none">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden border-t border-outline-variant sm:hidden">
        <div class="space-y-1 px-3 pb-3 pt-3">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Trang chủ') }}
            </x-responsive-nav-link>
        </div>

        <!-- Responsive Settings Options -->
        <div class="border-t border-outline-variant px-3 py-3">
            <p class="px-4 py-2 text-xs font-medium text-on-surface-variant">Dự án của bạn</p>
            <div class="max-h-64 space-y-1 overflow-y-auto">
                @forelse ($navigationProjects as $navigationProject)
                    <x-responsive-nav-link :href="route('projects.show', $navigationProject)" :active="request()->route('project')?->id === $navigationProject->id" :aria-current="request()->route('project')?->id === $navigationProject->id ? 'page' : null" class="break-words">
                        {{ $navigationProject->name }}
                    </x-responsive-nav-link>
                @empty
                    <p class="px-4 py-2 text-sm text-on-surface-variant">Bạn chưa tham gia dự án nào.</p>
                @endforelse
            </div>
        </div>
        @can('access-admin')
            <div class="border-t border-outline-variant px-3 py-3">
                <p class="px-4 py-2 text-xs font-medium text-on-surface-variant">Quản trị</p>
                <x-responsive-nav-link :href="route('admin.projects.index')" :active="request()->routeIs('admin.projects.*')">Quản lý dự án</x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.users.index')" :active="request()->routeIs('admin.users.*')">Quản lý người dùng</x-responsive-nav-link>
            </div>
        @endcan
        <div class="border-t border-outline-variant pb-3 pt-4">
            <div class="flex items-center gap-3 px-4">
                <span class="flex h-10 w-10 items-center justify-center rounded-full bg-primary-container text-base font-medium text-on-primary-container">
                    {{ Str::of(Auth::user()->name)->substr(0, 1)->upper() }}
                </span>
                <div>
                    <div class="text-base font-medium text-on-surface">{{ Auth::user()->name }}</div>
                    <div class="text-sm text-on-surface-variant">{{ Auth::user()->email }}</div>
                </div>
            </div>

            <div class="mt-3 space-y-1 px-3">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>

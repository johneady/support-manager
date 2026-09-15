<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    @include('partials.head')
</head>
<body class="min-h-screen bg-white dark:bg-zinc-800">
    <flux:header
        container
        class="border-b border-blue-100/50 bg-white/90 shadow-sm dark:border-zinc-700/50 dark:bg-zinc-800/90"
    >
        <flux:sidebar.toggle
            class="mr-2 text-zinc-600 hover:text-zinc-900 lg:hidden dark:text-zinc-300 dark:hover:text-white"
            icon="bars-2"
            inset="left"
        />

        <x-app-logo href="{{ route('dashboard') }}" wire:navigate />

        <flux:navbar class="-mb-px max-lg:hidden">
            <flux:navbar.item
                icon="layout-grid"
                :href="route('dashboard')"
                :current="request()->routeIs('dashboard')"
                wire:navigate
                class="font-medium text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-700 dark:hover:text-white"
            >
                {{ __('Dashboard') }}
            </flux:navbar.item>
        </flux:navbar>

        <flux:spacer />

        <flux:navbar class="me-1.5 space-x-0.5 py-0! rtl:space-x-reverse">
            <flux:tooltip :content="__('Search')" position="bottom">
                <flux:navbar.item
                    class="[&>div>svg]:size-5 !h-10 font-medium text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-700 dark:hover:text-white"
                    icon="magnifying-glass"
                    href="#"
                    :label="__('Search')"
                />
            </flux:tooltip>
            <flux:tooltip :content="__('Repository')" position="bottom">
                <flux:navbar.item
                    class="[&>div>svg]:size-5 h-10 font-medium text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 max-lg:hidden dark:text-zinc-300 dark:hover:bg-zinc-700 dark:hover:text-white"
                    icon="folder-git-2"
                    href="https://github.com/laravel/livewire-starter-kit"
                    target="_blank"
                    :label="__('Repository')"
                />
            </flux:tooltip>
            <flux:tooltip :content="__('Documentation')" position="bottom">
                <flux:navbar.item
                    class="[&>div>svg]:size-5 h-10 font-medium text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 max-lg:hidden dark:text-zinc-300 dark:hover:bg-zinc-700 dark:hover:text-white"
                    icon="book-open-text"
                    href="https://laravel.com/docs/starter-kits#livewire"
                    target="_blank"
                    :label="__('Documentation')"
                />
            </flux:tooltip>
        </flux:navbar>

        <x-desktop-user-menu />
    </flux:header>

    <!-- Mobile Menu -->
    <flux:sidebar
        collapsible="mobile"
        sticky
        class="border-e border-blue-100/50 bg-white/90 shadow-sm lg:hidden dark:border-zinc-700/50 dark:bg-zinc-800/90"
    >
        <flux:sidebar.header>
            <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
            <flux:sidebar.collapse class="text-zinc-600 hover:text-zinc-900 in-data-flux-sidebar-on-desktop:not-in-data-flux-sidebar-collapsed-desktop:-mr-2 dark:text-zinc-300 dark:hover:text-white" />
        </flux:sidebar.header>

        <flux:sidebar.nav>
            <flux:sidebar.group :heading="__('Platform')" class="font-medium text-zinc-600/90 dark:text-zinc-300/90">
                <flux:sidebar.item
                    icon="layout-grid"
                    :href="route('dashboard')"
                    :current="request()->routeIs('dashboard')"
                    wire:navigate
                    class="font-medium text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-700 dark:hover:text-white"
                >
                    {{ __('Dashboard') }}
                </flux:sidebar.item>
            </flux:sidebar.group>
        </flux:sidebar.nav>

        <flux:spacer />

        <flux:sidebar.nav>
            <flux:sidebar.item
                icon="folder-git-2"
                href="https://github.com/laravel/livewire-starter-kit"
                target="_blank"
                class="font-medium text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-700 dark:hover:text-white"
            >
                {{ __('Repository') }}
            </flux:sidebar.item>
            <flux:sidebar.item
                icon="book-open-text"
                href="https://laravel.com/docs/starter-kits#livewire"
                target="_blank"
                class="font-medium text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-700 dark:hover:text-white"
            >
                {{ __('Documentation') }}
            </flux:sidebar.item>
        </flux:sidebar.nav>
    </flux:sidebar>

    {{ $slot }}

    @fluxScripts
</body>
</html>

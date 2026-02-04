<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:header container class="border-b border-blue-100/50 bg-white/90 dark:bg-zinc-800/90 dark:border-zinc-700/50 shadow-sm">
            <flux:sidebar.toggle class="lg:hidden mr-2 text-zinc-600 dark:text-zinc-300 hover:text-zinc-900 dark:hover:text-white" icon="bars-2" inset="left" />

            <x-app-logo href="{{ route('dashboard') }}" wire:navigate />

            <flux:navbar class="-mb-px max-lg:hidden">
                <flux:navbar.item icon="layout-grid" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate class="text-zinc-600 dark:text-zinc-300 hover:text-zinc-900 dark:hover:text-white hover:bg-zinc-100 dark:hover:bg-zinc-700 font-medium">
                    {{ __('Dashboard') }}
                </flux:navbar.item>
            </flux:navbar>

            <flux:spacer />

            <flux:navbar class="me-1.5 space-x-0.5 rtl:space-x-reverse py-0!">
                <flux:tooltip :content="__('Search')" position="bottom">
                    <flux:navbar.item class="!h-10 [&>div>svg]:size-5 text-zinc-600 dark:text-zinc-300 hover:text-zinc-900 dark:hover:text-white hover:bg-zinc-100 dark:hover:bg-zinc-700 font-medium" icon="magnifying-glass" href="#" :label="__('Search')" />
                </flux:tooltip>
                <flux:tooltip :content="__('Repository')" position="bottom">
                    <flux:navbar.item
                        class="h-10 max-lg:hidden [&>div>svg]:size-5 text-zinc-600 dark:text-zinc-300 hover:text-zinc-900 dark:hover:text-white hover:bg-zinc-100 dark:hover:bg-zinc-700 font-medium"
                        icon="folder-git-2"
                        href="https://github.com/laravel/livewire-starter-kit"
                        target="_blank"
                        :label="__('Repository')"
                    />
                </flux:tooltip>
                <flux:tooltip :content="__('Documentation')" position="bottom">
                    <flux:navbar.item
                        class="h-10 max-lg:hidden [&>div>svg]:size-5 text-zinc-600 dark:text-zinc-300 hover:text-zinc-900 dark:hover:text-white hover:bg-zinc-100 dark:hover:bg-zinc-700 font-medium"
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
        <flux:sidebar collapsible="mobile" sticky class="lg:hidden border-e border-blue-100/50 bg-white/90 dark:bg-zinc-800/90 dark:border-zinc-700/50 shadow-sm">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="in-data-flux-sidebar-on-desktop:not-in-data-flux-sidebar-collapsed-desktop:-mr-2 text-zinc-600 dark:text-zinc-300 hover:text-zinc-900 dark:hover:text-white" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Platform')" class="text-zinc-600/90 dark:text-zinc-300/90 font-medium">
                    <flux:sidebar.item icon="layout-grid" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate class="text-zinc-600 dark:text-zinc-300 hover:text-zinc-900 dark:hover:text-white hover:bg-zinc-100 dark:hover:bg-zinc-700 font-medium">
                        {{ __('Dashboard')  }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
            </flux:sidebar.nav>

            <flux:spacer />

            <flux:sidebar.nav>
                <flux:sidebar.item icon="folder-git-2" href="https://github.com/laravel/livewire-starter-kit" target="_blank" class="text-zinc-600 dark:text-zinc-300 hover:text-zinc-900 dark:hover:text-white hover:bg-zinc-100 dark:hover:bg-zinc-700 font-medium">
                    {{ __('Repository') }}
                </flux:sidebar.item>
                <flux:sidebar.item icon="book-open-text" href="https://laravel.com/docs/starter-kits#livewire" target="_blank" class="text-zinc-600 dark:text-zinc-300 hover:text-zinc-900 dark:hover:text-white hover:bg-zinc-100 dark:hover:bg-zinc-700 font-medium">
                    {{ __('Documentation') }}
                </flux:sidebar.item>
            </flux:sidebar.nav>
        </flux:sidebar>

        {{ $slot }}

        @fluxScripts
    </body>
</html>

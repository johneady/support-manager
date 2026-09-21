<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    @include('partials.head')
</head>
<body class="min-h-screen bg-white dark:bg-zinc-800">
    <flux:sidebar
        sticky
        collapsible="mobile"
        class="border-e border-zinc-200 bg-zinc-100 shadow-md dark:border-zinc-700 dark:bg-zinc-900"
    >
        <flux:sidebar.header>
            <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
            <flux:sidebar.collapse class="text-zinc-700 hover:text-zinc-900 lg:hidden dark:text-zinc-300 dark:hover:text-zinc-100" />
        </flux:sidebar.header>

        <flux:sidebar.nav>
            <flux:sidebar.group :heading="__('Platform')" class="grid">
                <flux:sidebar.item
                    icon="home"
                    :href="route('dashboard')"
                    :current="request()->routeIs('dashboard')"
                    wire:navigate
                    class="font-medium text-zinc-900 hover:bg-zinc-200 hover:text-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800 dark:hover:text-zinc-100"
                >
                    {{ __('Dashboard') }}
                </flux:sidebar.item>
                @unless (auth()->user()?->isAdmin())
                    <flux:sidebar.item
                        icon="ticket"
                        :href="route('tickets.index')"
                        :current="request()->routeIs('tickets.index') || request()->routeIs('tickets.create') || request()->routeIs('tickets.show')"
                        wire:navigate
                        class="font-medium text-zinc-900 hover:bg-zinc-200 hover:text-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800 dark:hover:text-zinc-100"
                    >
                        {{ __('My Tickets') }}
                    </flux:sidebar.item>
                @endunless
                @if (auth()->user()?->isAdmin())
                    <livewire:ticket-queue-badge />
                    <flux:sidebar.item
                        icon="layout-grid"
                        :href="route('tickets.all')"
                        :current="request()->routeIs('tickets.all')"
                        wire:navigate
                        class="font-medium text-zinc-900 hover:bg-zinc-200 hover:text-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800 dark:hover:text-zinc-100"
                    >
                        {{ __('All Tickets') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item
                        icon="tag"
                        :href="route('admin.categories')"
                        :current="request()->routeIs('admin.categories')"
                        wire:navigate
                        class="font-medium text-zinc-900 hover:bg-zinc-200 hover:text-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800 dark:hover:text-zinc-100"
                    >
                        {{ __('Categories') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item
                        icon="users"
                        :href="route('admin.users')"
                        :current="request()->routeIs('admin.users')"
                        wire:navigate
                        class="font-medium text-zinc-900 hover:bg-zinc-200 hover:text-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800 dark:hover:text-zinc-100"
                    >
                        {{ __('Users') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item
                        icon="clipboard-document-list"
                        :href="route('admin.faqs')"
                        :current="request()->routeIs('admin.faqs')"
                        wire:navigate
                        class="font-medium text-zinc-900 hover:bg-zinc-200 hover:text-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800 dark:hover:text-zinc-100"
                    >
                        {{ __('FAQ Management') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item
                        icon="shield-check"
                        href="/health"
                        :current="request()->path() === '/health'"
                        wire:navigate
                        class="font-medium text-zinc-900 hover:bg-zinc-200 hover:text-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800 dark:hover:text-zinc-100"
                    >
                        {{ __('Health') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item
                        icon="adjustments-horizontal"
                        :href="route('admin.settings')"
                        :current="request()->routeIs('admin.settings')"
                        wire:navigate
                        class="font-medium text-zinc-900 hover:bg-zinc-200 hover:text-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800 dark:hover:text-zinc-100"
                    >
                        {{ __('Platform Settings') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item
                        icon="ticket"
                        :href="route('tickets.index')"
                        :current="request()->routeIs('tickets.index') || request()->routeIs('tickets.create') || request()->routeIs('tickets.show')"
                        wire:navigate
                        class="font-medium text-zinc-900 hover:bg-zinc-200 hover:text-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800 dark:hover:text-zinc-100"
                    >
                        {{ __('My Tickets') }}
                    </flux:sidebar.item>
                @endif
            </flux:sidebar.group>
            <flux:sidebar.group :heading="__('Help')" class="grid">
                <flux:sidebar.item
                    icon="question-mark-circle"
                    :href="route('faq')"
                    :current="request()->routeIs('faq')"
                    wire:navigate
                    class="font-medium text-zinc-900 hover:bg-zinc-200 hover:text-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800 dark:hover:text-zinc-100"
                >
                    {{ __('FAQ') }}
                </flux:sidebar.item>
            </flux:sidebar.group>
            <flux:sidebar.group :heading="__('Account')" class="grid">
                <flux:sidebar.item
                    icon="cog"
                    :href="route('profile.edit')"
                    :current="request()->routeIs('profile.edit') || request()->routeIs('settings.*')"
                    wire:navigate
                    class="font-medium text-zinc-900 hover:bg-zinc-200 hover:text-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800 dark:hover:text-zinc-100"
                >
                    {{ __('Settings') }}
                </flux:sidebar.item>
                <flux:sidebar.item
                    icon="arrow-right-start-on-rectangle"
                    href="#"
                    x-on:click.prevent="$refs.logoutForm.submit()"
                    class="font-medium text-zinc-900 hover:bg-zinc-200 hover:text-zinc-900 dark:text-zinc-300 dark:hover:bg-zinc-800 dark:hover:text-zinc-100"
                >
                    {{ __('Log Out') }}
                </flux:sidebar.item>
            </flux:sidebar.group>
        </flux:sidebar.nav>

        <flux:spacer />

        <form x-ref="logoutForm" method="POST" action="{{ route('logout') }}" class="hidden">
            @csrf
        </form>
    </flux:sidebar>

    <!-- Mobile Header with Toggle -->
    <flux:header class="border-b border-zinc-200 bg-zinc-100 shadow-sm lg:hidden! dark:border-zinc-700 dark:bg-zinc-900">
        <flux:sidebar.toggle
            class="text-zinc-700 hover:bg-zinc-200 hover:text-zinc-900 lg:hidden dark:text-zinc-300 dark:hover:bg-zinc-800 dark:hover:text-zinc-100"
            icon="bars-2"
            inset="left"
        />

        <flux:spacer />

        <x-app-logo href="{{ route('dashboard') }}" wire:navigate />

        <flux:spacer />
    </flux:header>

    {{ $slot }}

    @fluxScripts
</body>
</html>

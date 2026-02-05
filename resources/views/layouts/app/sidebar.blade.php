<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:sidebar sticky collapsible="mobile" class="border-e border-blue-200/50 bg-linear-to-b from-blue-400 via-sky-400 to-cyan-400 lg:from-blue-400/50 lg:via-sky-400/50 lg:to-cyan-400/50 dark:border-blue-700/50 dark:from-blue-600 dark:via-sky-600 dark:to-cyan-600 dark:lg:from-blue-600/50 dark:lg:via-sky-600/50 dark:lg:to-cyan-600/50 shadow-md shadow-blue-500/15">
            <flux:sidebar.header>
                <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
                <flux:sidebar.collapse class="lg:hidden text-white hover:text-white/90" />
            </flux:sidebar.header>

            <flux:sidebar.nav>
                <flux:sidebar.group :heading="__('Platform')" class="grid">
                    <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate class="text-zinc-900 dark:text-zinc-800 hover:text-zinc-900 dark:hover:text-zinc-800 hover:bg-white/20 font-medium">
                        {{ __('Dashboard') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="ticket" :href="route('tickets.index')" :current="request()->routeIs('tickets.index') || request()->routeIs('tickets.create') || request()->routeIs('tickets.show')" wire:navigate class="text-zinc-900 dark:text-zinc-800 hover:text-zinc-900 dark:hover:text-zinc-800 hover:bg-white/20 font-medium">
                        {{ __('My Tickets') }}
                    </flux:sidebar.item>
                    @if(auth()->user()?->isAdmin())
                        <flux:sidebar.item icon="inbox-stack" :href="route('tickets.queue')" :current="request()->routeIs('tickets.queue')" wire:navigate class="text-zinc-900 dark:text-zinc-800 hover:text-zinc-900 dark:hover:text-zinc-800 hover:bg-white/20 font-medium">
                            {{ __('Ticket Queue') }}
                        </flux:sidebar.item>
                    @endif
                </flux:sidebar.group>
                <flux:sidebar.group :heading="__('Help')" class="grid">
                    <flux:sidebar.item icon="question-mark-circle" :href="route('faq')" :current="request()->routeIs('faq')" wire:navigate class="text-zinc-900 dark:text-zinc-800 hover:text-zinc-900 dark:hover:text-zinc-800 hover:bg-white/20 font-medium">
                        {{ __('FAQ') }}
                    </flux:sidebar.item>
                </flux:sidebar.group>
                <flux:sidebar.group :heading="__('Account')" class="grid">
                    <flux:sidebar.item icon="cog" :href="route('profile.edit')" :current="request()->routeIs('profile.edit') || request()->routeIs('settings.*')" wire:navigate class="text-zinc-900 dark:text-zinc-800 hover:text-zinc-900 dark:hover:text-zinc-800 hover:bg-white/20 font-medium">
                        {{ __('Settings') }}
                    </flux:sidebar.item>
                    <flux:sidebar.item icon="arrow-right-start-on-rectangle" href="#" x-on:click.prevent="$refs.logoutForm.submit()" class="text-zinc-900 dark:text-zinc-800 hover:text-zinc-900 dark:hover:text-zinc-800 hover:bg-white/20 font-medium">
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
        <flux:header class="lg:hidden! border-b border-blue-200/50 bg-linear-to-r from-blue-400/50 via-sky-400/50 to-cyan-400/50 dark:border-blue-700/50 dark:from-blue-600/50 dark:via-sky-600/50 dark:to-cyan-600/50 shadow-sm shadow-blue-500/15">
            <flux:sidebar.toggle class="lg:hidden text-zinc-900 dark:text-zinc-800 hover:bg-white/20" icon="bars-2" inset="left" />

            <flux:spacer />

            <x-app-logo href="{{ route('dashboard') }}" wire:navigate />

            <flux:spacer />
        </flux:header>

        {{ $slot }}

        @fluxScripts
    </body>
</html>

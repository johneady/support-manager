<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:sidebar sticky collapsible="mobile" class="border-e border-violet-200/50 bg-gradient-to-b from-violet-400/85 via-pink-400/85 to-amber-400/85 dark:border-violet-700/50 dark:from-violet-600/85 dark:via-pink-600/85 dark:to-amber-600/85 shadow-md shadow-violet-500/15">
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
            </flux:sidebar.nav>

            <flux:spacer />
        </flux:sidebar>

        {{ $slot }}

        @fluxScripts
    </body>
</html>

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

        <!-- Desktop Header -->
        <flux:header class="hidden lg:flex border-b border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900">
            <flux:navbar class="w-full">
                <flux:spacer />

                <flux:dropdown position="bottom" align="end">
                    <flux:profile
                        :name="auth()->user()->name"
                        :initials="auth()->user()->initials()"
                        icon-trailing="chevron-down"
                    />

                    <flux:menu>
                        <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                            <flux:avatar
                                :name="auth()->user()->name"
                                :initials="auth()->user()->initials()"
                            />
                            <div class="grid flex-1 text-start text-sm leading-tight">
                                <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                            </div>
                        </div>

                        <flux:menu.separator />

                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            {{ __('Settings') }}
                        </flux:menu.item>

                        <flux:menu.separator />

                        <form method="POST" action="{{ route('logout') }}" class="w-full">
                            @csrf
                            <flux:menu.item
                                as="button"
                                type="submit"
                                icon="arrow-right-start-on-rectangle"
                                class="w-full cursor-pointer"
                            >
                                {{ __('Log Out') }}
                            </flux:menu.item>
                        </form>
                    </flux:menu>
                </flux:dropdown>
            </flux:navbar>
        </flux:header>

        <!-- Mobile Header -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <flux:avatar
                                    :name="auth()->user()->name"
                                    :initials="auth()->user()->initials()"
                                />

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                    <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                            {{ __('Settings') }}
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer"
                            data-test="logout-button"
                        >
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @fluxScripts
    </body>
</html>

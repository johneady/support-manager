<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>FAQ - {{ config('app.name', 'Support Manager') }}</title>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @fluxAppearance
    </head>
    <body class="antialiased">
        <div class="min-h-screen bg-linear-to-br from-violet-50 via-pink-50 to-amber-50 dark:from-zinc-900 dark:via-purple-950/30 dark:to-zinc-900">
            {{-- Header --}}
            <header class="bg-white/80 dark:bg-zinc-800/80 backdrop-blur-sm border-b border-violet-100 dark:border-zinc-700">
                <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                    <nav class="flex items-center justify-between">
                        <a href="/" class="flex items-center gap-2 group">
                            <x-app-logo-icon class="size-8 transition-transform group-hover:scale-110" />
                            <span class="font-semibold bg-linear-to-r from-violet-600 to-pink-600 dark:from-violet-400 dark:to-pink-400 bg-clip-text text-transparent">Support Manager</span>
                        </a>
                        <div class="flex items-center gap-4">
                            <flux:button href="{{ route('faq') }}" variant="ghost" size="sm">FAQ</flux:button>
                            @auth
                                <flux:button href="{{ url('/dashboard') }}" variant="ghost" size="sm">Dashboard</flux:button>
                            @else
                                @if (Route::has('login'))
                                    <flux:button href="{{ route('login') }}" variant="ghost" size="sm">Log in</flux:button>
                                @endif
                                @if (Route::has('register'))
                                    <flux:button href="{{ route('register') }}" variant="primary" size="sm" class="bg-linear-to-r! from-violet-500! to-pink-500! hover:from-violet-600! hover:to-pink-600! border-0!">Register</flux:button>
                                @endif
                            @endauth
                        </div>
                    </nav>
                </div>
            </header>

            {{-- Hero Section --}}
            <section class="relative py-12 sm:py-16 overflow-hidden">
                {{-- Decorative background elements --}}
                <div class="absolute inset-0 overflow-hidden pointer-events-none">
                    <div class="absolute -top-40 -right-40 w-80 h-80 bg-linear-to-br from-violet-300/30 to-pink-300/30 dark:from-violet-600/20 dark:to-pink-600/20 rounded-full blur-3xl"></div>
                    <div class="absolute -bottom-40 -left-40 w-80 h-80 bg-linear-to-br from-amber-300/30 to-orange-300/30 dark:from-amber-600/20 dark:to-orange-600/20 rounded-full blur-3xl"></div>
                </div>

                <div class="relative max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                    <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-linear-to-r from-violet-100 to-pink-100 dark:from-violet-900/50 dark:to-pink-900/50 text-violet-700 dark:text-violet-300 text-sm font-medium mb-6">
                        <flux:icon name="light-bulb" class="size-4" />
                        Knowledge Base
                    </div>
                    <flux:heading size="xl" level="1" class="text-3xl sm:text-4xl bg-linear-to-r! from-violet-600! via-pink-600! to-amber-600! dark:from-violet-400! dark:via-pink-400! dark:to-amber-400! bg-clip-text! text-transparent!">
                        Frequently Asked Questions
                    </flux:heading>
                    <flux:text class="mt-4 text-lg text-zinc-600 dark:text-zinc-400 max-w-2xl mx-auto">
                        Find answers to common questions. Can't find what you're looking for? Submit a support ticket.
                    </flux:text>
                </div>
            </section>

            {{-- FAQs Section --}}
            <section class="pb-16">
                <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
                    <livewire:faq-list />

                    <div class="mt-12 text-center">
                        <div class="inline-flex flex-col sm:flex-row items-center gap-4 p-6 rounded-2xl bg-white/60 dark:bg-zinc-800/60 backdrop-blur-sm border border-violet-100 dark:border-zinc-700">
                            <div class="w-12 h-12 rounded-xl bg-linear-to-br from-violet-500 to-pink-500 flex items-center justify-center">
                                <flux:icon name="chat-bubble-left-right" class="size-6 text-white" />
                            </div>
                            <div class="text-center sm:text-left">
                                <p class="font-medium text-zinc-900 dark:text-white">Still need help?</p>
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">Our support team is ready to assist you.</p>
                            </div>
                            @auth
                                <flux:button href="{{ route('tickets.create') }}" variant="primary" class="bg-linear-to-r! from-violet-500! to-pink-500! hover:from-violet-600! hover:to-pink-600! border-0!">
                                    Create a Ticket
                                </flux:button>
                            @else
                                <flux:button href="{{ route('login') }}" variant="primary" class="bg-linear-to-r! from-violet-500! to-pink-500! hover:from-violet-600! hover:to-pink-600! border-0!">
                                    Sign in to Submit
                                </flux:button>
                            @endauth
                        </div>
                    </div>
                </div>
            </section>

            {{-- Footer --}}
            <footer class="bg-white/40 dark:bg-zinc-900/80 backdrop-blur-sm border-t border-violet-100 dark:border-zinc-700 py-8">
                <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                        <flux:text size="sm" class="text-zinc-500">
                            &copy; {{ date('Y') }} Support Manager. All rights reserved.
                        </flux:text>
                        <div class="flex items-center gap-6">
                            <flux:link href="#" variant="subtle" class="text-sm text-violet-600! dark:text-violet-400!">Privacy Policy</flux:link>
                            <flux:link href="#" variant="subtle" class="text-sm text-violet-600! dark:text-violet-400!">Terms of Service</flux:link>
                        </div>
                    </div>
                </div>
            </footer>
        </div>

        @fluxScripts
    </body>
</html>

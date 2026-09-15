<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <title>{{ isset($title) ? $title . ' - ' : '' }}{{ config('app.name', 'Support Manager') }}</title>

    <link rel="icon" href="/favicon.ico" sizes="any" />
    <link rel="icon" href="/favicon.svg" type="image/svg+xml" />
    <link rel="apple-touch-icon" href="/apple-touch-icon.png" />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net" />
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @fluxAppearance
</head>
<body class="antialiased">
    <div class="min-h-screen bg-linear-to-br from-blue-50 via-sky-50 to-cyan-50 dark:from-zinc-900 dark:via-blue-950/30 dark:to-zinc-900">
        {{-- Header --}}
        <header class="border-b border-blue-100 bg-white/80 backdrop-blur-sm dark:border-zinc-700 dark:bg-zinc-800/80">
            <div class="mx-auto max-w-5xl px-4 py-4 sm:px-6 lg:px-8">
                <nav class="flex items-center justify-between">
                    <a href="/" class="group flex items-center gap-2">
                        <x-app-logo-icon class="size-8 transition-transform group-hover:scale-110" />
                        <span class="bg-linear-to-r from-blue-600 to-sky-600 bg-clip-text font-semibold text-transparent dark:from-blue-400 dark:to-sky-400">Support Manager</span>
                    </a>
                    <div class="flex items-center gap-4">
                        <flux:button href="{{ route('faq') }}" variant="ghost" size="sm">FAQ</flux:button>
                        @auth
                            <flux:button href="{{ url('/dashboard') }}" variant="ghost" size="sm"
                                >Dashboard</flux:button>
                        @else
                            @if (Route::has('login'))
                                <flux:button href="{{ route('login') }}" variant="ghost" size="sm">Log in</flux:button>
                            @endif
                            @if (Route::has('register'))
                                <flux:button
                                    href="{{ route('register') }}"
                                    variant="primary"
                                    size="sm"
                                    class="border-0! bg-linear-to-r! from-blue-500! to-sky-500! hover:from-blue-600! hover:to-sky-600!"
                                >Register</flux:button>
                            @endif
                        @endauth
                    </div>
                </nav>
            </div>
        </header>

        {{ $slot }}

        {{-- Footer --}}
        <footer class="border-t border-blue-100 bg-white/40 py-8 backdrop-blur-sm dark:border-zinc-700 dark:bg-zinc-900/80">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col items-center justify-between gap-4 sm:flex-row">
                    <flux:text size="sm" class="text-zinc-500">
                        &copy; {{ date('Y') }} Support Manager. All rights reserved.
                    </flux:text>
                    <div class="flex items-center gap-6">
                        <flux:link
                            href="{{ route('privacy-policy') }}"
                            variant="subtle"
                            class="text-sm text-blue-600! dark:text-blue-400!"
                        >Privacy Policy</flux:link>
                        <flux:link
                            href="{{ route('terms-of-service') }}"
                            variant="subtle"
                            class="text-sm text-blue-600! dark:text-blue-400!"
                        >Terms of Service</flux:link>
                    </div>
                </div>
            </div>
        </footer>
    </div>

    @fluxScripts
</body>
</html>

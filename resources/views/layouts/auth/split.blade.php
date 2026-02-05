<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-blue-50 antialiased dark:bg-linear-to-b dark:from-blue-950 dark:to-neutral-900">
        <div class="relative grid h-dvh flex-col items-center justify-center px-8 sm:px-0 lg:max-w-none lg:grid-cols-2 lg:px-0">
            <div class="relative hidden h-full flex-col p-10 text-white lg:flex dark:border-e dark:border-neutral-800">
                <img src="{{ asset('images/login-panel.jpg') }}" alt="" class="absolute inset-0 h-full w-full object-cover" />
                <div class="absolute inset-0 bg-white/30 dark:bg-black/30"></div>
                <a href="{{ route('home') }}" class="relative z-20 flex items-center text-lg font-medium text-black dark:text-white" wire:navigate>
                    <span class="flex h-10 w-10 items-center justify-center rounded-md">
                        <x-app-logo-icon class="me-2 h-7 fill-current text-black dark:text-white" />
                    </span>
                    {{ config('app.name', 'Laravel') }}
                </a>
            </div>
            <div class="w-full lg:p-8">
                <div class="mx-auto flex w-full flex-col justify-center space-y-6 sm:w-[350px]">
                    <a href="{{ route('home') }}" class="z-20 flex flex-col items-center gap-2 font-medium lg:hidden" wire:navigate>
                        <span class="flex h-9 w-9 items-center justify-center rounded-md bg-blue-600">
                            <x-app-logo-icon class="size-9 fill-current text-white" />
                        </span>

                        <span class="sr-only">{{ config('app.name', 'Laravel') }}</span>
                    </a>
                    {{ $slot }}
                </div>
            </div>
        </div>
        @fluxScripts
    </body>
</html>

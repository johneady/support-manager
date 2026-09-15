<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    @include('partials.head')
</head>
<body class="min-h-screen bg-blue-50 antialiased dark:bg-linear-to-b dark:from-blue-950 dark:to-neutral-900">
    <div class="flex min-h-svh flex-col items-center justify-center gap-6 bg-blue-100 p-6 md:p-10 dark:bg-blue-900/30">
        <div class="flex w-full max-w-md flex-col gap-6">
            <a href="{{ route('home') }}" class="flex flex-col items-center gap-2 font-medium" wire:navigate>
                <span class="flex h-9 w-9 items-center justify-center rounded-md bg-blue-600">
                    <x-app-logo-icon class="size-9 fill-current text-white" />
                </span>

                <span class="sr-only">{{ config('app.name', 'Laravel') }}</span>
            </a>

            <div class="flex flex-col gap-6">
                <div class="rounded-xl border bg-white text-stone-800 shadow-xs dark:border-blue-800 dark:bg-stone-950">
                    <div class="px-10 py-8">{{ $slot }}</div>
                </div>
            </div>
        </div>
    </div>
    @fluxScripts
</body>
</html>

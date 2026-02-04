@props([
    'sidebar' => false,
])

@if($sidebar)
    <flux:sidebar.brand name="Support Manager" {{ $attributes }}>
        <x-slot name="logo" class="flex items-center justify-center">
            <x-app-logo-icon class="size-8 fill-current text-white dark:text-black" />
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand name="Support Manager" {{ $attributes }}>
        <x-slot name="logo" class="flex items-center justify-center">
            <x-app-logo-icon class="size-8 fill-current text-white dark:text-black" />
        </x-slot>
    </flux:brand>
@endif

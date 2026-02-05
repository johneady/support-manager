<x-layouts::auth>
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Forgot password')" :description="__('Enter your email to receive a password reset link')" />

        <!-- Session Status -->
        <x-auth-session-status class="text-center" :status="session('status')" />

        <form method="POST" action="{{ route('password.email') }}" class="flex flex-col gap-6">
            @csrf

            <!-- Email Address -->
            <flux:input
                name="email"
                :label="__('Email Address')"
                type="email"
                required
                autofocus
                placeholder="email@example.com"
            />

            <flux:button variant="primary" type="submit" class="w-full bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600" data-test="email-password-reset-link-button">
                {{ __('Email password reset link') }}
            </flux:button>
        </form>

        <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-600 dark:text-zinc-400">
            <span>{{ __('Or, return to') }}</span>
            <flux:link :href="route('login')" wire:navigate class="text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">{{ __('log in') }}</flux:link>
        </div>
    </div>
</x-layouts::auth>

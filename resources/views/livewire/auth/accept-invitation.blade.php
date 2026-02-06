<x-layouts::auth>
    <div class="flex flex-col gap-6">
        <x-auth-header :title="__('Set Your Password')" :description="__('Create your password to complete your account setup')" />

        @if($errorMessage)
            <flux:callout variant="danger" icon="exclamation-circle" dismissible>
                {{ $errorMessage }}
            </flux:callout>
        @elseif(!$this->isTokenValid)
            <flux:callout variant="danger" icon="exclamation-circle">
                {{ __('Invalid or expired invitation token. Please contact support.') }}
            </flux:callout>
        @else
            <form wire:submit="acceptInvitation" class="flex flex-col gap-6">
                <!-- Password -->
                <flux:field>
                    <flux:label>{{ __('Password') }}</flux:label>
                    <flux:input
                        wire:model="password"
                        type="password"
                        :placeholder="__('Minimum 8 characters')"
                        required
                        autocomplete="new-password"
                        viewable
                    />
                    <flux:error name="password" />
                </flux:field>

                <!-- Confirm Password -->
                <flux:field>
                    <flux:label>{{ __('Confirm Password') }}</flux:label>
                    <flux:input
                        wire:model="password_confirmation"
                        type="password"
                        :placeholder="__('Confirm your password')"
                        required
                        autocomplete="new-password"
                        viewable
                    />
                    <flux:error name="password_confirmation" />
                </flux:field>

                <div class="flex items-center justify-end">
                    <flux:button type="submit" variant="primary" class="w-full bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600" data-test="accept-invitation-button">
                        {{ __('Set Password') }}
                    </flux:button>
                </div>
            </form>
        @endif
    </div>
</x-layouts::auth>

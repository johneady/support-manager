<?php

namespace App\Livewire\Settings;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ResolvesAuthenticatedUser;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class Password extends Component
{
    use PasswordValidationRules;
    use ResolvesAuthenticatedUser;

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    /**
     * Update the password for the currently authenticated user.
     */
    public function updatePassword(): void
    {
        try {
            $validated = $this->validate([
                'current_password' => $this->currentPasswordRules(),
                'password' => $this->passwordRules(),
            ]);
        } catch (ValidationException $e) {
            $this->reset('current_password', 'password', 'password_confirmation');

            throw $e;
        }

        $this->authenticatedUser()->update([
            'password' => $validated['password'],
        ]);

        $this->reset('current_password', 'password', 'password_confirmation');

        $this->dispatch('password-updated');
    }
}

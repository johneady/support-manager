<?php

namespace App\Livewire\Settings;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ResolvesAuthenticatedUser;
use App\Livewire\Actions\Logout;
use Livewire\Component;

class DeleteUserForm extends Component
{
    use PasswordValidationRules;
    use ResolvesAuthenticatedUser;

    public string $password = '';

    /**
     * Delete the currently authenticated user.
     */
    public function deleteUser(Logout $logout): void
    {
        $this->validate([
            'password' => $this->currentPasswordRules(),
        ]);

        tap($this->authenticatedUser(), $logout(...))->delete();

        $this->redirect('/', navigate: true);
    }
}

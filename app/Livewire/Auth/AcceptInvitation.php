<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Component;

class AcceptInvitation extends Component
{
    #[Locked]
    public string $token = '';

    #[Validate('required|string|min:8')]
    public string $password = '';

    #[Validate('required|string|same:password')]
    public string $password_confirmation = '';

    public ?User $user = null;

    public string $errorMessage = '';

    public function mount(string $token): void
    {
        $this->token = $token;

        $this->user = User::where('invitation_token', $token)->first();

        if (! $this->user) {
            $this->errorMessage = 'Invalid invitation token. Please contact support.';
        } elseif (! $this->user->isInvitationValid($token)) {
            if ($this->user->invitation_accepted_at !== null) {
                $this->errorMessage = 'This invitation has already been accepted. Please log in.';
            } else {
                $this->errorMessage = 'This invitation has expired. Please contact support for a new invitation.';
            }
        }
    }

    public function acceptInvitation(): void
    {
        $this->validate();

        if (! $this->user || ! $this->user->isInvitationValid($this->token)) {
            $this->errorMessage = 'Invalid or expired invitation token. Please contact support.';

            return;
        }

        $success = $this->user->acceptInvitation($this->token, $this->password);

        if (! $success) {
            $this->errorMessage = 'Failed to accept invitation. Please try again or contact support.';

            return;
        }

        // Authenticate the user
        Auth::login($this->user);

        // Redirect to dashboard
        $this->redirect(route('dashboard'), navigate: true);
    }

    #[Computed]
    public function isTokenValid(): bool
    {
        return $this->user !== null && $this->user->isInvitationValid($this->token);
    }
}

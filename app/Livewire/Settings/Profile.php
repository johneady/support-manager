<?php

namespace App\Livewire\Settings;

use App\Concerns\ProfileValidationRules;
use App\Concerns\ResolvesAuthenticatedUser;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Profile extends Component
{
    use ProfileValidationRules;
    use ResolvesAuthenticatedUser;

    public string $name = '';

    public string $email = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $user = $this->authenticatedUser();

        $this->name = $user->name;
        $this->email = $user->email;
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = $this->authenticatedUser();

        $validated = $this->validate($this->profileRules($user->id));

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $this->dispatch('profile-updated', name: $user->name);
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function resendVerificationNotification(): void
    {
        $user = $this->authenticatedUser();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $key = 'verify-email:'.$user->id;

        if (RateLimiter::tooManyAttempts($key, 3)) {
            $this->addError('email', 'Too many verification attempts. Please try again later.');

            return;
        }

        RateLimiter::increment($key, 3600);

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    #[Computed]
    public function hasUnverifiedEmail(): bool
    {
        return ! $this->authenticatedUser()->hasVerifiedEmail();
    }

    #[Computed]
    public function showDeleteUser(): bool
    {
        return $this->authenticatedUser()->hasVerifiedEmail();
    }
}

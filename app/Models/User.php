<?php

namespace App\Models;

use App\Notifications\QueuedResetPassword;
use App\Notifications\QueuedVerifyEmail;
use App\Notifications\UserInvitation;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
        'invitation_token',
        'invitation_created_at',
        'invitation_accepted_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'invitation_created_at' => 'datetime',
            'invitation_accepted_at' => 'datetime',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    /**
     * @return HasMany<Ticket, $this>
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }

    /**
     * Get all admin users, cached for 5 minutes.
     *
     * @return Collection<int, User>
     */
    public static function admins(): Collection
    {
        return Cache::remember('admin_users', 300, fn () => static::where('is_admin', true)->get());
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new QueuedVerifyEmail);
    }

    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        $this->notify(new QueuedResetPassword($token));
    }

    /**
     * Generate a secure invitation token.
     */
    public function generateInvitationToken(): string
    {
        $this->invitation_token = Str::random(60);
        $this->invitation_created_at = now();
        $this->invitation_accepted_at = null;
        $this->save();

        return $this->invitation_token;
    }

    /**
     * Send the invitation notification.
     */
    public function sendInvitationNotification(string $inviterName): void
    {
        $token = $this->generateInvitationToken();
        $this->notify(new UserInvitation($token, $inviterName));
    }

    /**
     * Resend the invitation with a new token.
     */
    public function resendInvitation(string $inviterName): void
    {
        $this->sendInvitationNotification($inviterName);
    }

    /**
     * Accept the invitation by setting the password.
     */
    public function acceptInvitation(string $token, string $password): bool
    {
        if (! $this->isInvitationValid($token)) {
            return false;
        }

        $this->password = $password;
        $this->invitation_token = null;
        $this->invitation_accepted_at = now();
        $this->save();

        return true;
    }

    /**
     * Check if the invitation token is valid and not expired.
     */
    public function isInvitationValid(string $token): bool
    {
        if ($this->invitation_token !== $token) {
            return false;
        }

        if ($this->invitation_accepted_at !== null) {
            return false;
        }

        if ($this->invitation_created_at === null) {
            return false;
        }

        $expiryDays = config('fortify.invitation_token_expiration_days', 7);
        $expiryDate = $this->invitation_created_at->addDays($expiryDays);

        return now()->lt($expiryDate);
    }

    /**
     * Check if the user has a pending invitation.
     */
    public function hasPendingInvitation(): bool
    {
        return $this->invitation_token !== null && $this->invitation_accepted_at === null;
    }

    /**
     * Get the invitation status.
     *
     * @return 'pending'|'accepted'|null
     */
    public function getInvitationStatus(): ?string
    {
        if ($this->invitation_accepted_at === null && $this->invitation_token === null) {
            return null;
        }

        if ($this->invitation_token === null) {
            return 'accepted';
        }

        return 'pending';
    }
}

<?php

use App\Models\User;
use App\Notifications\UserInvitation;
use Illuminate\Support\Facades\Notification;

test('generating invitation token creates and stores token', function () {
    $user = User::factory()->create();

    $token = $user->generateInvitationToken();

    expect($token)->toBeString();
    expect($token)->toHaveLength(60);
    expect($user->invitation_token)->toBe($token);
    expect($user->invitation_created_at)->not->toBeNull();
    expect($user->invitation_accepted_at)->toBeNull();
});

test('sending invitation notification sends email with token', function () {
    Notification::fake();

    $user = User::factory()->create();
    $inviterName = 'Admin User';

    $user->sendInvitationNotification($inviterName);

    Notification::assertSentTo($user, UserInvitation::class);
});

test('resending invitation generates new token', function () {
    Notification::fake();

    $user = User::factory()->create();
    $inviterName = 'Admin User';

    $user->sendInvitationNotification($inviterName);
    $oldToken = $user->invitation_token;

    $user->resendInvitation($inviterName);

    $user->refresh();

    expect($user->invitation_token)->not->toBe($oldToken);
    expect($user->invitation_created_at)->toBeGreaterThan(now()->subMinute());
});

test('isInvitationValid returns true for valid token', function () {
    $user = User::factory()->create();
    $token = $user->generateInvitationToken();

    expect($user->isInvitationValid($token))->toBeTrue();
});

test('isInvitationValid returns false for invalid token', function () {
    $user = User::factory()->create();
    $user->generateInvitationToken();

    expect($user->isInvitationValid('invalid-token'))->toBeFalse();
});

test('isInvitationValid returns false for expired token', function () {
    $user = User::factory()->create();
    $token = $user->generateInvitationToken();

    // Set invitation_created_at to 8 days ago (expired)
    $user->update(['invitation_created_at' => now()->subDays(8)]);

    expect($user->isInvitationValid($token))->toBeFalse();
});

test('isInvitationValid returns false for already accepted invitation', function () {
    $user = User::factory()->create();
    $token = $user->generateInvitationToken();

    $user->update(['invitation_accepted_at' => now()]);

    expect($user->isInvitationValid($token))->toBeFalse();
});

test('acceptInvitation sets password and marks as accepted', function () {
    $user = User::factory()->create();
    $token = $user->generateInvitationToken();
    $password = 'Password123!';

    $success = $user->acceptInvitation($token, $password);

    expect($success)->toBeTrue();

    $user->refresh();

    expect($user->password)->not->toBeNull();
    expect($user->invitation_token)->toBeNull();
    expect($user->invitation_accepted_at)->not->toBeNull();
});

test('acceptInvitation fails for invalid token', function () {
    $user = User::factory()->create();
    $password = 'Password123!';

    $success = $user->acceptInvitation('invalid-token', $password);

    expect($success)->toBeFalse();
});

test('acceptInvitation fails for expired token', function () {
    $user = User::factory()->create();
    $token = $user->generateInvitationToken();
    $password = 'Password123!';

    // Set invitation_created_at to 8 days ago (expired)
    $user->update(['invitation_created_at' => now()->subDays(8)]);

    $success = $user->acceptInvitation($token, $password);

    expect($success)->toBeFalse();
});

test('hasPendingInvitation returns true for pending invitation', function () {
    $user = User::factory()->create();
    $user->generateInvitationToken();

    expect($user->hasPendingInvitation())->toBeTrue();
});

test('hasPendingInvitation returns false for accepted invitation', function () {
    $user = User::factory()->create();
    $token = $user->generateInvitationToken();

    $user->acceptInvitation($token, 'Password123!');

    expect($user->hasPendingInvitation())->toBeFalse();
});

test('hasPendingInvitation returns false for user without invitation', function () {
    $user = User::factory()->create(['password' => 'hashed_password']);

    expect($user->hasPendingInvitation())->toBeFalse();
});

test('getInvitationStatus returns pending for pending invitation', function () {
    $user = User::factory()->create();
    $user->generateInvitationToken();

    expect($user->getInvitationStatus())->toBe('pending');
});

test('getInvitationStatus returns accepted for accepted invitation', function () {
    $user = User::factory()->create();
    $token = $user->generateInvitationToken();

    $user->acceptInvitation($token, 'Password123!');

    expect($user->getInvitationStatus())->toBe('accepted');
});

test('getInvitationStatus returns null for user without invitation', function () {
    $user = User::factory()->create(['password' => 'hashed_password']);

    expect($user->getInvitationStatus())->toBeNull();
});

test('invitation token is invalidated after acceptance', function () {
    $user = User::factory()->create();
    $token = $user->generateInvitationToken();

    $user->acceptInvitation($token, 'Password123!');

    $user->refresh();

    expect($user->invitation_token)->toBeNull();
    expect($user->isInvitationValid($token))->toBeFalse();
});

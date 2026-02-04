<?php

use App\Notifications\NewTicketNotification;
use App\Notifications\TicketReplyNotification;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();
});

test('it sends new ticket notification', function () {
    $this->artisan('mail:preview', [
        'type' => 'new-ticket',
        '--to' => 'test@example.com',
    ])
        ->expectsOutput('Sending new-ticket...')
        ->assertSuccessful();

    Notification::assertSentTimes(NewTicketNotification::class, 1);
});

test('it sends ticket reply to customer notification', function () {
    $this->artisan('mail:preview', [
        'type' => 'ticket-reply-to-customer',
        '--to' => 'test@example.com',
    ])
        ->expectsOutput('Sending ticket-reply-to-customer...')
        ->assertSuccessful();

    Notification::assertSentTimes(TicketReplyNotification::class, 1);
});

test('it sends ticket reply to admin notification', function () {
    $this->artisan('mail:preview', [
        'type' => 'ticket-reply-to-admin',
        '--to' => 'test@example.com',
    ])
        ->expectsOutput('Sending ticket-reply-to-admin...')
        ->assertSuccessful();

    Notification::assertSentTimes(TicketReplyNotification::class, 1);
});

test('it sends all email types with --all flag', function () {
    $this->artisan('mail:preview', [
        '--to' => 'test@example.com',
        '--all' => true,
    ])
        ->expectsOutput('Sending all email types...')
        ->expectsOutput('All emails sent!')
        ->assertSuccessful();

    Notification::assertSentTimes(NewTicketNotification::class, 1);
    Notification::assertSentTimes(TicketReplyNotification::class, 2);
});

test('it fails with invalid email address', function () {
    $this->artisan('mail:preview', [
        'type' => 'new-ticket',
        '--to' => 'invalid-email',
    ])
        ->expectsOutput('Invalid email address provided.')
        ->assertFailed();

    Notification::assertNothingSent();
});

test('it fails with unknown email type', function () {
    $this->artisan('mail:preview', [
        'type' => 'unknown-type',
        '--to' => 'test@example.com',
    ])
        ->expectsOutput('Unknown email type: unknown-type')
        ->assertFailed();

    Notification::assertNothingSent();
});

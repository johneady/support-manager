<?php

namespace App\Console\Commands;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\User;
use App\Notifications\NewTicketNotification;
use App\Notifications\TicketAutoClosedNotification;
use App\Notifications\TicketReplyNotification;
use App\Notifications\UserInvitation;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Password;

use function Laravel\Prompts\select;

class PreviewMailCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mail:preview
                            {type? : The email type to preview}
                            {--to= : Email address to send to}
                            {--all : Send all email types}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Preview and send test emails for development';

    /**
     * Available email types with descriptions.
     *
     * @var array<string, string>
     */
    protected array $emailTypes = [
        'password-reset' => 'Password reset link (ResetPassword)',
        'email-verification' => 'Email verification link (VerifyEmail)',
        'new-ticket' => '[Admin] New support ticket notification (NewTicketNotification)',
        'ticket-reply-to-customer' => 'Reply notification to customer (TicketReplyNotification)',
        'ticket-reply-to-admin' => '[Admin] Customer reply notification (TicketReplyNotification)',
        'ticket-auto-closed' => 'Ticket auto-closed notification (TicketAutoClosedNotification)',
        'user-invitation' => 'User invitation email (UserInvitation)',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $toEmail = $this->option('to');

        if (! $toEmail) {
            $toEmail = $this->ask('Enter email address to send test emails to', 'nobody@nobody.com');
        }

        if (! filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            $this->error('Invalid email address provided.');

            return Command::FAILURE;
        }

        if ($this->option('all')) {
            return $this->sendAllEmails($toEmail);
        }

        $type = $this->argument('type');

        if (! $type) {
            $options = array_merge(['all' => 'All emails'], $this->emailTypes);
            $type = select(
                label: 'Which email would you like to preview?',
                options: $options,
            );
        }

        if ($type === 'all') {
            return $this->sendAllEmails($toEmail);
        }

        if (! array_key_exists($type, $this->emailTypes)) {
            $this->error("Unknown email type: {$type}");
            $this->line('Available types: '.implode(', ', array_keys($this->emailTypes)));

            return Command::FAILURE;
        }

        return $this->sendEmail($type, $toEmail);
    }

    /**
     * Send all email types.
     */
    protected function sendAllEmails(string $toEmail): int
    {
        $this->info('Sending all email types...');
        $this->newLine();

        foreach (array_keys($this->emailTypes) as $type) {
            $this->sendEmail($type, $toEmail);
        }

        $this->newLine();
        $this->info('All emails sent!');

        return Command::SUCCESS;
    }

    /**
     * Send a specific email type.
     */
    protected function sendEmail(string $type, string $toEmail): int
    {
        $this->line("Sending {$type}...");

        match ($type) {
            'password-reset' => $this->sendPasswordResetNotification($toEmail),
            'email-verification' => $this->sendEmailVerificationNotification($toEmail),
            'new-ticket' => $this->sendNewTicketNotification($toEmail),
            'ticket-reply-to-customer' => $this->sendTicketReplyToCustomerNotification($toEmail),
            'ticket-reply-to-admin' => $this->sendTicketReplyToAdminNotification($toEmail),
            'ticket-auto-closed' => $this->sendTicketAutoClosedNotification($toEmail),
            'user-invitation' => $this->sendUserInvitationNotification($toEmail),
        };

        $this->info("  Sent: {$this->emailTypes[$type]}");

        return Command::SUCCESS;
    }

    /**
     * Create test data for emails.
     *
     * @return array{user: User, admin: User, ticket: Ticket}
     */
    protected function createTestData(): array
    {
        $user = User::factory()->make([
            'name' => 'Test Customer',
            'email' => 'customer@example.com',
        ]);
        $user->id = 99999;

        $admin = User::factory()->make([
            'name' => 'Support Admin',
            'email' => 'admin@example.com',
            'is_admin' => true,
        ]);
        $admin->id = 99998;

        $ticket = Ticket::factory()->make([
            'user_id' => $user->id,
            'subject' => 'Unable to reset my password',
            'description' => 'I have been trying to reset my password but I am not receiving the reset email. I have checked my spam folder but nothing is there. Can you please help me resolve this issue?',
            'status' => TicketStatus::Open,
            'priority' => TicketPriority::High,
        ]);
        $ticket->id = 99999;
        $ticket->setRelation('user', $user);

        return compact('user', 'admin', 'ticket');
    }

    /**
     * Create a test user with the given email for notifications.
     */
    protected function createTestUser(string $email, bool $isAdmin = false): User
    {
        $user = User::factory()->make([
            'name' => $isAdmin ? 'Support Admin' : 'Test Customer',
            'email' => $email,
            'is_admin' => $isAdmin,
        ]);
        $user->id = $isAdmin ? 99998 : 99999;

        return $user;
    }

    protected function sendPasswordResetNotification(string $toEmail): void
    {
        $user = $this->createTestUser($toEmail);
        $token = Password::broker()->createToken($user);

        $user->notifyNow(new ResetPassword($token));
    }

    protected function sendEmailVerificationNotification(string $toEmail): void
    {
        $user = $this->createTestUser($toEmail);
        $user->email_verified_at = null;

        $user->notifyNow(new VerifyEmail);
    }

    protected function sendNewTicketNotification(string $toEmail): void
    {
        $data = $this->createTestData();
        $admin = $this->createTestUser($toEmail, true);

        $admin->notifyNow(new NewTicketNotification($data['ticket']));
    }

    protected function sendTicketReplyToCustomerNotification(string $toEmail): void
    {
        $data = $this->createTestData();
        $customer = $this->createTestUser($toEmail);

        $reply = TicketReply::factory()->fromAdmin()->make([
            'ticket_id' => $data['ticket']->id,
            'user_id' => $data['admin']->id,
            'body' => "Hello,\n\nThank you for reaching out. I've looked into your account and can confirm that there was a temporary issue with our email delivery system.\n\nI've manually triggered a password reset email for you. Please check your inbox in the next few minutes.\n\nIf you still don't receive it, please let me know and I can help you reset your password directly.\n\nBest regards,\nSupport Team",
        ]);
        $reply->id = 99999;
        $reply->setRelation('ticket', $data['ticket']);
        $reply->setRelation('user', $data['admin']);

        $customer->notifyNow(new TicketReplyNotification($reply));
    }

    protected function sendTicketReplyToAdminNotification(string $toEmail): void
    {
        $data = $this->createTestData();
        $admin = $this->createTestUser($toEmail, true);

        $reply = TicketReply::factory()->fromCustomer()->make([
            'ticket_id' => $data['ticket']->id,
            'user_id' => $data['user']->id,
            'body' => "Hi,\n\nThank you for your quick response! I received the password reset email and was able to successfully change my password.\n\nI really appreciate your help with this issue.\n\nBest,\nTest Customer",
        ]);
        $reply->id = 99999;
        $reply->setRelation('ticket', $data['ticket']);
        $reply->setRelation('user', $data['user']);

        $admin->notifyNow(new TicketReplyNotification($reply));
    }

    protected function sendTicketAutoClosedNotification(string $toEmail): void
    {
        $data = $this->createTestData();
        $customer = $this->createTestUser($toEmail);

        $ticket = Ticket::factory()->make([
            'user_id' => $customer->id,
            'subject' => 'Unable to reset my password',
            'description' => 'I have been trying to reset my password but I am not receiving the reset email. I have checked my spam folder but nothing is there. Can you please help me resolve this issue?',
            'status' => TicketStatus::Closed,
            'priority' => TicketPriority::High,
        ]);
        $ticket->id = 99999;
        $ticket->setRelation('user', $customer);

        $customer->notifyNow(new TicketAutoClosedNotification($ticket));
    }

    protected function sendUserInvitationNotification(string $toEmail): void
    {
        $user = $this->createTestUser($toEmail);
        $token = 'test-invitation-token-'.str()->random(32);
        $inviterName = 'Support Admin';

        $user->notifyNow(new UserInvitation($token, $inviterName));
    }
}

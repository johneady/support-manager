<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * A diagnostic email an admin sends from Platform Settings to confirm the
 * configured mailer actually delivers.
 *
 * Deliberately NOT ShouldQueue. The point of the button is to prove delivery
 * works, and a queued send only proves the job was enqueued — on a deployment
 * whose worker is stopped or misconfigured the admin would be told the mail
 * was sent and never receive it, hiding the exact fault being tested for.
 */
class TestEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $sentByName) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Test Email from '.config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.test-email',
            with: [
                'sentByName' => $this->sentByName,
                'mailer' => config('mail.default'),
                'sentAt' => now(),
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}

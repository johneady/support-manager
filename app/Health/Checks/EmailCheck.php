<?php

namespace App\Health\Checks;

use Exception;
use Illuminate\Support\Facades\Mail;
use Spatie\Health\Checks\Check;
use Spatie\Health\Checks\Result;

final class EmailCheck extends Check
{
    protected ?string $mailer = null;

    public function mailer(string $mailer): self
    {
        $this->mailer = $mailer;

        return $this;
    }

    public function run(): Result
    {
        $mailer = $this->mailer ?? $this->defaultMailer();

        $result = Result::make()->meta([
            'mailer' => $mailer,
        ]);

        try {
            return $this->canSendEmail($mailer)
                ? $result->ok()
                : $result->failed('Could not send a test email.');
        } catch (Exception $exception) {
            return $result->failed("An exception occurred with the email system: `{$exception->getMessage()}`");
        }
    }

    protected function defaultMailer(): ?string
    {
        return config('mail.default', 'smtp');
    }

    protected function canSendEmail(?string $mailer): bool
    {
        try {
            // Get the mailer instance
            $mailManager = Mail::mailer($mailer);

            // Try to get the mailer transport to verify it's configured
            $transport = $mailManager->getSymfonyTransport();

            // Verify the transport is properly configured
            if (method_exists($transport, 'getStream')) {
                // For SMTP and similar transports
                $stream = $transport->getStream();
                if (method_exists($stream, 'getHost')) {
                    return ! empty($stream->getHost());
                }
            }

            // For other transport types, verify the mailer is configured
            $config = config("mail.mailers.{$mailer}");
            if (empty($config)) {
                return false;
            }

            return true;
        } catch (Exception $exception) {
            return false;
        }
    }
}

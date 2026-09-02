<x-mail::message>
# Test Email

This is a test email from **{{ config('app.name') }}**.

If you are reading this, your outgoing mail configuration is working correctly.

<x-mail::panel>
**Sent by:** {{ $sentByName }}<br>
**Mailer:** {{ $mailer }}<br>
**Sent at:** {{ $sentAt->toDayDateTimeString() }}
</x-mail::panel>

No action is required.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>

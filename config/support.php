<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Admin Emails
    |--------------------------------------------------------------------------
    |
    | Email addresses that should be considered administrators for the support
    | ticketing system. These users will receive notifications for new tickets
    | and will be able to manage all tickets through the Filament admin panel.
    |
    */
    'admin_emails' => array_filter(array_map('trim', explode(',', env('SUPPORT_ADMIN_EMAILS', '')))),
];

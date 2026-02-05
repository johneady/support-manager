### Todo

* create nightly job that if there is no reponse from a customer after an admin has replied to a ticket, at the 7 day mark, close the ticket with an automated reply saying why it was closed, and send a queued email to the customer saying there was no response from the ticket and it was automatically closed. But be nice in the email. also update the previewmailcommand for this new email

* after replying to a ticket and clicking send reply, close the modal. its in two places

beyondcode/laravel-query-detector

make session encrypted by default by updating the /config/session.php and updates to all .env files except dev.
   'secure' => env('SESSION_SECURE_COOKIE', env('APP_ENV') === 'production'),
   
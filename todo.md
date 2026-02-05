### Todo

* cud (create update delete) opertions for users, and cud operations for the FAQ. The update pages make them modals, and after the update is successful, close the modal. Make the modal the colors of the site (see the mytickets modal for colors).

* create nightly job that if there is no reponse from a customer after an admin has replied to a ticket, at the 7 day mark, close the ticket, and send an email to the customer saying there was no response from the ticket and it was automatically closed.

* after replying to a ticket and clicking send reply, close the modal. its in two places

beyondcode/laravel-query-detector

make session encrypted by default by updating the /config/session.php and updates to all .env files except dev.
   'secure' => env('SESSION_SECURE_COOKIE', env('APP_ENV') === 'production'),
   
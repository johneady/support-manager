### Todo

make session encrypted by default by updating the /config/session.php and updates to all .env files except dev.
   'secure' => env('SESSION_SECURE_COOKIE', env('APP_ENV') === 'production'),

* the links at the bottom of the public page, and contact detials, and documenation links...24 hours

* Give the customer a fancy looking 6 or 7 digit "reference number" and put that prominently in the emails for the customer and on the customer's My Tickets page. I like something like TX-1138-000001. The last part could be the id number from the database.

* Add filter at the top of the Ticket Queue (tickets/queue) by category. Have a reset or all as well. Default to all.
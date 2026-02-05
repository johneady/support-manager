### Todo

* after replying to a ticket and clicking send reply, close the modal. its in two places

beyondcode/laravel-query-detector

make session encrypted by default by updating the /config/session.php and updates to all .env files except dev.
   'secure' => env('SESSION_SECURE_COOKIE', env('APP_ENV') === 'production'),
   
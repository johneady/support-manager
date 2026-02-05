### Todo

make session encrypted by default by updating the /config/session.php and updates to all .env files except dev.
   'secure' => env('SESSION_SECURE_COOKIE', env('APP_ENV') === 'production'),
Why does it take 900 miliseconds to display the my tickets page. What is consumgint all that time
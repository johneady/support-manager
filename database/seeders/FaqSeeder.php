<?php

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
    public function run(): void
    {
        $faqs = [
            [
                'question' => 'What are the minimum server requirements for deploying a Laravel application?',
                'answer' => 'Laravel requires PHP 8.2 or higher with the following extensions: Ctype, cURL, DOM, Fileinfo, Filter, Hash, Mbstring, OpenSSL, PCRE, PDO, Session, Tokenizer, and XML. For MySQL, version 5.7 or higher is required (MySQL 8.0+ recommended). You\'ll also need Composer for dependency management and typically 512MB+ RAM for small applications.',
            ],
            [
                'question' => 'How do I configure my Laravel application to connect to MySQL?',
                'answer' => 'Update your .env file with your MySQL credentials: DB_CONNECTION=mysql, DB_HOST=127.0.0.1 (or your database server IP), DB_PORT=3306, DB_DATABASE=your_database_name, DB_USERNAME=your_username, and DB_PASSWORD=your_password. Never commit your .env file to version control. After configuring, run "php artisan config:cache" to cache your configuration.',
            ],
            [
                'question' => 'What commands should I run when deploying a Laravel application to production?',
                'answer' => 'Run these commands in order: "composer install --optimize-autoloader --no-dev" to install dependencies, "php artisan config:cache" to cache configuration, "php artisan route:cache" to cache routes, "php artisan view:cache" to cache views, and "php artisan migrate --force" to run database migrations. Also ensure your storage and bootstrap/cache directories are writable by the web server.',
            ],
            [
                'question' => 'How do I set up a queue worker for background jobs?',
                'answer' => 'First, configure your queue driver in .env (QUEUE_CONNECTION=database or redis). Run "php artisan queue:table" and "php artisan migrate" if using the database driver. In production, use a process manager like Supervisor to keep your queue worker running: "php artisan queue:work --sleep=3 --tries=3 --max-time=3600". Configure Supervisor to restart the worker automatically if it fails.',
            ],
            [
                'question' => 'Why am I getting a "500 Internal Server Error" after deployment?',
                'answer' => 'Common causes include: incorrect file permissions (storage and bootstrap/cache need to be writable), missing .env file or APP_KEY, missing PHP extensions, incorrect web server configuration, or cached configuration from development. Check your Laravel log at storage/logs/laravel.log for specific errors. Run "php artisan config:clear" and "php artisan cache:clear" to reset caches.',
            ],
            [
                'question' => 'How should I handle file uploads and storage in production?',
                'answer' => 'Configure the FILESYSTEM_DISK in your .env file. For local storage, ensure storage/app/public is linked to public/storage by running "php artisan storage:link". For scalable deployments, consider using cloud storage like Amazon S3 (install league/flysystem-aws-s3-v3). Always validate file uploads, limit file sizes, and scan for malware in production environments.',
            ],
            [
                'question' => 'What\'s the recommended way to handle database migrations in production?',
                'answer' => 'Always backup your database before running migrations. Use "php artisan migrate --force" in production (the --force flag is required in production). For zero-downtime deployments, write migrations that are backwards compatible. Avoid destructive migrations during peak hours. Consider using "php artisan migrate:status" to check pending migrations before applying them.',
            ],
            [
                'question' => 'How do I optimize my Laravel application for better performance?',
                'answer' => 'Enable all caching: config:cache, route:cache, view:cache. Use OPcache for PHP bytecode caching. Implement database query optimization with eager loading to prevent N+1 queries. Use Redis for session and cache storage instead of file-based storage. Enable MySQL query cache and optimize your database indexes. Consider using Laravel Octane for significant performance improvements.',
            ],
            [
                'question' => 'How do I configure HTTPS/SSL for my Laravel application?',
                'answer' => 'Obtain an SSL certificate (Let\'s Encrypt is free). Configure your web server (Nginx/Apache) to serve HTTPS. In Laravel, set APP_URL to use https:// in your .env file. Add "URL::forceScheme(\'https\')" in AppServiceProvider for HTTPS enforcement. Use the TrustProxies middleware if behind a load balancer or reverse proxy. Set SESSION_SECURE_COOKIE=true in production.',
            ],
            [
                'question' => 'What security measures should I implement for a production Laravel application?',
                'answer' => 'Set APP_DEBUG=false and APP_ENV=production in .env. Keep Laravel and all packages updated. Use CSRF protection (enabled by default). Implement rate limiting on API routes. Use prepared statements (Eloquent does this automatically). Configure proper CORS settings. Set secure cookie flags. Use strong passwords and consider 2FA. Regularly backup your database and files. Review your application with "php artisan security:check" if available.',
            ],
        ];

        foreach ($faqs as $index => $faq) {
            Faq::create([
                'question' => $faq['question'],
                'answer' => $faq['answer'],
                'is_published' => true,
                'sort_order' => $index + 1,
            ]);
        }
    }
}

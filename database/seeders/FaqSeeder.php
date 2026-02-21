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
                'question' => 'Domain Registration',
                'answer' => '# Domain Registration

## Choosing a Domain Name

Keep it short and memorable (under 15 characters), use easy-to-spell words, and avoid hyphens and numbers when possible. Popular extensions include .com (most trusted), .net (tech services), .org (non-profits), and .io (startups).

## Registering Your Domain

1. Check availability using a registrar\'s search tool
2. Choose a registrar (Namecheap, GoDaddy, Cloudflare, etc.)
3. Create an account and add contact information
4. Enable WHOIS privacy protection and auto-renewal
5. Complete payment

## DNS Configuration

Configure basic DNS records:
- **A Record**: Points domain to an IP address
- **CNAME**: Points domain to another domain name
- **MX Record**: Mail exchange for email
- **TXT Record**: Verification and SPF records

DNS changes may take 24-48 hours to propagate.

## Security & Renewal

Enable two-factor authentication on your registrar account, use WHOIS privacy, enable domain lock, and set up expiration alerts. Enable auto-renewal or set calendar reminders 30 days before expiration.',
            ],
            [
                'question' => 'PHP Script Installation',
                'answer' => '# PHP Script Installation

## Prerequisites

- PHP 8.1 or higher
- MySQL 5.7+ or MariaDB 10.3+
- Web server (Apache or Nginx)
- 256MB+ PHP memory limit
- Cron job access (if script requires scheduled tasks)
- Required extensions: cURL, JSON, Mbstring, OpenSSL, PDO, XML (check script documentation for specific requirements)

## Installation Steps

1. **Download & Upload**: Extract files and upload to your web directory (e.g., `/home/username/public_html/` or `/var/www/html/`)

2. **Create Database**: Create a MySQL database and user with full privileges using your hosting control panel or phpMyAdmin

3. **Configure Script**: Edit the configuration file (usually `config.php`, `settings.php`, or similar) and update database credentials, site URL, and any required settings

4. **Run Installation**: Visit the installation page (e.g., `install.php` or `setup.php`) in your browser and follow the on-screen instructions, or import the provided SQL file using phpMyAdmin

5. **Set Up Cron Job** (if required): Add a cron job for scheduled tasks: `* * * * * /usr/bin/php /path/to/cron.php` (adjust path and frequency as needed)

6. **Set Permissions**: Set appropriate file permissions using your FTP client or file manager. Typically: `chmod 644` for files and `chmod 755` for directories. For writable directories (uploads, cache, logs): `chmod 755` or `777` depending on server configuration

## Common Issues

- **500 Internal Server Error**: Check file permissions, verify PHP version meets requirements, review error logs, and ensure all required PHP extensions are enabled
- **Database Connection**: Verify database credentials in configuration file, ensure user has proper privileges, check DB_HOST (may be `localhost` or IP address), and confirm database exists
- **Files Not Loading**: Check file paths in configuration, verify all files were uploaded correctly, and ensure directory structure is intact
- **White Screen**: Enable error reporting in php.ini or add error_reporting(E_ALL) and ini_set(\'display_errors\', 1) to debug

## Maintenance

Regularly backup your database and files. Keep the script updated by checking for new versions and following the update instructions provided by the developer.',
            ],
            [
                'question' => 'Troubleshooting Guide',
                'answer' => '# Common Troubleshooting

## Getting Started

Clear browser cache, check browser console for errors, try a different browser, and note the exact error message. Application logs are typically in `logs/` or `storage/logs/` directory.

## Installation Issues

**500 Internal Server Error**: Check file permissions (`chmod -R 775 storage cache logs`), verify configuration file exists with valid settings, and review error logs.

**Database Connection**: Verify credentials in configuration file, ensure user has proper privileges, check DB_HOST (may be `localhost` or IP address), and confirm database exists.

**404 Not Found**: Verify mod_rewrite is enabled, check `.htaccess` is present (for Apache), ensure virtual host points to correct directory, and clear browser cache.

## Runtime Issues

**Scheduled Tasks Not Running**: Verify cron job runs every minute, test the scheduled task script manually, and review scheduler logs.

**Email Not Sending**: Verify mail configuration in configuration file, check credentials, test email functionality using a test script, review mail logs, and check spam folder.

**File Uploads Failing**: Check PHP upload settings (`upload_max_filesize`, `post_max_size`), verify storage permissions (775), and ensure upload directory exists and is writable.

**Session/Login Issues**: Clear browser cookies and cache, check session configuration in configuration file, verify sessions directory is writable, and try incognito mode.

## Performance

**Slow Page Loads**: Enable application caching (check application documentation), check database queries, verify OPcache is enabled, and optimize images.

**High Memory Usage**: Increase PHP memory limit in `php.ini`, optimize database queries, clear old logs and cache, and check for memory leaks.

**Background Jobs Stuck**: Verify background job processor is running, check queue configuration in configuration file, start the worker process, and configure Supervisor or similar for production.

## Security

**Mixed Content Warnings**: Ensure SSL certificate is installed, force HTTPS, and update HTTP URLs to HTTPS.

**Permission Denied**: Check file and directory permissions, verify ownership, ensure configuration file is not publicly accessible, and review web server configuration.

## Getting Help

Check logs, search error messages online, review documentation, and submit support tickets with error details, reproduction steps, screenshots, and environment information.',
            ],
            [
                'question' => 'Account Management',
                'answer' => '# Account Management

## Creating Your Account

Register with your email and a strong password (minimum 8 characters, mix of letters, numbers, and symbols). Check your inbox (and spam folder) for the verification link and click it within 24 hours.

## Profile Settings

Update personal information (name, email, phone, timezone, language) in Account Settings. Change your password by entering your current password, then the new password twice. Use password best practices: 12+ characters, mixed case, numbers and symbols, avoid personal info, and use a password manager.

## Troubleshooting

**Can\'t Log In**: Verify credentials, reset password via "Forgot Password", clear browser cache, try a different browser, or check if account is locked.

**Not Receiving Emails**: Check spam folder, verify email address, add sender to whitelist, check notification settings, or contact support.

## Best Practices

Use a unique strong password, don\'t share credentials, log out from shared devices, and monitor account activity. Keep contact information current, review security settings periodically, and regularly back up important data.',
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

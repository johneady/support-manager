<?php

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
    public function run(): void
    {
        $faqs = [
            // Original Laravel FAQs (10)
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
            // Billing Documents (4)
            [
                'question' => 'Billing Overview',
                'answer' => '# Billing Overview

This document provides a high-level overview of the billing system for our applications.

## Billing Cycle

Billing is processed on a monthly basis. All charges are calculated at the end of each billing cycle and invoices are generated automatically.

## Payment Methods

We accept the following payment methods:
- Credit/Debit Cards (Visa, MasterCard, American Express)
- PayPal
- Bank Transfer (for enterprise accounts)

## Invoice Delivery

Invoices are delivered via email to the account holder\'s registered email address. You can also access invoices through your account dashboard.

## Late Payments

Payments are due within 30 days of invoice date. Late payments may incur a late fee of 1.5% per month on the outstanding balance. Accounts with payments overdue by 60 days or more may be suspended.',
            ],
            [
                'question' => 'Subscription Plans',
                'answer' => '# Subscription Plans

We offer several subscription tiers to meet different needs.

## Basic Plan

- **Price**: $19/month
- **Features**: Single project, 5 users, basic support
- **Storage**: 10GB
- **Bandwidth**: 50GB/month

## Professional Plan

- **Price**: $49/month
- **Features**: 5 projects, 25 users, priority support
- **Storage**: 50GB
- **Bandwidth**: 200GB/month

## Enterprise Plan

- **Price**: $149/month
- **Features**: Unlimited projects, unlimited users, 24/7 dedicated support
- **Storage**: 500GB
- **Bandwidth**: Unlimited

## Custom Plans

For organizations with specific requirements, we offer custom plans. Contact our sales team for a quote.

## Upgrading/Downgrading

You can change your plan at any time. Changes take effect at the start of the next billing cycle. Upgrades are prorated; downgrades are not.',
            ],
            [
                'question' => 'Refund Policy',
                'answer' => '# Refund Policy

We want you to be satisfied with our services. Here\'s our refund policy.

## 30-Day Money-Back Guarantee

New subscriptions are eligible for a full refund within 30 days of the initial purchase. No questions asked.

## Pro-Rated Refunds

After the initial 30-day period, refunds are calculated on a pro-rated basis based on unused time in the current billing cycle.

## Refund Process

To request a refund:
1. Submit a support ticket with your account details
2. Specify the reason for the refund request
3. Our team will review and process within 5-7 business days

## Non-Refundable Items

- Setup fees
- Custom development work
- Third-party services
- Add-on purchases

## Account Cancellation

Upon cancellation, you will retain access until the end of your current billing period. No refunds are provided for partial months.',
            ],
            [
                'question' => 'Billing Support',
                'answer' => '# Billing Support

Need help with billing? Here\'s how to get assistance.

## Contact Methods

### Email
Send billing inquiries to: billing@example.com

### Support Ticket
Submit a billing ticket through your account dashboard. Select "Billing" as the category.

### Phone
Call our billing department: 1-800-BILLING (1-800-245-5464)
Hours: Monday-Friday, 9AM-5PM EST

## Common Issues

### Invoice Not Received
- Check your spam folder
- Verify your email address in account settings
- Request a resend through the dashboard

### Payment Failed
- Verify payment method is valid
- Check with your bank/card issuer
- Update payment method in account settings

### Plan Changes
- Changes take effect next billing cycle
- Upgrades are prorated
- Downgrades are not prorated

## Dispute Resolution

If you believe there\'s an error on your invoice, please contact us within 30 days of the invoice date. We\'ll investigate and resolve any legitimate discrepancies promptly.',
            ],
            // Pet Adoption Documents (4)
            [
                'question' => 'Pet Adoption System Overview',
                'answer' => '# Pet Adoption Management System Overview

The Pet Adoption Management System is a comprehensive Laravel-based application designed to streamline pet adoption operations, foster care management, and assistance applications.

## Core Features

### Pet Management
- Complete pet profiles with photos, medical records, and behavioral notes
- Adoption application tracking and approval workflows
- Foster care assignment and monitoring
- Medical treatment records and vaccination schedules

### User Management
- Adopter registration and verification
- Foster parent applications and background checks
- Volunteer coordination and scheduling
- Staff role-based access control

### Application Processing
- Online adoption applications
- Application review and approval workflows
- Home visit scheduling and documentation
- Adoption contract generation

### Reporting & Analytics
- Adoption statistics and trends
- Foster care utilization metrics
- Volunteer hours tracking
- Financial reporting for donations and grants

## Technical Architecture

Built on Laravel 12 with PHP 8.3+, the system uses MySQL for data storage and includes:
- Queue-based job processing for background tasks
- Scheduled tasks for automated notifications
- File storage for pet photos and documents
- Email notifications for application updates

## Security Features

- Secure user authentication with two-factor authentication
- Role-based access control for staff and volunteers
- Encrypted sensitive data storage
- Audit logging for all critical operations',
            ],
            [
                'question' => 'Pet Adoption Installation Guide',
                'answer' => '# Pet Adoption System Installation Guide

This guide covers installing the Pet Adoption Management System on shared hosting or VPS environments.

## Prerequisites

- PHP 8.2 or higher
- MySQL 5.7+ or MariaDB
- Cron job access (mandatory)
- File manager or FTP access
- Minimum 256MB PHP memory limit

## Installation Steps

### 1. Server Compatibility Test
Upload `public/shared_install.php` to your server and access it via browser to verify all requirements are met.

### 2. File Upload
Extract the application files and upload to a directory outside your public web root (e.g., `/home/username/pet-adoption/`).

### 3. Public Directory Configuration
Choose one of three options:
- **Option A (Recommended)**: Create a symbolic link from `public_html` to `pet-adoption/public`
- **Option B**: Move public folder contents to your web directory and update paths in `index.php`
- **Option C**: Configure custom document root in hosting control panel

### 4. Database Setup
Create a new MySQL database and user with all privileges via your hosting control panel.

### 5. Environment Configuration
Copy `.env.example` to `.env` and update:
- APP_NAME="Pet Adoption"
- Database credentials
- First user account details
- Generate APP_KEY using `php artisan key:generate`

### 6. Run Installation
Visit `https://yourdomain.com/update.php` to run migrations, create storage symlink, and optimize the application.

### 7. Cron Job Setup (MANDATORY)
Add a cron job running every minute:
```bash
/usr/bin/php /home/username/pet-adoption/artisan schedule:run >> /dev/null 2>&1
```

### 8. File Permissions
Set `storage/` and `bootstrap/cache/` to 775, `.env` to 644.

## Post-Installation

- Verify storage symlink is working
- Test file uploads
- Configure email settings
- Set up backup procedures',
            ],
            [
                'question' => 'Pet Adoption Features',
                'answer' => '# Pet Adoption System Features

A comprehensive breakdown of the Pet Adoption Management System\'s capabilities.

## Pet Management

### Pet Profiles
- Detailed pet information including breed, age, gender, weight
- Multiple photo uploads with gallery view
- Medical history and vaccination records
- Behavioral assessments and notes
- Special needs and dietary requirements

### Adoption Workflow
- Online adoption application forms
- Application status tracking (Pending, Under Review, Approved, Rejected)
- Home visit scheduling and documentation
- Adoption contract generation with e-signature support
- Post-adoption follow-up system

### Foster Care Management
- Foster parent registration and vetting
- Foster assignment and tracking
- Foster care duration monitoring
- Foster home inspection records
- Foster reimbursement tracking

## User Management

### Adopter Portal
- Browse available pets with advanced filters
- Save favorite pets to wishlist
- Submit adoption applications
- Track application status
- View adoption history

### Volunteer Management
- Volunteer registration and availability
- Shift scheduling and time tracking
- Task assignment system
- Volunteer hours reporting

### Staff Administration
- Role-based access control (Admin, Staff, Volunteer)
- Permission management per role
- Activity audit logs
- Performance metrics dashboard

## Communication Tools

### Automated Notifications
- Email notifications for application updates
- SMS alerts for urgent matters
- In-app messaging system
- Reminder scheduling for appointments

### Document Management
- Generate PDF contracts and agreements
- Upload and store supporting documents
- Document version control
- Secure document sharing with applicants

## Reporting & Analytics

### Operational Reports
- Monthly adoption statistics
- Foster care utilization rates
- Volunteer hours summary
- Application conversion rates

### Financial Reports
- Donation tracking and reporting
- Grant management
- Expense categorization
- Budget vs. actual analysis',
            ],
            [
                'question' => 'Pet Adoption Troubleshooting',
                'answer' => '# Pet Adoption System Troubleshooting

Common issues and solutions for the Pet Adoption Management System.

## Installation Issues

### 500 Internal Server Error
**Symptoms**: All pages show 500 error
**Solutions**:
- Check `storage/` and `bootstrap/cache/` permissions (should be 775)
- Verify `.env` file exists and contains valid APP_KEY
- Check `.htaccess` file is present and correct
- Review error logs in `storage/logs/laravel.log`

### Database Connection Errors
**Symptoms**: Unable to connect to database
**Solutions**:
- Verify database credentials in `.env` file
- Ensure database user has proper privileges
- Check DB_HOST (may be `localhost` or IP address)
- Confirm database exists and is accessible

### Images Not Displaying (404)
**Symptoms**: Uploaded pet photos show 404 errors
**Solutions**:
- Verify storage symlink exists (`public/storage` → `storage/app/public`)
- Check storage directory permissions
- If symlink not supported, use file copy or sync script
- Ensure cron job for storage sync is running

## Runtime Issues

### Scheduled Tasks Not Running
**Symptoms**: Automated notifications not sent, cleanup jobs not executing
**Solutions**:
- Verify cron job is configured correctly
- Check cron job runs every minute (`* * * * *`)
- Test cron job manually: `php artisan schedule:run`
- Review scheduler logs in storage/logs/

### Email Not Sending
**Symptoms**: Notifications not delivered
**Solutions**:
- Verify mail configuration in `.env`
- Check mail credentials are correct
- Test mail settings: `php artisan tinker` → `Mail::raw(\'Test\', fn($m) => $m->to(\'test@example.com\'))`
- Review mail logs in `storage/logs/laravel.log`
- Check spam folder

### Queue Jobs Not Processing
**Symptoms**: Background tasks stuck in queue
**Solutions**:
- Verify queue worker is running
- Check QUEUE_CONNECTION in `.env`
- Start queue worker: `php artisan queue:work`
- Configure Supervisor for production to keep worker running

## Performance Issues

### Slow Page Load Times
**Solutions**:
- Enable caching: `php artisan config:cache`, `route:cache`, `view:cache`
- Clear cache: `php artisan cache:clear`
- Check database query performance
- Verify OPcache is enabled
- Consider upgrading hosting resources

### High Memory Usage
**Solutions**:
- Increase PHP memory limit in `.htaccess` or php.ini
- Optimize database queries
- Clear old logs and cache
- Review and optimize image uploads

## Maintenance Mode

### Enable Maintenance Mode
```php
// Create public/down.php
<?php
require __DIR__.\'/../vendor/autoload.php\';
$app = require_once __DIR__.\'/../bootstrap/app.php\';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->call(\'down\');
echo \'Maintenance mode enabled. DELETE THIS FILE AFTER UPDATE!\';
```

### Disable Maintenance Mode
```php
// Create public/up.php
<?php
require __DIR__.\'/../vendor/autoload.php\';
$app = require_once __DIR__.\'/../bootstrap/app.php\';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->call(\'up\');
echo \'Application is now live! DELETE THIS FILE!\';
```

## Getting Additional Help

If issues persist:
1. Check the application logs: `storage/logs/laravel.log`
2. Review browser console for JavaScript errors
3. Submit a support ticket with detailed error information
4. Include screenshots when applicable',
            ],
            // Affiliate Master Documents (4)
            [
                'question' => 'Affiliate Master System Overview',
                'answer' => '# Affiliate Master System Overview

Affiliate Master is a comprehensive Laravel-based affiliate management platform designed to track, manage, and optimize affiliate programs.

## Core Features

### Affiliate Management
- Affiliate registration and onboarding
- Tiered commission structures
- Performance-based bonuses
- Multi-level referral tracking
- Affiliate status management (Active, Suspended, Pending)

### Tracking & Analytics
- Real-time click tracking
- Conversion tracking and attribution
- Revenue and commission calculations
- Performance metrics dashboard
- Custom reporting capabilities

### Campaign Management
- Create and manage affiliate campaigns
- Campaign-specific tracking links
- A/B testing for creatives
- Geo-targeting options
- Campaign performance analytics

### Payout Management
- Automated commission calculations
- Multiple payout methods (PayPal, Bank Transfer, Check)
- Minimum payout thresholds
- Payout history and reconciliation
- Tax document generation

## Technical Architecture

Built on Laravel 12 with PHP 8.3+, the system includes:
- MySQL database for affiliate and transaction data
- Redis for caching and session management
- Queue-based job processing for payouts
- Scheduled tasks for commission calculations
- Secure payment gateway integration

## Security Features

- Secure affiliate authentication
- Fraud detection and prevention
- IP tracking and geolocation
- Encrypted sensitive data
- Comprehensive audit logging

## Integration Capabilities

- E-commerce platform integrations
- CRM system connections
- Email marketing platform sync
- Third-party analytics integration
- Custom API access for developers',
            ],
            [
                'question' => 'Affiliate Master Installation Guide',
                'answer' => '# Affiliate Master Installation Guide

Complete installation instructions for the Affiliate Master platform.

## Prerequisites

- PHP 8.2 or higher
- MySQL 5.7+ or MariaDB
- Cron job access (mandatory)
- File manager or FTP access
- Minimum 256MB PHP memory limit

## Installation Steps

### 1. Server Compatibility Test
Upload `public/shared_install.php` to your server and access it via browser to verify all requirements are met.

### 2. File Upload
Extract the application files and upload to a directory outside your public web root (e.g., `/home/username/affiliate-master/`).

### 3. Public Directory Configuration
Choose one of three options:
- **Option A (Recommended)**: Create a symbolic link from `public_html` to `affiliate-master/public`
- **Option B**: Move public folder contents to your web directory and update paths in `index.php`
- **Option C**: Configure custom document root in hosting control panel

### 4. Database Setup
Create a new MySQL database and user with all privileges via your hosting control panel.

### 5. Environment Configuration
Copy `.env.example` to `.env` and update:
```env
APP_NAME="Affiliate Master"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Payment Gateway Configuration
PAYMENT_GATEWAY=stripe
STRIPE_PUBLIC_KEY=pk_live_xxx
STRIPE_SECRET_KEY=sk_live_xxx
```

### 6. Run Installation
Visit `https://yourdomain.com/update.php` to run migrations, create storage symlink, and optimize the application.

### 7. Cron Job Setup (MANDATORY)
Add a cron job running every minute:
```bash
/usr/bin/php /home/username/affiliate-master/artisan schedule:run >> /dev/null 2>&1
```

### 8. File Permissions
Set `storage/` and `bootstrap/cache/` to 775, `.env` to 644.

## Post-Installation Configuration

### Payment Gateway Setup
1. Create accounts with payment providers (Stripe, PayPal, etc.)
2. Add API keys to `.env` file
3. Configure payout methods in admin panel
4. Set minimum payout thresholds

### Email Configuration
Configure SMTP settings in `.env` for affiliate notifications:
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your_username
MAIL_PASSWORD=your_password
```

### Security Settings
- Enable two-factor authentication for admin accounts
- Configure rate limiting on public endpoints
- Set up SSL certificate
- Configure backup schedule

## Testing

After installation:
1. Test affiliate registration flow
2. Verify tracking links work correctly
3. Test commission calculations
4. Verify email notifications are sent
5. Test payout process in sandbox mode',
            ],
            [
                'question' => 'Affiliate Master Features',
                'answer' => '# Affiliate Master Features

Comprehensive feature breakdown for the Affiliate Master platform.

## Affiliate Management

### Registration & Onboarding
- Customizable registration forms
- Automatic approval or manual review
- Welcome email sequences
- Affiliate agreement acceptance
- Tax form collection (W-9, W-8BEN)

### Commission Structures
- Percentage-based commissions
- Fixed amount per sale
- Tiered commission rates
- Performance bonuses
- Recurring commission options
- Cookie duration configuration

### Affiliate Portal
- Dashboard with performance metrics
- Tracking link generator
- Creative asset library
- Earnings and payout history
- Real-time statistics
- Referral tracking

## Tracking & Analytics

### Click Tracking
- Real-time click monitoring
- Unique vs. total clicks
- Conversion rate tracking
- Geographic distribution
- Device and browser analytics
- Referrer source tracking

### Conversion Tracking
- Multi-touch attribution
- Last-click attribution
- First-click attribution
- Custom attribution windows
- Conversion value tracking
- Product-level tracking

### Reporting
- Custom date range reports
- Export to CSV/PDF
- Scheduled report delivery
- Performance comparison
- Trend analysis
- ROI calculations

## Campaign Management

### Campaign Creation
- Campaign-specific commission rates
- Custom tracking domains
- A/B testing capabilities
- Geo-targeting rules
- Device targeting options
- Time-based campaigns

### Creative Management
- Banner ads (multiple sizes)
- Text links
- Email templates
- Social media assets
- Video creatives
- Dynamic content insertion

## Payout Management

### Commission Calculation
- Automated daily/weekly calculations
- Commission adjustments
- Clawback processing
- Bonus calculations
- Tax withholding (if applicable)

### Payout Methods
- PayPal integration
- Bank transfer (ACH, SEPA)
- Check by mail
- Cryptocurrency options
- Custom payout methods

### Payout Processing
- Minimum payout thresholds
- Payout scheduling (weekly, bi-weekly, monthly)
- Batch processing
- Payout notifications
- Transaction history

## Fraud Prevention

### Detection Systems
- IP address monitoring
- Device fingerprinting
- Suspicious activity alerts
- Velocity checks
- Pattern recognition
- Manual review queue

### Prevention Measures
- CAPTCHA on registration
- Email verification
- Phone verification (optional)
- Geographic restrictions
- Traffic quality scoring

## Integration Capabilities

### E-commerce Platforms
- Shopify integration
- WooCommerce integration
- Magento integration
- Custom API integration
- Webhook support

### Marketing Tools
- Email marketing sync
- CRM integration
- Analytics platform connection
- Social media tracking
- Mobile app SDK

## Admin Features

### User Management
- Role-based access control
- Permission management
- Activity logs
- Audit trails
- Bulk actions

### System Configuration
- Global commission settings
- Payment gateway configuration
- Email template customization
- Notification preferences
- API key management

### Support Tools
- Affiliate messaging system
- Support ticket integration
- Knowledge base
- FAQ management
- Resource library',
            ],
            [
                'question' => 'Affiliate Master Troubleshooting',
                'answer' => '# Affiliate Master Troubleshooting

Common issues and solutions for the Affiliate Master platform.

## Installation Issues

### 500 Internal Server Error
**Symptoms**: All pages show 500 error
**Solutions**:
- Check `storage/` and `bootstrap/cache/` permissions (should be 775)
- Verify `.env` file exists and contains valid APP_KEY
- Check `.htaccess` file is present and correct
- Review error logs in `storage/logs/laravel.log`

### Database Connection Errors
**Symptoms**: Unable to connect to database
**Solutions**:
- Verify database credentials in `.env` file
- Ensure database user has proper privileges
- Check DB_HOST (may be `localhost` or IP address)
- Confirm database exists and is accessible

### Images Not Displaying (404)
**Symptoms**: Uploaded creative assets show 404 errors
**Solutions**:
- Verify storage symlink exists (`public/storage` → `storage/app/public`)
- Check storage directory permissions
- If symlink not supported, use file copy or sync script
- Ensure cron job for storage sync is running

## Runtime Issues

### Tracking Not Working
**Symptoms**: Affiliate clicks not being recorded
**Solutions**:
- Verify tracking links are correctly formatted
- Check JavaScript tracking code is present
- Review browser console for errors
- Ensure cookies are enabled in browser
- Check ad blockers are not interfering
- Verify affiliate is active (not suspended)

### Commissions Not Calculating
**Symptoms**: Sales not generating commissions
**Solutions**:
- Verify commission structure is configured
- Check conversion tracking is working
- Review attribution window settings
- Ensure product is eligible for commission
- Check affiliate status is active
- Review commission calculation logs

### Payouts Not Processing
**Symptoms**: Scheduled payouts not executing
**Solutions**:
- Verify cron job is configured correctly
- Check payout schedule settings
- Ensure minimum threshold is met
- Verify payment gateway credentials
- Review payout queue: `php artisan queue:work`
- Check payout logs in storage

### Email Notifications Not Sending
**Symptoms**: Affiliates not receiving emails
**Solutions**:
- Verify mail configuration in `.env`
- Check mail credentials are correct
- Test mail settings: `php artisan tinker`
- Review mail logs in `storage/logs/laravel.log`
- Check spam folder
- Verify email templates are configured

## Payment Gateway Issues

### Stripe Integration Problems
**Symptoms**: Payouts failing via Stripe
**Solutions**:
- Verify API keys are correct (use live keys for production)
- Check Stripe account is verified
- Ensure sufficient balance in Stripe account
- Review Stripe dashboard for errors
- Test with Stripe CLI: `stripe login` → `stripe trigger payout.created`

### PayPal Integration Problems
**Symptoms**: PayPal payouts failing
**Solutions**:
- Verify PayPal API credentials
- Check PayPal account is business verified
- Ensure PayPal email is confirmed
- Review PayPal IPN settings
- Test PayPal webhook endpoint

## Performance Issues

### Slow Page Load Times
**Solutions**:
- Enable caching: `php artisan config:cache`, `route:cache`, `view:cache`
- Clear cache: `php artisan cache:clear`
- Check database query performance
- Verify OPcache is enabled
- Consider upgrading hosting resources
- Optimize image sizes for creative assets

### High Database Load
**Solutions**:
- Add database indexes on frequently queried columns
- Implement query result caching
- Archive old tracking data
- Use read replicas for reporting queries
- Optimize commission calculation queries

## Fraud Detection Issues

### False Positives
**Symptoms**: Legitimate affiliates flagged as suspicious
**Solutions**:
- Review fraud detection thresholds
- Whitelist trusted affiliate IPs
- Adjust velocity check settings
- Review manual review queue regularly
- Provide feedback to improve detection

### False Negatives
**Symptoms**: Fraudulent activity not detected
**Solutions**:
- Lower fraud detection thresholds
- Enable additional detection rules
- Implement CAPTCHA on registration
- Require email verification
- Enable phone verification for high-risk affiliates

## Maintenance Mode

### Enable Maintenance Mode
```php
// Create public/down.php
<?php
require __DIR__.\'/../vendor/autoload.php\';
$app = require_once __DIR__.\'/../bootstrap/app.php\';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->call(\'down\');
echo \'Maintenance mode enabled. DELETE THIS FILE AFTER UPDATE!\';
```

### Disable Maintenance Mode
```php
// Create public/up.php
<?php
require __DIR__.\'/../vendor/autoload.php\';
$app = require_once __DIR__.\'/../bootstrap/app.php\';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->call(\'up\');
echo \'Application is now live! DELETE THIS FILE!\';
```

## Getting Additional Help

If issues persist:
1. Check the application logs: `storage/logs/laravel.log`
2. Review browser console for JavaScript errors
3. Submit a support ticket with detailed error information
4. Include screenshots when applicable
5. Provide affiliate ID or transaction ID for tracking issues',
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

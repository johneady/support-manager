<div align="center">

# 🚢 Deployment Guide

---

</div>

This project uses [Laravel Envoy](https://laravel.com/docs/envoy) for streamlined deployment to production servers. Envoy provides a simple, fluent syntax for defining common tasks on remote servers.

## 📦 Setup

Before deploying, you need to configure both the production environment file and the Envoy configuration.

### 1️⃣ Configure Production Environment

Create a `.env.production` file in your project root with production-specific settings. This file will be copied to `.env` on the server during deployment.

**Required Environment Variables:**

```env
APP_NAME="Support Manager"
APP_ENV=production
APP_KEY=base64:your-generated-app-key
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database Configuration
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_production_database
DB_USERNAME=your_db_user
DB_PASSWORD=your_secure_password

# Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-smtp-username
MAIL_PASSWORD=your-smtp-password
MAIL_FROM_ADDRESS="support@your-domain.com"
MAIL_FROM_NAME="Support Manager"

# Queue Configuration
QUEUE_CONNECTION=database

# Session Configuration
SESSION_DRIVER=database
SESSION_LIFETIME=120
```

> 💡 **Important:** Generate a secure `APP_KEY` using `php artisan key:generate` on your local machine and copy it to the production environment file.

### 2️⃣ Configure Envoy

Update `Envoy.blade.php` with your server details:

**Server Configuration (Line 1):**
```php
@servers(['your-server-name.com' => 'user@your-server-ip -p 22', 'localhost' => '127.0.0.1'])
```

Replace with your actual server details:
- `your-server-name.com`: A descriptive name for your server
- `user`: Your SSH username
- `your-server-ip`: Your server's IP address or hostname
- `-p 22`: Your SSH port (change if using a custom port)

**Server Setup Configuration (Lines 4-10):**
```php
$servers = [
    'your-server-name.com' => [
        'path' => '/path/to/your/support-manager',
        'env' => '.env.production',
        'folder' => 'support-manager',
    ],
];
```

Update these values:
- `path`: The full path to your application directory on the server
- `env`: The name of your production environment file (default: `.env.production`)
- `folder`: The name of the application folder (default: `support-manager`)

> 📁 **Deployment Path Examples by Hosting Type:**
>
> The `path` should point to where the application files are deployed on your server. This varies by hosting provider:
>
> **cPanel / Shared Hosting:**
> ```php
> 'path' => '/home/username/public_html/support-manager',
> // or for subdomain:
> 'path' => '/home/username/support.example.com/support-manager',
> // or for addon domain:
> 'path' => '/home/username/example.com/support-manager',
> ```
>
> **Plesk Hosting:**
> ```php
> 'path' => '/var/www/vhosts/example.com/httpdocs/support-manager',
> // or for subdomain:
> 'path' => '/var/www/vhosts/example.com/subdomains/support/httpdocs/support-manager',
> ```
>
> **DirectAdmin:**
> ```php
> 'path' => '/home/username/domains/example.com/public_html/support-manager',
> // or for subdomain:
> 'path' => '/home/username/domains/support.example.com/public_html/support-manager',
> ```
>
> **VPS / Dedicated Server (Apache):**
> ```php
> 'path' => '/var/www/html/support-manager',
> // or custom DocumentRoot:
> 'path' => '/var/www/example.com/support-manager',
> ```
>
> **VPS / Dedicated Server (Nginx):**
> ```php
> 'path' => '/var/www/example.com/support-manager',
> // or using user home:
> 'path' => '/home/deploy/applications/support-manager',
> ```
>
> **Laravel Forge / Vapor:**
> ```php
> 'path' => '/home/forge/example.com',
> // Forge typically manages paths automatically
> ```
>
> **DigitalOcean App Platform / Render:**
> ```php
> 'path' => '/workspace',
> // These platforms typically use a standard workspace path
> ```
>
> **Local Development (for testing):**
> ```php
> 'path' => '/home/john/php/support-manager',
> ```
>
> **Note:** The `path` should point to the Laravel project root (containing `artisan`, `app/`, `public/`, etc.), NOT the `public/` directory itself. The web server's document root should be configured to point to the `public/` subdirectory within this path.

---

## 🎯 Available Envoy Tasks

### 🚀 Initial Installation

Deploy the application to a fresh server:

```bash
vendor/bin/envoy run install --server=your-server-name.com
```

This task will:
- ✅ Clone the repository
- ✅ Install Composer dependencies
- ✅ Copy `.env.production` to `.env`
- ✅ Run migrations and seed the database
- ✅ Build frontend assets
- ✅ Clean up temporary files

> ⚠️ **Warning:** The install task runs `db:seed --force`, which creates test data. Ensure your `.env.production` file does not contain the development seeder credentials in production.

### 🔄 Update Deployment

Deploy the latest changes to an existing installation:

```bash
vendor/bin/envoy run update --server=your-server-name.com
```

This task will:
- 📦 Create a backup of files and database
- 📥 Pull the latest code from git
- 📦 Install Composer dependencies (production only)
- 🗄️ Run migrations
- 🔨 Build frontend assets
- 🧹 Clean up temporary files
- ✅ Restore application availability

The update task automatically creates backups before deployment. If deployment fails, you can restore using the restore task.

### 💾 Backup

Create manual backups of files and database:

```bash
vendor/bin/envoy run backup --server=your-server-name.com
```

This task creates:
- 📦 A compressed tar.gz archive of application files
- 🗄️ A SQL dump of the database
- 📝 A `last_backup_info` file with backup locations

Backups are stored in `../backups/` relative to your application path. Old backups are automatically pruned, keeping only the most recent backup.

### 🔙 Restore

Restore from the last backup:

```bash
vendor/bin/envoy run restore --server=your-server-name.com
```

This task will:
- 📦 Restore files from the last backup
- 🗄️ Restore the database from the last backup
- 🧹 Clear and optimize configuration
- ✅ Bring the application back online

Use this if a deployment fails and you need to roll back to the previous state.

### ⚡ Individual Tasks

You can also run individual tasks:

```bash
vendor/bin/envoy run pull-and-deploy --server=your-server-name.com
```

---

## ⏰ Cron Job Setup

After initial installation, set up a cron job to run the Laravel scheduler every minute:

```bash
crontab -e
```

Add this line:

```cron
* * * * * php /path/to/your/support-manager/artisan schedule:run 1> /dev/null 2> /path/to/logs/cron_error.log
```

Replace `/path/to/your/support-manager` with your actual application path and `/path/to/logs/cron_error.log` with your desired log location.

---

## ✅ Deployment Checklist

Before deploying to production:

- [ ] Create `.env.production` with production settings
- [ ] Generate and set a secure `APP_KEY`
- [ ] Configure database credentials in `.env.production`
- [ ] Set up mail configuration in `.env.production`
- [ ] Update server details in `Envoy.blade.php`
- [ ] Ensure SSH access to the server is configured
- [ ] Verify database exists and user has proper permissions
- [ ] Set up the cron job for the scheduler
- [ ] Test the deployment process on a staging environment first

---

## 🔧 Troubleshooting

**Deployment fails during migration:**
- Check database credentials in `.env.production`
- Ensure database exists and user has proper permissions
- Review migration files for any issues

**Assets not loading after deployment:**
- Run `php artisan storage:link` on the server
- Check that `npm run build` completed successfully
- Verify `public/build` directory exists and contains compiled assets

**Queue jobs not processing:**
- Ensure `QUEUE_CONNECTION=database` in `.env.production`
- Set up a queue worker process or supervisor configuration
- Verify the jobs table exists

**Scheduler not running:**
- Verify the cron job is set up correctly
- Check that the path to `artisan` is correct
- Review the cron error log for issues

---

<div align="center">

[⬆ Back to README](README.md)

</div>

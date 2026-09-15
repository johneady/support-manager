<div align="center">

# 🎫 Support Manager

### ✨ A modern, open-source support ticket management system

[![Latest Stable Version](https://img.shields.io/badge/Laravel-13-red.svg)](https://laravel.com)
[![PHP Version](https://img.shields.io/badge/PHP-8.2+-blue.svg)](https://php.net)
[![Livewire](https://img.shields.io/badge/Livewire-4-pink.svg)](https://livewire.laravel.com)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

Manage customer support tickets, FAQs, and team members through a clean, intuitive interface.

---

</div>

## 📖 About

I created this project simply because I needed a very simple ticket system that met my needs (single developer). It supports multiple admins, but I didn't add features like ticket assignment, as I'm the only user! All tickets needing a response are in a single queue for all admins to see and manage. As it's only me, I never added the ability to assign tickets. Maybe that can be a future enhancement.

---

## 🚀 Features

| Feature | Description |
|---------|-------------|
| 🎯 **Ticket Management** | Create, track, and resolve support tickets with priority levels (Low, Medium, High), statuses (Open, Closed), and reference numbers (TX-1138-{id}) |
| 🗂️ **Ticket Categories** | Database-driven categories with color coding and sort ordering, managed from the admin panel |
| 📊 **Admin Dashboard** | Overview of open tickets, tickets needing response, and recently resolved issues, with a live sidebar badge for the response queue |
| 👤 **User Dashboard** | Customers can view and manage their own tickets |
| 🛠️ **Admin Panel** | Manage users, ticket categories, FAQs, and platform settings from dedicated admin pages |
| ⚙️ **Platform Settings** | Configure health check intervals and health alert email, inspect the active mail configuration, and send test emails |
| ❓ **FAQ System** | Markdown-powered FAQ pages with auto-slug generation, reading time estimates, and admin CRUD |
| ✉️ **User Invitations** | Invite team members via token-based email invitations |
| 🔐 **Two-Factor Authentication** | Built-in 2FA with recovery codes via Laravel Fortify |
| 📧 **Email Notifications** | Queued notifications for new tickets, replies, and auto-closures |
| ⏰ **Auto-Close Inactive Tickets** | Scheduled job to automatically close stale tickets |
| 🛡️ **Spam Protection** | Honeypot fields via Spatie Laravel Honeypot |
| ❤️ **Health Monitoring** | Health checks (including a mail transport check) via Spatie Laravel Health, an admin health page, alert email routing, and automatic health history pruning |
| 📄 **Legal Pages** | Built-in privacy policy and terms of service pages |
| 🌙 **Dark Mode** | Theme appearance settings with light/dark mode support |

---

## 🛠️ Tech Stack

| Layer | Technology | Version |
|-------|------------|---------|
| **Framework** | Laravel | 13 |
| **Frontend** | Livewire (Volt single-file components built in) | 4 |
| **UI Library** | Flux UI Free | - |
| **Styling** | Tailwind CSS | 4 |
| **Rich Text** | Tiptap Editor | 3 |
| **Authentication** | Laravel Fortify | 1 |
| **Database** | SQLite (dev) / MariaDB or MySQL (production) | - |
| **Testing** | Pest (with the browser plugin) | 5 |
| **Code Style** | Laravel Pint | 1 |
| **Static Analysis** | Larastan | 3 |
| **Asset Bundling** | Vite Plus | - |

---

## 📋 Requirements

- **PHP** >= 8.2
- **Composer**
- **Node.js** >= 22
- **NPM**
- **SQLite** (local development), or **MariaDB** / **MySQL** (other environments)

Optional, for the containerised deployment only:

- **Docker** with the Compose plugin — see [Deployment](#-deployment)

---

## 🚀 Installation

### 1️⃣ Clone the repository

```bash
git clone https://github.com/johneady/support-manager.git
cd support-manager
```

### 2️⃣ Install and setup

Run the composer setup command which handles dependency installation, environment configuration, database migration, and frontend asset building:

```bash
composer setup
```

> 💡 **Tip:** Optionally seed the database with sample data. For development, this simplifies the login flow by creating an admin user with simple credentials. This project leverages the [spatie/laravel-login-link](https://github.com/spatie/laravel-login-link) package to streamline development logins via one-click authentication links.

```bash
php artisan db:seed
```

> ⚠️ **Security Warning:** Never run the seeder in production, as this creates a significant security vulnerability with predictable credentials.

### 3️⃣ Start the application

For development with all services (server, scheduler, queue worker, log viewer, and Vite):

```bash
composer run dev
```

The application will be available at `http://localhost:8000`.

---

## ⚙️ Configuration

### 📧 Mail Configuration

Configure your mail driver in `.env` to enable email notifications:

```env
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-username
MAIL_PASSWORD=your-password
MAIL_FROM_ADDRESS="support@example.com"
MAIL_FROM_NAME="Support Manager"
```

### 👑 Admin Users

To create an admin user, register a new account and then promote it via Tinker:

```bash
php artisan tinker
>>> User::where('email', 'admin@example.com')->update(['is_admin' => true]);
```

---

## 💡 Developer Tips

### 📧 Previewing Emails

Use the `mail:preview` command to preview and send test emails during development. This command creates test data on-the-fly without saving to your database and sends emails immediately.

```bash
# Interactive mode - prompts for email and email type
php artisan mail:preview

# Send a specific email type
php artisan mail:preview password-reset
php artisan mail:preview new-ticket
php artisan mail:preview ticket-reply-to-customer

# Send to a specific email address
php artisan mail:preview --to=you@example.com new-ticket

# Send all email types at once
php artisan mail:preview --all
php artisan mail:preview --to=you@example.com --all
```

**Available Email Types:**

| Type | Description |
|------|-------------|
| `password-reset` | Password reset link (ResetPassword) |
| `email-verification` | Email verification link (VerifyEmail) |
| `new-ticket` | Admin notification for new support tickets |
| `ticket-reply-to-customer` | Reply notification to customer |
| `ticket-reply-to-admin` | Admin notification for customer replies |
| `ticket-auto-closed` | Ticket auto-closed notification |
| `user-invitation` | User invitation email |

The command uses Laravel Prompts for an interactive selection menu when run without arguments. All test data (users, tickets, replies) is created in-memory using model factories, so no database records are created.

---

## 🧪 Testing

This project uses [Pest](https://pestphp.com) for testing, with feature, unit, and browser test suites (browser tests live in `tests/Browser`).

```bash
# Run all tests
php artisan test

# Run tests with compact output
php artisan test --compact

# Run a specific test file
php artisan test --filter=TicketTest

# Run tests in parallel
vendor/bin/pest --parallel

# Run linting + static analysis + tests (CI pipeline)
composer test
```

---

## 🎨 Code Style

This project follows the [Laravel coding style](https://laravel.com/docs/contributions#coding-style) enforced by [Laravel Pint](https://laravel.com/docs/pint).

```bash
# Fix code style
vendor/bin/pint

# Check code style without fixing
vendor/bin/pint --test
```

### 🔍 Static Analysis

Static analysis is handled by [Larastan](https://github.com/larastan/larastan):

```bash
# Run static analysis
composer types:check
```

---

## 📁 Project Structure

```
app/
├── Actions/Fortify/       # Fortify actions (CreateNewUser, ResetUserPassword)
├── Concerns/              # Shared traits (validation rules, auth user resolution)
├── Console/Commands/      # Artisan commands (CloseInactiveTickets, PreviewMail)
├── Enums/                 # TicketStatus, TicketPriority
├── Health/                # Health checks and health alert routing
├── Http/Controllers/      # Web controllers (Dashboard, Faq, Health)
├── Http/Middleware/       # EnsureUserIsAdmin, SecurityHeaders
├── Jobs/                  # Background jobs (CloseInactiveTickets)
├── Livewire/              # Livewire class components (auth, settings)
├── Mail/                  # TestEmail mailable
├── Models/                # Eloquent models (Faq, Setting, Ticket, TicketCategory, TicketReply, User)
├── Notifications/         # Email notifications
├── Policies/              # Authorization policies
└── Providers/             # App and Fortify service providers

resources/views/
├── admin/                 # Admin panel pages (users, categories, FAQs, settings)
├── components/            # Blade components (incl. Volt ⚡ components with co-located tests)
├── emails/                # Email templates
├── layouts/               # App and auth layouts
├── livewire/              # Volt single-file components (⚡ prefixed)
├── partials/              # Shared view partials
└── tickets/               # Ticket management views

docker/                    # Container configuration (see Deployment)
├── entrypoint/            # Boot script and supervisor config
├── nginx/                 # Webserver vhost
└── php/                   # php.ini and php-fpm pool
```

---

## 🚢 Deployment

Two deployment paths are supported.

### 🐳 Docker (Dokploy)

The container image runs nginx and php-fpm under supervisor, with a second
container for the scheduler. Deploy `docker-compose.dokploy.yml` — never
`docker-compose.yml`, which is the local stack and embeds a throwaway database
password and app key.

Set these in Dokploy's environment settings before the first deploy; a missing
one fails the deploy with a named error rather than booting on a silent default:

| Variable | Notes |
| --- | --- |
| `APP_KEY` | At cutover, reuse the existing key — see the warning below |
| `APP_URL` | The `https://` domain Traefik serves |
| `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` | The Dokploy-managed database |
| `MAIL_MAILER`, `MAIL_FROM_ADDRESS` | `smtp` in production; `log` silently delivers nothing |
| `ALERTS_TO_ADDRESS` | Where failing health checks are sent |
| `TRUST_PROXIES` | `*` behind Traefik, so HTTPS is detected and HSTS is sent |

> ⚠️ **Migrating an existing site:** carry the **current** `APP_KEY` across
> rather than generating a new one. Sessions are encrypted
> (`SESSION_ENCRYPT=true`), so a different key signs every user out.

Point the Domains tab at port **80** on the `app` service; Dokploy injects the
Traefik labels and requests the certificate.

To verify the image locally before deploying:

```bash
docker compose --env-file .env.docker up --build -d
open http://localhost:8080
docker compose --env-file .env.docker down -v
```

Always pass `--env-file .env.docker`. Compose's default variable file is the
project-root `.env` — this application's own Laravel config — and reading it
would configure the containers from your development settings.

### 🖥️ Envoy (HestiaCP)

For the traditional deployment to a HestiaCP server, see the
[DEPLOYMENT.md](DEPLOYMENT.md) guide.

---

## 🤝 Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details on how to contribute to this project.

---

## 🔒 Security

If you discover a security vulnerability, please see [SECURITY.md](SECURITY.md) for reporting instructions. Do not open a public issue for security vulnerabilities.

---

## 📝 Changelog

Please see [CHANGELOG.md](CHANGELOG.md) for a history of notable changes.

---

## 📄 License

Support Manager is open-sourced software licensed under the [MIT license](LICENSE).

---

<div align="center">

**Made with ❤️ by [John Eady](https://github.com/johneady)**

[⬆ Back to top](#-support-manager)

</div>

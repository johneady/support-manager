# Support Manager

A modern, open-source support ticket management system built with Laravel 12, Livewire, and Flux UI. Manage customer support tickets, FAQs, and team members through a clean, reactive interface.

I created this project simply becuse I needed a very simple ticket system that met my needs (single developer). It supports multiple developers, but I didn't add any features like ticket assignment, as I'm the only user. All tickets needing a response are in a single queue for all admins to see and manage. As it's only me, I never added the ability to assign tickets. That could be a future feature.

## Features

- **Ticket Management** -- Create, track, and resolve support tickets with priority levels (Low, Medium, High) and categorization
- **Admin Dashboard** -- Overview of open tickets, tickets needing response, and recently resolved issues
- **User Dashboard** -- Customers can view and manage their own tickets
- **FAQ System** -- Markdown-powered FAQ pages with auto-slug generation and reading time estimates
- **User Invitations** -- Invite team members via token-based email invitations
- **Two-Factor Authentication** -- Built-in 2FA with recovery codes via Laravel Fortify
- **Email Notifications** -- Queued notifications for new tickets, replies, and auto-closures
- **Auto-Close Inactive Tickets** -- Scheduled job to automatically close stale tickets
- **Spam Protection** -- Honeypot fields via Spatie Laravel Honeypot
- **Health Monitoring** -- Application health checks via Spatie Laravel Health
- **Dark Mode** -- Theme appearance settings with light/dark mode support

## Tech Stack

| Layer | Technology |
|-------|------------|
| Framework | Laravel 12 |
| Frontend | Livewire 4, Flux UI Free, Tailwind CSS 4 |
| Rich Text | Tiptap Editor |
| Authentication | Laravel Fortify |
| Database | SQLite (default), MySQL/PostgreSQL supported |
| Testing | Pest 4 |
| Code Style | Laravel Pint |
| Asset Bundling | Vite 7 |

## Requirements

- PHP >= 8.2
- Composer
- Node.js >= 22
- NPM
- SQLite (default) or MySQL/PostgreSQL

## Installation

### 1. Clone the repository

```bash
git clone https://github.com/johneady/support-manager.git
cd support-manager
```

### 2. Install dependencies

Install all dependencies:

```bash
composer install
npm install
```

### 3. Environment setup

```bash
cp .env.example .env
php artisan key:generate
```

### 4. Database setup

The default configuration uses SQLite. The database file will be created automatically:

```bash
touch database/database.sqlite
php artisan migrate
```

Optionally seed the database with sample data:

```bash
php artisan db:seed
```

### 5. Build frontend assets

```bash
npm run build
```

### 6. Start the application

For development with all services (server, queue worker, log viewer, and Vite):

```bash
composer run dev
```

The application will be available at `http://localhost:8000`.

## Configuration

### Mail

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

### Queue

The default queue driver is `database`. For production, consider using Redis:

```env
QUEUE_CONNECTION=redis
```

Run the queue worker:

```bash
php artisan queue:work
```

### Admin Users

To create an admin user, register a new account and then promote it via Tinker:

```bash
php artisan tinker
>>> User::where('email', 'admin@example.com')->update(['is_admin' => true]);
```

Or use the invitation system from an existing admin account.

## Testing

This project uses [Pest](https://pestphp.com) for testing.

```bash
# Run all tests
php artisan test

# Run tests with compact output
php artisan test --compact

# Run a specific test file
php artisan test --filter=TicketTest

# Run tests in parallel
vendor/bin/pest --parallel

# Run linting + tests
composer test
```

## Code Style

This project follows the [Laravel coding style](https://laravel.com/docs/contributions#coding-style) enforced by [Laravel Pint](https://laravel.com/docs/pint).

```bash
# Fix code style
vendor/bin/pint

# Check code style without fixing
vendor/bin/pint --test
```

## Project Structure

```
app/
├── Console/Commands/     # Artisan commands
├── Enums/                # TicketStatus, TicketPriority
├── Http/Controllers/     # Web controllers
├── Jobs/                 # Background jobs (CloseInactiveTickets)
├── Livewire/             # Livewire components
├── Models/               # Eloquent models
├── Notifications/        # Email notifications
└── Policies/             # Authorization policies

resources/views/
├── admin/                # Admin panel views
├── components/           # Blade components
├── layouts/              # App and auth layouts
├── livewire/             # Livewire component views
└── tickets/              # Ticket management views
```

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details on how to contribute to this project.

## Security

If you discover a security vulnerability, please see [SECURITY.md](SECURITY.md) for reporting instructions. Do not open a public issue for security vulnerabilities.

## Changelog

Please see [CHANGELOG.md](CHANGELOG.md) for a history of notable changes.

## License

Support Manager is open-sourced software licensed under the [MIT license](LICENSE).

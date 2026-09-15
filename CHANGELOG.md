# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com), and this project adheres to [Semantic Versioning](https://semver.org).

## Version - 1.1.0

### Added

- Platform settings management in the admin panel
- Test email functionality with email configuration display in platform settings
- Configurable health check schedules and intervals, plus a health history pruning command
- `HealthAlertNotifiable` for dynamic health alert email routing
- Docker support for containerised deployment, with nginx and php-fpm configuration
- Browser smoke tests (Pest browser plugin) covering public, auth, and ticket flows
- PHPStan/Larastan static analysis configuration

### Changed

- Upgraded to Laravel 13, Livewire 4, and Flux 2, with dependencies updated to match
- Ticket replies now render in chronological order across all views
- Extracted FAQ route logic into a dedicated `FaqController`
- Refactored the admin user management component
- Extracted Envoy server configuration into a separate file
- Simplified deployment environment configuration and added an optimisation step
- Automated `APP_URL`, application key, and database credential handling during deployment
- Database options now target SQLite for development and MariaDB/MySQL elsewhere; PostgreSQL support removed
- Raised the minimum PHP version to 8.5 to match CI, Docker, and the installed dependencies
- Compressed Livewire JSON responses and enabled on-disk CLI opcode caching
- Reduced the `/up` healthcheck interval from 30s to 120s and the schedule heartbeat from every minute to every five
- Capped php-fpm at 4 workers and limited container memory to prevent host-wide OOM cascades
- Pointed the security contact at john@ instead of support@
- Removed queue checks from health monitoring and improved config error handling
- Refreshed views and components, including reduced FAQ hero padding and an updated terms-of-service page

### Fixed

- Use facade-level `Password::createToken` to satisfy static analysis
- Resolve Vite HMR host from `APP_URL` and clean up the stale `public/hot` file
- Reset to `origin/main` during deploys to avoid divergent-branch pull failures
- Correct the `ticket_categories` migration file date
- Remove a duplicate `ticket_replies` migration

## Version - 1.0.1

### Added Envoy Deployments

- Add laravel/envoy package to require-dev
- Create Envoy.blade.php deployment script
- Add DEPLOYMENT.md documentation with setup instructions
- Update CHANGELOG and README to reflect deployment capabilities

### Updated FAQ Seeder

- Enhanced FAQ seeder with comprehensive documentation content
- Added detailed FAQ entries covering domain registration, PHP script installation, troubleshooting guide, and account management
- All FAQ entries include markdown-formatted answers with structured sections, code examples, and best practices

## Version - 1.0.0

### Added

- Ticket management system with priorities (Low, Medium, High) and statuses (Open, Closed)
- Database-driven ticket categories with color coding and sort ordering
- Ticket reference numbers (TX-1138-{id})
- Admin dashboard with ticket statistics and response tracking
- User dashboard with personal ticket overview
- FAQ system with markdown rendering and auto-slug generation
- User invitation system with token-based email invitations
- Two-factor authentication with recovery codes
- Email notifications for tickets, replies, and auto-closures
- Auto-close inactive tickets scheduled job
- Spam protection via honeypot fields
- Application health monitoring
- Dark mode / appearance settings
- CI workflows for testing and linting

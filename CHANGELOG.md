# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com), and this project adheres to [Semantic Versioning](https://semver.org).

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

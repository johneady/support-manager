# Contributing to Support Manager

Thank you for your interest in contributing! This guide will help you get started.

## Development Setup

1. Fork the repository and clone your fork
2. Follow the [installation instructions](README.md#installation)
3. Create a new branch for your feature or fix:

```bash
git checkout -b feature/your-feature-name
```

## Development Workflow

### Running the Dev Server

```bash
composer run dev
```

This starts the Laravel server, queue worker, log viewer, and Vite dev server concurrently.

### Code Style

This project uses [Laravel Pint](https://laravel.com/docs/pint) to enforce a consistent code style. Run Pint before committing:

```bash
vendor/bin/pint
```

### Testing

All changes must include tests. This project uses [Pest](https://pestphp.com).

```bash
# Run all tests
php artisan test --compact

# Run a specific test
php artisan test --compact --filter=YourTestName

# Run tests in parallel
vendor/bin/pest --parallel
```

### Creating Tests

```bash
# Feature test (default)
php artisan make:test --pest YourFeatureTest

# Unit test
php artisan make:test --pest --unit YourUnitTest
```

## Pull Request Guidelines

1. **One feature per PR** -- Keep pull requests focused on a single change
2. **Write tests** -- All new features and bug fixes should include tests
3. **Follow existing conventions** -- Check sibling files for patterns and naming
4. **Update documentation** -- If your change affects setup or usage, update the README
5. **Run the full test suite** -- Ensure all tests pass before submitting
6. **Run Pint** -- Ensure your code follows the project's style

### PR Process

1. Push your branch to your fork
2. Open a pull request against the `main` branch
3. Fill out the pull request template
4. Wait for CI checks to pass
5. A maintainer will review your PR

## Reporting Bugs

When filing a bug report, please include:

- A clear description of the issue
- Steps to reproduce the behavior
- Expected behavior vs actual behavior
- PHP version, Laravel version, and OS
- Any relevant log output

## Suggesting Features

Feature requests are welcome. Please open an issue and include:

- A clear description of the feature
- The use case or problem it solves
- Any examples of how it might work

## Code of Conduct

Please be respectful and constructive in all interactions. We are committed to providing a welcoming and inclusive experience for everyone.

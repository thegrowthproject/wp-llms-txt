# Contributing to WP LLMs.txt

Thank you for your interest in contributing to WP LLMs.txt! This document provides guidelines for contributing to the project.

## Development Setup

### Requirements

- PHP 8.2+
- Composer
- WordPress 5.0+ (for testing)
- Local development environment (LocalWP, wp-env, etc.)

### Getting Started

1. Fork the repository
2. Clone your fork:
   ```bash
   git clone https://github.com/YOUR-USERNAME/wp-llms-txt.git
   ```
3. Install dependencies:
   ```bash
   composer install
   ```
4. Create a feature branch:
   ```bash
   git checkout -b feature/your-feature-name
   ```

## Coding Standards

This project follows the [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/).

### Running Lint

```bash
# Check for issues
composer lint

# Auto-fix issues
composer lint:fix
```

All code must pass PHPCS before being merged.

## Branching Strategy

- `main` - Stable releases only
- `dev` - Development integration branch
- `feature/*` - New features
- `fix/*` - Bug fixes

### Workflow

1. Create your branch from `dev`
2. Make your changes
3. Ensure lint passes
4. Open a PR to `dev`
5. After review, changes are merged to `dev`
6. Periodically, `dev` is merged to `main` for releases

## Pull Request Process

1. Update documentation if needed
2. Add a CHANGELOG entry if applicable
3. Ensure all checks pass
4. Request a review

## Reporting Bugs

Please use the [bug report template](.github/ISSUE_TEMPLATE/bug_report.md) and include:

- WordPress version
- PHP version
- Plugin version
- Steps to reproduce
- Expected vs actual behavior

## Requesting Features

Please use the [feature request template](.github/ISSUE_TEMPLATE/feature_request.md) and describe:

- The problem you're trying to solve
- Your proposed solution
- Alternatives you've considered

## Code of Conduct

Be respectful and constructive. We're all here to make the project better.

## Questions?

Open an issue or reach out at [thegrowthproject.com.au/contact](https://thegrowthproject.com.au/contact).

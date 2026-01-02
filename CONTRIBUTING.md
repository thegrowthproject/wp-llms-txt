# Contributing to WP LLMs.txt

Thank you for your interest in contributing to WP LLMs.txt! This document provides guidelines for contributing to the project.

## Development Setup

### Requirements

- PHP 8.2+
- Node.js 18+
- Composer
- WordPress 6.5+ (Interactivity API required)
- Docker (for E2E tests)
- Local development environment (LocalWP, wp-env, etc.)

### Getting Started

1. Fork the repository
2. Clone your fork:
   ```bash
   git clone https://github.com/YOUR-USERNAME/wp-llms-txt.git
   cd wp-llms-txt
   ```
3. Install dependencies:
   ```bash
   composer install
   npm install
   ```
4. Create a feature branch from `dev`:
   ```bash
   git checkout dev
   git pull origin dev
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

## Testing

We use three testing frameworks. See [docs/TESTING.md](docs/TESTING.md) for details.

```bash
# PHP unit tests
composer test

# JavaScript unit tests
npm test

# E2E tests (requires Docker)
npm run wp-env start
npm run test:e2e
```

All tests must pass before merging.

## Branching Strategy

| Branch | Purpose |
|--------|---------|
| `main` | Stable releases only |
| `dev` | Development integration branch |
| `release/x.y.z` | Release preparation |
| `feature/*` | New features |
| `fix/*` | Bug fixes |

### Feature Development Workflow

1. Create your branch from `dev`:
   ```bash
   git checkout dev && git pull
   git checkout -b feature/my-feature
   ```
2. Make your changes
3. Ensure lint and tests pass
4. Push and open a PR to `dev`
5. After review, squash merge to `dev`

### Release Process

When preparing a release:

1. **Create release branch from `dev`:**
   ```bash
   git checkout dev && git pull
   git checkout -b release/x.y.z
   ```

2. **Update version numbers:**
   - `tgp-llms-txt.php` (plugin header)
   - `CHANGELOG.md` (add release date)

3. **Open PR to `main`:**
   - Title: `Release vX.Y.Z`
   - Include changelog summary in description

4. **Merge to main:**
   - Use "Create a merge commit" (not squash)
   - This preserves the full commit history

5. **Create GitHub release:**
   - Tag: `vX.Y.Z`
   - Title: `vX.Y.Z - Release Name`
   - Body: Copy from CHANGELOG.md

6. **Sync dev with main:**
   ```bash
   git checkout dev
   git merge main
   git push origin dev
   ```

## Pull Request Process

1. **Before submitting:**
   - Run `composer lint` and fix any issues
   - Run `composer test` and `npm test`
   - Update documentation if needed
   - Add CHANGELOG entry under `[Unreleased]`

2. **PR description should include:**
   - Summary of changes
   - Related issue number (e.g., "Closes #123")
   - Test plan or verification steps

3. **After approval:**
   - Squash merge feature branches to `dev`
   - Merge commit for release branches to `main`

## Commit Messages

Follow [Conventional Commits](https://www.conventionalcommits.org/):

```
type(scope): description

[optional body]

[optional footer]
```

**Types:**
- `feat` — New feature
- `fix` — Bug fix
- `docs` — Documentation only
- `refactor` — Code change that neither fixes a bug nor adds a feature
- `test` — Adding or updating tests
- `chore` — Maintenance tasks

**Examples:**
```
feat(blog-filters): add search input block
fix(copy-button): resolve clipboard permission error
docs: update testing guide with E2E section
refactor: extract shared button renderer class
```

## Reporting Bugs

Please use the [bug report template](.github/ISSUE_TEMPLATE/bug_report.md) and include:

- WordPress version
- PHP version
- Plugin version
- Steps to reproduce
- Expected vs actual behavior
- Browser/environment details

## Requesting Features

Please use the [feature request template](.github/ISSUE_TEMPLATE/feature_request.md) and describe:

- The problem you're trying to solve
- Your proposed solution
- Alternatives you've considered

## Code of Conduct

### Our Standards

We are committed to providing a welcoming and inclusive environment. All contributors are expected to:

- **Be respectful** — Treat everyone with respect. No harassment, discrimination, or personal attacks.
- **Be constructive** — Provide helpful feedback. Critique ideas, not people.
- **Be collaborative** — Work together toward shared goals. Help others learn and grow.
- **Be professional** — Communicate clearly and courteously. Assume good intent.

### Unacceptable Behavior

- Harassment, intimidation, or discrimination of any kind
- Personal attacks, insults, or derogatory comments
- Publishing others' private information without consent
- Disruptive or unconstructive behavior in discussions

### Enforcement

Violations may result in:
1. Warning
2. Temporary ban from participation
3. Permanent ban from the project

Report issues to [thegrowthproject.com.au/contact](https://thegrowthproject.com.au/contact).

## Questions?

- Check [existing issues](https://github.com/thegrowthproject/wp-llms-txt/issues)
- Open a new issue for bugs or feature requests
- Contact us at [thegrowthproject.com.au/contact](https://thegrowthproject.com.au/contact)

## License

By contributing, you agree that your contributions will be licensed under the GPL v2 or later license.

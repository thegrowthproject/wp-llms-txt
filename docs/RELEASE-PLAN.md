# WP LLMs.txt v1.0.0 Release Plan

## Overview

This document outlines the complete release preparation for wp-llms-txt v1.0.0, including branching strategy, CI/CD automation, packaging, and release workflow.

---

## 1. Branching Strategy

### Branch Structure

```
main          ← stable, released code only
  └── dev     ← active development, PRs merge here
       └── feature/*   ← feature branches
       └── fix/*       ← bugfix branches
```

### Branch Rules

| Branch | Purpose | Protection |
|--------|---------|------------|
| `main` | Production releases only | Require PR, require status checks, no direct push |
| `dev` | Development integration | Require status checks |
| `feature/*` | New features | None |
| `fix/*` | Bug fixes | None |

### Workflow

1. Create feature/fix branch from `dev`
2. Open PR to `dev` → runs lint
3. Merge to `dev` after review
4. When ready for release: PR from `dev` → `main`
5. Merge to `main` triggers release preparation
6. Create GitHub Release with tag `v1.0.0`
7. Release workflow builds zip and attaches to release

---

## 2. Files to Create

### 2.1 `.distignore` - Exclude from release zip

```
# Development files
.git
.github
.editorconfig
.gitignore
.distignore

# Composer dev
composer.json
composer.lock
vendor/
phpcs.xml.dist

# Documentation (dev)
docs/
CLAUDE.md
CONTRIBUTING.md

# IDE
.idea/
.vscode/
*.sublime-*

# OS
.DS_Store
Thumbs.db

# Build
node_modules/
*.log
```

### 2.2 `.github/workflows/release.yml` - Release automation

Triggers on: GitHub Release published
Actions:
1. Checkout code
2. Build clean plugin zip (respecting .distignore)
3. Upload zip as release asset

### 2.3 `.github/workflows/lint.yml` - Update existing

Add trigger for `dev` branch in addition to `main`.

### 2.4 `.github/ISSUE_TEMPLATE/bug_report.md`

Standard bug report template with:
- WordPress version
- PHP version
- Steps to reproduce
- Expected vs actual behavior

### 2.5 `.github/ISSUE_TEMPLATE/feature_request.md`

Feature request template with:
- Problem description
- Proposed solution
- Alternatives considered

### 2.6 `.github/PULL_REQUEST_TEMPLATE.md`

PR template with:
- Description of changes
- Type of change (bug fix, feature, etc.)
- Checklist (tested, lint passes, etc.)

### 2.7 `CONTRIBUTING.md`

Contribution guidelines:
- How to set up development environment
- Coding standards (WordPress)
- PR process
- Issue reporting

---

## 3. GitHub Actions Workflows

### 3.1 Lint Workflow (update existing)

**File:** `.github/workflows/lint.yml`

```yaml
name: Lint

on:
  push:
    branches: [main, dev]
  pull_request:
    branches: [main, dev]

jobs:
  phpcs:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
      - run: composer install
      - run: composer lint
```

### 3.2 Release Workflow (new)

**File:** `.github/workflows/release.yml`

```yaml
name: Release

on:
  release:
    types: [published]

jobs:
  build:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4

      - name: Build plugin zip
        run: |
          mkdir -p build
          rsync -av --exclude-from='.distignore' . build/wp-llms-txt/
          cd build && zip -r wp-llms-txt.zip wp-llms-txt/

      - name: Upload release asset
        uses: softprops/action-gh-release@v1
        with:
          files: build/wp-llms-txt.zip
```

---

## 4. Release Checklist

### Pre-Release

- [ ] All PRs merged to `dev`
- [ ] Lint passes on `dev`
- [ ] Manual testing complete
- [ ] Version number updated in `tgp-llms-txt.php` header
- [ ] CHANGELOG.md updated with release notes
- [ ] PR from `dev` to `main` created and approved

### Release

- [ ] Merge PR to `main`
- [ ] Create GitHub Release
  - Tag: `v1.0.0`
  - Title: `v1.0.0`
  - Description: Copy from CHANGELOG.md
- [ ] Verify zip is attached to release
- [ ] Test downloaded zip installs correctly

### Post-Release

- [ ] Announce release (if applicable)
- [ ] Merge `main` back to `dev` (if any release commits)

---

## 5. Version Numbering

Follow [Semantic Versioning](https://semver.org/):

- **MAJOR** (1.x.x): Breaking changes
- **MINOR** (x.1.x): New features, backwards compatible
- **PATCH** (x.x.1): Bug fixes, backwards compatible

### Version Locations

Update version in these files for each release:

1. `tgp-llms-txt.php` - Plugin header `Version:`
2. `tgp-llms-txt.php` - `TGP_LLMS_VERSION` constant
3. `CHANGELOG.md` - New release section
4. `README.md` - If version is mentioned

---

## 6. Implementation Order

### Phase 1: Branch Setup
1. Create `dev` branch from `main`
2. Set `dev` as default branch (optional, for development)

### Phase 2: Add Release Infrastructure
3. Create `.distignore`
4. Update `.github/workflows/lint.yml` for dev branch
5. Create `.github/workflows/release.yml`

### Phase 3: Add Templates
6. Create `.github/ISSUE_TEMPLATE/bug_report.md`
7. Create `.github/ISSUE_TEMPLATE/feature_request.md`
8. Create `.github/PULL_REQUEST_TEMPLATE.md`
9. Create `CONTRIBUTING.md`

### Phase 4: Prepare v1.0.0
10. Verify version is `1.0.0` in plugin header
11. Update CHANGELOG.md for v1.0.0 release
12. Commit all changes to `dev`
13. Create PR from `dev` to `main`
14. Merge PR
15. Create GitHub Release with tag `v1.0.0`
16. Verify zip is generated and attached

---

## 7. Branch Protection Rules (GitHub Settings)

### For `main` branch:

- [x] Require a pull request before merging
- [x] Require status checks to pass (phpcs)
- [x] Do not allow bypassing the above settings
- [ ] Require approvals (optional for solo dev)

### For `dev` branch:

- [x] Require status checks to pass (phpcs)

---

## 8. Directory Structure After Implementation

```
wp-llms-txt/
├── .github/
│   ├── ISSUE_TEMPLATE/
│   │   ├── bug_report.md
│   │   └── feature_request.md
│   ├── workflows/
│   │   ├── lint.yml
│   │   └── release.yml
│   └── PULL_REQUEST_TEMPLATE.md
├── blocks/
│   └── llm-buttons/
├── docs/
│   └── RELEASE-PLAN.md
├── includes/
├── .distignore
├── .editorconfig
├── .gitignore
├── CHANGELOG.md
├── CLAUDE.md
├── composer.json
├── CONTRIBUTING.md
├── LICENSE
├── phpcs.xml.dist
├── README.md
└── tgp-llms-txt.php
```

---

## 9. Approval

Ready to implement this plan?

- [ ] Plan reviewed
- [ ] Ready to proceed

Once approved, implementation will follow Phase 1-4 in order.

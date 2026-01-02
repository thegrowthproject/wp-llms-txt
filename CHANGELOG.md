# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.2.0] - 2026-01-02

### Added
- **Blog Filters blocks** — Three new blocks for filtering blog archives:
  - `tgp/blog-filters` — Parent container with Interactivity API state management
  - `tgp/blog-category-filter` — Category pills with active state styling
  - `tgp/blog-search` — Search input with icon and clear button
- **Testing infrastructure:**
  - PHPUnit with Brain Monkey (38 tests for shared helpers)
  - Jest with Interactivity API mock (21 tests for stores)
  - Playwright E2E tests for frontend rendering
  - GitHub Actions workflow for E2E tests
- **Architecture Decision Records** — Documented key technical decisions:
  - ADR-001: State vs Context in Interactivity API
  - ADR-002: Skip serialization pattern for style variations
  - ADR-003: Shared PHP helper classes
- **Shared helper classes:**
  - `TGP_Button_Block_Renderer` — Consolidated button rendering logic
  - `TGP_Pill_Block_Renderer` — Pill/filter button rendering
  - `TGP_SVG_Sanitizer` — Centralized SVG wp_kses configuration
- **New filters:**
  - `tgp_llms_txt_description` — Customize llms.txt site description
  - `tgp_llms_txt_contact_url` — Customize contact page path
  - `tgp_llms_txt_pages` — Customize page listings
- `uninstall.php` for proper plugin cleanup

### Changed
- **BREAKING:** CSS class names migrated to BEM pattern
  - Before: `.tgp-copy-btn`, `.tgp-btn-icon`
  - After: `.wp-block-tgp-copy-button`, `.wp-block-tgp-copy-button__icon`
- Moved posts data from context to global state (reduces HTML payload)
- Refactored button render.php files (70%+ code reduction via shared helpers)
- Improved empty state styling for blog filters

### Fixed
- Button style variations now apply correctly (skip serialization pattern)
- ABSPATH security check added to all PHP files
- Accessibility improvements:
  - `aria-hidden` on decorative icons
  - `aria-live="polite"` for copy button status announcements
  - Screen reader text for button context
  - Translator comments on all i18n strings

### Documentation
- Comprehensive README with usage instructions and development guide
- Testing guide (`docs/TESTING.md`)
- Block development patterns (`docs/BLOCK-DEVELOPMENT.md`)
- Button styling guide (`docs/BUTTON-STYLING.md`)
- README files for each block directory

## [1.1.0] - 2025-12-31

### Changed
- **BREAKING:** Requires WordPress 6.5+ (was 5.0+)
- Migrated copy button to WordPress Interactivity API for reactive state management
- Copy button now fetches directly from `.md` endpoint (faster, cached) instead of admin-ajax.php
- Replaced custom color/style attributes with WordPress Block Supports API
- Buttons now inherit theme styles automatically (Brand, Dark, Light, Tint variants)
- Split `llm-buttons` block into separate `copy-button` and `view-button` blocks

### Removed
- `includes/class-ui-buttons.php` (AJAX handler no longer needed)
- `blocks/llm-buttons/editor.css` (styles now from Block Supports)
- Custom `backgroundColor`, `textColor`, `borderRadius` attributes (replaced by Block Supports)

### Fixed
- Button styles now match theme design system
- Improved performance by eliminating admin-ajax.php overhead

## [1.0.0] - 2025-12-30

### Added
- `/llms.txt` endpoint with site index following llmstxt.org specification
- `.md` endpoints for individual pages and posts
- Gutenberg block for copy-to-clipboard functionality
- YAML frontmatter for markdown files (title, description, date, author, tags)
- Gutenberg to markdown conversion (headings, lists, tables, links, emphasis, code)
- Cache headers for performance (1 hour)

### Developer Experience
- WordPress Coding Standards (PHPCS) with CI
- GitHub Actions for linting and releases
- Automated plugin zip builds on release
- Issue and PR templates
- Contributing guidelines

### Requirements
- WordPress 5.0+ (Gutenberg)
- PHP 8.2+

# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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

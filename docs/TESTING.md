# Testing Guide

This document explains how to run and write tests for the TGP LLMs.txt plugin.

## Overview

The plugin uses three testing frameworks:

| Framework | Language | Purpose |
|-----------|----------|---------|
| PHPUnit   | PHP      | Server-side logic, shared helpers, block rendering |
| Jest      | JS       | Interactivity API stores, client-side behavior |
| Playwright | JS      | End-to-end browser testing |

## Quick Start

```bash
# Install dependencies
composer install
npm install

# Run all tests
composer test          # PHP unit tests
npm test               # JS unit tests
npm run test:e2e       # E2E tests (requires Docker)

# Run with coverage
composer test:coverage
npm run test:coverage
```

## PHP Testing (PHPUnit)

### Setup

PHPUnit is configured via `phpunit.xml.dist`. Tests use [Brain Monkey](https://brain-wp.github.io/BrainMonkey/) for mocking WordPress functions without loading WordPress core.

### Directory Structure

```
tests/php/
├── bootstrap.php           # Test bootstrap (loads Brain Monkey)
└── includes/
    ├── ButtonBlockRendererTest.php
    └── PillBlockRendererTest.php
```

### Running Tests

```bash
# Run all PHP tests
composer test

# Run specific test file
vendor/bin/phpunit tests/php/includes/ButtonBlockRendererTest.php

# Run specific test method
vendor/bin/phpunit --filter testGetStyleAttributesExtractsBlockSupports

# Run with coverage report
composer test:coverage
# Coverage report generated in coverage/ directory
```

### Writing PHP Tests

Tests extend `PHPUnit\Framework\TestCase` and use Brain Monkey for WordPress function mocks:

```php
<?php
use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;

class MyBlockTest extends TestCase {
    protected function setUp(): void {
        parent::setUp();
        Monkey\setUp();

        // Mock WordPress functions as needed
        Functions\when( 'esc_attr' )->returnArg();
        Functions\when( 'esc_html' )->returnArg();
    }

    protected function tearDown(): void {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testSomething(): void {
        // Your test code
        $this->assertEquals( 'expected', 'expected' );
    }
}
```

### What to Test (PHP)

- **Shared Helpers** (`includes/class-*-renderer.php`)
  - Style attribute extraction
  - Class name building
  - Color resolution
  - Inline style generation

- **Block Registration** (future)
  - Block attributes
  - Render callbacks

## JavaScript Testing (Jest)

### Setup

Jest is configured in `package.json` with a custom mock for `@wordpress/interactivity`.

### Directory Structure

```
tests/js/
├── __mocks__/
│   └── interactivity.js    # Mock for @wordpress/interactivity
├── setup.js                # Jest setup (DOM matchers, fetch mock)
└── blocks/
    ├── copy-button.test.js
    └── blog-filters.test.js
```

### Running Tests

```bash
# Run all JS tests
npm test

# Watch mode (re-runs on file changes)
npm run test:watch

# Run specific test file
npm test -- tests/js/blocks/copy-button.test.js

# Run with coverage
npm run test:coverage
```

### Writing JS Tests

Tests use the Interactivity API mock which provides helpers for setting context and state:

```javascript
import { store, getContext, setMockContext, setMockState, getStore } from '@wordpress/interactivity';

describe( 'my-store', () => {
    beforeEach( () => {
        // Set up global state (from PHP wp_interactivity_state)
        setMockState( {
            posts: [],
            categories: [],
        } );

        // Register your store
        store( 'my-namespace/my-store', {
            state: {
                get computedValue() {
                    const ctx = getContext();
                    return ctx.someValue * 2;
                },
            },
            actions: {
                doSomething() {
                    const ctx = getContext();
                    ctx.someValue = 42;
                },
            },
        } );
    } );

    it( 'computes value correctly', () => {
        setMockContext( { someValue: 5 } );

        const myStore = getStore( 'my-namespace/my-store' );
        expect( myStore.state.computedValue ).toBe( 10 );
    } );

    it( 'action modifies context', () => {
        const ctx = { someValue: 0 };
        setMockContext( ctx );

        const myStore = getStore( 'my-namespace/my-store' );
        myStore.actions.doSomething();

        expect( ctx.someValue ).toBe( 42 );
    } );
} );
```

### Mock API Reference

The `@wordpress/interactivity` mock provides:

| Function | Description |
|----------|-------------|
| `store( namespace, definition )` | Register a store |
| `getContext()` | Get current mock context |
| `setMockContext( ctx )` | Set context for next test |
| `setMockState( state )` | Set global state for next test |
| `getStore( namespace )` | Get registered store |
| `resetMocks()` | Reset all mocks (called in beforeEach) |

### What to Test (JS)

- **State Getters**
  - Computed values based on context
  - Conditional logic (active states, visibility)
  - Text formatting (labels, counts)

- **Actions**
  - Context mutations
  - State changes
  - Async operations (use generators for fetch)

## Test Patterns

### Testing State Getters

```javascript
describe( 'state.isActive', () => {
    it( 'returns true when condition met', () => {
        setMockContext( { status: 'active' } );
        expect( getStore( 'my/store' ).state.isActive ).toBe( true );
    } );

    it( 'returns false when condition not met', () => {
        setMockContext( { status: 'inactive' } );
        expect( getStore( 'my/store' ).state.isActive ).toBe( false );
    } );
} );
```

### Testing Actions That Modify Context

```javascript
describe( 'actions.toggle', () => {
    it( 'toggles value', () => {
        const ctx = { enabled: false };
        setMockContext( ctx );

        getStore( 'my/store' ).actions.toggle();

        expect( ctx.enabled ).toBe( true );
    } );
} );
```

### Testing Generator Actions (Async)

```javascript
describe( 'actions.fetchData', () => {
    it( 'fetches and updates context', async () => {
        global.fetch = jest.fn().mockResolvedValue( {
            ok: true,
            json: () => Promise.resolve( { data: 'test' } ),
        } );

        const ctx = { data: null, loading: false };
        setMockContext( ctx );

        // Generator actions need to be iterated
        const generator = getStore( 'my/store' ).actions.fetchData();
        await runGenerator( generator );

        expect( ctx.data ).toBe( 'test' );
    } );
} );

// Helper to run generator actions
async function runGenerator( gen ) {
    let result = gen.next();
    while ( ! result.done ) {
        const value = await result.value;
        result = gen.next( value );
    }
    return result.value;
}
```

## E2E Testing (Playwright)

### Prerequisites

E2E tests require Docker to run `wp-env`, which provides an isolated WordPress environment.

```bash
# Verify Docker is running
docker --version

# Install Playwright browsers
npx playwright install chromium
```

### Setup

E2E tests use [`@wordpress/env`](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/) (wp-env) for a clean WordPress installation with the plugin activated.

Configuration files:
- `.wp-env.json` — WordPress environment configuration
- `playwright.config.js` — Playwright test configuration

### Directory Structure

```
tests/e2e/
├── fixtures.js       # Test utilities (createPost, deletePost, auth fixtures)
└── blocks.spec.js    # Block E2E tests
```

### Running Tests

```bash
# Start wp-env (if not already running)
npm run wp-env start

# Run all E2E tests
npm run test:e2e

# Run with UI mode (interactive debugging)
npm run test:e2e:ui

# Run specific test file
npx playwright test tests/e2e/blocks.spec.js

# Run in headed mode (see browser)
npx playwright test --headed
```

### Writing E2E Tests

Tests use Playwright's test fixtures with custom utilities for WordPress:

```javascript
const { test, expect, createPost, deletePost } = require( './fixtures' );

test.describe( 'My feature', () => {
    let testPost;

    test.beforeAll( async () => {
        // Create test content via wp-cli
        testPost = await createPost( 'Test Title', '<!-- wp:paragraph --><p>Content</p><!-- /wp:paragraph -->' );
    } );

    test.afterAll( async () => {
        if ( testPost ) {
            await deletePost( testPost.id );
        }
    } );

    test( 'renders correctly', async ( { page } ) => {
        await page.goto( testPost.url );
        await expect( page.locator( 'h1' ) ).toContainText( 'Test Title' );
    } );
} );
```

### Fixtures

The `fixtures.js` file provides:

| Fixture | Description |
|---------|-------------|
| `page` | Standard Playwright page (logged out) |
| `adminPage` | Page pre-authenticated as WordPress admin |
| `context` | Browser context |
| `createPost( title, content )` | Create a published post via wp-cli |
| `deletePost( id )` | Delete a post via wp-cli |

### What to Test (E2E)

- **Frontend rendering** — Blocks display correctly on the frontend
- **User interactions** — Copy button click, clipboard operations
- **Endpoint responses** — `.md` URLs return valid markdown
- **Block insertion** — Blocks appear in inserter and can be added (currently skipped due to wp-env issue)

### Known Issues

**Block insertion tests are skipped**: The wp-env environment has issues with block registration in the editor. Blocks show as "Unsupported" in the block inserter preview, even though they work correctly on the frontend. This is a wp-env/block registration timing issue, not a plugin bug.

## Coverage

Both unit test runners generate coverage reports:

- **PHPUnit**: `coverage/` directory (HTML report)
- **Jest**: `coverage/` directory (HTML report)

View the HTML reports in a browser to see line-by-line coverage.

## CI Integration

Tests run automatically on GitHub Actions via two workflows:

### `.github/workflows/lint.yml` (CI)

Runs on push/PR to `dev` and `main`:
- **PHPCS** — PHP coding standards
- **PHP Tests** — PHPUnit unit tests
- **JS Tests** — Jest unit tests

### `.github/workflows/e2e.yml` (E2E Tests)

Runs on push/PR to `dev` and `main`:
- Starts wp-env with Docker
- Runs Playwright E2E tests
- Uploads test artifacts on failure

## Troubleshooting

### PHPUnit "Class not found" errors

Ensure autoload is up to date:
```bash
composer dump-autoload
```

### Jest "Cannot find module" errors

Check that the mock path in `package.json` is correct:
```json
"moduleNameMapper": {
    "^@wordpress/interactivity$": "<rootDir>/tests/js/__mocks__/interactivity.js"
}
```

### Brain Monkey "Function not mocked" warnings

Add the missing function mock in your test's `setUp()`:
```php
Functions\when( 'missing_function' )->returnArg();
```

### E2E tests fail with "Cannot connect to localhost:8888"

Ensure wp-env is running:
```bash
npm run wp-env start
```

### E2E tests timeout on block insertion

This is a known issue with wp-env block registration. The block insertion tests are skipped by default. Frontend tests should work correctly.

### Playwright "browser not found" errors

Install the Playwright browsers:
```bash
npx playwright install chromium
```

### wp-env won't start (Docker issues)

Ensure Docker Desktop is running, then:
```bash
npm run wp-env stop
npm run wp-env start
```

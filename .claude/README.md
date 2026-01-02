# Claude Guidelines for TGP LLMs.txt

This document provides context for Claude when working on this WordPress plugin.

## Project Overview

TGP LLMs.txt is a WordPress plugin implementing the [llmstxt.org](https://llmstxt.org) standard. It provides:
- `/llms.txt` endpoint with site index
- `.md` endpoints for pages/posts
- Gutenberg blocks for copy-to-clipboard and blog filtering

## Testing

### Running Tests

Always run tests after making changes to PHP helpers or JS stores:

```bash
# PHP tests (38 tests)
composer test

# JS tests (21 tests)
npm test
```

### When to Write Tests

**PHP tests required for:**
- Changes to `includes/class-*-renderer.php` helper classes
- New shared helper methods
- Block registration logic

**JS tests required for:**
- Changes to Interactivity API stores (`blocks/*/view.js`)
- New state getters or actions
- Store behavior changes

### Test Patterns

**PHP (PHPUnit + Brain Monkey):**
```php
public function testMethodName(): void {
    // Arrange
    $attributes = ['style' => ['color' => ['text' => '#000']]];

    // Act
    $result = TGP_Button_Block_Renderer::get_style_attributes($attributes);

    // Assert
    $this->assertEquals('#000', $result['text_color']);
}
```

**JS (Jest + Interactivity mock):**
```javascript
it('returns correct value', () => {
    setMockContext({ status: 'active' });

    const store = getStore('tgp/my-store');
    expect(store.state.isActive).toBe(true);
});
```

### Test File Locations

| Type | Location | Naming |
|------|----------|--------|
| PHP | `tests/php/includes/` | `*Test.php` |
| JS | `tests/js/blocks/` | `*.test.js` |

## Architecture

### Shared Helpers

Two helper classes exist for common block patterns:

| Class | Purpose | Used By |
|-------|---------|---------|
| `TGP_Button_Block_Renderer` | Button-style blocks with style variations | copy-button, view-button |
| `TGP_Pill_Block_Renderer` | Pill/toggle blocks with active states | blog-category-filter |

When creating new blocks, check if these helpers apply before writing custom logic.

### Interactivity API

**State vs Context:**
- **State**: Global data shared across all blocks (posts, categories, totalPosts)
- **Context**: Per-instance data (selectedCategories, searchQuery, copyState)

See `docs/adr/001-interactivity-api-state-vs-context.md` for detailed guidance.

### Style Variations

Blocks use `__experimentalSkipSerialization` to prevent duplicate styles. See `docs/adr/002-skip-serialization-pattern.md`.

## Skills

Available skills in `.claude/skills/`:

| Skill | Trigger | Purpose |
|-------|---------|---------|
| `/new-block` | Creating new Gutenberg block | Scaffolds block with correct patterns |
| `/refactor-block` | Extracting shared logic | Guides refactoring to use helpers |

## Key Files

| File | Purpose |
|------|---------|
| `tgp-llms-txt.php` | Main plugin file, block registration |
| `includes/class-*-renderer.php` | Shared helper classes |
| `blocks/*/render.php` | Server-side block rendering |
| `blocks/*/view.js` | Interactivity API stores |
| `docs/TESTING.md` | Full testing documentation |
| `docs/adr/*.md` | Architecture decision records |

## Coding Standards

- PHP: WordPress Coding Standards (run `composer lint`)
- JS: WordPress scripts (tabs, single quotes in JSX)
- Tests: Descriptive names, arrange-act-assert pattern

## Before Committing

1. Run linter: `composer lint`
2. Run PHP tests: `composer test`
3. Run JS tests: `npm test`

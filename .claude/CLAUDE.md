# CLAUDE.md

This file provides context for AI-assisted development on this project.

## Project Overview

**WP LLMs.txt** — A WordPress plugin that implements the [llmstxt.org](https://llmstxt.org/) standard for AI/LLM content consumption.

**Primary function:** Convert WordPress Gutenberg content to clean markdown and serve it via standardized endpoints.

**Requirements:**
- WordPress 6.5+ (Interactivity API)
- PHP 8.2+

## Development Commands

| Command | Description |
|---------|-------------|
| `composer install` | Install PHP dependencies (PHPCS) |
| `composer lint` | Check code against WordPress Coding Standards |
| `composer lint:fix` | Auto-fix coding standards violations |

### Manual Testing

| Command | Description |
|---------|-------------|
| `curl -s "http://localhost:10003/llms.txt"` | Test site index endpoint |
| `curl -s "http://localhost:10003/about.md"` | Test page markdown endpoint |
| `curl -s "http://localhost:10003/blog/post-slug.md"` | Test post markdown endpoint |
| `curl -I "http://localhost:10003/about.md" \| grep Content-Type` | Verify Content-Type header |

## Architecture

```
tgp-llms-txt/
├── tgp-llms-txt.php              # Main plugin file, bootstraps everything
├── CLAUDE.md                     # This file
├── README.md                     # Plugin documentation
├── composer.json                 # PHP dependencies & scripts
├── phpcs.xml.dist                # WordPress Coding Standards config
├── includes/
│   ├── class-endpoint-handler.php    # Routes requests to .md and llms.txt
│   ├── class-llms-txt-generator.php  # Generates the /llms.txt index
│   ├── class-markdown-converter.php  # Converts Gutenberg HTML to markdown
│   └── class-frontmatter.php         # Generates YAML frontmatter for .md files
├── blocks/
│   ├── copy-button/                  # "Copy for LLM" button block
│   │   ├── block.json                # Block registration & supports
│   │   ├── index.js                  # Editor registration & Edit component
│   │   ├── view.js                   # Interactivity API store (frontend)
│   │   ├── render.php                # Server-side render template
│   │   └── style.css                 # Frontend & editor styles
│   └── view-button/                  # "View as Markdown" link block
│       ├── block.json
│       ├── index.js
│       ├── render.php
│       └── style.css
└── docs/
    ├── BLOCK-DEVELOPMENT.md          # Block development guide
    └── BUTTON-STYLING.md             # Button styling guide
```

### Request Flow

```
Browser Request → Endpoint Handler → Generator/Converter → Response
                       ↓
              Pattern matching:
              • *.md → Markdown Converter + Frontmatter
              • llms.txt → LLMs Txt Generator
              • llms-full.txt → Full site concatenation
```

### Class Responsibilities

| Class | Single Responsibility |
|-------|----------------------|
| `TGP_Endpoint_Handler` | Route requests, early interception at `init` priority 0 |
| `TGP_LLMs_Txt_Generator` | Build the llms.txt site index |
| `TGP_Markdown_Converter` | Transform Gutenberg HTML to markdown |
| `TGP_Frontmatter` | Generate YAML metadata for .md files |

## Block Registration Flow

Both blocks are registered via `block.json` with dynamic rendering (server-side PHP):

```
1. block.json defines:
   ├── name, attributes, supports
   ├── editorScript → index.js
   ├── viewScriptModule → view.js (copy-button only)
   ├── render → render.php
   └── style → style.css

2. index.js registers block in editor:
   ├── registerBlockType() with Edit component
   └── save() returns null (dynamic block)

3. render.php generates frontend HTML:
   ├── Reads attributes from $attributes
   ├── Applies style variation classes
   └── Outputs wp-block-button structure

4. view.js (copy-button) adds interactivity:
   └── Interactivity API store for copy functionality
```

## Blocks Reference

### Copy for LLM (`tgp/copy-button`)

Copies current page content as markdown to clipboard.

**Files:**
- [blocks/copy-button/block.json](blocks/copy-button/block.json) — Registration
- [blocks/copy-button/index.js](blocks/copy-button/index.js) — Editor component
- [blocks/copy-button/view.js](blocks/copy-button/view.js) — Interactivity API store
- [blocks/copy-button/render.php](blocks/copy-button/render.php) — Server render

**Attributes:**

| Attribute | Type | Default | Description |
|-----------|------|---------|-------------|
| `label` | `string` | `"Copy for LLM"` | Button text |
| `showIcon` | `boolean` | `true` | Show copy icon |
| `width` | `number` | — | Button width (25, 50, 75, 100%) |
| `backgroundColor` | `string` | — | Preset background color slug |
| `textColor` | `string` | — | Preset text color slug |
| `gradient` | `string` | — | Preset gradient slug |

**Supports:** color (skip serialization), typography, spacing, border, shadow, interactivity

**Parent:** `core/buttons`

### View as Markdown (`tgp/view-button`)

Links to the `.md` version of the current page.

**Files:**
- [blocks/view-button/block.json](blocks/view-button/block.json) — Registration
- [blocks/view-button/index.js](blocks/view-button/index.js) — Editor component
- [blocks/view-button/render.php](blocks/view-button/render.php) — Server render

**Attributes:**

| Attribute | Type | Default | Description |
|-----------|------|---------|-------------|
| `label` | `string` | `"View as Markdown"` | Button text |
| `showIcon` | `boolean` | `true` | Show document icon |
| `width` | `number` | — | Button width (25, 50, 75, 100%) |
| `backgroundColor` | `string` | — | Preset background color slug |
| `textColor` | `string` | — | Preset text color slug |
| `gradient` | `string` | — | Preset gradient slug |

**Supports:** color (skip serialization), typography, spacing, border, shadow

**Parent:** `core/buttons`

## Key Implementation Details

### Style Variation System

Both blocks use `__experimentalSkipSerialization` to prevent WordPress from automatically adding color classes. This allows style variations (Fill, Outline, Brand, etc.) to work correctly.

**Why this matters:**
- Without skip serialization, WordPress adds `has-{color}-background-color` classes
- These classes have `!important` in global styles, overriding variation styles
- Skip serialization lets us manually control class output based on variation

**Implementation pattern:**

```json
// block.json
"supports": {
  "color": {
    "__experimentalSkipSerialization": true,
    "gradients": true
  }
}
```

```php
// render.php - Only add color classes for Fill style
$style_variation = null;
if ( preg_match( '/is-style-([a-z0-9-]+)/', $wrapper_attributes, $matches ) ) {
    $style_variation = $matches[1];
}
$has_style_variation = $style_variation && 'fill' !== $style_variation;

if ( ! $has_style_variation ) {
    // Add color classes only for Fill style
    if ( ! empty( $attributes['backgroundColor'] ) ) {
        $inner_classes[] = 'has-background';
        $inner_classes[] = 'has-' . $attributes['backgroundColor'] . '-background-color';
    }
}
```

### Interactivity API (Copy Button)

The copy button uses WordPress Interactivity API for reactive state management:

```javascript
// view.js
import { store, getContext } from '@wordpress/interactivity';

const { state } = store( 'tgp/copy-button', {
    state: {
        get buttonText() {
            const context = getContext();
            if ( context.isLoading ) return context.loadingText;
            if ( context.isCopied ) return context.copiedText;
            if ( context.isError ) return context.errorText;
            return context.label;
        },
    },
    actions: {
        *copyContent() {
            const context = getContext();
            context.isLoading = true;

            const response = yield fetch( context.mdUrl );
            const markdown = yield response.text();
            yield navigator.clipboard.writeText( markdown );

            context.isLoading = false;
            context.isCopied = true;
        },
    },
} );
```

```php
// render.php - Set up context
<?php
$context = array(
    'mdUrl'       => $md_url,
    'label'       => $label,
    'loadingText' => __( 'Copying...', 'tgp-llms-txt' ),
    'copiedText'  => __( 'Copied!', 'tgp-llms-txt' ),
    'errorText'   => __( 'Failed', 'tgp-llms-txt' ),
    'isLoading'   => false,
    'isCopied'    => false,
    'isError'     => false,
);
?>
<div <?php echo wp_kses_data( $wrapper_attributes ); ?>
     data-wp-interactive="tgp/copy-button"
     data-wp-context="<?php echo esc_attr( wp_json_encode( $context ) ); ?>">
```

### Two-Element Button Structure

Both blocks render with WordPress's standard button structure:

```html
<!-- Outer wrapper with style variation -->
<div class="wp-block-button is-style-button-brand">
  <!-- Inner button/link element -->
  <button class="wp-block-button__link wp-element-button tgp-copy-btn">
    <span class="tgp-btn-icon">...</span>
    <span class="tgp-btn-text">Copy for LLM</span>
  </button>
</div>
```

- Style variation class (`is-style-*`) goes on outer `div`
- Color classes (`has-*-background-color`) go on inner button
- Plugin classes (`tgp-copy-btn`) identify the element

## Code Conventions

### PHP Standards

Follow [WordPress PHP Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/).

```php
// Class names: Capitalized words with underscores
class TGP_Markdown_Converter {}

// Method names: lowercase with underscores
public function convert_headings( $content ) {}

// Hooks: array syntax for class methods
add_action( 'init', [ $this, 'method_name' ] );

// Spacing: space inside parentheses
if ( $condition ) {
    // code
}

// Security: Always check direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Security: Escape output
echo esc_html( $title );
echo esc_url( $url );
echo esc_attr( $attribute );
```

### JavaScript Standards

```javascript
// Use WordPress script dependencies, not imports (no build process)
( function( wp ) {
    const { registerBlockType } = wp.blocks;
    const { useBlockProps } = wp.blockEditor;
    const { __ } = wp.i18n;
    const { createElement: el } = wp.element;

    registerBlockType( 'tgp/block-name', {
        edit: function( props ) {
            // Edit component
        },
        save: function() {
            return null; // Dynamic block
        }
    } );
} )( window.wp );
```

### CSS Conventions

```css
/* Plugin-specific classes */
.tgp-copy-btn {}
.tgp-view-btn {}
.tgp-btn-icon {}
.tgp-btn-text {}

/* State classes */
.tgp-copy-btn.is-loading {}

/* Use CSS custom properties for theme integration */
.tgp-copy-btn {
    background-color: var(--wp--preset--color--primary);
}
```

## Development Principles

1. **No build process** — Vanilla JS uses `wp` global, no npm/webpack
2. **Dynamic rendering** — Blocks render server-side via `render.php`
3. **Theme integration** — Buttons inherit theme styles via Block Supports
4. **Skip serialization** — Prevents WordPress auto-classes for style variations
5. **Early interception** — Endpoints hook at `init` priority 0
6. **Exit after serve** — Always `exit` after custom content response

## Domain Language

| Term | Meaning |
|------|---------|
| Endpoint | A URL route handled by this plugin (`/llms.txt`, `*.md`) |
| Converter | Transforms HTML to markdown |
| Generator | Builds content from scratch (llms.txt index) |
| Frontmatter | YAML metadata block at top of .md files |
| Block | Gutenberg editor block |
| Style Variation | Predefined button style (Fill, Outline, Brand, etc.) |
| Skip Serialization | Prevent WordPress from auto-adding classes |

## Common Tasks

### Adding a new block

1. Create directory under `blocks/{block-name}/`
2. Add `block.json` with attributes and supports
3. Add `index.js` for editor registration
4. Add `render.php` for server-side output
5. Add `style.css` for styling
6. Register block in `tgp-llms-txt.php`

See [docs/BLOCK-DEVELOPMENT.md](docs/BLOCK-DEVELOPMENT.md) for detailed guide.

### Adding a new endpoint

1. Add pattern match in `TGP_Endpoint_Handler::check_for_custom_endpoints()`
2. Add rewrite rule in `TGP_Endpoint_Handler::add_rewrite_rules()`
3. Add handler method in appropriate class
4. Flush permalinks after changes

### Modifying markdown output

Edit conversion pipeline in `TGP_Markdown_Converter::convert()`:

```php
$content = $this->strip_block_comments( $content );  // Step 1
$content = $this->convert_headings( $content );       // Step 2
$content = $this->convert_paragraphs( $content );     // Step 3
// Add new conversions here
$content = $this->cleanup( $content );                // Final step
```

Order matters — block elements before inline elements.

## Git Commits

- **No AI attribution** — Do not add "Generated with Claude Code" or Co-Authored-By lines to commits
- Follow conventional commit format: `type: description`
- Types: `feat`, `fix`, `refactor`, `docs`, `style`, `test`, `chore`

### Git Hook Setup

A commit-msg hook enforces the no-attribution rule. Set it up after cloning:

```bash
git config core.hooksPath .githooks
```

The hook will reject commits containing Claude Code attribution.

## What Not to Change

- **Hook priorities** — `init` at priority 0 for early request interception
- **Exit after serve** — Always `exit` after serving custom content
- **Content-Type headers** — Must be set before output
- **Button structure** — Two-element pattern required for WordPress styling
- **Skip serialization** — Required for style variations to work

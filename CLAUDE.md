# CLAUDE.md

This file provides context for AI-assisted development on this project.

## Project Overview

**What this is:** A WordPress plugin that implements the [llmstxt.org](https://llmstxt.org/) standard. It exposes site content as clean markdown for AI consumption.

**Primary function:** Convert WordPress Gutenberg content to markdown and serve it via `/llms.txt`, `/llms-full.txt`, and `*.md` endpoints.

## Architecture

```
Request → Endpoint Handler → Generator/Converter → Response
```

### Request Flow

1. **Early interception** — `TGP_Endpoint_Handler` hooks into `init` at priority 0
2. **Pattern matching** — Checks for `.md` or `llms.txt` in the URL path
3. **Content generation** — Routes to appropriate class
4. **Response** — Serves with correct Content-Type header

### Class Responsibilities

| Class | Single Responsibility |
|-------|----------------------|
| `TGP_Endpoint_Handler` | Route requests to handlers |
| `TGP_LLMs_Txt_Generator` | Build the llms.txt index |
| `TGP_Markdown_Converter` | Transform HTML to markdown |
| `TGP_Frontmatter` | Generate YAML metadata |
| `TGP_UI_Buttons` | Frontend copy functionality |

## Code Conventions

### PHP Standards

**WordPress Coding Standards** — This plugin follows [WordPress PHP Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/).

```php
// Class names: Capitalized words with underscores
class TGP_Markdown_Converter {}

// Method names: lowercase with underscores
public function convert_headings( $content ) {}

// Private methods: same convention, prefixed logic
private function strip_block_comments( $content ) {}

// Hooks: use array syntax for class methods
add_action( 'init', [ $this, 'method_name' ] );

// Spacing: space inside parentheses
if ( $condition ) {
    // code
}
```

### File Naming

```
class-{name}.php        → Contains a single class
{block-name}/           → Gutenberg block directory
  ├── block.json        → Block registration
  ├── index.js          → Editor script
  ├── view.js           → Frontend script
  ├── render.php        → Server-side render
  ├── style.css         → Frontend styles
  └── editor.css        → Editor-only styles
```

### Class Structure

Each class follows this order:

```php
class TGP_Example {

    // 1. Properties (private first, then protected, then public)
    private $property;

    // 2. Constructor
    public function __construct() {}

    // 3. Public methods (main API)
    public function generate() {}

    // 4. Private methods (internal helpers)
    private function helper_method() {}
}
```

### Security Patterns

```php
// Always check for direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Escape output
echo esc_html( $title );
echo esc_url( $url );
echo esc_attr( $attribute );

// Sanitize input
$slug = sanitize_title( $input );
$text = sanitize_text_field( $input );

// Nonce verification for AJAX
wp_verify_nonce( $_POST['nonce'], 'tgp_llm_nonce' );
```

### Error Handling

```php
// Return early on invalid state
if ( empty( $post_name ) ) {
    $this->send_404();
    return;
}

// Use WordPress error patterns
if ( ! $found_post ) {
    $this->send_404();
    return;
}
```

## Domain Language

Use these terms consistently:

| Term | Meaning |
|------|---------|
| Endpoint | A URL route handled by this plugin |
| Converter | Transforms HTML to markdown |
| Generator | Builds content from scratch (llms.txt) |
| Frontmatter | YAML metadata block at top of .md files |
| Block | Gutenberg editor block |

## Testing Changes

### Manual Testing

```bash
# Test llms.txt endpoint
curl -s "http://localhost:10003/llms.txt"

# Test markdown endpoint
curl -s "http://localhost:10003/about.md"

# Check Content-Type header
curl -I "http://localhost:10003/about.md" | grep Content-Type
```

### Expected Behavior

| Request | Expected Response |
|---------|-------------------|
| `/llms.txt` | Plain text index, 200 OK |
| `/about.md` | Markdown with frontmatter, 200 OK |
| `/nonexistent.md` | 404 Not Found |
| `/about` (no .md) | Normal WordPress page |

## Common Tasks

### Adding a new endpoint

1. Add pattern match in `TGP_Endpoint_Handler::check_for_custom_endpoints()`
2. Add rewrite rule in `TGP_Endpoint_Handler::add_rewrite_rules()`
3. Add handler method in appropriate class
4. Flush permalinks after changes

### Modifying markdown output

The conversion pipeline in `TGP_Markdown_Converter::convert()`:

```php
$content = $this->strip_block_comments( $content );  // Step 1
$content = $this->convert_headings( $content );       // Step 2
$content = $this->convert_paragraphs( $content );     // Step 3
// ... more conversions
$content = $this->cleanup( $content );                // Final step
```

Add new conversions between strip and cleanup. Order matters — headings before paragraphs, block elements before inline.

### Adding frontmatter fields

Edit `TGP_Frontmatter::generate()`. Follow the existing YAML format:

```yaml
---
title: "Page Title"
new_field: "value"
---
```

## What Not to Change

- **Hook priorities** — `init` at priority 0 is intentional for early request interception
- **Exit after serve** — Always `exit` after serving custom content
- **Content-Type headers** — Must be set before output

## Dependencies

- WordPress 5.0+ (Gutenberg)
- PHP 8.2+
- No external PHP dependencies
- No npm build process (vanilla JS in blocks)

## File Locations

```
Plugin root:     wp-content/plugins/tgp-llms-txt/
Main file:       tgp-llms-txt.php
Classes:         includes/
Blocks:          blocks/
```

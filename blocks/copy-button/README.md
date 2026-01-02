# Copy for LLM (`tgp/copy-button`)

A button that copies the current page content as markdown for AI assistants.

## Purpose

Provides a one-click way for users to copy page content in a format optimized for AI tools like ChatGPT, Claude, or other LLMs. When clicked, the button fetches the page's `.md` endpoint and copies the markdown to the clipboard.

## Requirements

- WordPress 6.5+ (Interactivity API)
- Must be placed inside a `core/buttons` block

## Attributes

| Attribute | Type | Default | Description |
|-----------|------|---------|-------------|
| `label` | `string` | `"Copy for LLM"` | Button text |
| `showIcon` | `boolean` | `true` | Show clipboard icon |
| `width` | `number` | — | Button width percentage (25, 50, 75, 100) |
| `backgroundColor` | `string` | — | Background color preset slug |
| `textColor` | `string` | — | Text color preset slug |
| `gradient` | `string` | — | Gradient preset slug |

## Style Variations

| Name | Description |
|------|-------------|
| `fill` | Solid background (default) |
| `outline` | Transparent with border |

## Usage

```html
<!-- wp:buttons -->
<div class="wp-block-buttons">
  <!-- wp:tgp/copy-button {"label":"Copy for AI"} /-->
</div>
<!-- /wp:buttons -->
```

## Behavior

1. User clicks the button
2. Button shows "Copying..." state
3. Fetches `/current-page.md` endpoint
4. Copies markdown content to clipboard
5. Shows "Copied!" success state for 2 seconds
6. Returns to default state

If an error occurs (network failure, clipboard permission denied), shows "Failed" state.

## Architecture

### Files

| File | Purpose |
|------|---------|
| [block.json](block.json) | Block metadata and configuration |
| [index.js](index.js) | Editor component (inherits from core/button) |
| [render.php](render.php) | Server-side rendering with `TGP_Button_Block_Renderer` |
| [view.js](view.js) | Interactivity API store for copy functionality |
| [style.css](style.css) | Loading animation styles |

### Shared Helpers

Uses `TGP_Button_Block_Renderer` for:
- Style attribute extraction
- Class building (outer/inner)
- Inline style generation

Uses `TGP_SVG_Sanitizer` for icon rendering.

### Interactivity API

**Store:** `tgp/copy-button`

**Context:**
```json
{
  "mdUrl": "https://example.com/page.md",
  "label": "Copy for LLM",
  "labelCopying": "Copying...",
  "labelSuccess": "Copied!",
  "labelError": "Failed",
  "copyState": "idle"
}
```

**State (computed):**
- `buttonText` - Current button label based on state
- `isLoading` - Whether copy is in progress
- `isDisabled` - Whether button should be disabled

**Actions:**
- `copyMarkdown` - Generator function that handles fetch and clipboard

## Block Supports

This block uses `__experimentalSkipSerialization` for all style supports to give full control to the shared helper:

- Color (background, text, gradients)
- Typography (size, weight, family, etc.)
- Spacing (padding)
- Border (radius, width, style, color)
- Shadow

## Known Limitations

1. Requires clipboard permission in the browser
2. Does not work in HTTP (non-secure) contexts
3. Parent block (`core/buttons`) required for proper styling

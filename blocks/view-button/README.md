# View as Markdown (`tgp/view-button`)

A button that links to the markdown version of the current page.

## Purpose

Provides a link to view the page's content as plain markdown in a new tab. Useful for users who want to see the raw markdown format or manually copy content.

## Requirements

- Must be placed inside a `core/buttons` block

## Attributes

| Attribute | Type | Default | Description |
|-----------|------|---------|-------------|
| `label` | `string` | `"View as Markdown"` | Button text |
| `showIcon` | `boolean` | `true` | Show external link icon |
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
  <!-- wp:tgp/view-button {"label":"View Raw"} /-->
</div>
<!-- /wp:buttons -->
```

## Behavior

1. User clicks the button
2. New tab opens with the `.md` endpoint
3. Browser displays plain text markdown

No JavaScript interaction required beyond standard link behavior.

## Architecture

### Files

| File | Purpose |
|------|---------|
| [block.json](block.json) | Block metadata and configuration |
| [index.js](index.js) | Editor component (inherits from core/button) |
| [render.php](render.php) | Server-side rendering with `TGP_Button_Block_Renderer` |
| [style.css](style.css) | Icon positioning styles |

### Shared Helpers

Uses `TGP_Button_Block_Renderer` for:
- Style attribute extraction
- Class building (outer/inner)
- Inline style generation

Uses `TGP_SVG_Sanitizer` for icon rendering.

### No Interactivity API

This block renders a simple `<a>` tag and does not require the Interactivity API. It supports client navigation for SPAs via `interactivity.clientNavigation: true`.

## Block Supports

This block uses `__experimentalSkipSerialization` for all style supports to give full control to the shared helper:

- Color (background, text, gradients)
- Typography (size, weight, family, etc.)
- Spacing (padding)
- Border (radius, width, style, color)
- Shadow

## Comparison with Copy Button

| Feature | View Button | Copy Button |
|---------|-------------|-------------|
| Action | Opens link | Copies to clipboard |
| Interactivity | None | Full Interactivity API |
| Accessibility | Standard link | Button with aria-live |
| Offline | N/A | Requires network |

## Known Limitations

1. Opens in new tab (cannot be configured)
2. Parent block (`core/buttons`) required for proper styling

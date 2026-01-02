# Button Styling Guide

This guide covers how to style the **Copy for LLM** and **View as Markdown** buttons in your WordPress theme.

## Overview

Both button blocks automatically inherit your theme's button styles through WordPress Block Supports API. They support all standard WordPress button features including:

- Style variations (Fill, Outline, theme-defined styles)
- Custom colors (background, text, gradient)
- Typography (font size, weight, line height)
- Spacing (padding)
- Border (radius, width, color)
- Shadow

## Available Style Variations

The blocks support the standard WordPress button styles plus any theme-defined variations.

### Core Styles

| Style | Description |
|-------|-------------|
| **Fill** | Default solid button with background color |
| **Outline** | Transparent background with border |

### Theme Styles (Ollie theme example)

If your theme defines button variations in `theme.json`, they're automatically available:

| Style | Slug | Colors |
|-------|------|--------|
| **Brand** | `button-brand` | Primary background, base text |
| **Brand Alt** | `button-brand-alt` | Primary-alt background, primary-alt-accent text |
| **Dark** | `button-dark` | Main (dark) background, base text |
| **Light** | `button-light` | Base (white) background, main text |
| **Tint** | `secondary-button` | Tertiary background, main text |

## Applying Styles in the Editor

### Method 1: Style Picker (Recommended)

1. Select the button block in the editor
2. In the right sidebar, find the **Styles** panel
3. Click on the style variation you want

The style picker shows a visual preview of each variation.

### Method 2: Block Toolbar

1. Select the button block
2. Click the **Styles** icon in the block toolbar (paintbrush icon)
3. Choose from available variations

### Method 3: Custom Colors

When using the default **Fill** style, you can set custom colors:

1. Select the button block
2. In the sidebar, expand **Color** settings
3. Set **Background** and **Text** colors

**Note:** Custom colors only apply when using the Fill style. Other style variations (Outline, Brand, etc.) use their predefined colors.

## HTML Structure

The buttons render with a two-element structure matching WordPress core buttons:

```html
<!-- Outer wrapper with style variation class -->
<div class="wp-block-button is-style-button-brand">
  <!-- Inner button element -->
  <button class="wp-block-button__link wp-element-button tgp-copy-btn">
    <span class="tgp-btn-icon">...</span>
    <span class="tgp-btn-text">Copy for LLM</span>
  </button>
</div>
```

Key classes:
- `is-style-{variation}` — Applied to outer wrapper, determines styling
- `wp-block-button__link` — Standard WordPress button class
- `wp-element-button` — WordPress element class for global styles
- `tgp-copy-btn` / `tgp-view-btn` — Plugin-specific class

## How Styling Works

### Style Variations

When you select a style variation (e.g., Brand), WordPress adds `is-style-button-brand` to the outer wrapper. The plugin's CSS then applies the appropriate colors:

```css
.wp-block-button.is-style-button-brand .wp-block-button__link {
  background-color: var(--wp--preset--color--primary);
  color: var(--wp--preset--color--base);
}
```

### Custom Colors

When using Fill style with custom colors, the inner button receives color classes:

```html
<button class="wp-block-button__link wp-element-button tgp-copy-btn
               has-background has-primary-background-color
               has-text-color has-base-color">
```

### Priority System

The plugin uses `__experimentalSkipSerialization` to prevent conflicts between style variations and WordPress's automatic color class serialization. This ensures:

1. Style variations take precedence when selected
2. Custom colors only apply to Fill style
3. No CSS specificity conflicts with `!important` rules

## Adding Custom Theme Styles

To add your own button variations, create a JSON file in your theme:

```
your-theme/
└── styles/
    └── blocks/
        └── button/
            └── button-custom.json
```

Example content:

```json
{
  "$schema": "https://schemas.wp.org/trunk/theme.json",
  "version": 3,
  "title": "Custom Style",
  "slug": "button-custom",
  "blockTypes": ["core/button"],
  "styles": {
    "color": {
      "background": "var:preset|color|your-color",
      "text": "var:preset|color|your-text-color"
    }
  }
}
```

The plugin automatically detects theme button variations and generates the corresponding CSS for its blocks.

## Block Settings

Both buttons include additional settings in the sidebar:

### Width

Control button width (25%, 50%, 75%, 100%):

```html
<div class="wp-block-button has-custom-width wp-block-button__width-50">
```

### Show Icon

Toggle the icon visibility (copy icon for Copy button, document icon for View button).

## Troubleshooting

### Style not applying

1. **Check the wrapper class** — Inspect the button in browser DevTools. The outer `div` should have `is-style-{variation}`.

2. **Clear caches** — If using a caching plugin, clear all caches after changing styles.

3. **Check for conflicting CSS** — Custom theme CSS may override button styles. Use browser DevTools to identify conflicts.

### Custom colors not working

Custom colors only work with the **Fill** style. If you select a style variation (Outline, Brand, etc.), the variation's colors take precedence.

### Buttons look different in editor vs frontend

The plugin synchronizes editor and frontend styling. If they differ:

1. Hard refresh the editor (Cmd/Ctrl + Shift + R)
2. Check for editor-only CSS in your theme

## CSS Reference

### Plugin Classes

| Class | Element | Purpose |
|-------|---------|---------|
| `tgp-copy-btn` | `<button>` | Copy button identifier |
| `tgp-view-btn` | `<a>` | View button identifier |
| `tgp-btn-icon` | `<span>` | Icon wrapper |
| `tgp-btn-text` | `<span>` | Label text wrapper |
| `is-loading` | `<button>` | Applied during copy operation |

### Loading States (Copy Button)

```css
.tgp-copy-btn.is-loading {
  opacity: 0.7;
  cursor: wait;
}

.tgp-copy-btn.is-loading .tgp-btn-icon svg {
  animation: tgp-spin 1s linear infinite;
}
```

### Custom Styling Example

To customize the buttons beyond theme variations:

```css
/* Custom hover effect */
.tgp-copy-btn:hover,
.tgp-view-btn:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

/* Custom icon color */
.tgp-copy-btn .tgp-btn-icon svg {
  stroke: currentColor;
}

/* Larger icon */
.tgp-copy-btn .tgp-btn-icon svg,
.tgp-view-btn .tgp-btn-icon svg {
  width: 1.2em;
  height: 1.2em;
}
```

## Technical Details

For implementation details on how style variations are handled, see [PLAN-button-styling.md](../PLAN-button-styling.md).

### Key Implementation Points

1. **`block.json`** — Uses `__experimentalSkipSerialization` for color, typography, spacing, border, and shadow to prevent automatic class serialization.

2. **`render.php`** — Detects style variation from wrapper attributes and only adds color classes when using Fill style (or no variation).

3. **`index.js`** — Editor preview mirrors frontend logic with conditional color class application.

4. **`tgp-llms-txt.php`** — Generates CSS for theme button variations dynamically.

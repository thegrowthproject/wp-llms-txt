# ADR 002: Skip Serialization Pattern for Style Variations

## Status

Accepted

## Context

WordPress Block Supports automatically serialize style attributes into class names and inline styles on the block wrapper. For example:

```json
{
  "backgroundColor": "primary"
}
```

Becomes:

```html
<div class="has-primary-background-color has-background">
```

This works well for simple blocks, but creates problems when:

1. **Style variations** need different colors (e.g., outline vs fill)
2. **Inner elements** need the styles (not the wrapper)
3. **Conditional styling** is needed (e.g., only when active)

Our button blocks (`copy-button`, `view-button`) and filter pills (`blog-category-filter`) need styles applied to the inner `<button>` or `<a>` element, not the outer wrapper.

## Decision

Use `__experimentalSkipSerialization` for all style supports and handle styling manually in PHP.

### block.json Configuration

```json
{
  "supports": {
    "color": {
      "__experimentalSkipSerialization": true,
      "background": true,
      "text": true
    },
    "typography": {
      "__experimentalSkipSerialization": [
        "fontSize", "lineHeight", "fontFamily",
        "fontWeight", "fontStyle", "textTransform",
        "textDecoration", "letterSpacing"
      ]
    },
    "spacing": {
      "__experimentalSkipSerialization": true,
      "padding": true
    },
    "__experimentalBorder": {
      "__experimentalSkipSerialization": true,
      "radius": true,
      "width": true
    },
    "shadow": {
      "__experimentalSkipSerialization": true
    }
  }
}
```

### PHP Implementation

Extract styles in `render.php` and apply to the correct element:

```php
$style_attrs = TGP_Button_Block_Renderer::get_style_attributes( $attributes );
$style_info  = TGP_Button_Block_Renderer::get_style_variation( $wrapper_attrs );

$inner_classes = TGP_Button_Block_Renderer::build_inner_classes(
    $style_attrs,
    'wp-block-tgp-copy-button',
    $style_info['has_variation']
);

$style_attr = TGP_Button_Block_Renderer::get_style_attribute(
    $style_attrs,
    $style_info['has_variation']
);
```

## Consequences

### Positive

1. **Full control** - Styles applied exactly where needed
2. **Style variation support** - Different styles for different states
3. **Conditional styling** - Apply only when conditions met (e.g., active pill)
4. **Inner element styling** - Buttons get styles, not wrappers

### Negative

1. **More code** - Manual style extraction required
2. **Maintenance burden** - Must update when Block Supports change
3. **Duplication risk** - Same logic needed in multiple blocks

### Mitigation

Created shared helper classes to consolidate logic:

- `TGP_Button_Block_Renderer` - For button-style blocks
- `TGP_Pill_Block_Renderer` (planned) - For toggleable pill blocks

## Style Variation Interaction

When a style variation is active (e.g., `is-style-outline`), custom colors should NOT be applied - the variation's CSS handles colors.

```php
// Only apply color classes/styles if NOT using a variation
if ( ! $has_style_variation ) {
    if ( $style_attrs['bg_color_slug'] ) {
        $classes[] = 'has-' . $style_attrs['bg_color_slug'] . '-background-color';
    }
}
```

This allows themes to define variation colors without user overrides breaking them.

## Related

- `TGP_Button_Block_Renderer` class
- WordPress Block Supports documentation
- ADR 003: Shared Helper Classes

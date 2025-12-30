# Implementation Plan: Button Style Variations (Option A)

## Overview

Implement proper theme button style variation support for `tgp/copy-button` and `tgp/view-button` blocks by following the WordPress core button pattern using `__experimentalSkipSerialization`.

## Problem Summary

Currently, WordPress automatically serializes color attributes to `has-*-background-color` classes on the inner button element. These classes have `!important` in global-styles, which overrides our style variation CSS.

**Current broken flow:**
1. User selects "Brand" style variation
2. WordPress adds `is-style-button-brand` to outer wrapper (correct)
3. WordPress also adds `has-primary-background-color` to inner button (from Block Supports)
4. Global-styles CSS: `.has-primary-background-color { background-color: ... !important; }`
5. Our variation CSS without `!important` is overridden

## Solution

Use `__experimentalSkipSerialization` to prevent automatic color class serialization, then manually apply colors only when no style variation is selected (or when user explicitly sets custom colors).

---

## Files to Modify

### 1. `blocks/copy-button/block.json`
### 2. `blocks/view-button/block.json`

**Changes:**
- Add `__experimentalSkipSerialization` to color, typography, spacing, border, and shadow supports
- This matches core/button's approach

```json
"supports": {
    "color": {
        "__experimentalSkipSerialization": true,
        "gradients": true,
        "__experimentalDefaultControls": {
            "background": true,
            "text": true
        }
    },
    "typography": {
        "__experimentalSkipSerialization": [
            "fontSize",
            "lineHeight",
            "fontFamily",
            "fontWeight",
            "fontStyle",
            "textTransform",
            "textDecoration",
            "letterSpacing"
        ],
        "fontSize": true,
        ...
    },
    "shadow": {
        "__experimentalSkipSerialization": true
    },
    "spacing": {
        "__experimentalSkipSerialization": true,
        "padding": [ "horizontal", "vertical" ],
        ...
    },
    "__experimentalBorder": {
        "__experimentalSkipSerialization": true,
        "color": true,
        ...
    }
}
```

---

### 3. `blocks/copy-button/render.php`
### 4. `blocks/view-button/render.php`

**Changes:**
- Detect if a style variation is active (has `is-style-*` class that's not `is-style-fill`)
- Only apply custom color classes when NO style variation is active OR user has explicitly set custom colors
- Build inline styles manually for typography, spacing, border, shadow

**New Logic:**

```php
// Get wrapper attributes (will now be minimal due to skip serialization)
$wrapper_attrs_string = get_block_wrapper_attributes();

// Check if using a style variation (not default fill)
$has_style_variation = false;
$style_class = '';
if ( preg_match( '/is-style-([a-z0-9-]+)/', $wrapper_attrs_string, $style_match ) ) {
    $style_class = $style_match[1];
    // Default 'fill' style should allow custom colors
    $has_style_variation = ( $style_class !== 'fill' );
}

// Build outer wrapper classes
$outer_classes = [ 'wp-block-button' ];
if ( $style_class ) {
    $outer_classes[] = 'is-style-' . $style_class;
}
if ( $width ) {
    $outer_classes[] = 'has-custom-width';
    $outer_classes[] = 'wp-block-button__width-' . $width;
}

// Build inner button classes
$inner_classes = [ 'wp-block-button__link', 'wp-element-button', 'tgp-copy-btn' ];

// Only add color classes if NOT using a style variation
// This allows the variation CSS to apply without conflict
if ( ! $has_style_variation ) {
    // Check for custom colors in attributes
    $bg_color = $attributes['backgroundColor'] ?? null;
    $text_color = $attributes['textColor'] ?? null;
    $gradient = $attributes['gradient'] ?? null;

    if ( $bg_color ) {
        $inner_classes[] = 'has-background';
        $inner_classes[] = 'has-' . $bg_color . '-background-color';
    }
    if ( $text_color ) {
        $inner_classes[] = 'has-text-color';
        $inner_classes[] = 'has-' . $text_color . '-color';
    }
    if ( $gradient ) {
        $inner_classes[] = 'has-background';
        $inner_classes[] = 'has-' . $gradient . '-gradient-background';
    }
}

// Build inline styles from attributes (since we skipped serialization)
$inline_styles = [];

// Custom color styles (only if not using variation)
if ( ! $has_style_variation ) {
    $custom_bg = $attributes['style']['color']['background'] ?? null;
    $custom_text = $attributes['style']['color']['text'] ?? null;
    $custom_gradient = $attributes['style']['color']['gradient'] ?? null;

    if ( $custom_bg ) {
        $inline_styles[] = 'background-color: ' . $custom_bg;
    }
    if ( $custom_text ) {
        $inline_styles[] = 'color: ' . $custom_text;
    }
    if ( $custom_gradient ) {
        $inline_styles[] = 'background: ' . $custom_gradient;
    }
}

// Typography styles (always apply)
$font_size = $attributes['style']['typography']['fontSize'] ?? null;
$line_height = $attributes['style']['typography']['lineHeight'] ?? null;
$font_weight = $attributes['style']['typography']['fontWeight'] ?? null;
$font_family = $attributes['style']['typography']['fontFamily'] ?? null;
$letter_spacing = $attributes['style']['typography']['letterSpacing'] ?? null;
$text_transform = $attributes['style']['typography']['textTransform'] ?? null;
$text_decoration = $attributes['style']['typography']['textDecoration'] ?? null;

if ( $font_size ) { $inline_styles[] = 'font-size: ' . $font_size; }
if ( $line_height ) { $inline_styles[] = 'line-height: ' . $line_height; }
if ( $font_weight ) { $inline_styles[] = 'font-weight: ' . $font_weight; }
if ( $font_family ) { $inline_styles[] = 'font-family: ' . $font_family; }
if ( $letter_spacing ) { $inline_styles[] = 'letter-spacing: ' . $letter_spacing; }
if ( $text_transform ) { $inline_styles[] = 'text-transform: ' . $text_transform; }
if ( $text_decoration ) { $inline_styles[] = 'text-decoration: ' . $text_decoration; }

// Spacing styles (always apply)
$padding = $attributes['style']['spacing']['padding'] ?? null;
if ( $padding ) {
    if ( is_array( $padding ) ) {
        if ( isset( $padding['top'] ) ) { $inline_styles[] = 'padding-top: ' . $padding['top']; }
        if ( isset( $padding['right'] ) ) { $inline_styles[] = 'padding-right: ' . $padding['right']; }
        if ( isset( $padding['bottom'] ) ) { $inline_styles[] = 'padding-bottom: ' . $padding['bottom']; }
        if ( isset( $padding['left'] ) ) { $inline_styles[] = 'padding-left: ' . $padding['left']; }
    }
}

// Border styles (always apply)
$border = $attributes['style']['border'] ?? null;
if ( $border ) {
    if ( isset( $border['radius'] ) ) { $inline_styles[] = 'border-radius: ' . $border['radius']; }
    if ( isset( $border['width'] ) ) { $inline_styles[] = 'border-width: ' . $border['width']; }
    if ( isset( $border['style'] ) ) { $inline_styles[] = 'border-style: ' . $border['style']; }
    if ( isset( $border['color'] ) ) { $inline_styles[] = 'border-color: ' . $border['color']; }
}

// Shadow styles (always apply)
$shadow = $attributes['style']['shadow'] ?? null;
if ( $shadow ) {
    $inline_styles[] = 'box-shadow: ' . $shadow;
}

// Build style attribute
$style_attr = ! empty( $inline_styles ) ? ' style="' . esc_attr( implode( '; ', $inline_styles ) ) . '"' : '';
```

---

### 5. `blocks/copy-button/index.js`
### 6. `blocks/view-button/index.js`

**Changes:**
- Update editor script to match render.php logic
- Detect style variation from className
- Only apply color classes/styles when not using a variation

**Key changes to edit function:**

```javascript
edit: function( props ) {
    const { attributes, setAttributes, className } = props;
    const { label, showIcon, width, backgroundColor, textColor, gradient, style } = attributes;

    // Check if using a style variation (from className)
    const styleMatch = className ? className.match( /is-style-([a-z0-9-]+)/ ) : null;
    const styleVariation = styleMatch ? styleMatch[1] : null;
    const hasStyleVariation = styleVariation && styleVariation !== 'fill';

    // Build outer wrapper classes
    const wrapperClasses = [ 'wp-block-button' ];
    if ( width ) {
        wrapperClasses.push( 'has-custom-width' );
        wrapperClasses.push( 'wp-block-button__width-' + width );
    }

    const blockProps = useBlockProps( {
        className: wrapperClasses.join( ' ' )
    } );

    // Build inner button classes
    const innerClasses = [ 'wp-block-button__link', 'wp-element-button', 'tgp-copy-btn' ];

    // Only add color classes if NOT using a style variation
    if ( ! hasStyleVariation ) {
        if ( backgroundColor ) {
            innerClasses.push( 'has-background' );
            innerClasses.push( 'has-' + backgroundColor + '-background-color' );
        }
        if ( textColor ) {
            innerClasses.push( 'has-text-color' );
            innerClasses.push( 'has-' + textColor + '-color' );
        }
        if ( gradient ) {
            innerClasses.push( 'has-background' );
            innerClasses.push( 'has-' + gradient + '-gradient-background' );
        }
    }

    // Build inline styles
    const innerStyles = {};

    // Color styles only if not using variation
    if ( ! hasStyleVariation ) {
        if ( style?.color?.background ) {
            innerStyles.backgroundColor = style.color.background;
        }
        if ( style?.color?.text ) {
            innerStyles.color = style.color.text;
        }
        if ( style?.color?.gradient ) {
            innerStyles.background = style.color.gradient;
        }
    }

    // Typography/spacing/border always apply
    // ... (copy from blockProps.style or build manually)

    const innerProps = {
        className: innerClasses.join( ' ' ),
        style: innerStyles
    };

    // ... rest of edit function
}
```

---

### 7. `tgp-llms-txt.php`

**Changes to `enqueue_button_variation_styles()`:**
- Keep the current CSS generation logic
- Ensure outline fix remains in place
- The CSS will now work because inner buttons won't have conflicting `has-*` classes

**No major changes needed** - the current implementation will work once render.php stops adding conflicting classes.

---

## New Attributes

Add color attributes to block.json to support custom colors:

```json
"attributes": {
    "label": { ... },
    "showIcon": { ... },
    "width": { ... },
    "backgroundColor": {
        "type": "string"
    },
    "textColor": {
        "type": "string"
    },
    "gradient": {
        "type": "string"
    }
}
```

---

## Testing Plan

1. **Style Variations Work**
   - Select each style (Fill, Outline, Brand, Brand Alt, Dark, Light, Tint)
   - Verify correct colors apply on frontend
   - Verify correct colors show in editor

2. **Custom Colors Work (when no variation selected)**
   - Set custom background color
   - Set custom text color
   - Set gradient background
   - Verify these override default fill style

3. **Style Variation Overrides Custom Colors**
   - Set custom colors
   - Switch to a style variation
   - Verify variation colors take precedence
   - Switch back to Fill
   - Verify custom colors return

4. **Editor Preview**
   - Hover over style options
   - Verify preview shows correct styling

5. **Typography/Spacing/Border**
   - Set custom font size, padding, border radius
   - Verify these apply regardless of style variation

---

## Order of Implementation

1. Update `block.json` files (add skipSerialization, add color attributes)
2. Update `render.php` files (implement conditional color logic)
3. Update `index.js` files (implement conditional color logic for editor)
4. Test all scenarios
5. Clean up any unused code in `tgp-llms-txt.php`

---

## Risks and Considerations

1. **Existing blocks in database**: Blocks already placed may have color attributes saved. The new logic should handle this gracefully by checking both style variation AND whether colors are explicitly set.

2. **User mental model**: Users might expect custom colors to work even with style variations. Consider whether we want an "override" behavior or "variation takes precedence" behavior.

3. **Editor preview for variations**: The style picker preview relies on WordPress internal mechanisms. Our changes shouldn't affect this, but testing is needed.

4. **Backwards compatibility**: The `tgp-llms-button-variations` CSS will still be generated but may become redundant if theme.json styles start applying correctly. We should keep it as a fallback.

---

## Alternative Simplification

If the full implementation is too complex, a simpler approach:

**In render.php only:**
- If `is-style-*` class exists (not fill), strip ALL `has-*-background-color` and `has-*-color` classes from inner element
- Keep everything else the same

This is less "correct" but achieves the same result with less code change.

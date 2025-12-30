# Block Development Guidelines

Guidelines for building WordPress Gutenberg blocks that properly support theme style variations.

## The Problem

WordPress Block Supports automatically serialize attributes to CSS classes. For colors, this creates classes like `has-primary-background-color`. These preset color classes use `!important` in global styles:

```css
.has-primary-background-color {
  background-color: var(--wp--preset--color--primary) !important;
}
```

When a user selects a style variation (e.g., "Outline"), the variation CSS cannot override the `!important` preset classes. The button looks wrong.

## The Solution

Use `__experimentalSkipSerialization` to prevent automatic class serialization, then manually apply color classes only when appropriate.

**Flow:**
1. User selects style variation → variation CSS applies (no conflicts)
2. User selects Fill + custom color → we manually add color classes
3. User selects Fill with no customization → default theme styles apply

---

## Implementation Checklist

For each new block that needs theme style variation support:

- [ ] Update `block.json` with skip serialization
- [ ] Update `render.php` with conditional color logic
- [ ] Update `index.js` (editor) with matching conditional logic
- [ ] Register style variations if needed
- [ ] Test all style variations on frontend and editor

---

## File Templates

### 1. block.json

Add `__experimentalSkipSerialization` to prevent automatic class output:

```json
{
  "$schema": "https://schemas.wp.org/trunk/block.json",
  "apiVersion": 3,
  "name": "tgp/your-block",
  "title": "Your Block",
  "category": "design",
  "attributes": {
    "label": {
      "type": "string",
      "default": "Button Text"
    },
    "backgroundColor": {
      "type": "string"
    },
    "textColor": {
      "type": "string"
    },
    "gradient": {
      "type": "string"
    }
  },
  "supports": {
    "html": false,
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
      "lineHeight": true,
      "__experimentalFontFamily": true,
      "__experimentalFontWeight": true,
      "__experimentalFontStyle": true,
      "__experimentalTextTransform": true,
      "__experimentalTextDecoration": true,
      "__experimentalLetterSpacing": true,
      "__experimentalDefaultControls": {
        "fontSize": true
      }
    },
    "shadow": {
      "__experimentalSkipSerialization": true
    },
    "spacing": {
      "__experimentalSkipSerialization": true,
      "padding": ["horizontal", "vertical"],
      "__experimentalDefaultControls": {
        "padding": true
      }
    },
    "__experimentalBorder": {
      "__experimentalSkipSerialization": true,
      "color": true,
      "radius": true,
      "style": true,
      "width": true,
      "__experimentalDefaultControls": {
        "color": true,
        "radius": true,
        "style": true,
        "width": true
      }
    }
  },
  "styles": [
    { "name": "fill", "label": "Fill", "isDefault": true },
    { "name": "outline", "label": "Outline" }
  ],
  "editorScript": "file:./index.js",
  "style": "file:./style.css",
  "render": "file:./render.php"
}
```

**Key points:**
- `__experimentalSkipSerialization: true` for color prevents `has-*-background-color` classes
- Array format for typography skips specific properties
- Color attributes (`backgroundColor`, `textColor`, `gradient`) must be explicitly defined
- `styles` array registers available variations

---

### 2. render.php

Detect style variation and conditionally apply colors:

```php
<?php
/**
 * Block render template.
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block content.
 * @var WP_Block $block      Block instance.
 */

// Get wrapper attributes (minimal due to skip serialization).
$wrapper_attrs_string = get_block_wrapper_attributes();

// === STYLE VARIATION DETECTION ===
$style_variation     = 'fill';
$has_style_variation = false;

if ( preg_match( '/is-style-([a-z0-9-]+)/', $wrapper_attrs_string, $style_match ) ) {
    $style_variation     = $style_match[1];
    $has_style_variation = ( 'fill' !== $style_variation );
}

// === BUILD OUTER WRAPPER CLASSES ===
$outer_classes = array( 'wp-block-button' );

if ( $style_variation ) {
    $outer_classes[] = 'is-style-' . $style_variation;
}

// Width support.
$width = $attributes['width'] ?? null;
if ( $width ) {
    $outer_classes[] = 'has-custom-width';
    $outer_classes[] = 'wp-block-button__width-' . $width;
}

// === BUILD INNER ELEMENT CLASSES ===
$inner_classes = array( 'wp-block-button__link', 'wp-element-button', 'your-block-class' );

// Only add color classes if NOT using a style variation.
if ( ! $has_style_variation ) {
    $bg_color   = $attributes['backgroundColor'] ?? null;
    $text_color = $attributes['textColor'] ?? null;
    $gradient   = $attributes['gradient'] ?? null;

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

    // Custom color values (not presets).
    $custom_bg    = $attributes['style']['color']['background'] ?? null;
    $custom_text  = $attributes['style']['color']['text'] ?? null;
    $custom_grad  = $attributes['style']['color']['gradient'] ?? null;

    if ( $custom_bg || $custom_grad ) {
        $inner_classes[] = 'has-background';
    }
    if ( $custom_text ) {
        $inner_classes[] = 'has-text-color';
    }
}

// === BUILD INLINE STYLES ===
$inline_styles = array();

// Color styles only if NOT using a style variation.
if ( ! $has_style_variation ) {
    $custom_bg   = $attributes['style']['color']['background'] ?? null;
    $custom_text = $attributes['style']['color']['text'] ?? null;
    $custom_grad = $attributes['style']['color']['gradient'] ?? null;

    if ( $custom_bg ) {
        $inline_styles[] = 'background-color: ' . $custom_bg;
    }
    if ( $custom_text ) {
        $inline_styles[] = 'color: ' . $custom_text;
    }
    if ( $custom_grad ) {
        $inline_styles[] = 'background: ' . $custom_grad;
    }
}

// Typography styles (always apply).
$typography_props = array(
    'fontSize'       => 'font-size',
    'lineHeight'     => 'line-height',
    'fontWeight'     => 'font-weight',
    'fontFamily'     => 'font-family',
    'letterSpacing'  => 'letter-spacing',
    'textTransform'  => 'text-transform',
    'textDecoration' => 'text-decoration',
);

foreach ( $typography_props as $attr => $css_prop ) {
    $value = $attributes['style']['typography'][ $attr ] ?? null;
    if ( $value ) {
        $inline_styles[] = $css_prop . ': ' . $value;
    }
}

// Spacing styles (always apply).
$padding = $attributes['style']['spacing']['padding'] ?? null;
if ( $padding && is_array( $padding ) ) {
    foreach ( array( 'top', 'right', 'bottom', 'left' ) as $side ) {
        if ( isset( $padding[ $side ] ) ) {
            $inline_styles[] = 'padding-' . $side . ': ' . $padding[ $side ];
        }
    }
}

// Border styles (always apply).
$border = $attributes['style']['border'] ?? null;
if ( $border ) {
    if ( isset( $border['radius'] ) ) {
        $inline_styles[] = 'border-radius: ' . $border['radius'];
    }
    if ( isset( $border['width'] ) ) {
        $inline_styles[] = 'border-width: ' . $border['width'];
    }
    if ( isset( $border['style'] ) ) {
        $inline_styles[] = 'border-style: ' . $border['style'];
    }
    if ( isset( $border['color'] ) ) {
        $inline_styles[] = 'border-color: ' . $border['color'];
    }
}

// Shadow styles (always apply).
$shadow = $attributes['style']['shadow'] ?? null;
if ( $shadow ) {
    $inline_styles[] = 'box-shadow: ' . $shadow;
}

// Build style attribute.
$style_attr = ! empty( $inline_styles )
    ? ' style="' . esc_attr( implode( '; ', $inline_styles ) ) . '"'
    : '';

// === RENDER ===
?>
<div class="<?php echo esc_attr( implode( ' ', $outer_classes ) ); ?>">
    <button
        class="<?php echo esc_attr( implode( ' ', $inner_classes ) ); ?>"
        <?php echo $style_attr; ?>
    >
        <?php echo esc_html( $attributes['label'] ?? 'Button' ); ?>
    </button>
</div>
```

---

### 3. index.js (Editor)

Mirror the render.php logic for consistent editor preview:

```javascript
( function( wp ) {
    const { registerBlockType } = wp.blocks;
    const { useBlockProps, InspectorControls, RichText } = wp.blockEditor;
    const { PanelBody } = wp.components;
    const { __ } = wp.i18n;
    const { createElement: el, Fragment } = wp.element;

    registerBlockType( 'tgp/your-block', {
        edit: function( props ) {
            const { attributes, setAttributes, className } = props;
            const {
                label,
                backgroundColor,
                textColor,
                gradient,
                style,
                width
            } = attributes;

            // === STYLE VARIATION DETECTION ===
            const styleMatch = className
                ? className.match( /is-style-([a-z0-9-]+)/ )
                : null;
            const styleVariation = styleMatch ? styleMatch[1] : 'fill';
            const hasStyleVariation = styleVariation && styleVariation !== 'fill';

            // === BUILD OUTER WRAPPER CLASSES ===
            const wrapperClasses = [ 'wp-block-button' ];
            if ( width ) {
                wrapperClasses.push( 'has-custom-width' );
                wrapperClasses.push( 'wp-block-button__width-' + width );
            }

            const blockProps = useBlockProps( {
                className: wrapperClasses.join( ' ' )
            } );

            // === BUILD INNER ELEMENT CLASSES ===
            const innerClasses = [
                'wp-block-button__link',
                'wp-element-button',
                'your-block-class'
            ];

            // Only add color classes if NOT using a style variation.
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
                if ( style && style.color ) {
                    if ( style.color.background || style.color.gradient ) {
                        innerClasses.push( 'has-background' );
                    }
                    if ( style.color.text ) {
                        innerClasses.push( 'has-text-color' );
                    }
                }
            }

            // === BUILD INLINE STYLES ===
            const innerStyles = {};

            // Color styles only if NOT using a style variation.
            if ( ! hasStyleVariation && style && style.color ) {
                if ( style.color.background ) {
                    innerStyles.backgroundColor = style.color.background;
                }
                if ( style.color.text ) {
                    innerStyles.color = style.color.text;
                }
                if ( style.color.gradient ) {
                    innerStyles.background = style.color.gradient;
                }
            }

            // Typography styles (always apply).
            if ( style && style.typography ) {
                const typoMap = {
                    fontSize: 'fontSize',
                    lineHeight: 'lineHeight',
                    fontWeight: 'fontWeight',
                    fontFamily: 'fontFamily',
                    letterSpacing: 'letterSpacing',
                    textTransform: 'textTransform',
                    textDecoration: 'textDecoration'
                };
                Object.keys( typoMap ).forEach( function( key ) {
                    if ( style.typography[ key ] ) {
                        innerStyles[ typoMap[ key ] ] = style.typography[ key ];
                    }
                } );
            }

            // Spacing styles (always apply).
            if ( style && style.spacing && style.spacing.padding ) {
                const padding = style.spacing.padding;
                if ( padding.top ) innerStyles.paddingTop = padding.top;
                if ( padding.right ) innerStyles.paddingRight = padding.right;
                if ( padding.bottom ) innerStyles.paddingBottom = padding.bottom;
                if ( padding.left ) innerStyles.paddingLeft = padding.left;
            }

            // Border styles (always apply).
            if ( style && style.border ) {
                if ( style.border.radius ) innerStyles.borderRadius = style.border.radius;
                if ( style.border.width ) innerStyles.borderWidth = style.border.width;
                if ( style.border.style ) innerStyles.borderStyle = style.border.style;
                if ( style.border.color ) innerStyles.borderColor = style.border.color;
            }

            // Shadow styles (always apply).
            if ( style && style.shadow ) {
                innerStyles.boxShadow = style.shadow;
            }

            const innerProps = {
                className: innerClasses.join( ' ' ),
                style: Object.keys( innerStyles ).length > 0 ? innerStyles : undefined
            };

            // === RENDER ===
            return el( Fragment, {},
                el( InspectorControls, {},
                    el( PanelBody, {
                        title: __( 'Settings', 'tgp-llms-txt' ),
                        initialOpen: true
                    },
                        // Add your controls here
                    )
                ),
                el( 'div', blockProps,
                    el( 'button', innerProps,
                        el( RichText, {
                            tagName: 'span',
                            value: label,
                            onChange: function( value ) {
                                setAttributes( { label: value } );
                            },
                            placeholder: __( 'Button text', 'tgp-llms-txt' ),
                            allowedFormats: []
                        } )
                    )
                )
            );
        },

        save: function() {
            return null; // Dynamic block, rendered via PHP.
        }
    } );
} )( window.wp );
```

---

## Registering Theme Style Variations

If your block should support theme-defined button styles (like Brand, Dark, Light), generate CSS dynamically:

```php
/**
 * Generate CSS for theme button variations.
 */
function enqueue_button_variation_styles() {
    // Get theme button variations.
    $variations = WP_Block_Styles_Registry::get_instance()
        ->get_registered_styles_for_block( 'core/button' );

    if ( empty( $variations ) ) {
        return;
    }

    $css = '';

    foreach ( $variations as $slug => $variation ) {
        // Skip core styles.
        if ( in_array( $slug, array( 'fill', 'outline' ), true ) ) {
            continue;
        }

        // Get style data from theme.json.
        $style_data = get_button_variation_styles( $slug );
        if ( ! $style_data ) {
            continue;
        }

        $bg_color   = $style_data['color']['background'] ?? null;
        $text_color = $style_data['color']['text'] ?? null;

        if ( $bg_color || $text_color ) {
            $css .= ".wp-block-button.is-style-{$slug} .wp-block-button__link { ";
            if ( $bg_color ) {
                $css .= "background-color: {$bg_color}; ";
            }
            if ( $text_color ) {
                $css .= "color: {$text_color}; ";
            }
            $css .= "}\n";
        }
    }

    if ( $css ) {
        wp_add_inline_style( 'your-block-style-handle', $css );
    }
}
add_action( 'wp_enqueue_scripts', 'enqueue_button_variation_styles' );
```

---

## Testing Checklist

After implementing, verify:

### Frontend
- [ ] Fill style with default colors works
- [ ] Fill style with custom background color works
- [ ] Fill style with custom text color works
- [ ] Fill style with gradient works
- [ ] Outline style works (transparent bg, border visible)
- [ ] Theme variations work (Brand, Dark, Light, Tint, etc.)
- [ ] Typography customizations apply
- [ ] Padding customizations apply
- [ ] Border customizations apply
- [ ] No `has-*-background-color` classes on inner element when using variations

### Editor
- [ ] Style picker shows all variations
- [ ] Style picker preview is accurate
- [ ] Switching styles updates preview immediately
- [ ] Custom colors show in preview (Fill style only)
- [ ] Typography/spacing/border show in preview

### Inspect HTML
```bash
# Check for conflicting classes
curl -s "http://yoursite.com/page-with-block/" | grep -A 5 "your-block-class"

# Should see:
# - Outer: is-style-{variation}
# - Inner: NO has-*-background-color when using variations
```

---

## Common Issues

### Issue: Style variation not applying

**Cause:** Inner element has `has-*-background-color` class with `!important`.

**Fix:** Ensure `__experimentalSkipSerialization: true` is set for color in block.json.

### Issue: Custom colors not working

**Cause:** Style variation is selected (not Fill).

**Expected:** Custom colors only work with Fill style. This is by design.

### Issue: Editor preview differs from frontend

**Cause:** index.js logic doesn't match render.php.

**Fix:** Ensure both files use identical conditional logic for style detection and class application.

### Issue: Theme variations not available

**Cause:** Variations not registered for your block type.

**Fix:** Either set `"blockTypes": ["core/button", "tgp/your-block"]` in theme variation JSON, or dynamically copy core/button variations to your block.

---

## References

- [WordPress Block Supports](https://developer.wordpress.org/block-editor/reference-guides/block-api/block-supports/)
- [Skip Serialization](https://developer.wordpress.org/block-editor/reference-guides/block-api/block-supports/#skipSerialization)
- [Block Styles](https://developer.wordpress.org/block-editor/reference-guides/block-api/block-styles/)
- [Theme.json Button Variations](https://developer.wordpress.org/themes/global-settings-and-styles/style-variations/)

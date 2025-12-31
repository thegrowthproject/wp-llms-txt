# Block Development Guidelines

Comprehensive guide for building WordPress Gutenberg blocks following WordPress core patterns and conventions.

---

## Prerequisites

### Requirements

- Node.js 18+
- npm 9+
- WordPress 6.5+ (Interactivity API)
- PHP 8.2+

### Quick Start

```bash
# Scaffold a new block (WordPress official tool)
npx @wordpress/create-block@latest your-block-name
cd your-block-name
npm start
```

### Development Commands

| Command | Description |
|---------|-------------|
| `npm start` | Start development with hot reloading |
| `npm run build` | Build for production |
| `npm run lint:js` | Lint JavaScript files |
| `npm run lint:css` | Lint CSS/SCSS files |
| `npm run format` | Format code with Prettier |

---

## Project Structure

### Recommended Directory Layout

```
src/
├── blocks/
│   └── your-block/
│       ├── block.json          # Block metadata & configuration
│       ├── index.js            # Registration entry point
│       ├── edit.js             # Editor component
│       ├── save.js             # Static save (omit for dynamic blocks)
│       ├── render.php          # Server-side rendering
│       ├── view.js             # Frontend interactivity (Interactivity API)
│       ├── style.scss          # Base styles (editor + frontend)
│       ├── editor.scss         # Editor-only styles
│       └── view.scss           # Frontend-only styles
├── components/                 # Shared React components
├── hooks/                      # Custom React hooks
└── utils/                      # Utility functions
```

### File Purposes

| File | Purpose | When to Use |
|------|---------|-------------|
| `index.js` | Block registration only | Always required |
| `edit.js` | Editor UI component | Always required |
| `save.js` | Static HTML output | Static blocks only |
| `render.php` | Dynamic PHP rendering | Dynamic blocks (recommended) |
| `view.js` | Frontend JavaScript | Interactive blocks |
| `style.scss` | Styles for both contexts | Always required |
| `editor.scss` | Editor-specific styles | Complex editor UI |
| `view.scss` | Frontend-specific styles | With Interactivity API |

---

## File Templates

### block.json

The block manifest defines metadata, attributes, and capabilities.

```json
{
    "$schema": "https://schemas.wp.org/trunk/block.json",
    "apiVersion": 3,
    "name": "tgp/your-block",
    "version": "1.0.0",
    "title": "Your Block",
    "category": "design",
    "description": "A custom block that does something useful.",
    "textdomain": "tgp-llms-txt",
    "keywords": [ "button", "action" ],
    "attributes": {
        "label": {
            "type": "string",
            "default": "Button Text"
        },
        "showIcon": {
            "type": "boolean",
            "default": true
        }
    },
    "supports": {
        "html": false,
        "color": {
            "background": true,
            "text": true,
            "gradients": true
        },
        "typography": {
            "fontSize": true,
            "lineHeight": true
        },
        "spacing": {
            "padding": true
        }
    },
    "editorScript": "file:./index.js",
    "editorStyle": "file:./editor.scss",
    "style": "file:./style.scss",
    "render": "file:./render.php",
    "viewScriptModule": "file:./view.js",
    "viewStyle": "file:./view.scss"
}
```

---

### index.js (Entry Point)

Keep the entry point minimal — registration only.

```javascript
/**
 * Block registration.
 *
 * @package TGP_LLMs_Txt
 */

import { registerBlockType } from '@wordpress/blocks';
import metadata from './block.json';
import Edit from './edit';
import save from './save';
import './style.scss';

/**
 * Custom block icon.
 */
const icon = (
    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
        <path
            d="M19 6.5H5a2 2 0 00-2 2v7a2 2 0 002 2h14a2 2 0 002-2v-7a2 2 0 00-2-2z"
            fill="none"
            stroke="currentColor"
            strokeWidth="1.5"
        />
    </svg>
);

/**
 * Register the block.
 */
registerBlockType( metadata.name, {
    ...metadata,
    icon,
    edit: Edit,
    save,
} );
```

---

### edit.js (Editor Component)

Separate file for the editor interface.

```javascript
/**
 * Editor component.
 *
 * @package TGP_LLMs_Txt
 */

import { __ } from '@wordpress/i18n';
import {
    useBlockProps,
    InspectorControls,
    RichText,
} from '@wordpress/block-editor';
import {
    PanelBody,
    ToggleControl,
    TextControl,
} from '@wordpress/components';

/**
 * Edit component for the block.
 *
 * @param {Object}   props               Block props.
 * @param {Object}   props.attributes    Block attributes.
 * @param {Function} props.setAttributes Attribute setter.
 * @return {Element} Editor element.
 */
export default function Edit( { attributes, setAttributes } ) {
    const { label, showIcon } = attributes;

    const blockProps = useBlockProps( {
        className: 'wp-block-tgp-your-block',
    } );

    return (
        <>
            <InspectorControls>
                <PanelBody
                    title={ __( 'Settings', 'tgp-llms-txt' ) }
                    initialOpen={ true }
                >
                    <ToggleControl
                        __nextHasNoMarginBottom
                        label={ __( 'Show Icon', 'tgp-llms-txt' ) }
                        checked={ showIcon }
                        onChange={ ( value ) =>
                            setAttributes( { showIcon: value } )
                        }
                    />
                </PanelBody>
            </InspectorControls>

            <div { ...blockProps }>
                <button className="wp-block-tgp-your-block__button">
                    { showIcon && (
                        <span className="wp-block-tgp-your-block__icon">
                            { /* Icon SVG */ }
                        </span>
                    ) }
                    <RichText
                        tagName="span"
                        className="wp-block-tgp-your-block__label"
                        value={ label }
                        onChange={ ( value ) =>
                            setAttributes( { label: value } )
                        }
                        placeholder={ __( 'Button text', 'tgp-llms-txt' ) }
                        allowedFormats={ [] }
                    />
                </button>
            </div>
        </>
    );
}
```

---

### save.js (Static Output)

For static blocks that don't need server rendering.

```javascript
/**
 * Save component.
 *
 * @package TGP_LLMs_Txt
 */

import { useBlockProps, RichText } from '@wordpress/block-editor';

/**
 * Save the block markup.
 *
 * @param {Object} props            Block props.
 * @param {Object} props.attributes Block attributes.
 * @return {Element|null} Saved element or null.
 */
export default function save( { attributes } ) {
    const { label, showIcon } = attributes;

    // Return null for incomplete blocks.
    if ( ! label ) {
        return null;
    }

    // Note: useBlockProps.save() not useBlockProps()
    const blockProps = useBlockProps.save( {
        className: 'wp-block-tgp-your-block',
    } );

    return (
        <div { ...blockProps }>
            <button className="wp-block-tgp-your-block__button">
                { showIcon && (
                    <span className="wp-block-tgp-your-block__icon">
                        { /* Icon SVG */ }
                    </span>
                ) }
                <RichText.Content
                    tagName="span"
                    className="wp-block-tgp-your-block__label"
                    value={ label }
                />
            </button>
        </div>
    );
}
```

**For dynamic blocks:** Return `null` from save and use `render.php` instead.

```javascript
export default function save() {
    return null;
}
```

---

### render.php (Server Rendering)

Dynamic PHP rendering for blocks that need server-side logic.

```php
<?php
/**
 * Block render template.
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block inner content.
 * @var WP_Block $block      Block instance.
 *
 * @package TGP_LLMs_Txt
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Extract attributes with defaults.
$label     = $attributes['label'] ?? __( 'Button', 'tgp-llms-txt' );
$show_icon = $attributes['showIcon'] ?? true;

// Generate unique ID for accessibility.
$unique_id = wp_unique_id( 'tgp-block-' );

// Get wrapper attributes from Block Supports.
$wrapper_attributes = get_block_wrapper_attributes( array(
    'class' => 'wp-block-tgp-your-block',
) );
?>

<div <?php echo $wrapper_attributes; ?>>
    <button
        id="<?php echo esc_attr( $unique_id ); ?>"
        class="wp-block-tgp-your-block__button"
        type="button"
    >
        <?php if ( $show_icon ) : ?>
            <span class="wp-block-tgp-your-block__icon" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                    <!-- Icon path -->
                </svg>
            </span>
        <?php endif; ?>

        <span class="wp-block-tgp-your-block__label">
            <?php echo esc_html( $label ); ?>
        </span>
    </button>
</div>
```

---

## Interactivity API

For blocks that need frontend JavaScript behavior, use the WordPress Interactivity API.

### Enable in block.json

```json
{
    "supports": {
        "interactivity": true
    },
    "viewScriptModule": "file:./view.js"
}
```

### view.js (Frontend Store)

```javascript
/**
 * Frontend interactivity.
 *
 * @package TGP_LLMs_Txt
 */

import { store, getContext } from '@wordpress/interactivity';

const { state } = store( 'tgp/your-block', {
    /**
     * Reactive state getters.
     */
    state: {
        get isDisabled() {
            const context = getContext();
            return context.isLoading;
        },

        get buttonText() {
            const context = getContext();
            if ( context.isLoading ) {
                return context.labelLoading;
            }
            if ( context.hasError ) {
                return context.labelError;
            }
            if ( context.isSuccess ) {
                return context.labelSuccess;
            }
            return context.label;
        },
    },

    /**
     * Actions triggered by user interaction.
     */
    actions: {
        *handleClick() {
            const context = getContext();
            context.isLoading = true;
            context.hasError = false;

            try {
                // Async operation
                const response = yield fetch( context.apiUrl );
                const data = yield response.json();

                context.isSuccess = true;

                // Reset after delay
                setTimeout( () => {
                    context.isSuccess = false;
                }, 2000 );
            } catch ( error ) {
                context.hasError = true;
            } finally {
                context.isLoading = false;
            }
        },
    },

    /**
     * Reactive callbacks.
     */
    callbacks: {
        onStateChange() {
            const context = getContext();
            // React to state changes
        },
    },
} );
```

### render.php (Interactivity Attributes)

```php
<?php
$wrapper_attributes = get_block_wrapper_attributes( array(
    'class'               => 'wp-block-tgp-your-block',
    'data-wp-interactive' => 'tgp/your-block',
    'data-wp-context'     => wp_json_encode( array(
        'isLoading'    => false,
        'isSuccess'    => false,
        'hasError'     => false,
        'label'        => $label,
        'labelLoading' => __( 'Loading...', 'tgp-llms-txt' ),
        'labelSuccess' => __( 'Done!', 'tgp-llms-txt' ),
        'labelError'   => __( 'Error', 'tgp-llms-txt' ),
        'apiUrl'       => rest_url( 'tgp/v1/action' ),
    ) ),
) );
?>

<div <?php echo $wrapper_attributes; ?>>
    <button
        class="wp-block-tgp-your-block__button"
        data-wp-on--click="actions.handleClick"
        data-wp-bind--disabled="state.isDisabled"
        data-wp-class--is-loading="context.isLoading"
        data-wp-class--is-success="context.isSuccess"
        data-wp-class--has-error="context.hasError"
    >
        <span
            class="wp-block-tgp-your-block__label"
            data-wp-text="state.buttonText"
        ></span>
    </button>
</div>
```

### Directive Reference

| Directive | Purpose | Example Value |
|-----------|---------|---------------|
| `data-wp-interactive` | Register store namespace | `"tgp/your-block"` |
| `data-wp-context` | Pass data to store | `wp_json_encode( $data )` |
| `data-wp-on--click` | Click handler | `"actions.handleClick"` |
| `data-wp-on--keydown` | Keyboard handler | `"actions.handleKeyDown"` |
| `data-wp-bind--disabled` | Bind attribute | `"state.isDisabled"` |
| `data-wp-bind--aria-expanded` | Bind ARIA | `"context.isOpen"` |
| `data-wp-class--is-loading` | Toggle class | `"context.isLoading"` |
| `data-wp-text` | Set text content | `"state.buttonText"` |
| `data-wp-watch` | Reactive callback | `"callbacks.onStateChange"` |

---

## SCSS & CSS Conventions

### File Organization

```scss
// style.scss - Loads in editor AND frontend
.wp-block-tgp-your-block {
    // Base styles that apply everywhere
}

// editor.scss - Editor only
.wp-block-tgp-your-block {
    // Editor-specific overrides (selection states, etc.)
}

// view.scss - Frontend only (requires viewScriptModule)
.wp-block-tgp-your-block {
    // Frontend-specific styles (animations, etc.)
}
```

### BEM Naming Convention

```scss
// Block
.wp-block-tgp-your-block {
    display: flex;
    align-items: center;
}

// Element (double underscore)
.wp-block-tgp-your-block__button {
    cursor: pointer;
}

.wp-block-tgp-your-block__icon {
    flex-shrink: 0;
}

.wp-block-tgp-your-block__label {
    white-space: nowrap;
}

// Modifier (double dash)
.wp-block-tgp-your-block--loading {
    opacity: 0.7;
}

.wp-block-tgp-your-block__button--active {
    background-color: var(--wp--preset--color--primary);
}
```

### Theme Integration

```scss
.wp-block-tgp-your-block {
    // Inherit from theme
    font-family: inherit;
    font-size: inherit;
    color: inherit;

    // Use WordPress CSS custom properties
    &__button {
        background-color: var(--wp--preset--color--primary);
        color: var(--wp--preset--color--base);
        padding: var(--wp--preset--spacing--small);
        border-radius: var(--wp--preset--spacing--20);
    }
}
```

### Screen Reader Class

```scss
.screen-reader-text {
    border: 0;
    clip: rect( 1px, 1px, 1px, 1px );
    clip-path: inset( 50% );
    height: 1px;
    margin: -1px;
    overflow: hidden;
    padding: 0;
    position: absolute;
    width: 1px;
    word-wrap: normal !important;
}
```

---

## Accessibility

### ARIA Relationships

```php
<?php
// Generate unique ID for ARIA relationships.
$unique_id    = wp_unique_id( 'tgp-block-' );
$controls_id  = $unique_id . '-content';
?>

<button
    id="<?php echo esc_attr( $unique_id ); ?>"
    aria-expanded="false"
    aria-controls="<?php echo esc_attr( $controls_id ); ?>"
    data-wp-bind--aria-expanded="context.isOpen"
>
    <?php echo esc_html( $label ); ?>
</button>

<div
    id="<?php echo esc_attr( $controls_id ); ?>"
    aria-hidden="true"
    data-wp-bind--aria-hidden="!context.isOpen"
>
    <!-- Controlled content -->
</div>
```

### Screen Reader Text

```php
<button>
    <span class="screen-reader-text">
        <?php esc_html_e( 'Toggle menu', 'tgp-llms-txt' ); ?>
    </span>
    <span aria-hidden="true">☰</span>
</button>
```

### Keyboard Navigation

```javascript
actions: {
    handleKeyDown( event ) {
        const context = getContext();

        switch ( event.key ) {
            case 'Escape':
                context.isOpen = false;
                // Return focus to trigger
                break;

            case 'Enter':
            case ' ':
                event.preventDefault();
                context.isOpen = ! context.isOpen;
                break;

            case 'Tab':
                // Handle focus trapping if needed
                break;
        }
    },
},
```

### Focus Management

```javascript
callbacks: {
    onOpenChange() {
        const context = getContext();
        const element = getElement();

        if ( context.isOpen ) {
            // Focus first focusable element in content
            const firstFocusable = element.ref.querySelector(
                'a, button, input, [tabindex="0"]'
            );
            firstFocusable?.focus();
        }
    },
},
```

---

## Internationalization (i18n)

### JavaScript

```javascript
import { __, _n, sprintf } from '@wordpress/i18n';

// Simple string
__( 'Button Text', 'tgp-llms-txt' )

// With placeholder
sprintf(
    /* translators: %s: item name */
    __( 'Delete %s', 'tgp-llms-txt' ),
    itemName
)

// Pluralization
sprintf(
    _n(
        '%d item selected',
        '%d items selected',
        count,
        'tgp-llms-txt'
    ),
    count
)
```

### PHP

```php
// Simple string
esc_html__( 'Button Text', 'tgp-llms-txt' )

// Echo directly (already escaped)
esc_html_e( 'Button Text', 'tgp-llms-txt' );

// With placeholder
sprintf(
    /* translators: %s: item name */
    esc_html__( 'Delete %s', 'tgp-llms-txt' ),
    esc_html( $item_name )
)

// Pluralization
sprintf(
    _n(
        '%d item selected',
        '%d items selected',
        $count,
        'tgp-llms-txt'
    ),
    $count
)
```

### Translation File Generation

```bash
# Add to package.json
"scripts": {
    "make-pot": "wp i18n make-pot . languages/tgp-llms-txt.pot --exclude=node_modules,vendor"
}

# Generate .pot file
npm run make-pot
```

---

## Build Configuration

### package.json

```json
{
    "name": "tgp-llms-txt",
    "version": "1.0.0",
    "scripts": {
        "start": "wp-scripts start --experimental-modules",
        "build": "wp-scripts build --experimental-modules",
        "lint:js": "wp-scripts lint-js src/",
        "lint:css": "wp-scripts lint-style src/**/*.scss",
        "lint:php": "composer lint",
        "format": "wp-scripts format src/",
        "make-pot": "wp i18n make-pot . languages/tgp-llms-txt.pot",
        "plugin-zip": "wp-scripts plugin-zip"
    },
    "devDependencies": {
        "@wordpress/scripts": "^30.0.0"
    },
    "dependencies": {
        "@wordpress/icons": "^10.0.0"
    }
}
```

**Note:** The `--experimental-modules` flag enables ES module output for `viewScriptModule` (Interactivity API).

### Build Output

```
build/
└── blocks/
    └── your-block/
        ├── block.json          # Processed metadata
        ├── index.js            # Editor bundle
        ├── index.asset.php     # Dependencies manifest
        ├── view.js             # Frontend module
        ├── style-index.css     # Compiled styles
        ├── editor.css          # Editor styles
        ├── view.css            # Frontend styles
        └── render.php          # PHP template (copied)
```

### PHP Block Registration

```php
/**
 * Register blocks.
 */
function tgp_register_blocks() {
    register_block_type( __DIR__ . '/build/blocks/your-block' );
}
add_action( 'init', 'tgp_register_blocks' );
```

---

## Style Variations

### The Problem

WordPress Block Supports automatically serialize color attributes to classes like `has-primary-background-color`. These use `!important` in global styles, overriding theme style variations.

### The Solution

Use `__experimentalSkipSerialization` to prevent automatic serialization, then manually apply colors only when appropriate.

### block.json (Skip Serialization)

```json
{
    "attributes": {
        "backgroundColor": { "type": "string" },
        "textColor": { "type": "string" },
        "gradient": { "type": "string" }
    },
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
                "fontSize", "lineHeight", "fontFamily",
                "fontWeight", "fontStyle", "textTransform",
                "textDecoration", "letterSpacing"
            ]
        },
        "shadow": {
            "__experimentalSkipSerialization": true
        },
        "spacing": {
            "__experimentalSkipSerialization": true,
            "padding": [ "horizontal", "vertical" ]
        },
        "__experimentalBorder": {
            "__experimentalSkipSerialization": true,
            "color": true,
            "radius": true,
            "style": true,
            "width": true
        }
    },
    "styles": [
        { "name": "fill", "label": "Fill", "isDefault": true },
        { "name": "outline", "label": "Outline" }
    ]
}
```

### render.php (Conditional Colors)

```php
<?php
// Get wrapper attributes.
$wrapper_attrs_string = get_block_wrapper_attributes();

// Detect style variation.
$style_variation     = 'fill';
$has_style_variation = false;

if ( preg_match( '/is-style-([a-z0-9-]+)/', $wrapper_attrs_string, $match ) ) {
    $style_variation     = $match[1];
    $has_style_variation = ( 'fill' !== $style_variation );
}

// Build classes.
$outer_classes = array( 'wp-block-button', 'is-style-' . $style_variation );
$inner_classes = array( 'wp-block-button__link', 'wp-element-button' );

// Only add color classes if NOT using a style variation.
if ( ! $has_style_variation ) {
    $bg_color = $attributes['backgroundColor'] ?? null;
    if ( $bg_color ) {
        $inner_classes[] = 'has-background';
        $inner_classes[] = 'has-' . $bg_color . '-background-color';
    }

    $text_color = $attributes['textColor'] ?? null;
    if ( $text_color ) {
        $inner_classes[] = 'has-text-color';
        $inner_classes[] = 'has-' . $text_color . '-color';
    }
}
?>

<div class="<?php echo esc_attr( implode( ' ', $outer_classes ) ); ?>">
    <button class="<?php echo esc_attr( implode( ' ', $inner_classes ) ); ?>">
        <?php echo esc_html( $label ); ?>
    </button>
</div>
```

### edit.js (Mirror Logic)

```javascript
export default function Edit( { attributes, setAttributes, className } ) {
    const { label, backgroundColor, textColor } = attributes;

    // Detect style variation.
    const styleMatch = className?.match( /is-style-([a-z0-9-]+)/ );
    const styleVariation = styleMatch ? styleMatch[ 1 ] : 'fill';
    const hasStyleVariation = styleVariation !== 'fill';

    // Build classes.
    const innerClasses = [ 'wp-block-button__link', 'wp-element-button' ];

    if ( ! hasStyleVariation ) {
        if ( backgroundColor ) {
            innerClasses.push( 'has-background' );
            innerClasses.push( `has-${ backgroundColor }-background-color` );
        }
        if ( textColor ) {
            innerClasses.push( 'has-text-color' );
            innerClasses.push( `has-${ textColor }-color` );
        }
    }

    // ... rest of component
}
```

---

## Testing Checklist

### Frontend

- [ ] Block renders correctly
- [ ] Interactivity works (if applicable)
- [ ] Style variations apply correctly
- [ ] Custom colors work (Fill style only)
- [ ] Typography/spacing/border customizations apply
- [ ] Responsive behavior works
- [ ] Keyboard navigation works
- [ ] Screen reader announces correctly

### Editor

- [ ] Block appears in inserter
- [ ] Block renders in editor
- [ ] Inspector controls work
- [ ] Style picker shows all variations
- [ ] Custom colors show in preview
- [ ] RichText editing works
- [ ] Block can be saved without errors

### Validation

```bash
# Check HTML output
curl -s "http://yoursite.com/page/" | grep "wp-block-tgp"

# Check for console errors
# Open browser DevTools → Console

# Validate block markup
# Edit page → Check for "Block validation failed"
```

---

## Common Issues

### Block validation failed

**Cause:** save.js output doesn't match saved content.

**Fix:** For dynamic blocks, return `null` from save. For static blocks, ensure save output exactly matches what's in the database.

### Style variation not applying

**Cause:** Inner element has `has-*-background-color` class with `!important`.

**Fix:** Add `__experimentalSkipSerialization: true` to color supports.

### Editor preview differs from frontend

**Cause:** edit.js logic doesn't match render.php.

**Fix:** Ensure identical conditional logic in both files.

### Interactivity not working

**Cause:** Missing `data-wp-interactive` or store not registered.

**Fix:**
1. Ensure `"interactivity": true` in supports
2. Check `data-wp-interactive` matches store namespace
3. Verify view.js is loading (check Network tab)

### viewScriptModule not loading

**Cause:** Missing `--experimental-modules` flag.

**Fix:** Add flag to build commands:
```json
"build": "wp-scripts build --experimental-modules"
```

---

## References

- [Block Editor Handbook](https://developer.wordpress.org/block-editor/)
- [Interactivity API](https://developer.wordpress.org/block-editor/reference-guides/interactivity-api/)
- [Block Supports](https://developer.wordpress.org/block-editor/reference-guides/block-api/block-supports/)
- [Block Styles](https://developer.wordpress.org/block-editor/reference-guides/block-api/block-styles/)
- [@wordpress/scripts](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-scripts/)

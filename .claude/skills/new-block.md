# /new-block

Scaffold a new WordPress Gutenberg block with all patterns baked in.

## Usage

```
/new-block [block-name]
```

## Description

Creates a new block in `blocks/{block-name}/` with all necessary files following the patterns established in this plugin. The skill will prompt for configuration options and generate files accordingly.

## Workflow

1. **Get block name** (required)
   - Must be kebab-case (e.g., `info-card`, `feature-box`)
   - If not provided as argument, prompt for it

2. **Get block type** (required)
   - Standard block (default)
   - Button-style block (uses `TGP_Button_Block_Renderer`)
   - Child of parent block (uses `$block->context`)
   - Pill/toggle block (uses `TGP_Pill_Block_Renderer`)

3. **Get configuration options**
   - Ask which features to include using checkboxes

4. **Generate files** based on selections

5. **Update plugin registration**

6. **Update CLAUDE.md** with block reference

## Configuration Options

| Option | Default | Description |
|--------|---------|-------------|
| Style variations | Yes | Add `__experimentalSkipSerialization` pattern |
| Interactivity API | No | Add `view.js` with store template |
| Server rendering | Yes | Add `render.php` for dynamic rendering |
| Static save | No | Add `save.js` (mutually exclusive with render.php) |
| Editor styles | No | Add `editor.css` for editor-only styles |
| Button-style | No | Use `TGP_Button_Block_Renderer`, add parent: `core/buttons` |
| Child block | No | Add `parent` and `usesContext` for parent block |
| Pill/toggle | No | Use `TGP_Pill_Block_Renderer` with active/inactive states |

## Block Type Decision Tree

```
Is this block clickable/actionable?
├── Yes → Does it open a link?
│   ├── Yes → Button-style (view-button pattern)
│   └── No → Does it toggle state?
│       ├── Yes → Pill block (blog-category-filter pattern)
│       └── No → Button-style with Interactivity (copy-button pattern)
└── No → Does it need a parent block?
    ├── Yes → Child block (blog-search pattern)
    └── No → Standard block
```

## Generated Files

### Always Created

- `blocks/{name}/block.json` — Block metadata and configuration
- `blocks/{name}/index.js` — Block registration
- `blocks/{name}/style.css` — Base styles
- `blocks/{name}/README.md` — Block documentation

### Conditional

- `blocks/{name}/render.php` — If server rendering enabled
- `blocks/{name}/save.js` — If static save enabled (no render.php)
- `blocks/{name}/view.js` — If Interactivity API enabled
- `blocks/{name}/editor.css` — If editor styles enabled

---

## Standard Block Templates

### block.json (Standard)

```json
{
    "$schema": "https://schemas.wp.org/trunk/block.json",
    "apiVersion": 3,
    "name": "tgp/{block-name}",
    "version": "1.0.0",
    "title": "{Block Title}",
    "category": "design",
    "description": "A custom block.",
    "textdomain": "tgp-llms-txt",
    "keywords": [],
    "attributes": {
        "label": {
            "type": "string",
            "default": "Label"
        }
    },
    "supports": {
        "html": false,
        "anchor": true,
        "color": {
            "__experimentalSkipSerialization": true,
            "background": true,
            "text": true
        },
        "typography": {
            "fontSize": true
        },
        "spacing": {
            "padding": true
        }
    },
    "editorScript": "file:./index.js",
    "style": "file:./style.css",
    "render": "file:./render.php"
}
```

### index.js (Standard)

```javascript
( function( wp ) {
    const { registerBlockType } = wp.blocks;
    const {
        useBlockProps,
        InspectorControls,
        RichText
    } = wp.blockEditor;
    const {
        PanelBody,
        ToggleControl
    } = wp.components;
    const { __ } = wp.i18n;
    const { createElement: el, Fragment } = wp.element;
    const { SVG, Path } = wp.primitives;

    // Block icon
    const blockIcon = el( SVG, {
        xmlns: 'http://www.w3.org/2000/svg',
        viewBox: '0 0 24 24'
    },
        el( Path, {
            d: 'M19 6.5H5a2 2 0 00-2 2v7a2 2 0 002 2h14a2 2 0 002-2v-7a2 2 0 00-2-2z',
            fill: 'none',
            stroke: 'currentColor',
            strokeWidth: 1.5
        } )
    );

    registerBlockType( 'tgp/{block-name}', {
        icon: blockIcon,

        edit: function( props ) {
            const { attributes, setAttributes } = props;
            const { label } = attributes;

            const blockProps = useBlockProps( {
                className: 'wp-block-tgp-{block-name}'
            } );

            return el( Fragment, {},
                el( InspectorControls, {},
                    el( PanelBody, {
                        title: __( 'Settings', 'tgp-llms-txt' ),
                        initialOpen: true
                    },
                        // Add controls here
                    )
                ),

                el( 'div', blockProps,
                    el( RichText, {
                        tagName: 'span',
                        className: 'wp-block-tgp-{block-name}__label',
                        value: label,
                        onChange: function( value ) {
                            setAttributes( { label: value } );
                        },
                        placeholder: __( 'Label', 'tgp-llms-txt' ),
                        allowedFormats: []
                    } )
                )
            );
        },

        save: function() {
            return null;
        }
    } );
} )( window.wp );
```

### render.php (Standard)

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

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$label = $attributes['label'] ?? __( 'Label', 'tgp-llms-txt' );

$wrapper_attributes = get_block_wrapper_attributes( array(
    'class' => 'wp-block-tgp-{block-name}',
) );
?>

<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
    <span class="wp-block-tgp-{block-name}__label">
        <?php echo esc_html( $label ); ?>
    </span>
</div>
```

---

## Button-Style Block Templates

Use for blocks that render as buttons inside `core/buttons`.

### block.json (Button)

```json
{
    "$schema": "https://schemas.wp.org/trunk/block.json",
    "apiVersion": 3,
    "name": "tgp/{block-name}",
    "title": "{Block Title}",
    "category": "design",
    "parent": [ "core/buttons" ],
    "description": "A button block.",
    "keywords": [],
    "textdomain": "tgp-llms-txt",
    "attributes": {
        "label": {
            "type": "string",
            "default": "Button"
        },
        "showIcon": {
            "type": "boolean",
            "default": true
        },
        "width": {
            "type": "number"
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
        "anchor": true,
        "reusable": false,
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
            ],
            "fontSize": true,
            "lineHeight": true,
            "__experimentalFontFamily": true,
            "__experimentalFontWeight": true
        },
        "spacing": {
            "__experimentalSkipSerialization": true,
            "padding": [ "horizontal", "vertical" ]
        },
        "__experimentalBorder": {
            "__experimentalSkipSerialization": true,
            "radius": true,
            "width": true,
            "style": true,
            "color": true
        },
        "shadow": {
            "__experimentalSkipSerialization": true
        },
        "interactivity": true
    },
    "styles": [
        { "name": "fill", "label": "Fill", "isDefault": true },
        { "name": "outline", "label": "Outline" }
    ],
    "editorScript": "file:./index.js",
    "style": "file:./style.css",
    "render": "file:./render.php",
    "viewScriptModule": "file:./view.js",
    "selectors": {
        "root": ".wp-block-button .wp-block-button__link"
    }
}
```

### render.php (Button with TGP_Button_Block_Renderer)

```php
<?php
/**
 * Button block render template.
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block inner content.
 * @var WP_Block $block      Block instance.
 *
 * @package TGP_LLMs_Txt
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$label     = $attributes['label'] ?? __( 'Button', 'tgp-llms-txt' );
$show_icon = $attributes['showIcon'] ?? true;

// Extract styles using shared helper.
$style_attrs = TGP_Button_Block_Renderer::get_style_attributes( $attributes );

// Get wrapper attributes and detect style variation.
$wrapper_attrs = get_block_wrapper_attributes();
$style_info    = TGP_Button_Block_Renderer::get_style_variation( $wrapper_attrs );

// Build classes.
$outer_classes = TGP_Button_Block_Renderer::build_outer_classes(
    $style_attrs,
    $style_info['variation']
);

$inner_classes = TGP_Button_Block_Renderer::build_inner_classes(
    $style_attrs,
    'wp-block-tgp-{block-name}',
    $style_info['has_variation']
);

// Build inline styles.
$style_attr = TGP_Button_Block_Renderer::get_style_attribute(
    $style_attrs,
    $style_info['has_variation']
);

// Icon (customize as needed).
$icon = '<svg>...</svg>';
?>

<div class="<?php echo esc_attr( implode( ' ', $outer_classes ) ); ?>">
    <button
        type="button"
        class="<?php echo esc_attr( implode( ' ', $inner_classes ) ); ?>"<?php echo $style_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    >
        <?php if ( $show_icon ) : ?>
            <span class="wp-block-tgp-{block-name}__icon" aria-hidden="true">
                <?php echo wp_kses( $icon, TGP_SVG_Sanitizer::get_allowed_tags() ); ?>
            </span>
        <?php endif; ?>
        <span class="wp-block-tgp-{block-name}__text"><?php echo esc_html( $label ); ?></span>
    </button>
</div>
```

---

## Child Block Templates

Use for blocks that must be inside a parent block.

### block.json (Child)

```json
{
    "$schema": "https://schemas.wp.org/trunk/block.json",
    "apiVersion": 3,
    "name": "tgp/{block-name}",
    "title": "{Block Title}",
    "category": "widgets",
    "parent": [ "tgp/{parent-block}" ],
    "description": "A child block.",
    "keywords": [],
    "textdomain": "tgp-llms-txt",
    "usesContext": [ "tgp/{parentContext}" ],
    "attributes": {
        "placeholder": {
            "type": "string",
            "default": "Enter text..."
        }
    },
    "supports": {
        "html": false,
        "reusable": false,
        "spacing": {
            "margin": true
        }
    },
    "editorScript": "file:./index.js",
    "style": "file:./style.css",
    "render": "file:./render.php"
}
```

### render.php (Child with Context)

```php
<?php
/**
 * Child block render template.
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block inner content.
 * @var WP_Block $block      Block instance.
 *
 * @package TGP_LLMs_Txt
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Ensure we're inside the parent block.
if ( ! array_key_exists( 'tgp/{parentContext}', $block->context ) ) {
    return '';
}

$placeholder = $attributes['placeholder'] ?? __( 'Enter text...', 'tgp-llms-txt' );

$wrapper_attrs = get_block_wrapper_attributes();
?>

<div <?php echo $wrapper_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
    <!-- Child block content -->
</div>
```

---

## Interactivity API Templates

### view.js (Basic)

```javascript
/**
 * Frontend interactivity.
 *
 * @package TGP_LLMs_Txt
 */

import { store, getContext } from '@wordpress/interactivity';

const { state, actions } = store( 'tgp/{block-name}', {
    state: {
        // Computed getters only - DO NOT define defaults for PHP data here!
        get isDisabled() {
            const context = getContext();
            return context.isLoading;
        },
    },

    actions: {
        *handleClick() {
            const context = getContext();
            context.isLoading = true;

            try {
                // Action logic here
            } catch ( error ) {
                context.hasError = true;
            } finally {
                context.isLoading = false;
            }
        },
    },
} );
```

### Interactivity API Patterns

When using Interactivity API, decide data location:

| Data Type | Location | Example |
|-----------|----------|---------|
| Large shared data (posts, users) | `wp_interactivity_state()` | Posts array for filtering |
| Reactive UI state | `data-wp-context` | Search query, selected items |
| Computed values | JS getters in `state` | `get hasResults()` |

**Critical:** Do NOT define default values in JS for state from PHP - they will overwrite server values!

```javascript
// ❌ BAD - overwrites PHP data
state: {
    posts: [],
    categories: [],
}

// ✅ GOOD - only computed getters
state: {
    // posts, categories come from PHP via wp_interactivity_state()
    get hasResults() {
        return getContext().visiblePostIds.length > 0;
    }
}
```

---

## Pill Block Templates

Use for toggleable filter pills with active/inactive states.

### render.php (Pill with TGP_Pill_Block_Renderer)

```php
<?php
/**
 * Pill block render template.
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block inner content.
 * @var WP_Block $block      Block instance.
 *
 * @package TGP_LLMs_Txt
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Ensure parent context exists.
if ( ! array_key_exists( 'tgp/{parentContext}', $block->context ) ) {
    return '';
}

// Extract styles using shared helper.
$style_attrs = TGP_Pill_Block_Renderer::get_style_attributes( $attributes );
$colors      = TGP_Pill_Block_Renderer::resolve_colors( $style_attrs );

// Get wrapper attributes and style variation.
$wrapper_attrs   = get_block_wrapper_attributes();
$style_variation = TGP_Pill_Block_Renderer::get_style_variation( $wrapper_attrs );
$style_classes   = TGP_Pill_Block_Renderer::get_style_classes( $style_variation );

// Build active-state styles.
$active_styles = TGP_Pill_Block_Renderer::build_active_state_styles( $style_attrs, $colors );

// Inject marker classes.
$wrapper_attrs = TGP_Pill_Block_Renderer::inject_marker_classes(
    $wrapper_attrs,
    $active_styles['classes']
);

// Get style attributes.
$wrapper_style = TGP_Pill_Block_Renderer::get_wrapper_style_attribute( $active_styles );
$button_style  = TGP_Pill_Block_Renderer::get_button_style_attribute( $style_attrs );

// Build classes.
$pill_classes   = TGP_Pill_Block_Renderer::build_pill_wrapper_classes();
$button_classes = TGP_Pill_Block_Renderer::build_button_classes( 'wp-block-tgp-{block-name}__pill' );
?>

<div <?php echo $wrapper_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo $wrapper_style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
    <?php foreach ( $items as $item ) : ?>
    <div
        class="<?php echo esc_attr( implode( ' ', $pill_classes ) ); ?>"
        <?php echo wp_interactivity_data_wp_context( [ 'id' => $item->id ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        data-wp-class--wp-block-tgp-{block-name}__pill--active="state.isActive"
        data-wp-class--<?php echo esc_attr( $style_classes['active'] ); ?>="state.isActive"
        data-wp-class--<?php echo esc_attr( $style_classes['inactive'] ); ?>="!state.isActive"
    >
        <button
            type="button"
            class="<?php echo esc_attr( implode( ' ', $button_classes ) ); ?>"<?php echo $button_style; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
            data-wp-on--click="actions.toggle"
            aria-pressed="false"
            data-wp-bind--aria-pressed="state.isActive"
        >
            <?php echo esc_html( $item->name ); ?>
        </button>
    </div>
    <?php endforeach; ?>
</div>
```

---

## Plugin Registration

Add to `tgp-llms-txt.php` in the block registration section:

```php
register_block_type( __DIR__ . '/blocks/{block-name}' );
```

## CLAUDE.md Update

Add block reference to the Blocks Reference section:

```markdown
### {Block Title} (`tgp/{block-name}`)

{Description}

**Files:**
- [blocks/{block-name}/block.json](blocks/{block-name}/block.json) — Registration
- [blocks/{block-name}/index.js](blocks/{block-name}/index.js) — Editor component
- [blocks/{block-name}/render.php](blocks/{block-name}/render.php) — Server render

**Attributes:**

| Attribute | Type | Default | Description |
|-----------|------|---------|-------------|
| `label` | `string` | `"Label"` | Block label text |
```

---

## Example Usage

```
User: /new-block

Claude: What would you like to name your block? (kebab-case, e.g., info-card)

User: action-button

Claude: What type of block is this?

( ) Standard block
(x) Button-style block (inside core/buttons)
( ) Child block (inside a parent)
( ) Pill/toggle block (active/inactive states)

User: Button-style

Claude: Which features should I include?

[x] Style variations (fill/outline)
[x] Interactivity API (view.js)
[x] Server rendering (render.php)
[ ] Editor styles (editor.css)

User: (confirms selections)

Claude: Creating block...

Created 5 files:
  blocks/action-button/block.json
  blocks/action-button/index.js
  blocks/action-button/render.php
  blocks/action-button/view.js
  blocks/action-button/style.css
  blocks/action-button/README.md

Updated:
  tgp-llms-txt.php (registered block)
  CLAUDE.md (added block reference)

Your new block is ready! Add it inside a Buttons block using the block inserter.
```

---

## Notes

- Block names must be unique within the `tgp/` namespace
- Button-style blocks require `parent: ["core/buttons"]` to work with theme button styles
- Child blocks MUST check for parent context before rendering
- Interactivity API requires WordPress 6.5+
- Static save and server rendering are mutually exclusive
- Always create a README.md for new blocks

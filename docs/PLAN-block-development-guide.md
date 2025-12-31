# Master Plan: Block Development Guide & Documentation

## Overview

Comprehensive update to block development documentation based on research from:
- Ollie Menu Designer repository patterns
- WordPress official block editor tutorial
- WordPress custom block styles guide

## Goals

1. Create reusable patterns for future block development
2. Align with WordPress core conventions
3. Enable AI-assisted development via comprehensive CLAUDE.md
4. Establish foundation for future `/new-block` skill

---

## Phase 1: Documentation Updates

### 1.1 Update BLOCK-DEVELOPMENT.md

#### Section A: Prerequisites & Setup (NEW)

```markdown
## Prerequisites

- Node.js 18+
- npm 9+
- WordPress 6.5+
- PHP 8.2+

## Quick Start

npm init @wordpress/block@latest your-block-name
cd your-block-name
npm start
```

**Deliverables:**
- [ ] Prerequisites section
- [ ] @wordpress/create-block scaffolding
- [ ] npm scripts reference table

---

#### Section B: Project Structure (NEW)

```markdown
## Project Structure

src/
├── blocks/
│   └── your-block/
│       ├── block.json      # Block metadata & configuration
│       ├── index.js        # Registration entry point
│       ├── edit.js         # Editor component
│       ├── save.js         # Static save (or omit for dynamic)
│       ├── render.php      # Server-side rendering
│       ├── view.js         # Frontend interactivity
│       ├── style.scss      # Base styles (editor + frontend)
│       ├── editor.scss     # Editor-only styles
│       └── view.scss       # Frontend-only styles
├── components/             # Shared React components
├── hooks/                  # Custom React hooks
└── utils/                  # Utility functions
```

**Deliverables:**
- [ ] Directory structure diagram
- [ ] File purpose explanations
- [ ] When to use each file type

---

#### Section C: File Templates (UPDATE EXISTING)

**Current:** Single inline index.js
**Updated:** Separate files following WordPress pattern

##### index.js (Entry Point Only)

```javascript
import { registerBlockType } from '@wordpress/blocks';
import metadata from './block.json';
import Edit from './edit';
import save from './save';
import './style.scss';

registerBlockType( metadata.name, {
    ...metadata,
    edit: Edit,
    save,
} );
```

##### edit.js (Editor Component)

```javascript
import { __ } from '@wordpress/i18n';
import {
    useBlockProps,
    InspectorControls,
    RichText,
} from '@wordpress/block-editor';
import {
    PanelBody,
    ToggleControl,
} from '@wordpress/components';

export default function Edit( { attributes, setAttributes } ) {
    const { label, showIcon } = attributes;

    const blockProps = useBlockProps();

    return (
        <>
            <InspectorControls>
                <PanelBody title={ __( 'Settings', 'tgp-llms-txt' ) }>
                    <ToggleControl
                        label={ __( 'Show Icon', 'tgp-llms-txt' ) }
                        checked={ showIcon }
                        onChange={ ( value ) => setAttributes( { showIcon: value } ) }
                    />
                </PanelBody>
            </InspectorControls>

            <div { ...blockProps }>
                <RichText
                    tagName="span"
                    value={ label }
                    onChange={ ( value ) => setAttributes( { label: value } ) }
                    placeholder={ __( 'Button text', 'tgp-llms-txt' ) }
                />
            </div>
        </>
    );
}
```

##### save.js (Static Output)

```javascript
import { useBlockProps, RichText } from '@wordpress/block-editor';

export default function save( { attributes } ) {
    const { label } = attributes;

    // Return null for dynamic blocks (use render.php instead)
    if ( ! label ) {
        return null;
    }

    return (
        <div { ...useBlockProps.save() }>
            <RichText.Content tagName="span" value={ label } />
        </div>
    );
}
```

**Deliverables:**
- [ ] Separate index.js template
- [ ] Separate edit.js template
- [ ] Separate save.js template
- [ ] Explanation of save vs render.php

---

#### Section D: Interactivity API Patterns (NEW)

##### view.js (Frontend Store)

```javascript
import { store, getContext } from '@wordpress/interactivity';

const { state } = store( 'tgp/your-block', {
    state: {
        get isDisabled() {
            const context = getContext();
            return context.isLoading;
        },
    },

    actions: {
        handleClick: async () => {
            const context = getContext();
            context.isLoading = true;

            try {
                // Action logic
            } catch ( error ) {
                context.hasError = true;
            } finally {
                context.isLoading = false;
            }
        },
    },

    callbacks: {
        onStateChange: () => {
            const context = getContext();
            // Reactive callback
        },
    },
} );
```

##### render.php (Interactivity Attributes)

```php
$wrapper_attributes = get_block_wrapper_attributes( array(
    'data-wp-interactive' => 'tgp/your-block',
    'data-wp-context'     => wp_json_encode( array(
        'isLoading' => false,
        'hasError'  => false,
    ) ),
) );
```

##### HTML Directives Reference

| Directive | Purpose | Example |
|-----------|---------|---------|
| `data-wp-on--click` | Event handler | `actions.handleClick` |
| `data-wp-bind--disabled` | Bind attribute | `state.isDisabled` |
| `data-wp-class--is-loading` | Toggle class | `context.isLoading` |
| `data-wp-text` | Set text content | `context.label` |
| `data-wp-watch` | Reactive callback | `callbacks.onStateChange` |

**Deliverables:**
- [ ] view.js store template
- [ ] render.php integration example
- [ ] Directive reference table
- [ ] State management patterns

---

#### Section E: SCSS & CSS Conventions (NEW)

##### File Organization

```scss
// style.scss - Loads on editor AND frontend
.wp-block-tgp-your-block {
    // Base styles
}

// editor.scss - Editor only
.wp-block-tgp-your-block {
    // Editor-specific overrides
}

// view.scss - Frontend only (with viewScriptModule)
.wp-block-tgp-your-block {
    // Frontend-specific styles
}
```

##### BEM Naming Convention

```scss
// Block
.wp-block-tgp-your-block { }

// Element
.wp-block-tgp-your-block__toggle { }
.wp-block-tgp-your-block__icon { }
.wp-block-tgp-your-block__label { }

// Modifier
.wp-block-tgp-your-block--loading { }
.wp-block-tgp-your-block__toggle--active { }
```

##### Theme Integration

```scss
.wp-block-tgp-your-block {
    // Inherit from theme
    font-family: inherit;
    font-size: inherit;
    color: inherit;

    // Use CSS custom properties
    background-color: var(--wp--preset--color--primary);
    padding: var(--wp--preset--spacing--medium);
}
```

**Deliverables:**
- [ ] File organization guide
- [ ] BEM naming examples
- [ ] Theme integration patterns
- [ ] CSS custom property usage

---

#### Section F: Accessibility Patterns (NEW)

##### ARIA Attributes

```php
// Generate unique ID for ARIA relationships
$unique_id = wp_unique_id( 'tgp-block-' );
```

```html
<button
    aria-expanded="false"
    aria-controls="<?php echo esc_attr( $unique_id ); ?>"
    data-wp-bind--aria-expanded="context.isOpen"
>
    <span class="screen-reader-text">
        <?php esc_html_e( 'Toggle menu', 'tgp-llms-txt' ); ?>
    </span>
</button>

<div
    id="<?php echo esc_attr( $unique_id ); ?>"
    aria-hidden="true"
    data-wp-bind--aria-hidden="!context.isOpen"
>
    <!-- Content -->
</div>
```

##### Screen Reader Class

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

##### Keyboard Navigation

```javascript
actions: {
    handleKeyDown: ( event ) => {
        const context = getContext();

        switch ( event.key ) {
            case 'Escape':
                context.isOpen = false;
                break;
            case 'Enter':
            case ' ':
                event.preventDefault();
                context.isOpen = ! context.isOpen;
                break;
        }
    },
},
```

**Deliverables:**
- [ ] ARIA relationship patterns
- [ ] Screen reader text class
- [ ] Keyboard navigation example
- [ ] Focus management patterns

---

#### Section G: Internationalization (NEW)

##### JavaScript

```javascript
import { __ } from '@wordpress/i18n';
import { _n, sprintf } from '@wordpress/i18n';

// Simple string
__( 'Button Text', 'tgp-llms-txt' )

// With placeholder
sprintf(
    __( 'Copied %s items', 'tgp-llms-txt' ),
    count
)

// Pluralization
_n(
    '%d item',
    '%d items',
    count,
    'tgp-llms-txt'
)
```

##### PHP

```php
// Simple string
esc_html__( 'Button Text', 'tgp-llms-txt' )

// With placeholder
sprintf(
    esc_html__( 'Copied %d items', 'tgp-llms-txt' ),
    $count
)

// Already escaped
esc_html_e( 'Button Text', 'tgp-llms-txt' );
```

##### Translation File Generation

```bash
# In package.json
"scripts": {
    "make-pot": "wp i18n make-pot . languages/tgp-llms-txt.pot"
}

# Run
npm run make-pot
```

**Deliverables:**
- [ ] JavaScript i18n patterns
- [ ] PHP i18n patterns
- [ ] Translation workflow

---

#### Section H: Build Configuration (NEW)

##### package.json Scripts

```json
{
    "scripts": {
        "start": "wp-scripts start",
        "build": "wp-scripts build",
        "lint:js": "wp-scripts lint-js src/",
        "lint:css": "wp-scripts lint-style src/**/*.scss",
        "lint:php": "composer lint",
        "format": "wp-scripts format src/",
        "make-pot": "wp i18n make-pot . languages/tgp-llms-txt.pot",
        "plugin-zip": "wp-scripts plugin-zip"
    }
}
```

##### Experimental Modules (for Interactivity API)

```json
{
    "scripts": {
        "start": "wp-scripts start --experimental-modules",
        "build": "wp-scripts build --experimental-modules"
    }
}
```

##### block.json Asset References

```json
{
    "editorScript": "file:./index.js",
    "editorStyle": "file:./editor.css",
    "style": "file:./style.css",
    "viewScriptModule": "file:./view.js",
    "viewStyle": "file:./view.css",
    "render": "file:./render.php"
}
```

**Deliverables:**
- [ ] npm scripts reference
- [ ] Experimental modules flag explanation
- [ ] Asset reference patterns
- [ ] Build output structure

---

### 1.2 Update CLAUDE.md

Restructure following Ollie Menu Designer pattern:

```markdown
# CLAUDE.md

## Project Overview
[What the plugin does, key features]

## Development Commands
[All npm/composer scripts with descriptions]

## Architecture
[File structure diagram with explanations]

### Plugin Structure
[Directory tree with purpose annotations]

### Block Registration Flow
[PHP → JS → block.json sequence]

### Frontend Rendering
[Server-side vs client-side split]

### Editor Interface
[Which files handle which UI]

## Blocks Reference

### Copy for LLM (tgp/copy-button)
[Purpose, attributes table, key files]

### View as Markdown (tgp/view-button)
[Purpose, attributes table, key files]

## Key Implementation Details

### Style Variation System
[Skip serialization pattern, why it exists]

### Interactivity API Usage
[Store patterns, state management]

### Theme Integration
[How blocks inherit theme styles]

## Development Principles
- Use WordPress core components first
- Follow WordPress coding standards
- Server rendering via render.php
- Frontend interactivity via view.js
```

**Deliverables:**
- [ ] Project overview section
- [ ] Development commands table
- [ ] Architecture diagram
- [ ] Block registration flow
- [ ] Blocks reference with attributes
- [ ] Key implementation details
- [ ] Development principles

---

## Phase 2: Extended Documentation

### 2.1 Additional BLOCK-DEVELOPMENT.md Sections

#### Block Style Registration Methods

Document all 6 methods from WordPress blog:
1. JSON files in `/styles/blocks/`
2. PHP `register_block_style()` with `style_data`
3. PHP `register_block_style()` with `inline_style`
4. PHP `register_block_style()` with `style_handle`
5. JavaScript `wp.blocks.registerBlockStyle()`
6. Unregistering with `wp.blocks.unregisterBlockStyle()`

**Deliverables:**
- [ ] Method comparison table
- [ ] Code examples for each method
- [ ] When to use which method

---

#### Extending Core Blocks

```javascript
import { addFilter } from '@wordpress/hooks';

// Add attributes to core block
addFilter(
    'blocks.registerBlockType',
    'tgp/extend-navigation',
    ( settings, name ) => {
        if ( name !== 'core/navigation' ) {
            return settings;
        }
        return {
            ...settings,
            attributes: {
                ...settings.attributes,
                customAttribute: { type: 'string' },
            },
        };
    }
);

// Add block as allowed child
addFilter(
    'blocks.registerBlockType',
    'tgp/add-to-navigation',
    ( settings, name ) => {
        if ( name !== 'core/navigation' ) {
            return settings;
        }
        return {
            ...settings,
            allowedBlocks: [
                ...( settings.allowedBlocks || [] ),
                'tgp/your-block',
            ],
        };
    }
);
```

**Deliverables:**
- [ ] Adding attributes to core blocks
- [ ] Adding child blocks
- [ ] Modifying core block behavior

---

#### Block Context

```json
// block.json
{
    "usesContext": [ "postId", "postType" ],
    "providesContext": {
        "tgp/parentId": "id"
    }
}
```

```javascript
// edit.js
export default function Edit( { attributes, setAttributes, context } ) {
    const { postId, postType } = context;
    // Use parent context
}
```

**Deliverables:**
- [ ] usesContext examples
- [ ] providesContext examples
- [ ] Context use cases

---

#### Testing Setup

```json
// package.json
{
    "scripts": {
        "test": "wp-scripts test-unit-js",
        "test:watch": "wp-scripts test-unit-js --watch"
    }
}
```

```javascript
// src/blocks/your-block/test/edit.test.js
import { render, screen } from '@testing-library/react';
import Edit from '../edit';

describe( 'Edit component', () => {
    it( 'renders button text', () => {
        render( <Edit attributes={ { label: 'Test' } } /> );
        expect( screen.getByText( 'Test' ) ).toBeInTheDocument();
    } );
} );
```

**Deliverables:**
- [ ] Jest configuration
- [ ] Component testing examples
- [ ] E2E testing with Playwright

---

### 2.2 Create /new-block Skill

Skill that scaffolds a new block with all patterns baked in.

**Skill Flow:**
```
User: /new-block

Skill: Block name? (kebab-case)
User: info-card

Skill: Namespace? (default: tgp)
User: tgp

Skill: Features to include?
  [x] Style variations (skip serialization pattern)
  [x] Interactivity API (view.js)
  [ ] Static save (save.js)
  [x] Server rendering (render.php)
  [ ] Editor styles (editor.scss)

Skill: Creating block...

Created 8 files:
  src/blocks/info-card/block.json
  src/blocks/info-card/index.js
  src/blocks/info-card/edit.js
  src/blocks/info-card/render.php
  src/blocks/info-card/view.js
  src/blocks/info-card/style.scss
  src/blocks/info-card/view.scss

Updated:
  CLAUDE.md (added block reference)
  tgp-llms-txt.php (registered block)

Run `npm start` to begin development.
```

**Deliverables:**
- [ ] Skill definition file
- [ ] Template files for each feature combination
- [ ] CLAUDE.md auto-update logic
- [ ] Main plugin file registration

---

## Execution Order

### Phase 1 Tasks (Priority Order)

| # | Task | File | Est. Lines |
|---|------|------|------------|
| 1 | Prerequisites & Setup | BLOCK-DEVELOPMENT.md | +50 |
| 2 | Project Structure | BLOCK-DEVELOPMENT.md | +80 |
| 3 | Separate file templates | BLOCK-DEVELOPMENT.md | +150 |
| 4 | Interactivity API patterns | BLOCK-DEVELOPMENT.md | +120 |
| 5 | SCSS & CSS conventions | BLOCK-DEVELOPMENT.md | +80 |
| 6 | Accessibility patterns | BLOCK-DEVELOPMENT.md | +60 |
| 7 | Internationalization | BLOCK-DEVELOPMENT.md | +50 |
| 8 | Build configuration | BLOCK-DEVELOPMENT.md | +60 |
| 9 | Restructure CLAUDE.md | CLAUDE.md | ~200 |

**Phase 1 Total:** ~650 lines of documentation

### Phase 2 Tasks

| # | Task | File | Est. Lines |
|---|------|------|------------|
| 1 | Block style registration methods | BLOCK-DEVELOPMENT.md | +150 |
| 2 | Extending core blocks | BLOCK-DEVELOPMENT.md | +80 |
| 3 | Block context | BLOCK-DEVELOPMENT.md | +60 |
| 4 | Testing setup | BLOCK-DEVELOPMENT.md | +100 |
| 5 | Create /new-block skill | skills/new-block.md | +300 |

**Phase 2 Total:** ~690 lines

---

## Success Criteria

### Phase 1 Complete When:
- [ ] New developer can create a block following the guide
- [ ] All patterns from copy-button/view-button are documented
- [ ] CLAUDE.md enables effective AI-assisted development
- [ ] Code examples pass WordPress linting standards

### Phase 2 Complete When:
- [ ] All 6 style registration methods documented
- [ ] Core block extension patterns documented
- [ ] /new-block skill generates working blocks
- [ ] Generated blocks pass all linting checks

---

## References

- [WordPress Block Editor Handbook](https://developer.wordpress.org/block-editor/)
- [WordPress Interactivity API](https://developer.wordpress.org/block-editor/reference-guides/interactivity-api/)
- [Ollie Menu Designer](https://github.com/OllieWP/ollie-menu-designer)
- [WordPress Custom Block Styles](https://developer.wordpress.org/themes/features/block-style-variations/)

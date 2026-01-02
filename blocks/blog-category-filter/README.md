# Blog Category Filter (`tgp/blog-category-filter`)

Category filter pills for filtering blog posts. Uses single-selection mode - clicking a category filters to that category, clicking again shows all posts.

## Purpose

Renders toggleable category pills that filter posts within the parent `tgp/blog-filters` block. Pills show category name and post count, with visual feedback for the selected state.

## Requirements

- WordPress 6.5+ (Interactivity API)
- Must be placed inside a `tgp/blog-filters` block

## Attributes

| Attribute | Type | Default | Description |
|-----------|------|---------|-------------|
| `showCount` | `boolean` | `true` | Show post count badge on pills |
| `layout` | `string` | `"wrap"` | Layout mode: `"wrap"` or `"scroll"` |
| `backgroundColor` | `string` | — | Active pill background color preset |
| `textColor` | `string` | — | Active pill text color preset |
| `gradient` | `string` | — | Active pill gradient preset |

## Style Variations

Controls the appearance of **active** pills:

| Name | Description |
|------|-------------|
| `button-brand` | Theme brand color (default) |
| `button-brand-alt` | Alternative brand color |
| `button-dark` | Dark background |
| `button-light` | Light background |
| `outline` | Border only |

Inactive pills always use `secondary-button` styling (tint).

## Usage

```html
<!-- wp:tgp/blog-filters -->
<div class="wp-block-tgp-blog-filters">
  <!-- wp:tgp/blog-category-filter {"showCount":true,"layout":"wrap"} /-->
</div>
<!-- /wp:tgp/blog-filters -->
```

## Behavior

1. Block renders a pill for each category with posts
2. User clicks a pill to filter to that category
3. Pill shows active state, posts filter
4. Clicking the active pill deselects it (shows all posts)
5. URL updates with `?categories=slug`

### Selection Mode

Single selection only - one category active at a time. This was chosen for UX simplicity.

### Category Logic

When a category is selected:
- Only posts WITH that category are shown
- Combined with search using AND logic

## Architecture

### Files

| File | Purpose |
|------|---------|
| [block.json](block.json) | Block metadata, usesContext, style variations |
| [index.js](index.js) | Editor component showing static pills |
| [render.php](render.php) | Server-side rendering (250 lines - needs refactoring) |
| [style.css](style.css) | Pill styling, layout modes, active states |

### Parent-Child Relationship

```
tgp/blog-filters (providesContext)
  └── tgp/blog-category-filter (usesContext)
```

This block cannot be used outside its parent.

### Interactivity API

Uses the parent's `tgp/blog-filters` store.

**Context per pill:**
```json
{
  "slug": "ai-implementation"
}
```

**State read:**
- `isCategoryActive` - Computed getter checking if pill's slug is in `selectedCategories`

**Actions called:**
- `toggleCategory` - Toggle category selection

### Directives Used

```html
<div
  data-wp-context='{"slug":"category-slug"}'
  data-wp-class--wp-block-tgp-blog-category-filter__pill--active="state.isCategoryActive"
  data-wp-class--is-style-button-brand="state.isCategoryActive"
  data-wp-class--is-style-secondary-button="!state.isCategoryActive"
>
  <button data-wp-on--click="actions.toggleCategory">
    Category Name
  </button>
</div>
```

### Style Switching Pattern

Active/inactive styles use class swapping:

1. Inactive: `is-style-secondary-button` (tint)
2. Active: `is-style-{selected-variation}` (e.g., `is-style-button-brand`)

The selected style variation is detected from wrapper attributes and used for active state.

### Marker Class Pattern

Custom styles for active pills use CSS custom properties:

```css
.has-custom-active-bg .wp-block-tgp-blog-category-filter__pill--active {
  background-color: var(--tgp-active-bg);
}
```

Marker classes (`has-custom-active-bg`, `has-custom-active-text`, etc.) are added only when custom values exist, preventing empty variable issues.

## Block Supports

Uses `__experimentalSkipSerialization` for full control:

- Color (background, text, gradients) - applied to active state only
- Typography (size, weight, family, etc.) - applied to all pills
- Spacing (padding) - applied to all pills
- Border (radius, width, style, color) - radius to all, others to active

## Layout Modes

| Mode | Description |
|------|-------------|
| `wrap` | Pills wrap to multiple rows (default) |
| `scroll` | Horizontal scroll, single row |

## Known Limitations

1. Single category selection only (no multi-select)
2. AND logic only (no OR filtering)
3. `render.php` is 250 lines and needs refactoring to shared helper
4. No category hierarchy support (flat list only)
5. No category icons/images

## Refactoring Notes

The `render.php` file contains duplicate patterns from `TGP_Button_Block_Renderer`:
- Style attribute extraction (lines 27-61)
- Inline style building (lines 105-161)
- Marker class system (lines 166-222)

Future work: Extract to `TGP_Pill_Block_Renderer` to reduce to ~80 lines.

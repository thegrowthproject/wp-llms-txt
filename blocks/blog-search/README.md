# Blog Search (`tgp/blog-search`)

Search input for filtering blog posts by title and excerpt.

## Purpose

Provides a text input for searching posts within the parent `tgp/blog-filters` block. Filtering happens on blur or Enter key to avoid excessive updates while typing.

## Requirements

- WordPress 6.5+ (Interactivity API)
- Must be placed inside a `tgp/blog-filters` block

## Attributes

| Attribute | Type | Default | Description |
|-----------|------|---------|-------------|
| `placeholder` | `string` | `"Search posts..."` | Input placeholder text |
| `showIcon` | `boolean` | `false` | Show search icon |
| `showClearButton` | `boolean` | `true` | Show clear (×) button |
| `width` | `string` | — | CSS width value (e.g., "300px", "100%") |

## Usage

```html
<!-- wp:tgp/blog-filters -->
<div class="wp-block-tgp-blog-filters">
  <!-- wp:tgp/blog-search {"placeholder":"Find articles...", "showIcon":true} /-->
</div>
<!-- /wp:tgp/blog-filters -->
```

## Behavior

1. User types in search field
2. On blur OR Enter key, filtering triggers
3. Parent block filters posts by title and excerpt
4. Clear button resets search and shows all posts

### Search Logic

- Case-insensitive matching
- Matches against post title OR excerpt
- Combined with category filters using AND logic

## Architecture

### Files

| File | Purpose |
|------|---------|
| [block.json](block.json) | Block metadata, usesContext |
| [index.js](index.js) | Editor component with TextControl |
| [render.php](render.php) | Server-side rendering |
| [style.css](style.css) | Input and icon styling |

### Parent-Child Relationship

```
tgp/blog-filters (providesContext)
  └── tgp/blog-search (usesContext)
```

This block cannot be used outside its parent.

### Interactivity API

This block uses the parent's `tgp/blog-filters` store:

**Context read:**
- `searchQuery` - Current search value

**Actions called:**
- `updateSearch` - Sync input value to context
- `submitSearch` - Apply filter on blur
- `handleSearchKeydown` - Apply filter on Enter
- `clearSearch` - Reset search

### Directives Used

```html
<input
  data-wp-bind--value="context.searchQuery"
  data-wp-on--input="actions.updateSearch"
  data-wp-on--blur="actions.submitSearch"
  data-wp-on--keydown="actions.handleSearchKeydown"
/>
<button
  data-wp-on--click="actions.clearSearch"
  data-wp-bind--hidden="!context.searchQuery"
/>
```

## Block Supports

Limited supports since this is a child block:

- Spacing (margin only)
- No color/typography (inherits from theme)

## Accessibility

- `role="search"` on container
- `aria-label` for screen readers
- Clear button has `aria-label`
- Works with keyboard (Tab, Enter, Escape)

## Known Limitations

1. Cannot be used standalone (requires parent)
2. No debouncing (filters on blur/Enter only)
3. No advanced search syntax (exact match only)

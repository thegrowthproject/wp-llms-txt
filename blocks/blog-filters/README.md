# Blog Filters (`tgp/blog-filters`)

A wrapper block for blog search and category filtering with real-time results.

## Purpose

Provides a container that manages state for child filter blocks. Queries all posts on page load and enables client-side filtering without page reloads. Child blocks connect to this parent's Interactivity API store.

## Requirements

- WordPress 6.5+ (Interactivity API)

## Attributes

| Attribute | Type | Default | Description |
|-----------|------|---------|-------------|
| `showResultCount` | `boolean` | `true` | Display "Showing X of Y posts" |

## Child Blocks

Only these blocks can be inserted inside:

| Block | Purpose |
|-------|---------|
| `tgp/blog-search` | Search input for filtering by title/excerpt |
| `tgp/blog-category-filter` | Category pills for filtering by taxonomy |

## Usage

```html
<!-- wp:tgp/blog-filters {"showResultCount":true} -->
<div class="wp-block-tgp-blog-filters">
  <!-- wp:tgp/blog-search {"placeholder":"Search articles..."} /-->
  <!-- wp:tgp/blog-category-filter /-->
</div>
<!-- /wp:tgp/blog-filters -->
```

## Behavior

1. On page load, queries all published posts
2. Builds category data for filter pills
3. Parses URL parameters for initial state (`?search=`, `?categories=`)
4. Child blocks trigger filtering via shared store
5. Updates URL without page reload (history.replaceState)
6. Shows/hides posts based on filter matches

### Filtering Logic

- **Search:** Matches title OR excerpt (case-insensitive)
- **Categories:** AND logic (post must have ALL selected categories)
- Combined: Search AND Categories must match

## Architecture

### Files

| File | Purpose |
|------|---------|
| [block.json](block.json) | Block metadata, providesContext |
| [index.js](index.js) | Editor component with InnerBlocks |
| [render.php](render.php) | Server-side data preparation |
| [view.js](view.js) | Interactivity API store |
| [style.css](style.css) | Result count and no-results styling |
| [editor.css](editor.css) | Editor placeholder styling |

### Context Provider

This block provides context to children:

```json
{
  "tgp/blogFilters": true
}
```

Children use `usesContext: ["tgp/blogFilters"]` to access the parent's store.

### Interactivity API

**Store:** `tgp/blog-filters`

**Global State (via `wp_interactivity_state()`):**
```json
{
  "posts": [{"id": 1, "title": "...", "excerpt": "...", "categories": ["ai"]}],
  "categories": [{"id": 1, "name": "AI", "slug": "ai", "count": 5}],
  "totalPosts": 12
}
```

**Context (per-instance, reactive):**
```json
{
  "searchQuery": "",
  "selectedCategories": [],
  "visiblePostIds": [1, 2, 3],
  "showResultCount": true
}
```

**State (computed):**
- `hasResults` - Whether any posts match filters
- `resultCountText` - "Showing X of Y posts" text
- `isCategoryActive` - Whether a category pill is selected

**Actions:**
- `updateSearch` - Update search query from input
- `submitSearch` - Apply search filter
- `handleSearchKeydown` - Handle Enter key in search
- `clearSearch` - Clear search input
- `toggleCategory` - Toggle category selection
- `clearFilters` - Reset all filters
- `applyFilters` - Run filtering logic
- `updatePostVisibility` - Update DOM visibility

**Callbacks:**
- `init` - Apply initial filters from URL
- `syncUrl` - Keep URL in sync with filter state

### State vs Context

This block demonstrates the pattern for large shared data:

| Data | Location | Why |
|------|----------|-----|
| Posts array | `wp_interactivity_state()` | Large, shared, not reactive per-element |
| Categories array | `wp_interactivity_state()` | Shared reference data |
| Search query | `data-wp-context` | Reactive, per-instance |
| Selected categories | `data-wp-context` | Reactive, per-instance |

**Important:** Do NOT define default values in JavaScript for state properties from PHP. JS definitions overwrite PHP values.

## Post Visibility

Posts must have a `data-post-id` attribute to be filtered:

```html
<article data-post-id="123">...</article>
```

The block uses direct DOM manipulation to show/hide posts rather than re-rendering, for performance.

## URL State

Filter state is synced to URL parameters:

- `?search=query` - Search term
- `?categories=ai,devops` - Comma-separated category slugs

This enables:
- Shareable filtered views
- Back button support
- Bookmarkable searches

## Known Limitations

1. Queries ALL posts on page load (not suitable for >1000 posts)
2. Filtering is client-side only (no server pagination)
3. Post elements must have `data-post-id` attribute
4. Single category selection (OR logic not supported)

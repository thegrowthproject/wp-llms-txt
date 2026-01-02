# ADR 001: Interactivity API State vs Context

## Status

Accepted

## Context

The WordPress Interactivity API provides two mechanisms for managing data:

1. **Global State** (`wp_interactivity_state()` in PHP, `state` in JS store)
2. **Local Context** (`data-wp-context` attribute, `getContext()` in JS)

When building the `tgp/blog-filters` block, we needed to decide where to put different types of data:
- Posts array (~50KB for 100 posts)
- Categories array (~2KB)
- Search query (reactive, per-instance)
- Selected categories (reactive, per-instance)
- Visible post IDs (computed, per-instance)

## Decision

Use **global state** for large, shared, non-reactive data. Use **context** for small, reactive, per-instance data.

### Global State (via `wp_interactivity_state()`)

```php
wp_interactivity_state(
    'tgp/blog-filters',
    [
        'posts'      => $posts_data,      // Large array
        'categories' => $categories_data, // Reference data
        'totalPosts' => count( $posts_data ),
    ]
);
```

### Local Context (via `data-wp-context`)

```php
$context = [
    'searchQuery'        => '',    // Reactive input value
    'selectedCategories' => [],    // Reactive selection
    'visiblePostIds'     => [],    // Computed result
    'showResultCount'    => true,  // Instance config
];
```

## Consequences

### Positive

1. **Reduced HTML payload** - Posts array in state (not serialized per-element) reduced context from 3,200 bytes to 150 bytes per block instance
2. **Shared reference** - Multiple child blocks access same posts data without duplication
3. **Clear separation** - Large data vs reactive UI state

### Negative

1. **Learning curve** - Developers must understand when to use which
2. **State merging gotcha** - JS store definitions overwrite PHP values

### Critical Gotcha

**Do NOT define default values in JavaScript for state properties that come from PHP.**

```javascript
// ❌ BAD - This overwrites PHP data
state: {
    posts: [],
    categories: [],
    totalPosts: 0,
}

// ✅ GOOD - Only define computed getters
state: {
    // posts, categories, totalPosts come from PHP
    get hasResults() {
        return getContext().visiblePostIds.length > 0;
    }
}
```

PHP values are set first, then JS store definitions run and overwrite them.

## Decision Table

| Data Type | Location | Example |
|-----------|----------|---------|
| Large arrays | `wp_interactivity_state()` | Posts, comments |
| Reference data | `wp_interactivity_state()` | Categories, users |
| Reactive input values | `data-wp-context` | Search query, form fields |
| Selection state | `data-wp-context` | Checked items, active tab |
| Computed results | JS getter in `state` | Filtered list, validation |
| Per-instance config | `data-wp-context` | Show/hide options |

## Related

- [WordPress Interactivity API docs](https://developer.wordpress.org/block-editor/reference-guides/interactivity-api/)
- Issue #12: Global state optimization
- PR #13: Fix filtering after state optimization

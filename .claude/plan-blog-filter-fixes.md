# Blog Filter UX Fixes - Implementation Plan

## Issues Summary

1. **Selected button brand colour not applied** - Active category pills don't show brand styling
2. **Backend inline layout broken** - Search and filters stack vertically in editor
3. **Show results toggle hides child blocks** - Disabling toggle removes search/filters entirely
4. **Search field doesn't filter posts** - Typing in search has no visible effect

---

## Issue 1: Selected Button Brand Colour

### Root Cause
The CSS is correct (uses `--wp--preset--color--primary` which is `#5344F4`), but the `--active` class may not be applied. The issue is in `state.isCategoryActive` which uses `getElement()`.

Looking at view.js:43-48:
```javascript
get isCategoryActive() {
    const ctx = getContext();
    const { ref } = getElement();
    const slug = ref?.dataset?.categorySlug;
    return slug && ctx.selectedCategories.includes( slug );
}
```

The problem: `getElement()` returns the element that **triggered the directive evaluation**. For `data-wp-class--*` directives, this should work, but the `ref` might be undefined during initial render or after state changes.

### Fix
Change `state.isCategoryActive` to accept the slug from the directive context rather than relying on `getElement()`. Use a different approach - pass the slug via directive context.

**Files to modify:**
- `blocks/blog-category-filter/render.php` - Add slug to element context
- `blocks/blog-filters/view.js` - Update `isCategoryActive` to read from context

---

## Issue 2: Backend Inline Layout

### Root Cause
The `style.css` is loaded in the editor, but the editor renders InnerBlocks differently. The wrapper gets `wp-block-tgp-blog-filters` class via `useBlockProps()`, but InnerBlocks creates an intermediate wrapper that breaks the flex layout.

### Fix
Add an `editorStyle` CSS file specifically for the block editor that targets the InnerBlocks wrapper structure.

**Files to create/modify:**
- `blocks/blog-filters/block.json` - Add `editorStyle` property
- `blocks/blog-filters/editor.css` - Create editor-specific styles

---

## Issue 3: Show Results Toggle Hides Child Blocks

### Root Cause
In both child block render.php files:
```php
if ( empty( $block->context['tgp/blogFilters'] ) ) {
    return '';
}
```

The context value is `showResultCount` (boolean). When `false`, `empty(false)` = `true`, so blocks return empty.

### Fix
Change the check to verify the context **key exists** rather than checking its truthiness.

**Files to modify:**
- `blocks/blog-search/render.php` - Line 20
- `blocks/blog-category-filter/render.php` - Line 20

Change to:
```php
if ( ! array_key_exists( 'tgp/blogFilters', $block->context ) ) {
    return '';
}
```

---

## Issue 4: Search Field Doesn't Work

### Root Cause Analysis
The debounce logic looks correct. Possible issues:

1. **`getContext()` scope** - In Interactivity API, `getContext()` returns the context from the nearest `data-wp-context` ancestor. The search input is inside the blog-filters wrapper which has the context, so this should work.

2. **Event binding** - The input uses `data-wp-on--input="actions.updateSearch"` which should fire on every keystroke.

3. **Context mutation** - Setting `ctx.searchQuery = value` should trigger reactivity, but `ctx.posts` array needs to exist and have correct structure.

4. **`applyFilters()` execution** - The function queries `ctx.posts` which is set in render.php. Need to verify the context is populated correctly.

### Debugging Steps
Check if the Interactivity store is initializing. The `callbacks.init()` should run on page load.

### Potential Fix
The issue might be that `actions.applyFilters()` is being called before the store is fully initialized. Also, the `init` callback uses `data-wp-init` which should work.

Add console logging to verify the flow, or check if there's a JavaScript error in the browser console.

**Files to investigate:**
- `blocks/blog-filters/view.js` - Add error handling
- `blocks/blog-filters/render.php` - Verify context output

---

## Implementation Order

1. **Fix Issue 3 first** (context check) - Simple PHP change, unblocks frontend testing
2. **Fix Issue 1** (active state) - JavaScript/PHP coordination
3. **Fix Issue 4** (search) - Debug and fix filtering logic
4. **Fix Issue 2** (editor layout) - Add editor CSS

---

## File Changes Summary

| File | Change |
|------|--------|
| `blocks/blog-search/render.php` | Change `empty()` to `array_key_exists()` check |
| `blocks/blog-category-filter/render.php` | Change `empty()` to `array_key_exists()` check |
| `blocks/blog-category-filter/render.php` | Add `data-wp-context` with category slug |
| `blocks/blog-filters/view.js` | Update `isCategoryActive` to use element context |
| `blocks/blog-filters/block.json` | Add `editorStyle` property |
| `blocks/blog-filters/editor.css` | Create new file with editor flex layout |

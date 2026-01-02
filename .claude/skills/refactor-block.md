# /refactor-block

Extract shared patterns from an existing block into a reusable helper.

## Usage

```
/refactor-block [block-name]
```

## Description

Analyzes an existing block's render.php to identify extractable patterns, then either uses an existing helper class or creates a new one. Reduces code duplication and improves maintainability.

## Workflow

1. **Identify target block** (required)
   - If not provided as argument, prompt for it
   - Must exist in `blocks/` directory

2. **Analyze render.php**
   - Count lines and complexity
   - Identify style extraction patterns
   - Identify class building patterns
   - Identify inline style patterns
   - Check for context dependencies

3. **Recommend refactoring approach**
   - Use existing helper (TGP_Button_Block_Renderer, TGP_Pill_Block_Renderer)
   - Create new helper class
   - No refactoring needed (already optimal)

4. **Execute refactoring**
   - Update render.php to use helper
   - Verify linting passes
   - Test frontend output

5. **Update documentation**
   - Update block README.md
   - Update ADRs if new patterns established

## Refactoring Triggers

Consider refactoring when:

| Metric | Threshold | Action |
|--------|-----------|--------|
| Lines in render.php | >100 | Extract to helper |
| Style attribute extractions | >5 | Use shared helper |
| Inline style builders | >20 lines | Use shared helper |
| Duplicate patterns | Across 2+ blocks | Create new helper |

## Pattern Detection

### Style Extraction Pattern

Look for this pattern:

```php
$font_size       = $attributes['style']['typography']['fontSize'] ?? null;
$line_height     = $attributes['style']['typography']['lineHeight'] ?? null;
$font_weight     = $attributes['style']['typography']['fontWeight'] ?? null;
// ... more extractions
```

**Refactor to:**

```php
$style_attrs = TGP_Button_Block_Renderer::get_style_attributes( $attributes );
```

### Inline Style Building Pattern

Look for this pattern:

```php
$styles = [];
if ( $font_size ) {
    $styles[] = 'font-size: ' . $font_size;
}
if ( $line_height ) {
    $styles[] = 'line-height: ' . $line_height;
}
// ... more conditionals
$style_attr = implode( '; ', $styles );
```

**Refactor to:**

```php
$style_attr = TGP_Button_Block_Renderer::get_style_attribute( $style_attrs, $has_variation );
```

### Class Building Pattern

Look for this pattern:

```php
$classes = [ 'wp-block-button__link', 'wp-element-button' ];
if ( $bg_color_slug ) {
    $classes[] = 'has-background';
    $classes[] = 'has-' . $bg_color_slug . '-background-color';
}
```

**Refactor to:**

```php
$inner_classes = TGP_Button_Block_Renderer::build_inner_classes(
    $style_attrs,
    'wp-block-tgp-my-block',
    $has_variation
);
```

### Marker Class Pattern

Look for this pattern (for toggleable blocks):

```php
$marker_classes = [];
if ( $background_color ) {
    $marker_classes[] = 'has-custom-active-bg';
    $wrapper_styles[] = '--tgp-active-bg: ' . $background_color;
}
```

**Refactor to:**

```php
$active_styles = TGP_Pill_Block_Renderer::build_active_state_styles( $style_attrs, $colors );
$wrapper_attrs = TGP_Pill_Block_Renderer::inject_marker_classes(
    $wrapper_attrs,
    $active_styles['classes']
);
```

## Existing Helpers

### TGP_Button_Block_Renderer

For button-style blocks (copy-button, view-button pattern).

**Methods:**
- `get_style_attributes( $attributes )` - Extract all style data
- `get_style_variation( $wrapper_attrs )` - Detect is-style-* class
- `build_outer_classes( $style_attrs, $variation )` - Wrapper classes
- `build_inner_classes( $style_attrs, $base, $has_var )` - Button classes
- `build_inline_styles( $style_attrs, $has_var )` - CSS string
- `get_style_attribute( $style_attrs, $has_var )` - Full style attr

### TGP_Pill_Block_Renderer

For toggleable pill blocks (blog-category-filter pattern).

**Methods:**
- `get_style_attributes( $attributes )` - Extract style data
- `resolve_colors( $style_attrs )` - Convert presets to CSS vars
- `get_style_variation( $wrapper_attrs )` - Detect active style
- `build_pill_wrapper_classes()` - Pill wrapper classes
- `build_button_classes( $base )` - Inner button classes
- `build_button_styles( $style_attrs )` - Both-state styles
- `build_active_state_styles( $style_attrs, $colors )` - Active-only
- `get_style_classes( $variation )` - Active/inactive class names
- `inject_marker_classes( $attrs, $classes )` - Add marker classes

## Creating New Helpers

When existing helpers don't fit:

1. Create `includes/class-{pattern}-block-renderer.php`
2. Follow static method pattern
3. Add require_once in `tgp-llms-txt.php`
4. Update ADR 003 (shared-helper-classes.md)
5. Update this skill's documentation

### Helper Class Template

```php
<?php
/**
 * {Pattern} Block Renderer Helper
 *
 * Shared rendering logic for {description} blocks.
 *
 * @package TGP_LLMs_Txt
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class TGP_{Pattern}_Block_Renderer {

    /**
     * Extract style attributes from block attributes.
     *
     * @param array $attributes Block attributes.
     * @return array Extracted style attributes.
     */
    public static function get_style_attributes( $attributes ) {
        return [
            // Extract relevant attributes
        ];
    }

    // Add other methods as needed
}
```

## Example Refactoring

### Before (250 lines)

```php
// blog-category-filter/render.php

// 35 lines of style extraction
$font_size = $attributes['style']['typography']['fontSize'] ?? null;
$line_height = $attributes['style']['typography']['lineHeight'] ?? null;
// ... 30 more lines

// 60 lines of style building
$button_styles = [];
if ( $font_size ) {
    $button_styles[] = 'font-size: ' . $font_size;
}
// ... 55 more lines

// 40 lines of marker class handling
$marker_classes = [];
if ( $background_color ) {
    $marker_classes[] = 'has-custom-active-bg';
}
// ... 35 more lines

// Template output
```

### After (102 lines)

```php
// blog-category-filter/render.php

// Style extraction using helper
$style_attrs = TGP_Pill_Block_Renderer::get_style_attributes( $attributes );
$colors      = TGP_Pill_Block_Renderer::resolve_colors( $style_attrs );

// Style variation detection
$wrapper_attrs   = get_block_wrapper_attributes();
$style_variation = TGP_Pill_Block_Renderer::get_style_variation( $wrapper_attrs );
$style_classes   = TGP_Pill_Block_Renderer::get_style_classes( $style_variation );

// Active state handling
$active_styles    = TGP_Pill_Block_Renderer::build_active_state_styles( $style_attrs, $colors );
$wrapper_attrs    = TGP_Pill_Block_Renderer::inject_marker_classes( $wrapper_attrs, $active_styles['classes'] );

// Style attributes
$wrapper_style_attr = TGP_Pill_Block_Renderer::get_wrapper_style_attribute( $active_styles );
$button_style_attr  = TGP_Pill_Block_Renderer::get_button_style_attribute( $style_attrs );

// Template output
```

**Result:** 59% reduction in lines, logic consolidated in reusable helper.

## Verification Steps

After refactoring:

1. **Lint check**
   ```bash
   composer lint
   ```

2. **Frontend test**
   - Load page with block
   - Verify HTML output matches before
   - Check style attributes applied correctly
   - Test interactive behavior (if applicable)

3. **Editor test**
   - Open block in editor
   - Verify settings panel works
   - Check preview renders correctly

## Example Usage

```
User: /refactor-block blog-category-filter

Claude: Analyzing blocks/blog-category-filter/render.php...

Current state:
- 250 lines
- Style extraction: 35 lines (extractable)
- Style building: 60 lines (extractable)
- Marker classes: 40 lines (extractable)

Recommendation: Use TGP_Pill_Block_Renderer

This helper already exists and handles:
✓ Style attribute extraction
✓ Color resolution
✓ Button style building
✓ Active state styles
✓ Marker class injection

Proceeding with refactoring...

Refactored render.php:
- Before: 250 lines
- After: 102 lines
- Reduction: 59%

Running verification:
✓ composer lint passed
✓ Frontend output unchanged
✓ Style attributes applied correctly

Updated documentation:
- blocks/blog-category-filter/README.md
```

## Notes

- Always verify output matches before refactoring
- Run linter after every change
- Test frontend AND editor behavior
- Document new patterns in ADRs
- Prefer extending existing helpers over creating new ones

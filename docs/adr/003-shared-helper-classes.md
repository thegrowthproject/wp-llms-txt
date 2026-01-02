# ADR 003: Shared Helper Classes

## Status

Accepted

## Context

Multiple blocks in this plugin share rendering patterns:

1. **Button blocks** (`copy-button`, `view-button`) - Same wrapper structure, style handling, icon rendering
2. **Pill blocks** (`blog-category-filter`) - Similar style handling but with active/inactive states

Without shared helpers, each block's `render.php` duplicates:
- Style attribute extraction (~30 lines)
- Class building (~20 lines)
- Inline style generation (~50 lines)
- Icon sanitization (~10 lines)

This leads to:
- 150+ lines of duplicate code
- Inconsistent implementations
- Bug fixes needed in multiple places

## Decision

Create shared helper classes for common patterns:

### Existing Helpers

| Class | Purpose | Used By |
|-------|---------|---------|
| `TGP_Button_Block_Renderer` | Button rendering, style handling | `copy-button`, `view-button` |
| `TGP_SVG_Sanitizer` | Safe SVG output via `wp_kses` | All blocks with icons |

### Planned Helpers

| Class | Purpose | Will Be Used By |
|-------|---------|-----------------|
| `TGP_Pill_Block_Renderer` | Pill rendering with active states | `blog-category-filter` |

## Implementation

### When to Create a Helper

Create a shared helper when:

1. **3+ blocks** use the same pattern, OR
2. **2 blocks** with 50+ lines of shared logic, OR
3. Logic is **complex enough** that bugs are likely

### Helper Class Structure

```php
class TGP_Button_Block_Renderer {
    // Extract attributes from block
    public static function get_style_attributes( $attributes ) {}

    // Detect style variation
    public static function get_style_variation( $wrapper_attrs ) {}

    // Build class arrays
    public static function build_outer_classes( $style_attrs, $variation ) {}
    public static function build_inner_classes( $style_attrs, $base, $has_var ) {}

    // Build style strings
    public static function build_inline_styles( $style_attrs, $has_var ) {}
    public static function get_style_attribute( $style_attrs, $has_var ) {}
}
```

### Usage in render.php

```php
// Before: 100+ lines of style handling
// After: ~20 lines

$style_attrs = TGP_Button_Block_Renderer::get_style_attributes( $attributes );
$style_info  = TGP_Button_Block_Renderer::get_style_variation( $wrapper_attrs );

$outer_classes = TGP_Button_Block_Renderer::build_outer_classes(
    $style_attrs,
    $style_info['variation']
);

$inner_classes = TGP_Button_Block_Renderer::build_inner_classes(
    $style_attrs,
    'wp-block-tgp-copy-button',
    $style_info['has_variation']
);

$style_attr = TGP_Button_Block_Renderer::get_style_attribute(
    $style_attrs,
    $style_info['has_variation']
);
```

## Consequences

### Positive

1. **DRY code** - Single source of truth for patterns
2. **Consistency** - All blocks handle styles the same way
3. **Maintainability** - Fix bugs once, all blocks benefit
4. **Smaller render.php** - 100+ lines → 20 lines

### Negative

1. **Abstraction overhead** - Must understand helper API
2. **Coupling** - Blocks depend on shared classes
3. **Flexibility trade-off** - Edge cases may need workarounds

### Metrics

| Block | Before | After | Reduction |
|-------|--------|-------|-----------|
| `copy-button/render.php` | ~120 lines | ~40 lines | 67% |
| `view-button/render.php` | ~100 lines | ~35 lines | 65% |
| `blog-category-filter/render.php` | ~250 lines | ~80 lines (planned) | 68% |

## File Organization

```
includes/
├── class-button-block-renderer.php   # Button blocks
├── class-pill-block-renderer.php     # Pill/toggle blocks (planned)
├── class-svg-sanitizer.php           # SVG sanitization
└── class-llms-txt-generator.php      # Core functionality
```

All helpers are loaded in `tgp-llms-txt.php`:

```php
require_once __DIR__ . '/includes/class-button-block-renderer.php';
require_once __DIR__ . '/includes/class-svg-sanitizer.php';
```

## Related

- `TGP_Button_Block_Renderer` implementation
- `TGP_SVG_Sanitizer` implementation
- ADR 002: Skip Serialization Pattern
- Issue #14: Extract TGP_Pill_Block_Renderer

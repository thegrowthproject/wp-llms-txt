<?php
/**
 * Server-side rendering for Blog Category Filter block.
 *
 * Renders category filter pills that use the parent Blog Filters block's
 * Interactivity store. Uses AND logic - posts must have ALL selected categories.
 *
 * @package TGP_LLMs_Txt
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block content.
 * @var WP_Block $block      Block instance.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Ensure we're inside a blog-filters block.
if ( ! array_key_exists( 'tgp/blogFilters', $block->context ) ) {
	return '';
}

$show_count = $attributes['showCount'] ?? true;
$layout     = $attributes['layout'] ?? 'wrap';

// Typography from style attribute.
$font_size       = $attributes['style']['typography']['fontSize'] ?? null;
$line_height     = $attributes['style']['typography']['lineHeight'] ?? null;
$font_weight     = $attributes['style']['typography']['fontWeight'] ?? null;
$font_family     = $attributes['style']['typography']['fontFamily'] ?? null;
$letter_spacing  = $attributes['style']['typography']['letterSpacing'] ?? null;
$text_transform  = $attributes['style']['typography']['textTransform'] ?? null;
$text_decoration = $attributes['style']['typography']['textDecoration'] ?? null;

// Spacing from style attribute.
$padding = $attributes['style']['spacing']['padding'] ?? null;

// Border from style attribute.
$border = $attributes['style']['border'] ?? null;

// Shadow from style attribute.
$shadow = $attributes['style']['shadow'] ?? null;

// Color from attributes (preset slugs or custom values).
// Preset colors are stored as slugs in backgroundColor/textColor attributes.
// Custom colors are stored as CSS values in style.color.background/text.
$background_color_preset = $attributes['backgroundColor'] ?? null;
$text_color_preset       = $attributes['textColor'] ?? null;
$background_color_custom = $attributes['style']['color']['background'] ?? null;
$text_color_custom       = $attributes['style']['color']['text'] ?? null;

// Resolve colors: custom takes precedence, then preset converted to CSS var.
$background_color = $background_color_custom;
if ( ! $background_color && $background_color_preset ) {
	$background_color = 'var(--wp--preset--color--' . $background_color_preset . ')';
}
$text_color = $text_color_custom;
if ( ! $text_color && $text_color_preset ) {
	$text_color = 'var(--wp--preset--color--' . $text_color_preset . ')';
}

// Get all categories that have posts.
$categories = get_categories(
	[
		'hide_empty' => true,
		'orderby'    => 'name',
		'order'      => 'ASC',
	]
);

if ( empty( $categories ) ) {
	return '';
}

// Get block wrapper attributes.
$wrapper_attrs_string = get_block_wrapper_attributes();

// Detect style variation from wrapper attributes.
$style_variation = 'button-brand'; // Default.
if ( preg_match( '/is-style-([a-z0-9-]+)/', $wrapper_attrs_string, $style_match ) ) {
	$style_variation = $style_match[1];
}

// Build list wrapper class.
$list_class = 'wp-block-tgp-blog-category-filter__list';
if ( 'scroll' === $layout ) {
	$list_class .= ' wp-block-tgp-blog-category-filter__list--scroll';
}

// Build wrapper classes.
// Non-active pills use tint (secondary-button) styling.
// Active pills swap to selected style variation via Interactivity API.
$wrapper_classes = [ 'wp-block-button', 'is-style-secondary-button' ];

// Build dynamic class attribute for style swapping.
$style_class_active   = 'is-style-' . $style_variation;
$style_class_inactive = 'is-style-secondary-button';

// Build button classes (inner element).
$button_classes = [ 'wp-block-button__link', 'wp-element-button', 'wp-block-tgp-blog-category-filter__pill' ];

// Build button inline styles (apply to BOTH states).
// Typography, border-radius, and padding apply to all buttons.
$button_styles = [];

// Typography styles (both states).
if ( $font_size ) {
	$button_styles[] = 'font-size: ' . $font_size;
}
if ( $line_height ) {
	$button_styles[] = 'line-height: ' . $line_height;
}
if ( $font_weight ) {
	$button_styles[] = 'font-weight: ' . $font_weight;
}
if ( $font_family ) {
	$button_styles[] = 'font-family: ' . $font_family;
}
if ( $letter_spacing ) {
	$button_styles[] = 'letter-spacing: ' . $letter_spacing;
}
if ( $text_transform ) {
	$button_styles[] = 'text-transform: ' . $text_transform;
}
if ( $text_decoration ) {
	$button_styles[] = 'text-decoration: ' . $text_decoration;
}

// Border radius (both states).
if ( $border && isset( $border['radius'] ) ) {
	if ( is_array( $border['radius'] ) ) {
		$top_left     = $border['radius']['topLeft'] ?? '0';
		$top_right    = $border['radius']['topRight'] ?? '0';
		$bottom_right = $border['radius']['bottomRight'] ?? '0';
		$bottom_left  = $border['radius']['bottomLeft'] ?? '0';
		$button_styles[] = 'border-radius: ' . $top_left . ' ' . $top_right . ' ' . $bottom_right . ' ' . $bottom_left;
	} else {
		$button_styles[] = 'border-radius: ' . $border['radius'];
	}
}

// Padding (both states).
if ( $padding ) {
	if ( is_array( $padding ) ) {
		if ( isset( $padding['top'] ) ) {
			$button_styles[] = 'padding-top: ' . $padding['top'];
		}
		if ( isset( $padding['right'] ) ) {
			$button_styles[] = 'padding-right: ' . $padding['right'];
		}
		if ( isset( $padding['bottom'] ) ) {
			$button_styles[] = 'padding-bottom: ' . $padding['bottom'];
		}
		if ( isset( $padding['left'] ) ) {
			$button_styles[] = 'padding-left: ' . $padding['left'];
		}
	}
}

$button_style_attr = ! empty( $button_styles ) ? ' style="' . esc_attr( implode( '; ', $button_styles ) ) . '"' : '';

// Build wrapper CSS custom properties (apply to ACTIVE only).
// Colors, border width/style/color, and shadow only apply to active state.
// We use marker classes to conditionally apply CSS only when custom values exist.
$wrapper_styles       = [];
$block_marker_classes = [];

// Border properties (active only) - width, style, color.
if ( $border ) {
	if ( isset( $border['width'] ) ) {
		$block_marker_classes[] = 'has-custom-active-border';
		$wrapper_styles[]       = '--tgp-active-border-width: ' . $border['width'];
		// Default to solid if width is set but style isn't.
		if ( ! isset( $border['style'] ) ) {
			$wrapper_styles[] = '--tgp-active-border-style: solid';
		}
	}
	if ( isset( $border['style'] ) ) {
		$wrapper_styles[] = '--tgp-active-border-style: ' . $border['style'];
	}
	if ( isset( $border['color'] ) ) {
		$wrapper_styles[] = '--tgp-active-border-color: ' . $border['color'];
	}
}

// Shadow (active only).
if ( $shadow ) {
	$block_marker_classes[] = 'has-custom-active-shadow';
	$wrapper_styles[]       = '--tgp-active-box-shadow: ' . $shadow;
}

// Colors (active only).
if ( $background_color ) {
	$block_marker_classes[] = 'has-custom-active-bg';
	$wrapper_styles[]       = '--tgp-active-bg: ' . $background_color;
}
if ( $text_color ) {
	$block_marker_classes[] = 'has-custom-active-text';
	$wrapper_styles[]       = '--tgp-active-text: ' . $text_color;
}

$wrapper_style_attr = ! empty( $wrapper_styles ) ? ' style="' . esc_attr( implode( '; ', $wrapper_styles ) ) . '"' : '';

// Build class strings.
$button_class_string       = implode( ' ', $button_classes );
$pill_wrapper_class_string = implode( ' ', $wrapper_classes );

// Add marker classes to block wrapper attributes.
// These control which CSS custom properties are applied.
$block_marker_class_string = ! empty( $block_marker_classes ) ? ' ' . implode( ' ', $block_marker_classes ) : '';

// Inject marker classes into wrapper attributes.
// The get_block_wrapper_attributes() returns a string like 'class="wp-block-tgp-blog-category-filter ..."'.
// We need to append our marker classes to this.
if ( ! empty( $block_marker_classes ) ) {
	$wrapper_attrs_string = preg_replace(
		'/class="([^"]*)"/',
		'class="$1' . esc_attr( $block_marker_class_string ) . '"',
		$wrapper_attrs_string
	);
}
?>
<div <?php echo $wrapper_attrs_string; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo $wrapper_style_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="<?php echo esc_attr( $list_class ); ?>" role="group" aria-label="<?php /* translators: Accessible label for category filter group */ esc_attr_e( 'Filter by category', 'tgp-llms-txt' ); ?>">
		<?php foreach ( $categories as $category ) : ?>
		<div
			class="<?php echo esc_attr( $pill_wrapper_class_string ); ?>"
			<?php echo wp_interactivity_data_wp_context( [ 'slug' => $category->slug ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			data-wp-class--wp-block-tgp-blog-category-filter__pill--active="state.isCategoryActive"
			data-wp-class--<?php echo esc_attr( $style_class_active ); ?>="state.isCategoryActive"
			data-wp-class--<?php echo esc_attr( $style_class_inactive ); ?>="!state.isCategoryActive"
		>
			<button
				type="button"
				class="<?php echo esc_attr( $button_class_string ); ?>"<?php echo $button_style_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				data-category-slug="<?php echo esc_attr( $category->slug ); ?>"
				data-wp-on--click="actions.toggleCategory"
				aria-pressed="false"
				data-wp-bind--aria-pressed="state.isCategoryActive"
			>
				<span class="wp-block-tgp-blog-category-filter__pill-name"><?php echo esc_html( $category->name ); ?></span>
				<?php if ( $show_count ) : ?>
				<span class="wp-block-tgp-blog-category-filter__pill-count"><?php echo esc_html( $category->count ); ?></span>
				<?php endif; ?>
			</button>
		</div>
		<?php endforeach; ?>
	</div>
</div>

<?php
/**
 * Server-side rendering for View as Markdown button block.
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

// Get current post.
global $post;
if ( ! $post ) {
	return '';
}

// Get attributes with defaults.
/* translators: Default button label for viewing content as markdown */
$label     = $attributes['label'] ?? __( 'View as Markdown', 'tgp-llms-txt' );
$show_icon = $attributes['showIcon'] ?? true;
$width     = $attributes['width'] ?? null;

// Color attributes (used when no style variation is active).
$bg_color_slug   = $attributes['backgroundColor'] ?? null;
$text_color_slug = $attributes['textColor'] ?? null;
$gradient_slug   = $attributes['gradient'] ?? null;

// Custom color values from style attribute.
$custom_bg_color   = $attributes['style']['color']['background'] ?? null;
$custom_text_color = $attributes['style']['color']['text'] ?? null;
$custom_gradient   = $attributes['style']['color']['gradient'] ?? null;

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

// Build markdown URL.
$permalink = get_permalink( $post );
$md_url    = rtrim( $permalink, '/' ) . '.md';

// View/document icon SVG.
$view_icon = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>';

// Allowed SVG tags for wp_kses.
$allowed_svg = [
	'svg'      => [
		'xmlns'           => true,
		'width'           => true,
		'height'          => true,
		'viewbox'         => true,
		'fill'            => true,
		'stroke'          => true,
		'stroke-width'    => true,
		'stroke-linecap'  => true,
		'stroke-linejoin' => true,
	],
	'path'     => [
		'd' => true,
	],
	'polyline' => [
		'points' => true,
	],
	'line'     => [
		'x1' => true,
		'y1' => true,
		'x2' => true,
		'y2' => true,
	],
	'circle'   => [
		'cx' => true,
		'cy' => true,
		'r'  => true,
	],
];

// Get block wrapper attributes (minimal now due to skipSerialization).
$wrapper_attrs_string = get_block_wrapper_attributes();

// Detect style variation from wrapper attributes.
$style_variation     = 'fill'; // Default.
$has_style_variation = false;
if ( preg_match( '/is-style-([a-z0-9-]+)/', $wrapper_attrs_string, $style_match ) ) {
	$style_variation     = $style_match[1];
	$has_style_variation = ( 'fill' !== $style_variation );
}

// Build outer wrapper classes.
$outer_classes = [ 'wp-block-button', 'is-style-' . $style_variation ];

// Add width classes.
if ( $width ) {
	$outer_classes[] = 'has-custom-width';
	$outer_classes[] = 'wp-block-button__width-' . $width;
}

// Build inner button classes.
$inner_classes = [ 'wp-block-button__link', 'wp-element-button', 'tgp-view-btn' ];

// Only add color classes if NOT using a style variation (or using default fill).
if ( ! $has_style_variation ) {
	if ( $bg_color_slug ) {
		$inner_classes[] = 'has-background';
		$inner_classes[] = 'has-' . $bg_color_slug . '-background-color';
	}
	if ( $text_color_slug ) {
		$inner_classes[] = 'has-text-color';
		$inner_classes[] = 'has-' . $text_color_slug . '-color';
	}
	if ( $gradient_slug ) {
		$inner_classes[] = 'has-background';
		$inner_classes[] = 'has-' . $gradient_slug . '-gradient-background';
	}
	// Add has-background/has-text-color for custom values.
	if ( $custom_bg_color || $custom_gradient ) {
		$inner_classes[] = 'has-background';
	}
	if ( $custom_text_color ) {
		$inner_classes[] = 'has-text-color';
	}
}

// Build inline styles.
$inline_styles = [];

// Color styles only if NOT using a style variation.
if ( ! $has_style_variation ) {
	if ( $custom_bg_color ) {
		$inline_styles[] = 'background-color: ' . $custom_bg_color;
	}
	if ( $custom_text_color ) {
		$inline_styles[] = 'color: ' . $custom_text_color;
	}
	if ( $custom_gradient ) {
		$inline_styles[] = 'background: ' . $custom_gradient;
	}
}

// Typography styles (always apply).
if ( $font_size ) {
	$inline_styles[] = 'font-size: ' . $font_size;
}
if ( $line_height ) {
	$inline_styles[] = 'line-height: ' . $line_height;
}
if ( $font_weight ) {
	$inline_styles[] = 'font-weight: ' . $font_weight;
}
if ( $font_family ) {
	$inline_styles[] = 'font-family: ' . $font_family;
}
if ( $letter_spacing ) {
	$inline_styles[] = 'letter-spacing: ' . $letter_spacing;
}
if ( $text_transform ) {
	$inline_styles[] = 'text-transform: ' . $text_transform;
}
if ( $text_decoration ) {
	$inline_styles[] = 'text-decoration: ' . $text_decoration;
}

// Spacing styles (always apply).
if ( $padding ) {
	if ( is_array( $padding ) ) {
		if ( isset( $padding['top'] ) ) {
			$inline_styles[] = 'padding-top: ' . $padding['top'];
		}
		if ( isset( $padding['right'] ) ) {
			$inline_styles[] = 'padding-right: ' . $padding['right'];
		}
		if ( isset( $padding['bottom'] ) ) {
			$inline_styles[] = 'padding-bottom: ' . $padding['bottom'];
		}
		if ( isset( $padding['left'] ) ) {
			$inline_styles[] = 'padding-left: ' . $padding['left'];
		}
	}
}

// Border styles (always apply).
if ( $border ) {
	if ( isset( $border['radius'] ) ) {
		$inline_styles[] = 'border-radius: ' . $border['radius'];
	}
	if ( isset( $border['width'] ) ) {
		$inline_styles[] = 'border-width: ' . $border['width'];
	}
	if ( isset( $border['style'] ) ) {
		$inline_styles[] = 'border-style: ' . $border['style'];
	}
	if ( isset( $border['color'] ) ) {
		$inline_styles[] = 'border-color: ' . $border['color'];
	}
}

// Shadow styles (always apply).
if ( $shadow ) {
	$inline_styles[] = 'box-shadow: ' . $shadow;
}

// Build style attribute.
$style_attr = ! empty( $inline_styles ) ? ' style="' . esc_attr( implode( '; ', $inline_styles ) ) . '"' : '';
?>
<div class="<?php echo esc_attr( implode( ' ', $outer_classes ) ); ?>">
	<a
		href="<?php echo esc_url( $md_url ); ?>"
		target="_blank"
		rel="noopener noreferrer"
		class="<?php echo esc_attr( implode( ' ', $inner_classes ) ); ?>"<?php echo $style_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		title="<?php /* translators: Tooltip text for view markdown button */ esc_attr_e( 'View this content as plain markdown', 'tgp-llms-txt' ); ?>"
	>
		<?php if ( $show_icon ) : ?>
			<span class="tgp-btn-icon" aria-hidden="true"><?php echo wp_kses( $view_icon, $allowed_svg ); ?></span>
		<?php endif; ?>
		<span class="tgp-btn-text"><?php echo esc_html( $label ); ?></span>
		<span class="screen-reader-text"><?php /* translators: Screen reader text describing the view markdown button action */ esc_html_e( 'View page content as plain markdown in new tab', 'tgp-llms-txt' ); ?></span>
	</a>
</div>

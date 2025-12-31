<?php
/**
 * Server-side rendering for Copy Button block.
 *
 * Renders a button that copies the current page content as markdown.
 * Uses WordPress Interactivity API for reactive state management.
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

global $post;
if ( ! $post ) {
	return '';
}

// Get attributes with defaults.
/* translators: Default button label for copy to clipboard action */
$label     = $attributes['label'] ?? __( 'Copy for LLM', 'tgp-llms-txt' );
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

// Build markdown URL for fetching.
$permalink = get_permalink( $post );
$md_url    = rtrim( $permalink, '/' ) . '.md';

// Interactivity API context.
$context = [
	'mdUrl'        => $md_url,
	'copyState'    => 'idle',
	'label'        => $label,
	/* translators: Button text shown while copy operation is in progress */
	'labelCopying' => __( 'Copying...', 'tgp-llms-txt' ),
	/* translators: Button text shown after successful copy to clipboard */
	'labelSuccess' => __( 'Copied!', 'tgp-llms-txt' ),
	/* translators: Button text shown when copy operation fails */
	'labelError'   => __( 'Failed', 'tgp-llms-txt' ),
];

// Copy icon SVG.
$copy_icon = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>';

// Allowed SVG tags for wp_kses.
$allowed_svg = [
	'svg'    => [
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
	'rect'   => [
		'x'      => true,
		'y'      => true,
		'width'  => true,
		'height' => true,
		'rx'     => true,
		'ry'     => true,
	],
	'path'   => [
		'd' => true,
	],
	'circle' => [
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
$inner_classes = [ 'wp-block-button__link', 'wp-element-button', 'tgp-copy-btn' ];

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
<div
	class="<?php echo esc_attr( implode( ' ', $outer_classes ) ); ?>"
	data-wp-interactive="tgp/copy-button"
	<?php echo wp_interactivity_data_wp_context( $context ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
>
	<button
		type="button"
		class="<?php echo esc_attr( implode( ' ', $inner_classes ) ); ?>"<?php echo $style_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		title="<?php /* translators: Tooltip text for copy button */ esc_attr_e( 'Copy this content in markdown format for AI assistants', 'tgp-llms-txt' ); ?>"
		data-wp-on--click="actions.copyMarkdown"
		data-wp-bind--disabled="state.isDisabled"
		data-wp-class--is-loading="state.isLoading"
	>
		<?php if ( $show_icon ) : ?>
			<span class="tgp-btn-icon" aria-hidden="true"><?php echo wp_kses( $copy_icon, $allowed_svg ); ?></span>
		<?php endif; ?>
		<span class="tgp-btn-text" role="status" aria-live="polite" data-wp-text="state.buttonText"><?php echo esc_html( $label ); ?></span>
		<span class="screen-reader-text"><?php /* translators: Screen reader text describing the copy button action */ esc_html_e( 'Copy page content as markdown for AI assistants', 'tgp-llms-txt' ); ?></span>
	</button>
</div>

<?php
/**
 * Server-side rendering for Copy Button block.
 *
 * Renders a button that copies the current page content as markdown.
 *
 * @package TGP_LLMs_Txt
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block content.
 * @var WP_Block $block      Block instance.
 */

global $post;
if ( ! $post ) {
	return '';
}

// Get attributes with defaults.
$label     = $attributes['label'] ?? __( 'Copy for LLM', 'tgp-llms-txt' );
$show_icon = $attributes['showIcon'] ?? true;
$width     = $attributes['width'] ?? null;

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

// Get block wrapper attributes (includes styles and classes from Block Supports).
$wrapper_attrs_string = get_block_wrapper_attributes();

// Parse the wrapper attributes string to extract classes and styles.
$wrapper_classes = [];
$wrapper_styles  = '';

// Extract class attribute.
if ( preg_match( '/class="([^"]*)"/', $wrapper_attrs_string, $class_matches ) ) {
	$all_classes = explode( ' ', $class_matches[1] );

	foreach ( $all_classes as $class ) {
		$wrapper_classes[] = $class;
	}
}

// Extract style attribute.
if ( preg_match( '/style="([^"]*)"/', $wrapper_attrs_string, $style_matches ) ) {
	$wrapper_styles = $style_matches[1];
}

// Build outer wrapper classes (block identifier + style variant + width).
$outer_classes = [ 'wp-block-button' ];

// Add is-style-* class to outer wrapper (needed for fill/outline styles).
foreach ( $wrapper_classes as $class ) {
	if ( strpos( $class, 'is-style-' ) === 0 ) {
		$outer_classes[] = $class;
	}
}

// Add width classes.
if ( $width ) {
	$outer_classes[] = 'has-custom-width';
	$outer_classes[] = 'wp-block-button__width-' . $width;
}

// Build inner button classes (button styling classes + block support classes).
$inner_classes = [ 'wp-block-button__link', 'wp-element-button', 'tgp-copy-btn' ];

// Add has-* classes to inner button (color, background, etc.).
foreach ( $wrapper_classes as $class ) {
	if ( strpos( $class, 'has-' ) === 0 ) {
		$inner_classes[] = $class;
	}
}

// Build style attribute for inner button.
$style_attr = $wrapper_styles ? ' style="' . esc_attr( $wrapper_styles ) . '"' : '';
?>
<div class="<?php echo esc_attr( implode( ' ', $outer_classes ) ); ?>">
	<button
		type="button"
		class="<?php echo esc_attr( implode( ' ', $inner_classes ) ); ?>"<?php echo $style_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		data-post-id="<?php echo esc_attr( $post->ID ); ?>"
		title="<?php esc_attr_e( 'Copy this content in markdown format for AI assistants', 'tgp-llms-txt' ); ?>"
	>
		<?php if ( $show_icon ) : ?>
			<span class="tgp-btn-icon"><?php echo wp_kses( $copy_icon, $allowed_svg ); ?></span>
		<?php endif; ?>
		<span class="tgp-btn-text"><?php echo esc_html( $label ); ?></span>
	</button>
</div>

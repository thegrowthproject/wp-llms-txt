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

// Get current post.
global $post;
if ( ! $post ) {
	return '';
}

// Get attributes with defaults.
$label     = $attributes['label'] ?? __( 'View as Markdown', 'tgp-llms-txt' );
$show_icon = $attributes['showIcon'] ?? true;
$width     = $attributes['width'] ?? null;

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
$inner_classes = [ 'wp-block-button__link', 'wp-element-button', 'tgp-view-btn' ];

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
	<a
		href="<?php echo esc_url( $md_url ); ?>"
		target="_blank"
		rel="noopener noreferrer"
		class="<?php echo esc_attr( implode( ' ', $inner_classes ) ); ?>"<?php echo $style_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		title="<?php esc_attr_e( 'View this content as plain markdown', 'tgp-llms-txt' ); ?>"
	>
		<?php if ( $show_icon ) : ?>
			<span class="tgp-btn-icon"><?php echo wp_kses( $view_icon, $allowed_svg ); ?></span>
		<?php endif; ?>
		<span class="tgp-btn-text"><?php echo esc_html( $label ); ?></span>
	</a>
</div>

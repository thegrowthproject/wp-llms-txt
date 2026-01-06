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

use TGP\LLMsTxt\Blocks\ButtonRenderer;
use TGP\LLMsTxt\Blocks\SVGSanitizer;

global $post;
if ( ! $post ) {
	return '';
}

// Get attributes with defaults.
/* translators: Default button label for viewing content as markdown */
$label     = $attributes['label'] ?? __( 'View as Markdown', 'tgp-llms-txt' );
$show_icon = $attributes['showIcon'] ?? true;

// Build markdown URL.
$permalink = get_permalink( $post );
$md_url    = rtrim( $permalink, '/' ) . '.md';

// View/document icon SVG.
$view_icon = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>';

// Get style attributes using shared helper.
$style_attrs = ButtonRenderer::get_style_attributes( $attributes );

// Get block wrapper attributes and detect style variation.
$wrapper_attrs_string = get_block_wrapper_attributes();
$variation_info       = ButtonRenderer::get_style_variation( $wrapper_attrs_string );

// Build classes and styles using shared helper.
$outer_classes = ButtonRenderer::build_outer_classes( $style_attrs, $variation_info['variation'] );
$inner_classes = ButtonRenderer::build_inner_classes( $style_attrs, 'wp-block-tgp-view-button', $variation_info['has_variation'] );
$style_attr    = ButtonRenderer::get_style_attribute( $style_attrs, $variation_info['has_variation'] );
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
			<span class="wp-block-tgp-view-button__icon" aria-hidden="true"><?php echo wp_kses( $view_icon, SVGSanitizer::get_allowed_tags() ); ?></span>
		<?php endif; ?>
		<span class="wp-block-tgp-view-button__text"><?php echo esc_html( $label ); ?></span>
		<span class="screen-reader-text"><?php /* translators: Screen reader text describing the view markdown button action */ esc_html_e( 'View page content as plain markdown in new tab', 'tgp-llms-txt' ); ?></span>
	</a>
</div>

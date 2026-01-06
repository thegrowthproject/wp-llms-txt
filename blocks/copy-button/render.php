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

use TGP\LLMsTxt\Blocks\ButtonRenderer;
use TGP\LLMsTxt\Blocks\SVGSanitizer;

global $post;
if ( ! $post ) {
	return '';
}

// Get attributes with defaults.
/* translators: Default button label for copy to clipboard action */
$label     = $attributes['label'] ?? __( 'Copy for LLM', 'tgp-llms-txt' );
$show_icon = $attributes['showIcon'] ?? true;

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

// Get style attributes using shared helper.
$style_attrs = ButtonRenderer::get_style_attributes( $attributes );

// Get block wrapper attributes and detect style variation.
$wrapper_attrs_string = get_block_wrapper_attributes();
$variation_info       = ButtonRenderer::get_style_variation( $wrapper_attrs_string );

// Build classes and styles using shared helper.
$outer_classes = ButtonRenderer::build_outer_classes( $style_attrs, $variation_info['variation'] );
$inner_classes = ButtonRenderer::build_inner_classes( $style_attrs, 'wp-block-tgp-copy-button', $variation_info['has_variation'] );
$style_attr    = ButtonRenderer::get_style_attribute( $style_attrs, $variation_info['has_variation'] );
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
			<span class="wp-block-tgp-copy-button__icon" aria-hidden="true"><?php echo wp_kses( $copy_icon, SVGSanitizer::get_allowed_tags() ); ?></span>
		<?php endif; ?>
		<span class="wp-block-tgp-copy-button__text" role="status" aria-live="polite" data-wp-text="state.buttonText"><?php echo esc_html( $label ); ?></span>
		<span class="screen-reader-text"><?php /* translators: Screen reader text describing the copy button action */ esc_html_e( 'Copy page content as markdown for AI assistants', 'tgp-llms-txt' ); ?></span>
	</button>
</div>

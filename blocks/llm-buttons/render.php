<?php
/**
 * Server-side rendering for LLM Buttons block.
 *
 * Uses WordPress Block Supports for styling (colors, typography, borders).
 * Buttons use wp-element-button class to inherit theme button styles.
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
$show_icons = $attributes['showIcons'] ?? true;
$layout     = $attributes['layout'] ?? 'row';
$copy_label = $attributes['copyButtonLabel'] ?? 'Copy for LLM';
$view_label = $attributes['viewButtonLabel'] ?? 'View as Markdown';

// Build markdown URL.
$permalink = get_permalink( $post );
$md_url    = rtrim( $permalink, '/' ) . '.md';

// Build wrapper classes.
$wrapper_classes = 'wp-block-tgp-llm-buttons';
if ( 'stack' === $layout ) {
	$wrapper_classes .= ' is-layout-stack';
} else {
	$wrapper_classes .= ' is-layout-row';
}

// Icons SVG.
$copy_icon = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>';
$view_icon = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>';

// Get wrapper attributes (includes Block Supports styles automatically).
$wrapper_attributes = get_block_wrapper_attributes(
	[
		'class' => $wrapper_classes,
	]
);
?>
<div <?php echo wp_kses_post( $wrapper_attributes ); ?>>
	<button
		type="button"
		class="wp-element-button tgp-llm-btn tgp-copy-btn"
		data-post-id="<?php echo esc_attr( $post->ID ); ?>"
		title="<?php esc_attr_e( 'Copy this content in markdown format for AI assistants', 'tgp-llms-txt' ); ?>"
	>
		<?php if ( $show_icons ) : ?>
			<span class="tgp-btn-icon"><?php echo wp_kses_post( $copy_icon ); ?></span>
		<?php endif; ?>
		<span class="tgp-btn-text"><?php echo esc_html( $copy_label ); ?></span>
	</button>
	<a
		href="<?php echo esc_url( $md_url ); ?>"
		target="_blank"
		rel="noopener noreferrer"
		class="wp-element-button tgp-llm-btn tgp-view-btn"
		title="<?php esc_attr_e( 'View this content as plain markdown', 'tgp-llms-txt' ); ?>"
	>
		<?php if ( $show_icons ) : ?>
			<span class="tgp-btn-icon"><?php echo wp_kses_post( $view_icon ); ?></span>
		<?php endif; ?>
		<span class="tgp-btn-text"><?php echo esc_html( $view_label ); ?></span>
	</a>
</div>

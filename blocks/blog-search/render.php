<?php
/**
 * Server-side rendering for Blog Search block.
 *
 * Renders a search input that filters blog posts using the parent
 * Blog Filters block's Interactivity store.
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

use TGP\LLMsTxt\Blocks\SVGSanitizer;

// Ensure we're inside a blog-filters block.
if ( ! array_key_exists( 'tgp/blogFilters', $block->context ) ) {
	return '';
}

/* translators: Default placeholder text for blog search input */
$placeholder       = $attributes['placeholder'] ?? __( 'Search posts...', 'tgp-llms-txt' );
$show_icon         = $attributes['showIcon'] ?? false;
$show_clear_button = $attributes['showClearButton'] ?? true;
$width             = $attributes['width'] ?? null;

// Build block wrapper inline styles (applied to outer block element).
$block_styles = [];
if ( $width ) {
	$block_styles[] = 'width: ' . $width . ';';
}

// Search icon SVG.
$search_icon = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>';

$wrapper_attrs = get_block_wrapper_attributes(
	[
		'class' => 'wp-block-tgp-blog-search',
		'style' => ! empty( $block_styles ) ? implode( '; ', $block_styles ) : null,
	]
);
?>
<div <?php echo $wrapper_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="wp-block-tgp-blog-search__wrapper">
		<?php if ( $show_icon ) : ?>
		<span class="wp-block-tgp-blog-search__icon" aria-hidden="true">
			<?php echo wp_kses( $search_icon, SVGSanitizer::get_allowed_tags() ); ?>
		</span>
		<?php endif; ?>

		<input
			type="search"
			class="wp-block-tgp-blog-search__input"
			placeholder="<?php echo esc_attr( $placeholder ); ?>"
			data-wp-bind--value="context.searchQuery"
			data-wp-on--input="actions.updateSearch"
			data-wp-on--blur="actions.submitSearch"
			data-wp-on--keydown="actions.handleSearchKeydown"
			aria-label="<?php /* translators: Accessible label for search input */ esc_attr_e( 'Search blog posts', 'tgp-llms-txt' ); ?>"
		/>

		<?php if ( $show_clear_button ) : ?>
		<button
			type="button"
			class="wp-block-tgp-blog-search__clear"
			data-wp-on--click="actions.clearSearch"
			data-wp-bind--hidden="!context.searchQuery"
			aria-label="<?php /* translators: Accessible label for clear search button */ esc_attr_e( 'Clear search', 'tgp-llms-txt' ); ?>"
		>&times;</button>
		<?php endif; ?>
	</div>
</div>

<?php
/**
 * Server-side rendering for Blog Category Filter block.
 *
 * Renders category filter pills that use the parent Blog Filters block's
 * Interactivity store. Uses single-selection mode - one category at a time.
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

use TGP\LLMsTxt\Blocks\PillRenderer;

// Ensure we're inside a blog-filters block.
if ( ! array_key_exists( 'tgp/blogFilters', $block->context ) ) {
	return '';
}

// Block attributes.
$show_count = $attributes['showCount'] ?? true;
$layout     = $attributes['layout'] ?? 'wrap';

// Get categories with posts.
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

// Extract styles using shared helper.
$style_attrs = PillRenderer::get_style_attributes( $attributes );
$colors      = PillRenderer::resolve_colors( $style_attrs );

// Get wrapper attributes and detect style variation.
$wrapper_attrs_string = get_block_wrapper_attributes();
$style_variation      = PillRenderer::get_style_variation( $wrapper_attrs_string );
$style_classes        = PillRenderer::get_style_classes( $style_variation );

// Build active-state styles (colors, border, shadow).
$active_styles = PillRenderer::build_active_state_styles( $style_attrs, $colors );

// Inject marker classes for conditional CSS.
$wrapper_attrs_string = PillRenderer::inject_marker_classes(
	$wrapper_attrs_string,
	$active_styles['classes']
);

// Get wrapper and button style attributes.
$wrapper_style_attr = PillRenderer::get_wrapper_style_attribute( $active_styles );
$button_style_attr  = PillRenderer::get_button_style_attribute( $style_attrs );

// Build class strings.
$pill_wrapper_classes = PillRenderer::build_pill_wrapper_classes();
$button_classes       = PillRenderer::build_button_classes( 'wp-block-tgp-blog-category-filter__pill' );

$pill_wrapper_class_string = implode( ' ', $pill_wrapper_classes );
$button_class_string       = implode( ' ', $button_classes );

// List wrapper class.
$list_class = 'wp-block-tgp-blog-category-filter__list';
if ( 'scroll' === $layout ) {
	$list_class .= ' wp-block-tgp-blog-category-filter__list--scroll';
}
?>
<div <?php echo $wrapper_attrs_string; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?><?php echo $wrapper_style_attr; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="<?php echo esc_attr( $list_class ); ?>" role="group" aria-label="<?php /* translators: Accessible label for category filter group */ esc_attr_e( 'Filter by category', 'tgp-llms-txt' ); ?>">
		<?php foreach ( $categories as $category ) : ?>
		<div
			class="<?php echo esc_attr( $pill_wrapper_class_string ); ?>"
			<?php echo wp_interactivity_data_wp_context( [ 'slug' => $category->slug ] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			data-wp-class--wp-block-tgp-blog-category-filter__pill--active="state.isCategoryActive"
			data-wp-class--<?php echo esc_attr( $style_classes['active'] ); ?>="state.isCategoryActive"
			data-wp-class--<?php echo esc_attr( $style_classes['inactive'] ); ?>="!state.isCategoryActive"
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

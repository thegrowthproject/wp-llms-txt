<?php
/**
 * Server-side rendering for Blog Filters wrapper block.
 *
 * Queries all posts, builds categories data, and sets up Interactivity context.
 *
 * @package TGP_LLMs_Txt
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block content (InnerBlocks).
 * @var WP_Block $block      Block instance.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$show_result_count = $attributes['showResultCount'] ?? true;

// Query all published posts.
$posts_query = new WP_Query(
	[
		'post_type'      => 'post',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => 'date',
		'order'          => 'DESC',
	]
);

// Build posts data for filtering.
$posts_data = [];
foreach ( $posts_query->posts as $post_item ) {
	// Get post categories.
	$post_categories = wp_get_post_categories( $post_item->ID, [ 'fields' => 'slugs' ] );

	$posts_data[] = [
		'id'         => $post_item->ID,
		'title'      => get_the_title( $post_item ),
		'excerpt'    => wp_strip_all_tags( get_the_excerpt( $post_item ) ),
		'categories' => $post_categories,
	];
}

// Get all categories that have posts.
$categories = get_categories(
	[
		'hide_empty' => true,
		'orderby'    => 'name',
		'order'      => 'ASC',
	]
);

$categories_data = [];
foreach ( $categories as $category ) {
	$categories_data[] = [
		'id'    => $category->term_id,
		'name'  => $category->name,
		'slug'  => $category->slug,
		'count' => $category->count,
	];
}

// Parse URL parameters for initial state.
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$initial_search = isset( $_GET['search'] ) ? sanitize_text_field( wp_unslash( $_GET['search'] ) ) : '';
// phpcs:ignore WordPress.Security.NonceVerification.Recommended
$initial_categories = isset( $_GET['categories'] ) ? sanitize_text_field( wp_unslash( $_GET['categories'] ) ) : '';
$initial_categories = $initial_categories ? explode( ',', $initial_categories ) : [];

// Calculate initial visible posts based on URL params.
$initial_visible_ids = [];
foreach ( $posts_data as $post_item ) {
	$matches_search = true;
	$matches_cats   = true;

	// Check search.
	if ( $initial_search ) {
		$search_lower  = strtolower( $initial_search );
		$title_lower   = strtolower( $post_item['title'] );
		$excerpt_lower = strtolower( $post_item['excerpt'] );
		$matches_search = ( strpos( $title_lower, $search_lower ) !== false ) ||
							( strpos( $excerpt_lower, $search_lower ) !== false );
	}

	// Check categories (AND logic).
	if ( ! empty( $initial_categories ) ) {
		foreach ( $initial_categories as $cat_slug ) {
			if ( ! in_array( $cat_slug, $post_item['categories'], true ) ) {
				$matches_cats = false;
				break;
			}
		}
	}

	if ( $matches_search && $matches_cats ) {
		$initial_visible_ids[] = $post_item['id'];
	}
}

// Global state for large, shared data (reduces HTML payload).
wp_interactivity_state(
	'tgp/blog-filters',
	[
		'posts'      => $posts_data,
		'categories' => $categories_data,
		'totalPosts' => count( $posts_data ),
	]
);

// Context only for instance-specific, reactive values.
$context = [
	'searchQuery'        => $initial_search,
	'selectedCategories' => $initial_categories,
	'visiblePostIds'     => $initial_visible_ids,
	'showResultCount'    => $show_result_count,
];

// Build wrapper attributes.
$wrapper_attrs = get_block_wrapper_attributes(
	[
		'class'                  => 'wp-block-tgp-blog-filters',
		'data-wp-interactive'    => 'tgp/blog-filters',
		'data-wp-init'           => 'callbacks.init',
		'data-wp-watch'          => 'callbacks.syncUrl',
	]
);
?>
<div <?php echo $wrapper_attrs; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<?php echo wp_interactivity_data_wp_context( $context ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
>
	<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

	<?php if ( $show_result_count ) : ?>
	<div
		class="wp-block-tgp-blog-filters__result-count"
		data-wp-text="state.resultCountText"
		aria-live="polite"
	></div>
	<?php endif; ?>

	<div
		class="wp-block-tgp-blog-filters__no-results"
		data-wp-bind--hidden="state.hasResults"
	>
		<p><?php esc_html_e( 'No posts found matching your criteria.', 'tgp-llms-txt' ); ?></p>
		<button
			type="button"
			class="wp-block-tgp-blog-filters__clear-btn"
			data-wp-on--click="actions.clearFilters"
		>
			<?php esc_html_e( 'Clear filters', 'tgp-llms-txt' ); ?>
		</button>
	</div>
</div>

<?php
/**
 * LLMs.txt Generator
 *
 * Generates the /llms.txt index file listing all available content.
 * Follows the llmstxt.org specification for AI-readable site indexes.
 *
 * Filters available for customization:
 * - tgp_llms_txt_description: Custom site description paragraph
 * - tgp_llms_txt_contact_url: Contact page URL (default: /contact/)
 * - tgp_llms_txt_pages: Array of page slugs and descriptions to include
 * - tgp_llms_txt_posts_limit: Maximum number of posts to include (default: 50)
 * - tgp_llms_txt_exclude_categories: Array of category slugs to exclude
 *
 * @package TGP_LLMs_Txt
 */

declare(strict_types=1);

namespace TGP\LLMsTxt;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * LLMs.txt Generator class.
 *
 * Generates the site index for AI/LLM consumption.
 */
class Generator {

	/**
	 * Cache key for llms.txt content.
	 *
	 * @var string
	 */
	private const CACHE_KEY = 'tgp_llms_txt_content';

	/**
	 * Cache expiration time in seconds (1 hour).
	 *
	 * @var int
	 */
	private const CACHE_EXPIRATION = 3600;

	/**
	 * Initialize the generator.
	 *
	 * Creates a new instance and registers all WordPress hooks.
	 * Call this method once during plugin initialization.
	 */
	public static function init(): void {
		$instance = new self();

		// Invalidate cache on post save/delete.
		add_action( 'save_post', [ $instance, 'maybe_invalidate_cache' ], 10, 2 );
		add_action( 'delete_post', [ $instance, 'invalidate_cache' ] );
		add_action( 'wp_trash_post', [ $instance, 'invalidate_cache' ] );
	}

	/**
	 * Constructor.
	 *
	 * Public to allow direct instantiation for content generation.
	 * The init() method is used separately to register hooks.
	 */
	public function __construct() {
		// No side effects - hooks are registered in init().
	}

	/**
	 * Generate llms.txt content.
	 *
	 * Builds the complete llms.txt file content including:
	 * - Header with site info and standard reference
	 * - Site tagline (from WordPress settings)
	 * - Optional custom description (via filter)
	 * - Key pages section
	 * - Blog posts grouped by category
	 * - Contact section
	 *
	 * Results are cached for performance. Use invalidate_cache() to clear.
	 *
	 * @return string The llms.txt content.
	 */
	public function generate(): string {
		// Check for cached content.
		$cached = get_transient( self::CACHE_KEY );
		if ( false !== $cached ) {
			return $cached;
		}

		$site_name        = get_bloginfo( 'name' );
		$site_description = get_bloginfo( 'description' );
		$site_url         = home_url();
		$last_updated     = gmdate( 'Y-m-d' );

		$output  = "# {$site_name}\n\n";
		$output .= "> {$site_description}\n\n";
		$output .= "This file helps AI systems understand and access our content.\n";
		$output .= "Add .md to any URL to get the markdown version.\n\n";
		$output .= "- Standard: https://llmstxt.org/\n";
		$output .= "- Last Updated: {$last_updated}\n";
		$output .= "- Website: {$site_url}\n\n";

		/**
		 * Filters the custom description paragraph in llms.txt.
		 *
		 * Use this to add a custom description paragraph after the site tagline.
		 * Return an empty string to omit the custom description.
		 *
		 * @since 1.2.0
		 *
		 * @param string $description Custom description text. Default empty.
		 */
		$description = apply_filters( 'tgp_llms_txt_description', '' );
		if ( ! empty( $description ) ) {
			$output .= $description . "\n\n";
		}

		// Key pages.
		$output .= "## Pages\n\n";
		$output .= $this->get_pages_section();

		// Blog posts by category.
		$output .= "## Blog Posts\n\n";
		$output .= $this->get_posts_section();

		// Contact section.
		$output .= "## Contact\n\n";

		/**
		 * Filters the contact page URL path in llms.txt.
		 *
		 * @since 1.2.0
		 *
		 * @param string $contact_path The contact page path. Default '/contact/'.
		 */
		$contact_path = apply_filters( 'tgp_llms_txt_contact_url', '/contact/' );
		$output      .= "- Contact Page: {$site_url}{$contact_path}\n";

		// Cache the generated content.
		set_transient( self::CACHE_KEY, $output, self::CACHE_EXPIRATION );

		return $output;
	}

	/**
	 * Get pages section for llms.txt.
	 *
	 * Generates markdown list of key pages with their descriptions.
	 * Pages are defined as slug => description pairs and filtered
	 * via 'tgp_llms_txt_pages'.
	 *
	 * @return string Markdown formatted pages section.
	 */
	private function get_pages_section(): string {
		$output = '';

		/**
		 * Filters the pages to include in llms.txt.
		 *
		 * @since 1.2.0
		 *
		 * @param array $pages Array of page slug => description pairs.
		 */
		$key_pages = apply_filters(
			'tgp_llms_txt_pages',
			[
				'about'    => 'About us and how we work',
				'services' => 'What we deliver',
				'contact'  => 'Get in touch',
			]
		);

		foreach ( $key_pages as $slug => $description ) {
			$page = get_page_by_path( $slug );
			if ( $page ) {
				$url    = get_permalink( $page );
				$md_url = $this->get_md_url( $url );
				$title  = get_the_title( $page );
				$output .= "- [{$title}]({$md_url}): {$description}\n";
			}
		}

		$output .= "\n";
		return $output;
	}

	/**
	 * Get blog posts section for llms.txt.
	 *
	 * Generates markdown list of published posts grouped by category.
	 * If no categories exist, posts are listed without grouping.
	 *
	 * Optimized to use a single query for all posts, then group in PHP
	 * to avoid N+1 query problem (1 query instead of 1 + N categories).
	 *
	 * @return string Markdown formatted posts section.
	 */
	private function get_posts_section(): string {
		$output = '';

		/**
		 * Filters the maximum number of posts to include in llms.txt.
		 *
		 * @since 1.3.4
		 *
		 * @param int $limit Maximum number of posts. Default 50. Use -1 for unlimited.
		 */
		$posts_limit = apply_filters( 'tgp_llms_txt_posts_limit', 50 );

		/**
		 * Filters the categories to exclude from llms.txt.
		 *
		 * @since 1.3.4
		 *
		 * @param array $exclude_categories Array of category slugs to exclude.
		 */
		$exclude_categories = apply_filters( 'tgp_llms_txt_exclude_categories', [] );

		// Build query args.
		$query_args = [
			'posts_per_page' => $posts_limit,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'post_status'    => 'publish',
		];

		// Exclude categories if specified.
		if ( ! empty( $exclude_categories ) ) {
			$exclude_ids = [];
			foreach ( $exclude_categories as $slug ) {
				$term = get_term_by( 'slug', $slug, 'category' );
				if ( $term ) {
					$exclude_ids[] = $term->term_id;
				}
			}
			if ( ! empty( $exclude_ids ) ) {
				$query_args['category__not_in'] = $exclude_ids;
			}
		}

		$all_posts = get_posts( $query_args );

		if ( empty( $all_posts ) ) {
			return $output;
		}

		// Group posts by their primary category.
		$posts_by_category = [];
		$uncategorized     = [];

		foreach ( $all_posts as $post ) {
			$categories = get_the_category( $post->ID );
			if ( ! empty( $categories ) ) {
				// Use first category as primary (WordPress default behavior).
				$category_name = $categories[0]->name;
				$category_slug = $categories[0]->slug;

				if ( ! isset( $posts_by_category[ $category_slug ] ) ) {
					$posts_by_category[ $category_slug ] = [
						'name'  => $category_name,
						'posts' => [],
					];
				}
				$posts_by_category[ $category_slug ]['posts'][] = $post;
			} else {
				$uncategorized[] = $post;
			}
		}

		// Sort categories alphabetically by name.
		uasort(
			$posts_by_category,
			function ( $a, $b ) {
				return strcasecmp( $a['name'], $b['name'] );
			}
		);

		// Output posts grouped by category.
		if ( empty( $posts_by_category ) ) {
			// No categories, just list all posts.
			foreach ( $all_posts as $post ) {
				$output .= $this->format_post_line( $post );
			}
			$output .= "\n";
		} else {
			foreach ( $posts_by_category as $category_data ) {
				$output .= "### {$category_data['name']}\n\n";

				foreach ( $category_data['posts'] as $post ) {
					$output .= $this->format_post_line( $post );
				}

				$output .= "\n";
			}

			// Add uncategorized posts at the end if any.
			if ( ! empty( $uncategorized ) ) {
				$output .= "### Uncategorized\n\n";
				foreach ( $uncategorized as $post ) {
					$output .= $this->format_post_line( $post );
				}
				$output .= "\n";
			}
		}

		return $output;
	}

	/**
	 * Format a single post line for llms.txt output.
	 *
	 * @param \WP_Post $post The post object.
	 * @return string Formatted markdown line.
	 */
	private function format_post_line( \WP_Post $post ): string {
		$url     = get_permalink( $post );
		$md_url  = $this->get_md_url( $url );
		$title   = get_the_title( $post );
		$date    = get_the_date( 'Y-m-d', $post );
		$excerpt = $this->get_short_excerpt( $post );

		return "- [{$title}]({$md_url}) ({$date}): {$excerpt}\n";
	}

	/**
	 * Get short excerpt for a post.
	 *
	 * Returns a truncated excerpt suitable for llms.txt listings.
	 * Uses the post's excerpt if available, otherwise generates one
	 * from the post content (stripped of HTML and block comments).
	 *
	 * @param \WP_Post $post The post object.
	 * @return string Truncated excerpt (max 15 words).
	 */
	private function get_short_excerpt( \WP_Post $post ): string {
		$excerpt = get_the_excerpt( $post );
		if ( empty( $excerpt ) ) {
			$content = wp_strip_all_tags( $post->post_content );
			$content = preg_replace( '/<!--.*?-->/s', '', $content );
			$excerpt = wp_trim_words( $content, 15, '...' );
		} else {
			$excerpt = wp_trim_words( $excerpt, 15, '...' );
		}
		return $excerpt;
	}

	/**
	 * Convert a permalink to its markdown endpoint URL.
	 *
	 * Transforms a standard WordPress permalink into the corresponding
	 * .md endpoint URL for AI/LLM consumption.
	 *
	 * @param string $url The original permalink URL.
	 * @return string The markdown endpoint URL (with .md suffix).
	 */
	private function get_md_url( string $url ): string {
		// Remove trailing slash and add .md
		$url = rtrim( $url, '/' );
		return $url . '.md';
	}

	/**
	 * Conditionally invalidate cache on post save.
	 *
	 * Hooked to 'save_post' action. Invalidates the llms.txt cache when
	 * a published post or page is saved. Filters out autosaves, revisions,
	 * and non-published content.
	 *
	 * @param int      $post_id The post ID.
	 * @param \WP_Post $post    The post object.
	 */
	public function maybe_invalidate_cache( int $post_id, \WP_Post $post ): void {
		// Skip autosaves and revisions.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		// Only invalidate for published posts and pages.
		if ( 'publish' !== $post->post_status ) {
			return;
		}

		if ( ! in_array( $post->post_type, [ 'post', 'page' ], true ) ) {
			return;
		}

		$this->invalidate_cache();
	}

	/**
	 * Invalidate the llms.txt cache.
	 *
	 * Deletes the cached llms.txt content, forcing regeneration on next request.
	 * Can be called directly or hooked to post deletion/trash actions.
	 *
	 * @param int|null $post_id Optional post ID (unused, for hook compatibility).
	 */
	public function invalidate_cache( ?int $post_id = null ): void { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found
		delete_transient( self::CACHE_KEY );
	}
}

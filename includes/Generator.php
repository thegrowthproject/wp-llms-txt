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
	 * Initialize the generator.
	 *
	 * Creates a new instance and registers all WordPress hooks.
	 * Call this method once during plugin initialization.
	 */
	public static function init(): void {
		$instance = new self();

		// Regenerate on post save.
		add_action( 'save_post', [ $instance, 'maybe_regenerate' ], 10, 2 );
	}

	/**
	 * Constructor.
	 *
	 * Private to enforce use of init() method.
	 */
	private function __construct() {
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
	 * @return string The llms.txt content.
	 */
	public function generate(): string {
		$site_name        = get_bloginfo( 'name' );
		$site_description = get_bloginfo( 'description' );
		$site_url         = home_url();

		$output  = "# {$site_name} - llms.txt\n";
		$output .= "# {$site_url}/llms.txt\n";
		$output .= "#\n";
		$output .= "# This file helps AI systems understand and access our content.\n";
		$output .= "# Add .md to any URL to get the markdown version.\n";
		$output .= "#\n";
		$output .= "# Standard: https://llmstxt.org/\n\n";

		$output .= "> {$site_description}\n\n";

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
		$output .= "- Website: {$site_url}\n";

		/**
		 * Filters the contact page URL path in llms.txt.
		 *
		 * @since 1.2.0
		 *
		 * @param string $contact_path The contact page path. Default '/contact/'.
		 */
		$contact_path = apply_filters( 'tgp_llms_txt_contact_url', '/contact/' );
		$output      .= "- Contact: {$site_url}{$contact_path}\n";

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
	 * @return string Markdown formatted posts section.
	 */
	private function get_posts_section(): string {
		$output = '';

		// Get all categories with posts.
		$categories = get_categories(
			[
				'hide_empty' => true,
				'orderby'    => 'name',
				'order'      => 'ASC',
			]
		);

		if ( empty( $categories ) ) {
			// No categories, just list all posts.
			$output .= $this->get_posts_list();
		} else {
			// Group by category.
			foreach ( $categories as $category ) {
				$output .= "### {$category->name}\n\n";

				$posts = get_posts(
					[
						'category'       => $category->term_id,
						'posts_per_page' => -1,
						'orderby'        => 'date',
						'order'          => 'DESC',
						'post_status'    => 'publish',
					]
				);

				foreach ( $posts as $post ) {
					$url     = get_permalink( $post );
					$md_url  = $this->get_md_url( $url );
					$title   = get_the_title( $post );
					$excerpt = $this->get_short_excerpt( $post );
					$output .= "- [{$title}]({$md_url}): {$excerpt}\n";
				}

				$output .= "\n";
			}
		}

		return $output;
	}

	/**
	 * Get all posts list without category grouping.
	 *
	 * Used as fallback when no categories exist. Lists all published
	 * posts in descending date order with titles, markdown URLs, and excerpts.
	 *
	 * @return string Markdown formatted posts list.
	 */
	private function get_posts_list(): string {
		$output = '';

		$posts = get_posts(
			[
				'posts_per_page' => -1,
				'orderby'        => 'date',
				'order'          => 'DESC',
				'post_status'    => 'publish',
			]
		);

		foreach ( $posts as $post ) {
			$url = get_permalink( $post );
			$md_url = $this->get_md_url( $url );
			$title = get_the_title( $post );
			$excerpt = $this->get_short_excerpt( $post );
			$output .= "- [{$title}]({$md_url}): {$excerpt}\n";
		}

		return $output . "\n";
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
	 * Handle post save for potential llms.txt regeneration.
	 *
	 * Hooked to 'save_post' action. Currently a placeholder for future
	 * caching implementation. Filters out autosaves, revisions, and
	 * non-published posts/pages.
	 *
	 * @param int      $post_id The post ID.
	 * @param \WP_Post $post    The post object.
	 */
	public function maybe_regenerate( int $post_id, \WP_Post $post ): void {
		// Skip autosaves and revisions
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		// Only for published posts and pages.
		if ( 'publish' !== $post->post_status ) {
			return;
		}

		if ( ! in_array( $post->post_type, [ 'post', 'page' ], true ) ) {
			return;
		}

		// Could cache the generated content here if needed
		// For now, llms.txt is generated on-the-fly
	}
}

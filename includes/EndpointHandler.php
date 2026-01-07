<?php
/**
 * Endpoint Handler
 *
 * Registers and handles custom URL endpoints for markdown content
 *
 * @package TGP_LLMs_Txt
 */

declare(strict_types=1);

namespace TGP\LLMsTxt;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Endpoint Handler class.
 */
class EndpointHandler {

	/**
	 * Initialize the endpoint handler.
	 *
	 * Creates a new instance and registers all WordPress hooks.
	 * Call this method once during plugin initialization.
	 */
	public static function init(): void {
		$instance = new self();

		// Check for our endpoints at init (priority 0 = earliest).
		add_action( 'init', [ $instance, 'check_for_custom_endpoints' ], 0 );
		add_action( 'init', [ $instance, 'add_rewrite_rules' ], 1 );
		add_filter( 'query_vars', [ $instance, 'add_query_vars' ] );
		add_action( 'template_redirect', [ $instance, 'handle_request' ], 1 );
	}

	/**
	 * Constructor.
	 *
	 * Private to enforce use of init() method.
	 */
	private function __construct() {
		$this->rate_limiter = new RateLimiter();
	}

	/**
	 * Check for custom endpoints early in init
	 */
	public function check_for_custom_endpoints(): void {
		// Get the request path - sanitize server input.
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		$path = wp_parse_url( $request_uri, PHP_URL_PATH );
		$path = rtrim( $path, '/' );

		// Check for llms.txt
		if ( preg_match( '/\/llms\.txt$/i', $path ) ) {
			$this->serve_llms_txt();
			exit;
		}

		// Check for .md format
		if ( preg_match( '/\.md$/i', $path ) ) {
			// Extract the slug from the path
			$slug = preg_replace( '/\.md$/i', '', basename( $path ) );
			if ( $slug ) {
				$this->md_slug = $slug;
				$this->serve_markdown();
				exit;
			}
		}
	}

	/**
	 * Add rewrite rules for .md endpoints
	 */
	public function add_rewrite_rules(): void {
		// Match /blog/post-slug.md (for blogs with /blog/ prefix)
		add_rewrite_rule(
			'blog/([^/]+)\.md$',
			'index.php?name=$matches[1]&tgp_format=md',
			'top'
		);

		// Match /post-slug.md (for posts without prefix)
		add_rewrite_rule(
			'([^/]+)\.md$',
			'index.php?name=$matches[1]&tgp_format=md',
			'top'
		);

		// Match /category/post-slug.md
		add_rewrite_rule(
			'([^/]+)/([^/]+)\.md$',
			'index.php?category_name=$matches[1]&name=$matches[2]&tgp_format=md',
			'top'
		);

		// Match /llms.txt
		add_rewrite_rule(
			'llms\.txt$',
			'index.php?tgp_llms_txt=1',
			'top'
		);

		// Match page slugs with .md
		add_rewrite_rule(
			'(.+?)\.md$',
			'index.php?pagename=$matches[1]&tgp_format=md',
			'top'
		);
	}

	/**
	 * Add custom query vars
	 */
	public function add_query_vars( array $vars ): array {
		$vars[] = 'tgp_format';
		$vars[] = 'tgp_llms_txt';
		return $vars;
	}

	/**
	 * Store md slug for serving.
	 *
	 * @var string|null
	 */
	private ?string $md_slug = null;

	/**
	 * Rate limiter instance.
	 *
	 * @var RateLimiter
	 */
	private RateLimiter $rate_limiter;

	/**
	 * Handle markdown requests
	 */
	public function handle_request(): void {
		global $wp;

		// Handle llms.txt
		if ( get_query_var( 'tgp_llms_txt' ) ) {
			$this->serve_llms_txt();
			exit;
		}

		// Handle .md format - check both query var and our custom var.
		if ( 'md' === get_query_var( 'tgp_format' ) ||
			( isset( $wp->query_vars['tgp_format'] ) && 'md' === $wp->query_vars['tgp_format'] ) ) {
			$this->serve_markdown();
			exit;
		}
	}

	/**
	 * Serve markdown version of post/page.
	 */
	private function serve_markdown(): void {
		global $post;

		// Check rate limit before processing.
		$rate_info = $this->rate_limiter->check();

		// Get slug from our stored value.
		$post_name = $this->md_slug;

		if ( empty( $post_name ) ) {
			$this->send_404();
			return;
		}

		$found_post = null;

		// Try as post first
		$found_post = get_page_by_path( $post_name, OBJECT, 'post' );

		// Then try as page
		if ( ! $found_post ) {
			$found_post = get_page_by_path( $post_name, OBJECT, 'page' );
		}

		if ( ! empty( $found_post ) ) {
			// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Required for setup_postdata().
			$post = $found_post;
			setup_postdata( $post );
		} else {
			$this->send_404();
			return;
		}

		// Set headers.
		header( 'Content-Type: text/markdown; charset=utf-8' );
		header( 'X-Content-Type-Options: nosniff' );
		header( 'Cache-Control: public, max-age=3600' ); // Cache for 1 hour.
		$this->rate_limiter->send_headers( $rate_info );

		// Generate markdown
		$converter   = new MarkdownConverter();
		$frontmatter = new Frontmatter( $post );

		// Output text/markdown - escaping not needed for plain text output.
		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $frontmatter->generate();
		echo "\n\n";
		echo '# ' . $this->escape_markdown( get_the_title( $post ) ) . "\n\n";
		echo $converter->convert( $post->post_content );
		// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped

		exit;
	}

	/**
	 * Serve llms.txt file.
	 */
	private function serve_llms_txt(): void {
		// Check rate limit before processing.
		$rate_info = $this->rate_limiter->check();

		$generator = new Generator();

		header( 'Content-Type: text/plain; charset=utf-8' );
		header( 'X-Content-Type-Options: nosniff' );
		header( 'Cache-Control: public, max-age=3600' );
		$this->rate_limiter->send_headers( $rate_info );

		// Plain text output - escaping not needed.
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $generator->generate();
		exit;
	}

	/**
	 * Send 404 response.
	 */
	private function send_404(): void {
		global $wp_query;

		Logger::warning(
			'Markdown endpoint not found',
			[
				'slug'       => $this->md_slug,
				'request_ip' => $this->rate_limiter->get_client_ip(),
			]
		);

		$wp_query->set_404();
		status_header( 404 );
		nocache_headers();

		header( 'Content-Type: text/plain; charset=utf-8' );
		echo "# 404 Not Found\n\nThe requested markdown file could not be found.";
		exit;
	}

	/**
	 * Escape a string for safe use in markdown output.
	 *
	 * Escapes special markdown characters that could cause formatting issues
	 * when used in headings or other contexts.
	 *
	 * @param string $text The text to escape.
	 * @return string The escaped text.
	 */
	private function escape_markdown( string $text ): string {
		// Remove newlines/carriage returns (would break headings).
		$text = str_replace( [ "\r\n", "\r", "\n" ], ' ', $text );

		// Escape backslashes first.
		$text = str_replace( '\\', '\\\\', $text );

		// Escape markdown special characters.
		$special_chars = [ '*', '_', '`', '[', ']', '<', '>', '#' ];
		foreach ( $special_chars as $char ) {
			$text = str_replace( $char, '\\' . $char, $text );
		}

		return $text;
	}
}

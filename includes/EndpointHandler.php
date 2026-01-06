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
		// No side effects - hooks are registered in init().
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
	 * Default rate limit (requests per minute).
	 *
	 * @var int
	 */
	private int $default_rate_limit = 100;

	/**
	 * Rate limit window in seconds.
	 *
	 * @var int
	 */
	private int $rate_limit_window = 60;

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
		$rate_info = $this->check_rate_limit();

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
		$this->send_rate_limit_headers( $rate_info );

		// Generate markdown
		$converter   = new MarkdownConverter();
		$frontmatter = new Frontmatter( $post );

		// Output text/markdown - escaping not needed for plain text output.
		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $frontmatter->generate();
		echo "\n\n";
		echo '# ' . get_the_title( $post ) . "\n\n";
		echo $converter->convert( $post->post_content );
		// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped

		exit;
	}

	/**
	 * Serve llms.txt file.
	 */
	private function serve_llms_txt(): void {
		// Check rate limit before processing.
		$rate_info = $this->check_rate_limit();

		$generator = new Generator();

		header( 'Content-Type: text/plain; charset=utf-8' );
		header( 'X-Content-Type-Options: nosniff' );
		header( 'Cache-Control: public, max-age=3600' );
		$this->send_rate_limit_headers( $rate_info );

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
				'request_ip' => $this->get_client_ip(),
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
	 * Check and enforce rate limiting.
	 *
	 * Uses transient-based IP tracking to limit requests per minute.
	 * Sends 429 response if rate limit exceeded.
	 *
	 * @return array{limit: int, remaining: int, reset: int} Rate limit info for headers.
	 */
	private function check_rate_limit(): array {
		$ip            = $this->get_client_ip();
		$transient_key = 'tgp_llms_rate_' . md5( $ip );

		// Get current request count and timestamp.
		$rate_data = get_transient( $transient_key );

		if ( false === $rate_data ) {
			$rate_data = [
				'count' => 0,
				'start' => time(),
			];
		}

		/**
		 * Filter the rate limit for LLMs.txt endpoints.
		 *
		 * @param int    $limit The maximum requests per minute. Default 100.
		 * @param string $ip    The client IP address.
		 */
		$limit = (int) apply_filters( 'tgp_llms_txt_rate_limit', $this->default_rate_limit, $ip );

		// Calculate time until reset.
		$elapsed    = time() - $rate_data['start'];
		$reset_time = $rate_data['start'] + $this->rate_limit_window;

		// If window has passed, reset the counter.
		if ( $elapsed >= $this->rate_limit_window ) {
			$rate_data = [
				'count' => 0,
				'start' => time(),
			];
			$reset_time = time() + $this->rate_limit_window;
		}

		// Increment request count.
		++$rate_data['count'];

		// Calculate remaining requests.
		$remaining = max( 0, $limit - $rate_data['count'] );

		// Store updated count.
		set_transient( $transient_key, $rate_data, $this->rate_limit_window );

		// Check if over limit.
		if ( $rate_data['count'] > $limit ) {
			$this->send_rate_limit_exceeded( $limit, $reset_time );
		}

		return [
			'limit'     => $limit,
			'remaining' => $remaining,
			'reset'     => $reset_time,
		];
	}

	/**
	 * Get client IP address.
	 *
	 * Checks for proxy headers in a safe order.
	 *
	 * @return string The client IP address.
	 */
	private function get_client_ip(): string {
		// Check for forwarded IP (reverse proxy/load balancer).
		// Only trust these headers if behind a trusted proxy.
		$forwarded_headers = [
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_REAL_IP',
			'HTTP_CLIENT_IP',
		];

		foreach ( $forwarded_headers as $header ) {
			if ( ! empty( $_SERVER[ $header ] ) ) {
				// X-Forwarded-For can contain multiple IPs; get the first one.
				$ip = sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) );
				$ip = explode( ',', $ip )[0];
				$ip = trim( $ip );

				if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
					return $ip;
				}
			}
		}

		// Fall back to REMOTE_ADDR.
		return isset( $_SERVER['REMOTE_ADDR'] )
			? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) )
			: '0.0.0.0';
	}

	/**
	 * Send rate limit headers.
	 *
	 * @param array{limit: int, remaining: int, reset: int} $rate_info Rate limit information.
	 */
	private function send_rate_limit_headers( array $rate_info ): void {
		header( 'X-RateLimit-Limit: ' . $rate_info['limit'] );
		header( 'X-RateLimit-Remaining: ' . $rate_info['remaining'] );
		header( 'X-RateLimit-Reset: ' . $rate_info['reset'] );
	}

	/**
	 * Send 429 Too Many Requests response.
	 *
	 * @param int $limit      The rate limit.
	 * @param int $reset_time Unix timestamp when the rate limit resets.
	 */
	private function send_rate_limit_exceeded( int $limit, int $reset_time ): void {
		$retry_after = max( 1, $reset_time - time() );
		$client_ip   = $this->get_client_ip();

		Logger::warning(
			'Rate limit exceeded',
			[
				'ip'          => $client_ip,
				'limit'       => $limit,
				'retry_after' => $retry_after,
			]
		);

		status_header( 429 );
		header( 'Content-Type: text/plain; charset=utf-8' );
		header( 'Retry-After: ' . $retry_after );
		header( 'X-RateLimit-Limit: ' . $limit );
		header( 'X-RateLimit-Remaining: 0' );
		header( 'X-RateLimit-Reset: ' . $reset_time );

		// Plain text output - escaping not needed for static strings and integers.
		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
		echo "# 429 Too Many Requests\n\n";
		echo 'Rate limit exceeded. Please retry after ' . (int) $retry_after . ' seconds.';
		// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}
}

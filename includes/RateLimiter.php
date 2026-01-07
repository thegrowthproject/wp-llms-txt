<?php
/**
 * Rate Limiter
 *
 * Handles IP-based rate limiting for plugin endpoints.
 * Supports Redis/Memcached via WordPress object cache.
 *
 * @package TGP_LLMs_Txt
 */

declare(strict_types=1);

namespace TGP\LLMsTxt;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Rate Limiter class.
 *
 * Provides object cache-based rate limiting per IP address.
 * Automatically uses Redis/Memcached when a persistent object cache
 * plugin is installed, otherwise falls back to in-memory cache.
 */
class RateLimiter {

	/**
	 * Default rate limit (requests per minute).
	 *
	 * @var int
	 */
	private int $default_limit = 100;

	/**
	 * Rate limit window in seconds.
	 *
	 * @var int
	 */
	private int $window = 60;

	/**
	 * Cache key prefix.
	 *
	 * @var string
	 */
	private const CACHE_PREFIX = 'tgp_llms_rate_';

	/**
	 * Cache group for rate limiting.
	 *
	 * @var string
	 */
	private const CACHE_GROUP = 'tgp_llms_txt';

	/**
	 * Check rate limit and return info.
	 *
	 * Increments the request count and checks if the limit is exceeded.
	 * Sends 429 response and exits if over limit.
	 *
	 * @return array{limit: int, remaining: int, reset: int} Rate limit info for headers.
	 */
	public function check(): array {
		$ip        = $this->get_client_ip();
		$cache_key = self::CACHE_PREFIX . md5( $ip );

		// Get current request count and timestamp from object cache.
		$rate_data = wp_cache_get( $cache_key, self::CACHE_GROUP );

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
		$limit = (int) apply_filters( 'tgp_llms_txt_rate_limit', $this->default_limit, $ip );

		// Calculate time until reset.
		$elapsed    = time() - $rate_data['start'];
		$reset_time = $rate_data['start'] + $this->window;

		// If window has passed, reset the counter.
		if ( $elapsed >= $this->window ) {
			$rate_data = [
				'count' => 0,
				'start' => time(),
			];
			$reset_time = time() + $this->window;
		}

		// Increment request count.
		++$rate_data['count'];

		// Calculate remaining requests.
		$remaining = max( 0, $limit - $rate_data['count'] );

		// Store updated count in object cache.
		wp_cache_set( $cache_key, $rate_data, self::CACHE_GROUP, $this->window );

		// Check if over limit.
		if ( $rate_data['count'] > $limit ) {
			$this->send_exceeded_response( $limit, $reset_time, $ip );
		}

		return [
			'limit'     => $limit,
			'remaining' => $remaining,
			'reset'     => $reset_time,
		];
	}

	/**
	 * Send rate limit headers.
	 *
	 * @param array{limit: int, remaining: int, reset: int} $rate_info Rate limit information.
	 */
	public function send_headers( array $rate_info ): void {
		header( 'X-RateLimit-Limit: ' . $rate_info['limit'] );
		header( 'X-RateLimit-Remaining: ' . $rate_info['remaining'] );
		header( 'X-RateLimit-Reset: ' . $rate_info['reset'] );
	}

	/**
	 * Get client IP address.
	 *
	 * Checks for proxy headers in a safe order.
	 *
	 * @return string The client IP address.
	 */
	public function get_client_ip(): string {
		// Check for forwarded IP (reverse proxy/load balancer).
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
	 * Send 429 Too Many Requests response and exit.
	 *
	 * @param int    $limit      The rate limit.
	 * @param int    $reset_time Unix timestamp when the rate limit resets.
	 * @param string $client_ip  The client IP address.
	 */
	private function send_exceeded_response( int $limit, int $reset_time, string $client_ip ): void {
		$retry_after = max( 1, $reset_time - time() );

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

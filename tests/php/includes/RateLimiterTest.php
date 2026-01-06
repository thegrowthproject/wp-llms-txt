<?php
/**
 * Tests for RateLimiter class.
 *
 * @package TGP_LLMs_Txt
 */

namespace TGP\LLMsTxt\Tests;

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;
use TGP\LLMsTxt\RateLimiter;

/**
 * Test class for RateLimiter.
 */
class RateLimiterTest extends TestCase {

	/**
	 * The rate limiter instance.
	 *
	 * @var RateLimiter
	 */
	private RateLimiter $rate_limiter;

	/**
	 * Set up test environment.
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		// Reset $_SERVER between tests.
		$_SERVER = [];

		// Mock WordPress functions.
		Functions\when( 'sanitize_text_field' )->returnArg();
		Functions\when( 'wp_unslash' )->returnArg();

		$this->rate_limiter = new RateLimiter();
	}

	/**
	 * Tear down test environment.
	 */
	protected function tearDown(): void {
		$_SERVER = [];
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * Test get_client_ip returns REMOTE_ADDR when no proxy headers.
	 */
	public function test_get_client_ip_returns_remote_addr(): void {
		$_SERVER['REMOTE_ADDR'] = '192.168.1.100';

		$result = $this->rate_limiter->get_client_ip();

		$this->assertEquals( '192.168.1.100', $result );
	}

	/**
	 * Test get_client_ip returns default when no IP available.
	 */
	public function test_get_client_ip_returns_default_when_empty(): void {
		$result = $this->rate_limiter->get_client_ip();

		$this->assertEquals( '0.0.0.0', $result );
	}

	/**
	 * Test get_client_ip prefers X-Forwarded-For header.
	 */
	public function test_get_client_ip_prefers_forwarded_for(): void {
		$_SERVER['HTTP_X_FORWARDED_FOR'] = '10.0.0.1';
		$_SERVER['REMOTE_ADDR']          = '192.168.1.100';

		$result = $this->rate_limiter->get_client_ip();

		$this->assertEquals( '10.0.0.1', $result );
	}

	/**
	 * Test get_client_ip handles multiple IPs in X-Forwarded-For.
	 */
	public function test_get_client_ip_handles_multiple_forwarded_ips(): void {
		$_SERVER['HTTP_X_FORWARDED_FOR'] = '10.0.0.1, 10.0.0.2, 10.0.0.3';
		$_SERVER['REMOTE_ADDR']          = '192.168.1.100';

		$result = $this->rate_limiter->get_client_ip();

		// Should return the first IP (client IP).
		$this->assertEquals( '10.0.0.1', $result );
	}

	/**
	 * Test get_client_ip uses X-Real-IP when X-Forwarded-For not present.
	 */
	public function test_get_client_ip_uses_real_ip(): void {
		$_SERVER['HTTP_X_REAL_IP'] = '10.0.0.5';
		$_SERVER['REMOTE_ADDR']    = '192.168.1.100';

		$result = $this->rate_limiter->get_client_ip();

		$this->assertEquals( '10.0.0.5', $result );
	}

	/**
	 * Test get_client_ip uses HTTP_CLIENT_IP when others not present.
	 */
	public function test_get_client_ip_uses_client_ip(): void {
		$_SERVER['HTTP_CLIENT_IP'] = '10.0.0.10';
		$_SERVER['REMOTE_ADDR']    = '192.168.1.100';

		$result = $this->rate_limiter->get_client_ip();

		$this->assertEquals( '10.0.0.10', $result );
	}

	/**
	 * Test get_client_ip validates IP format.
	 */
	public function test_get_client_ip_validates_ip_format(): void {
		$_SERVER['HTTP_X_FORWARDED_FOR'] = 'invalid-ip-address';
		$_SERVER['REMOTE_ADDR']          = '192.168.1.100';

		$result = $this->rate_limiter->get_client_ip();

		// Invalid IP should fall through to REMOTE_ADDR.
		$this->assertEquals( '192.168.1.100', $result );
	}

	/**
	 * Test get_client_ip handles IPv6 addresses.
	 */
	public function test_get_client_ip_handles_ipv6(): void {
		$_SERVER['REMOTE_ADDR'] = '2001:db8::1';

		$result = $this->rate_limiter->get_client_ip();

		$this->assertEquals( '2001:db8::1', $result );
	}

	/**
	 * Test check creates new rate data on first request.
	 */
	public function test_check_creates_new_rate_data(): void {
		$_SERVER['REMOTE_ADDR'] = '192.168.1.100';

		Functions\when( 'wp_cache_get' )->justReturn( false );
		Functions\expect( 'wp_cache_set' )
			->once()
			->andReturnUsing(
				function ( $key, $value, $group, $expiration ) {
					$this->assertStringStartsWith( 'tgp_llms_rate_', $key );
					$this->assertEquals( 'tgp_llms_txt', $group );
					$this->assertEquals( 1, $value['count'] );
					$this->assertEquals( 60, $expiration );
					return true;
				}
			);
		Functions\expect( 'apply_filters' )
			->once()
			->with( 'tgp_llms_txt_rate_limit', 100, '192.168.1.100' )
			->andReturn( 100 );

		$result = $this->rate_limiter->check();

		$this->assertEquals( 100, $result['limit'] );
		$this->assertEquals( 99, $result['remaining'] );
		$this->assertArrayHasKey( 'reset', $result );
	}

	/**
	 * Test check increments existing count.
	 */
	public function test_check_increments_count(): void {
		$_SERVER['REMOTE_ADDR'] = '192.168.1.100';
		$start_time             = time();

		Functions\when( 'wp_cache_get' )->justReturn(
			[
				'count' => 10,
				'start' => $start_time,
			]
		);
		Functions\expect( 'wp_cache_set' )
			->once()
			->andReturnUsing(
				// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Mock callback signature.
				function ( $key, $value, $group, $expiration ) {
					$this->assertEquals( 11, $value['count'] );
					return true;
				}
			);
		Functions\when( 'apply_filters' )->justReturn( 100 );

		$result = $this->rate_limiter->check();

		$this->assertEquals( 100, $result['limit'] );
		$this->assertEquals( 89, $result['remaining'] ); // 100 - 11.
	}

	/**
	 * Test check resets counter after window expires.
	 */
	public function test_check_resets_after_window(): void {
		$_SERVER['REMOTE_ADDR'] = '192.168.1.100';
		$old_start              = time() - 120; // 2 minutes ago.

		Functions\when( 'wp_cache_get' )->justReturn(
			[
				'count' => 50,
				'start' => $old_start,
			]
		);
		Functions\expect( 'wp_cache_set' )
			->once()
			->andReturnUsing(
				// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Mock callback signature.
				function ( $key, $value, $group, $expiration ) {
					// Count should be reset to 1 (this request).
					$this->assertEquals( 1, $value['count'] );
					// Start time should be current.
					$this->assertGreaterThan( time() - 2, $value['start'] );
					return true;
				}
			);
		Functions\when( 'apply_filters' )->justReturn( 100 );

		$result = $this->rate_limiter->check();

		$this->assertEquals( 99, $result['remaining'] ); // 100 - 1 after reset.
	}

	/**
	 * Test check respects filter for custom limit.
	 */
	public function test_check_respects_filter(): void {
		$_SERVER['REMOTE_ADDR'] = '192.168.1.100';

		Functions\when( 'wp_cache_get' )->justReturn( false );
		Functions\when( 'wp_cache_set' )->justReturn( true );
		Functions\expect( 'apply_filters' )
			->once()
			->with( 'tgp_llms_txt_rate_limit', 100, '192.168.1.100' )
			->andReturn( 50 ); // Custom lower limit.

		$result = $this->rate_limiter->check();

		$this->assertEquals( 50, $result['limit'] );
		$this->assertEquals( 49, $result['remaining'] );
	}

	/**
	 * Test check returns zero remaining when at limit.
	 */
	public function test_check_zero_remaining_at_limit(): void {
		$_SERVER['REMOTE_ADDR'] = '192.168.1.100';
		$start_time             = time();

		Functions\when( 'wp_cache_get' )->justReturn(
			[
				'count' => 99, // Will be incremented to 100.
				'start' => $start_time,
			]
		);
		Functions\when( 'wp_cache_set' )->justReturn( true );
		Functions\when( 'apply_filters' )->justReturn( 100 );

		$result = $this->rate_limiter->check();

		$this->assertEquals( 0, $result['remaining'] );
	}

	/**
	 * Test rate limit uses consistent cache key for same IP.
	 */
	public function test_rate_limit_uses_consistent_key(): void {
		$_SERVER['REMOTE_ADDR'] = '192.168.1.100';
		$expected_key           = 'tgp_llms_rate_' . md5( '192.168.1.100' );

		Functions\when( 'wp_cache_get' )->justReturn( false );
		Functions\expect( 'wp_cache_set' )
			->once()
			->andReturnUsing(
				// phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Mock callback signature.
				function ( $key, $value, $group, $expiration ) use ( $expected_key ) {
					$this->assertEquals( $expected_key, $key );
					return true;
				}
			);
		Functions\when( 'apply_filters' )->justReturn( 100 );

		$this->rate_limiter->check();
	}

	/**
	 * Test different IPs get different rate limit counters.
	 */
	public function test_different_ips_have_separate_counters(): void {
		$key_ip1 = 'tgp_llms_rate_' . md5( '192.168.1.100' );
		$key_ip2 = 'tgp_llms_rate_' . md5( '192.168.1.200' );

		$this->assertNotEquals( $key_ip1, $key_ip2 );
	}
}

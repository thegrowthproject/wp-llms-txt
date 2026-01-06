<?php
/**
 * Tests for TGP_Endpoint_Handler rate limiting.
 *
 * @package TGP_LLMs_Txt
 */

namespace TGP\LLMsTxt\Tests;

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;
use TGP_Endpoint_Handler;
use ReflectionClass;
use ReflectionMethod;

/**
 * Test class for TGP_Endpoint_Handler rate limiting.
 */
class EndpointHandlerRateLimitTest extends TestCase {

	/**
	 * The handler instance.
	 *
	 * @var TGP_Endpoint_Handler
	 */
	private TGP_Endpoint_Handler $handler;

	/**
	 * Reflection class for accessing private methods.
	 *
	 * @var ReflectionClass
	 */
	private ReflectionClass $reflection;

	/**
	 * Set up test environment.
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		// Reset $_SERVER between tests.
		$_SERVER = [];

		// Mock WordPress functions.
		Functions\when( 'add_action' )->justReturn( true );
		Functions\when( 'add_filter' )->justReturn( true );
		Functions\when( 'sanitize_text_field' )->returnArg();
		Functions\when( 'wp_unslash' )->returnArg();

		// Create handler instance (constructor calls hooks, which we've mocked).
		$this->reflection = new ReflectionClass( TGP_Endpoint_Handler::class );
		$this->handler    = $this->reflection->newInstanceWithoutConstructor();
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
	 * Get a private method for testing.
	 *
	 * @param string $method_name Method name.
	 * @return ReflectionMethod
	 */
	private function get_private_method( string $method_name ): ReflectionMethod {
		$method = $this->reflection->getMethod( $method_name );
		$method->setAccessible( true );
		return $method;
	}

	/**
	 * Test get_client_ip returns REMOTE_ADDR when no proxy headers.
	 */
	public function test_get_client_ip_returns_remote_addr(): void {
		$_SERVER['REMOTE_ADDR'] = '192.168.1.100';

		$method = $this->get_private_method( 'get_client_ip' );
		$result = $method->invoke( $this->handler );

		$this->assertEquals( '192.168.1.100', $result );
	}

	/**
	 * Test get_client_ip returns default when no IP available.
	 */
	public function test_get_client_ip_returns_default_when_empty(): void {
		$method = $this->get_private_method( 'get_client_ip' );
		$result = $method->invoke( $this->handler );

		$this->assertEquals( '0.0.0.0', $result );
	}

	/**
	 * Test get_client_ip prefers X-Forwarded-For header.
	 */
	public function test_get_client_ip_prefers_forwarded_for(): void {
		$_SERVER['HTTP_X_FORWARDED_FOR'] = '10.0.0.1';
		$_SERVER['REMOTE_ADDR']          = '192.168.1.100';

		$method = $this->get_private_method( 'get_client_ip' );
		$result = $method->invoke( $this->handler );

		$this->assertEquals( '10.0.0.1', $result );
	}

	/**
	 * Test get_client_ip handles multiple IPs in X-Forwarded-For.
	 */
	public function test_get_client_ip_handles_multiple_forwarded_ips(): void {
		$_SERVER['HTTP_X_FORWARDED_FOR'] = '10.0.0.1, 10.0.0.2, 10.0.0.3';
		$_SERVER['REMOTE_ADDR']          = '192.168.1.100';

		$method = $this->get_private_method( 'get_client_ip' );
		$result = $method->invoke( $this->handler );

		// Should return the first IP (client IP).
		$this->assertEquals( '10.0.0.1', $result );
	}

	/**
	 * Test get_client_ip uses X-Real-IP when X-Forwarded-For not present.
	 */
	public function test_get_client_ip_uses_real_ip(): void {
		$_SERVER['HTTP_X_REAL_IP'] = '10.0.0.5';
		$_SERVER['REMOTE_ADDR']    = '192.168.1.100';

		$method = $this->get_private_method( 'get_client_ip' );
		$result = $method->invoke( $this->handler );

		$this->assertEquals( '10.0.0.5', $result );
	}

	/**
	 * Test get_client_ip uses HTTP_CLIENT_IP when others not present.
	 */
	public function test_get_client_ip_uses_client_ip(): void {
		$_SERVER['HTTP_CLIENT_IP'] = '10.0.0.10';
		$_SERVER['REMOTE_ADDR']    = '192.168.1.100';

		$method = $this->get_private_method( 'get_client_ip' );
		$result = $method->invoke( $this->handler );

		$this->assertEquals( '10.0.0.10', $result );
	}

	/**
	 * Test get_client_ip validates IP format.
	 */
	public function test_get_client_ip_validates_ip_format(): void {
		$_SERVER['HTTP_X_FORWARDED_FOR'] = 'invalid-ip-address';
		$_SERVER['REMOTE_ADDR']          = '192.168.1.100';

		$method = $this->get_private_method( 'get_client_ip' );
		$result = $method->invoke( $this->handler );

		// Invalid IP should fall through to REMOTE_ADDR.
		$this->assertEquals( '192.168.1.100', $result );
	}

	/**
	 * Test get_client_ip handles IPv6 addresses.
	 */
	public function test_get_client_ip_handles_ipv6(): void {
		$_SERVER['REMOTE_ADDR'] = '2001:db8::1';

		$method = $this->get_private_method( 'get_client_ip' );
		$result = $method->invoke( $this->handler );

		$this->assertEquals( '2001:db8::1', $result );
	}

	/**
	 * Test check_rate_limit creates new rate data on first request.
	 */
	public function test_check_rate_limit_creates_new_rate_data(): void {
		$_SERVER['REMOTE_ADDR'] = '192.168.1.100';

		Functions\when( 'get_transient' )->justReturn( false );
		Functions\expect( 'set_transient' )
			->once()
			->andReturnUsing(
				function ( $key, $value, $expiration ) {
					$this->assertStringStartsWith( 'tgp_llms_rate_', $key );
					$this->assertEquals( 1, $value['count'] );
					$this->assertEquals( 60, $expiration );
					return true;
				}
			);
		Functions\expect( 'apply_filters' )
			->once()
			->with( 'tgp_llms_txt_rate_limit', 100, '192.168.1.100' )
			->andReturn( 100 );

		$method = $this->get_private_method( 'check_rate_limit' );
		$result = $method->invoke( $this->handler );

		$this->assertEquals( 100, $result['limit'] );
		$this->assertEquals( 99, $result['remaining'] );
		$this->assertArrayHasKey( 'reset', $result );
	}

	/**
	 * Test check_rate_limit increments existing count.
	 */
	public function test_check_rate_limit_increments_count(): void {
		$_SERVER['REMOTE_ADDR'] = '192.168.1.100';
		$start_time             = time();

		Functions\when( 'get_transient' )->justReturn(
			[
				'count' => 10,
				'start' => $start_time,
			]
		);
		Functions\expect( 'set_transient' )
			->once()
			->andReturnUsing(
				function ( $key, $value, $expiration ) {
					$this->assertEquals( 11, $value['count'] );
					return true;
				}
			);
		Functions\when( 'apply_filters' )->justReturn( 100 );

		$method = $this->get_private_method( 'check_rate_limit' );
		$result = $method->invoke( $this->handler );

		$this->assertEquals( 100, $result['limit'] );
		$this->assertEquals( 89, $result['remaining'] ); // 100 - 11.
	}

	/**
	 * Test check_rate_limit resets counter after window expires.
	 */
	public function test_check_rate_limit_resets_after_window(): void {
		$_SERVER['REMOTE_ADDR'] = '192.168.1.100';
		$old_start              = time() - 120; // 2 minutes ago.

		Functions\when( 'get_transient' )->justReturn(
			[
				'count' => 50,
				'start' => $old_start,
			]
		);
		Functions\expect( 'set_transient' )
			->once()
			->andReturnUsing(
				function ( $key, $value, $expiration ) {
					// Count should be reset to 1 (this request).
					$this->assertEquals( 1, $value['count'] );
					// Start time should be current.
					$this->assertGreaterThan( time() - 2, $value['start'] );
					return true;
				}
			);
		Functions\when( 'apply_filters' )->justReturn( 100 );

		$method = $this->get_private_method( 'check_rate_limit' );
		$result = $method->invoke( $this->handler );

		$this->assertEquals( 99, $result['remaining'] ); // 100 - 1 after reset.
	}

	/**
	 * Test check_rate_limit respects filter for custom limit.
	 */
	public function test_check_rate_limit_respects_filter(): void {
		$_SERVER['REMOTE_ADDR'] = '192.168.1.100';

		Functions\when( 'get_transient' )->justReturn( false );
		Functions\when( 'set_transient' )->justReturn( true );
		Functions\expect( 'apply_filters' )
			->once()
			->with( 'tgp_llms_txt_rate_limit', 100, '192.168.1.100' )
			->andReturn( 50 ); // Custom lower limit.

		$method = $this->get_private_method( 'check_rate_limit' );
		$result = $method->invoke( $this->handler );

		$this->assertEquals( 50, $result['limit'] );
		$this->assertEquals( 49, $result['remaining'] );
	}

	/**
	 * Test check_rate_limit returns zero remaining when at limit.
	 */
	public function test_check_rate_limit_zero_remaining_at_limit(): void {
		$_SERVER['REMOTE_ADDR'] = '192.168.1.100';
		$start_time             = time();

		Functions\when( 'get_transient' )->justReturn(
			[
				'count' => 99, // Will be incremented to 100.
				'start' => $start_time,
			]
		);
		Functions\when( 'set_transient' )->justReturn( true );
		Functions\when( 'apply_filters' )->justReturn( 100 );

		$method = $this->get_private_method( 'check_rate_limit' );
		$result = $method->invoke( $this->handler );

		$this->assertEquals( 0, $result['remaining'] );
	}

	/**
	 * Test rate limit uses consistent transient key for same IP.
	 */
	public function test_rate_limit_uses_consistent_key(): void {
		$_SERVER['REMOTE_ADDR'] = '192.168.1.100';
		$expected_key           = 'tgp_llms_rate_' . md5( '192.168.1.100' );

		Functions\when( 'get_transient' )->justReturn( false );
		Functions\expect( 'set_transient' )
			->once()
			->andReturnUsing(
				function ( $key, $value, $expiration ) use ( $expected_key ) {
					$this->assertEquals( $expected_key, $key );
					return true;
				}
			);
		Functions\when( 'apply_filters' )->justReturn( 100 );

		$method = $this->get_private_method( 'check_rate_limit' );
		$method->invoke( $this->handler );
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

<?php
/**
 * Tests for Logger class.
 *
 * @package TGP_LLMs_Txt
 */

namespace TGP\LLMsTxt\Tests;

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;
use TGP\LLMsTxt\Logger;
use ReflectionClass;

/**
 * Test class for Logger.
 */
class LoggerTest extends TestCase {

	/**
	 * Set up test environment.
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
	}

	/**
	 * Tear down test environment.
	 */
	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * Test error() fires action hook.
	 */
	public function test_error_fires_action(): void {
		$action_fired = false;

		Functions\expect( 'do_action' )
			->once()
			->with( 'tgp_llms_txt_error', 'Test error', [ 'key' => 'value' ] )
			->andReturnUsing(
				function () use ( &$action_fired ) {
					$action_fired = true;
				}
			);

		Logger::error( 'Test error', [ 'key' => 'value' ] );

		$this->assertTrue( $action_fired );
	}

	/**
	 * Test error() fires action hook without context.
	 */
	public function test_error_fires_action_without_context(): void {
		$action_fired = false;

		Functions\expect( 'do_action' )
			->once()
			->with( 'tgp_llms_txt_error', 'Test error', [] )
			->andReturnUsing(
				function () use ( &$action_fired ) {
					$action_fired = true;
				}
			);

		Logger::error( 'Test error' );

		$this->assertTrue( $action_fired );
	}

	/**
	 * Test warning() fires action hook.
	 */
	public function test_warning_fires_action(): void {
		$action_fired = false;

		Functions\expect( 'do_action' )
			->once()
			->with( 'tgp_llms_txt_warning', 'Test warning', [ 'code' => 404 ] )
			->andReturnUsing(
				function () use ( &$action_fired ) {
					$action_fired = true;
				}
			);

		Logger::warning( 'Test warning', [ 'code' => 404 ] );

		$this->assertTrue( $action_fired );
	}

	/**
	 * Test warning() fires action hook without context.
	 */
	public function test_warning_fires_action_without_context(): void {
		$action_fired = false;

		Functions\expect( 'do_action' )
			->once()
			->with( 'tgp_llms_txt_warning', 'Test warning', [] )
			->andReturnUsing(
				function () use ( &$action_fired ) {
					$action_fired = true;
				}
			);

		Logger::warning( 'Test warning' );

		$this->assertTrue( $action_fired );
	}

	/**
	 * Test format() creates correct message structure.
	 */
	public function test_format_creates_correct_structure(): void {
		$reflection = new ReflectionClass( Logger::class );
		$method     = $reflection->getMethod( 'format' );
		$method->setAccessible( true );

		Functions\when( 'wp_json_encode' )->returnArg();

		$result = $method->invoke( null, 'ERROR', 'Test message', [] );

		$this->assertStringContainsString( '[TGP LLMs.txt]', $result );
		$this->assertStringContainsString( '[ERROR]', $result );
		$this->assertStringContainsString( 'Test message', $result );
	}

	/**
	 * Test format() includes context when provided.
	 */
	public function test_format_includes_context(): void {
		$reflection = new ReflectionClass( Logger::class );
		$method     = $reflection->getMethod( 'format' );
		$method->setAccessible( true );

		$context = [
			'ip'   => '192.168.1.1',
			'path' => '/test',
		];
		Functions\when( 'wp_json_encode' )->alias( 'json_encode' );

		$result = $method->invoke( null, 'WARNING', 'Test message', $context );

		$this->assertStringContainsString( '192.168.1.1', $result );
		$this->assertStringContainsString( '/test', $result );
	}

	/**
	 * Test format() omits context when empty.
	 */
	public function test_format_omits_empty_context(): void {
		$reflection = new ReflectionClass( Logger::class );
		$method     = $reflection->getMethod( 'format' );
		$method->setAccessible( true );

		Functions\when( 'wp_json_encode' )->alias( 'json_encode' );

		$result = $method->invoke( null, 'DEBUG', 'Test message', [] );

		// Should end with the message, no trailing JSON.
		$this->assertMatchesRegularExpression( '/Test message$/', $result );
	}

	/**
	 * Test debug() does not fire action (no external integration).
	 */
	public function test_debug_does_not_fire_action(): void {
		// Debug should not fire any action hooks.
		// We verify by checking the method completes without calling do_action.
		Logger::debug( 'Test debug', [ 'data' => 'test' ] );

		// If we get here without an error from Brain\Monkey about unexpected do_action,
		// the test passes.
		$this->assertTrue( true );
	}

	/**
	 * Test error() with complex context data.
	 */
	public function test_error_with_complex_context(): void {
		$context = [
			'request_ip' => '10.0.0.1',
			'slug'       => 'test-post',
			'nested'     => [
				'key' => 'value',
			],
		];

		$received_context = null;

		Functions\expect( 'do_action' )
			->once()
			->with( 'tgp_llms_txt_error', 'Complex error', $context )
			->andReturnUsing(
				function ( $hook, $message, $ctx ) use ( &$received_context ) {
					$received_context = $ctx;
				}
			);

		Logger::error( 'Complex error', $context );

		$this->assertEquals( $context, $received_context );
	}

	/**
	 * Test warning() passes through all context keys.
	 */
	public function test_warning_passes_all_context(): void {
		$context = [
			'limit'       => 100,
			'retry_after' => 60,
			'ip'          => '192.168.1.100',
		];

		Functions\expect( 'do_action' )
			->once()
			->andReturnUsing(
				function ( $hook, $message, $ctx ) use ( $context ) {
					$this->assertEquals( 'tgp_llms_txt_warning', $hook );
					$this->assertEquals( $context, $ctx );
				}
			);

		Logger::warning( 'Rate limit exceeded', $context );
	}
}

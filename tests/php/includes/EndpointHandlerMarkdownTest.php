<?php
/**
 * Tests for EndpointHandler markdown escaping.
 *
 * @package TGP_LLMs_Txt
 */

namespace TGP\LLMsTxt\Tests;

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;
use TGP\LLMsTxt\EndpointHandler;
use ReflectionClass;

/**
 * Test class for EndpointHandler markdown escaping.
 */
class EndpointHandlerMarkdownTest extends TestCase {

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
	 * Get escape_markdown method for testing.
	 *
	 * @return \ReflectionMethod
	 */
	private function get_escape_method(): \ReflectionMethod {
		$reflection = new ReflectionClass( EndpointHandler::class );
		$method     = $reflection->getMethod( 'escape_markdown' );
		$method->setAccessible( true );
		return $method;
	}

	/**
	 * Create an EndpointHandler instance using reflection.
	 *
	 * @return EndpointHandler
	 */
	private function create_endpoint_handler(): EndpointHandler {
		$reflection  = new ReflectionClass( EndpointHandler::class );
		$constructor = $reflection->getConstructor();
		$constructor->setAccessible( true );
		$instance = $reflection->newInstanceWithoutConstructor();
		$constructor->invoke( $instance );
		return $instance;
	}

	/**
	 * Test plain text is not escaped.
	 */
	public function test_escape_markdown_plain_text(): void {
		$handler = $this->create_endpoint_handler();
		$method  = $this->get_escape_method();

		$result = $method->invoke( $handler, 'Simple Title' );
		$this->assertEquals( 'Simple Title', $result );
	}

	/**
	 * Test asterisks are escaped.
	 */
	public function test_escape_markdown_asterisks(): void {
		$handler = $this->create_endpoint_handler();
		$method  = $this->get_escape_method();

		$result = $method->invoke( $handler, 'Using * and ** in Python' );
		$this->assertEquals( 'Using \\* and \\*\\* in Python', $result );
	}

	/**
	 * Test underscores are escaped.
	 */
	public function test_escape_markdown_underscores(): void {
		$handler = $this->create_endpoint_handler();
		$method  = $this->get_escape_method();

		$result = $method->invoke( $handler, 'snake_case_title' );
		$this->assertEquals( 'snake\\_case\\_title', $result );
	}

	/**
	 * Test backticks are escaped.
	 */
	public function test_escape_markdown_backticks(): void {
		$handler = $this->create_endpoint_handler();
		$method  = $this->get_escape_method();

		$result = $method->invoke( $handler, 'Using `code` in titles' );
		$this->assertEquals( 'Using \\`code\\` in titles', $result );
	}

	/**
	 * Test square brackets are escaped.
	 */
	public function test_escape_markdown_brackets(): void {
		$handler = $this->create_endpoint_handler();
		$method  = $this->get_escape_method();

		$result = $method->invoke( $handler, 'Title [with] brackets' );
		$this->assertEquals( 'Title \\[with\\] brackets', $result );
	}

	/**
	 * Test angle brackets are escaped.
	 */
	public function test_escape_markdown_angle_brackets(): void {
		$handler = $this->create_endpoint_handler();
		$method  = $this->get_escape_method();

		$result = $method->invoke( $handler, 'HTML <tags> in title' );
		$this->assertEquals( 'HTML \\<tags\\> in title', $result );
	}

	/**
	 * Test hash symbols are escaped.
	 */
	public function test_escape_markdown_hash(): void {
		$handler = $this->create_endpoint_handler();
		$method  = $this->get_escape_method();

		$result = $method->invoke( $handler, 'Issue #123: Fix' );
		$this->assertEquals( 'Issue \\#123: Fix', $result );
	}

	/**
	 * Test backslashes are escaped first.
	 */
	public function test_escape_markdown_backslashes(): void {
		$handler = $this->create_endpoint_handler();
		$method  = $this->get_escape_method();

		// Input: Title with \ backslash
		// Expected: Title with \\ backslash
		$result = $method->invoke( $handler, 'Title with \\ backslash' );
		$this->assertEquals( 'Title with \\\\ backslash', $result );
	}

	/**
	 * Test newlines are converted to spaces.
	 */
	public function test_escape_markdown_newlines(): void {
		$handler = $this->create_endpoint_handler();
		$method  = $this->get_escape_method();

		$result = $method->invoke( $handler, "Line1\nLine2" );
		$this->assertEquals( 'Line1 Line2', $result );
	}

	/**
	 * Test carriage returns are converted to spaces.
	 */
	public function test_escape_markdown_carriage_returns(): void {
		$handler = $this->create_endpoint_handler();
		$method  = $this->get_escape_method();

		$result = $method->invoke( $handler, "Line1\r\nLine2" );
		$this->assertEquals( 'Line1 Line2', $result );
	}

	/**
	 * Test multiple special characters are all escaped.
	 */
	public function test_escape_markdown_multiple_specials(): void {
		$handler = $this->create_endpoint_handler();
		$method  = $this->get_escape_method();

		$result = $method->invoke( $handler, '*Bold* and _italic_ [link]' );
		$this->assertEquals( '\\*Bold\\* and \\_italic\\_ \\[link\\]', $result );
	}
}

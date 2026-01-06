<?php
/**
 * Tests for Frontmatter class.
 *
 * @package TGP_LLMs_Txt
 */

namespace TGP\LLMsTxt\Tests;

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;
use TGP\LLMsTxt\Frontmatter;
use ReflectionClass;

/**
 * Test class for Frontmatter.
 */
class FrontmatterTest extends TestCase {

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
	 * Get escape_yaml_value method for testing.
	 *
	 * @param Frontmatter $frontmatter The frontmatter instance.
	 * @return \ReflectionMethod
	 */
	private function get_escape_method( Frontmatter $frontmatter ): \ReflectionMethod {
		$reflection = new ReflectionClass( $frontmatter );
		$method     = $reflection->getMethod( 'escape_yaml_value' );
		$method->setAccessible( true );
		return $method;
	}

	/**
	 * Create a mock WP_Post object.
	 *
	 * @return \WP_Post Mock post object.
	 */
	private function create_mock_post(): \WP_Post {
		$post                = new \WP_Post();
		$post->ID            = 1;
		$post->post_title    = 'Test Post';
		$post->post_content  = 'Test content';
		$post->post_excerpt  = 'Test excerpt';
		$post->post_author   = '1';
		$post->post_date     = '2024-01-01 00:00:00';
		$post->post_modified = '2024-01-01 00:00:00';
		$post->post_status   = 'publish';
		$post->post_type     = 'post';

		return $post;
	}

	/**
	 * Test plain text values are not quoted.
	 */
	public function test_escape_yaml_plain_text_not_quoted(): void {
		$post        = $this->create_mock_post();
		$frontmatter = new Frontmatter( $post );
		$method      = $this->get_escape_method( $frontmatter );

		$result = $method->invoke( $frontmatter, 'Simple text' );
		$this->assertEquals( 'Simple text', $result );
	}

	/**
	 * Test values with colons are quoted.
	 */
	public function test_escape_yaml_colon_quoted(): void {
		$post        = $this->create_mock_post();
		$frontmatter = new Frontmatter( $post );
		$method      = $this->get_escape_method( $frontmatter );

		$result = $method->invoke( $frontmatter, 'Title: With Colon' );
		$this->assertEquals( '"Title: With Colon"', $result );
	}

	/**
	 * Test values with quotes are escaped.
	 */
	public function test_escape_yaml_quotes_escaped(): void {
		$post        = $this->create_mock_post();
		$frontmatter = new Frontmatter( $post );
		$method      = $this->get_escape_method( $frontmatter );

		$result = $method->invoke( $frontmatter, 'Title "With" Quotes' );
		$this->assertEquals( '"Title \\"With\\" Quotes"', $result );
	}

	/**
	 * Test backslashes are escaped before quotes.
	 */
	public function test_escape_yaml_backslash_escaped_first(): void {
		$post        = $this->create_mock_post();
		$frontmatter = new Frontmatter( $post );
		$method      = $this->get_escape_method( $frontmatter );

		// Input: path\to\"file"
		// Expected: "path\\to\\\"file\""
		$result = $method->invoke( $frontmatter, 'path\\to\\"file"' );
		$this->assertEquals( '"path\\\\to\\\\\\"file\\""', $result );
	}

	/**
	 * Test newlines are escaped.
	 */
	public function test_escape_yaml_newlines_escaped(): void {
		$post        = $this->create_mock_post();
		$frontmatter = new Frontmatter( $post );
		$method      = $this->get_escape_method( $frontmatter );

		$result = $method->invoke( $frontmatter, "Line1\nLine2" );
		$this->assertEquals( '"Line1\\nLine2"', $result );
	}

	/**
	 * Test tabs are escaped.
	 */
	public function test_escape_yaml_tabs_escaped(): void {
		$post        = $this->create_mock_post();
		$frontmatter = new Frontmatter( $post );
		$method      = $this->get_escape_method( $frontmatter );

		$result = $method->invoke( $frontmatter, "Col1\tCol2" );
		$this->assertEquals( '"Col1\\tCol2"', $result );
	}

	/**
	 * Test YAML boolean-like values are quoted.
	 */
	public function test_escape_yaml_boolean_values_quoted(): void {
		$post        = $this->create_mock_post();
		$frontmatter = new Frontmatter( $post );
		$method      = $this->get_escape_method( $frontmatter );

		$this->assertEquals( '"true"', $method->invoke( $frontmatter, 'true' ) );
		$this->assertEquals( '"false"', $method->invoke( $frontmatter, 'false' ) );
		$this->assertEquals( '"yes"', $method->invoke( $frontmatter, 'yes' ) );
		$this->assertEquals( '"no"', $method->invoke( $frontmatter, 'no' ) );
		$this->assertEquals( '"null"', $method->invoke( $frontmatter, 'null' ) );
	}

	/**
	 * Test numeric values are quoted to preserve as strings.
	 */
	public function test_escape_yaml_numeric_values_quoted(): void {
		$post        = $this->create_mock_post();
		$frontmatter = new Frontmatter( $post );
		$method      = $this->get_escape_method( $frontmatter );

		$this->assertEquals( '"123"', $method->invoke( $frontmatter, '123' ) );
		$this->assertEquals( '"3.14"', $method->invoke( $frontmatter, '3.14' ) );
	}

	/**
	 * Test empty strings are quoted.
	 */
	public function test_escape_yaml_empty_string_quoted(): void {
		$post        = $this->create_mock_post();
		$frontmatter = new Frontmatter( $post );
		$method      = $this->get_escape_method( $frontmatter );

		$this->assertEquals( '""', $method->invoke( $frontmatter, '' ) );
	}

	/**
	 * Test leading/trailing whitespace triggers quoting.
	 */
	public function test_escape_yaml_whitespace_quoted(): void {
		$post        = $this->create_mock_post();
		$frontmatter = new Frontmatter( $post );
		$method      = $this->get_escape_method( $frontmatter );

		$this->assertEquals( '" leading"', $method->invoke( $frontmatter, ' leading' ) );
		$this->assertEquals( '"trailing "', $method->invoke( $frontmatter, 'trailing ' ) );
	}

	/**
	 * Test special YAML characters are quoted.
	 */
	public function test_escape_yaml_special_chars_quoted(): void {
		$post        = $this->create_mock_post();
		$frontmatter = new Frontmatter( $post );
		$method      = $this->get_escape_method( $frontmatter );

		$this->assertEquals( '"[array]"', $method->invoke( $frontmatter, '[array]' ) );
		$this->assertEquals( '"{object}"', $method->invoke( $frontmatter, '{object}' ) );
		$this->assertEquals( '"#comment"', $method->invoke( $frontmatter, '#comment' ) );
		$this->assertEquals( '"&anchor"', $method->invoke( $frontmatter, '&anchor' ) );
		$this->assertEquals( '"*alias"', $method->invoke( $frontmatter, '*alias' ) );
	}
}

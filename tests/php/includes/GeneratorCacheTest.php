<?php
/**
 * Tests for Generator caching functionality.
 *
 * @package TGP_LLMs_Txt
 */

namespace TGP\LLMsTxt\Tests;

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;
use TGP\LLMsTxt\Generator;
use ReflectionClass;

/**
 * Test class for Generator caching.
 */
class GeneratorCacheTest extends TestCase {

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
	 * Get the cache key constant value.
	 *
	 * @return string
	 */
	private function get_cache_key(): string {
		$reflection = new ReflectionClass( Generator::class );
		return $reflection->getConstant( 'CACHE_KEY' );
	}

	/**
	 * Test generate returns cached content when available.
	 */
	public function test_generate_returns_cached_content(): void {
		$cached_content = "# Cached llms.txt content\n";
		$cache_key      = $this->get_cache_key();

		Functions\expect( 'get_transient' )
			->once()
			->with( $cache_key )
			->andReturn( $cached_content );

		$generator = new Generator();
		$result    = $generator->generate();

		$this->assertEquals( $cached_content, $result );
	}

	/**
	 * Test generate creates new content when cache is empty.
	 */
	public function test_generate_creates_content_when_cache_empty(): void {
		$cache_key = $this->get_cache_key();

		Functions\expect( 'get_transient' )
			->once()
			->with( $cache_key )
			->andReturn( false );

		Functions\expect( 'get_bloginfo' )
			->with( 'name' )
			->andReturn( 'Test Site' );

		Functions\expect( 'get_bloginfo' )
			->with( 'description' )
			->andReturn( 'Test Description' );

		Functions\expect( 'home_url' )
			->andReturn( 'https://example.com' );

		Functions\expect( 'apply_filters' )
			->andReturnUsing(
				function ( $filter, $default_value ) {
					return $default_value;
				}
			);

		Functions\expect( 'get_posts' )
			->andReturn( [] );

		Functions\expect( 'get_page_by_path' )
			->andReturn( null );

		Functions\expect( 'set_transient' )
			->once()
			->with( $cache_key, \Mockery::type( 'string' ), 3600 )
			->andReturn( true );

		$generator = new Generator();
		$result    = $generator->generate();

		$this->assertStringContainsString( '# Test Site', $result );
		$this->assertStringContainsString( '> Test Site', $result );
		$this->assertStringContainsString( 'Last Updated:', $result );
	}

	/**
	 * Test invalidate_cache deletes transient.
	 */
	public function test_invalidate_cache_deletes_transient(): void {
		$cache_key = $this->get_cache_key();

		Functions\expect( 'delete_transient' )
			->once()
			->with( $cache_key )
			->andReturn( true );

		$generator = new Generator();
		$generator->invalidate_cache();

		// Assertion is in the mock expectation.
		$this->assertTrue( true );
	}

	/**
	 * Test maybe_invalidate_cache skips revisions.
	 */
	public function test_maybe_invalidate_cache_skips_revisions(): void {
		Functions\expect( 'wp_is_post_revision' )
			->once()
			->andReturn( true );

		Functions\expect( 'delete_transient' )
			->never();

		$post              = new \WP_Post();
		$post->post_status = 'publish';
		$post->post_type   = 'post';

		$generator = new Generator();
		$generator->maybe_invalidate_cache( 1, $post );

		// Assertion is in the mock expectation.
		$this->assertTrue( true );
	}

	/**
	 * Test maybe_invalidate_cache skips non-published posts.
	 */
	public function test_maybe_invalidate_cache_skips_drafts(): void {
		Functions\expect( 'wp_is_post_revision' )
			->once()
			->andReturn( false );

		Functions\expect( 'delete_transient' )
			->never();

		$post              = new \WP_Post();
		$post->post_status = 'draft';
		$post->post_type   = 'post';

		$generator = new Generator();
		$generator->maybe_invalidate_cache( 1, $post );

		// Assertion is in the mock expectation.
		$this->assertTrue( true );
	}

	/**
	 * Test maybe_invalidate_cache invalidates for published posts.
	 */
	public function test_maybe_invalidate_cache_invalidates_published_post(): void {
		$cache_key = $this->get_cache_key();

		Functions\expect( 'wp_is_post_revision' )
			->once()
			->andReturn( false );

		Functions\expect( 'delete_transient' )
			->once()
			->with( $cache_key )
			->andReturn( true );

		$post              = new \WP_Post();
		$post->post_status = 'publish';
		$post->post_type   = 'post';

		$generator = new Generator();
		$generator->maybe_invalidate_cache( 1, $post );

		// Assertion is in the mock expectation.
		$this->assertTrue( true );
	}

	/**
	 * Test maybe_invalidate_cache invalidates for published pages.
	 */
	public function test_maybe_invalidate_cache_invalidates_published_page(): void {
		$cache_key = $this->get_cache_key();

		Functions\expect( 'wp_is_post_revision' )
			->once()
			->andReturn( false );

		Functions\expect( 'delete_transient' )
			->once()
			->with( $cache_key )
			->andReturn( true );

		$post              = new \WP_Post();
		$post->post_status = 'publish';
		$post->post_type   = 'page';

		$generator = new Generator();
		$generator->maybe_invalidate_cache( 1, $post );

		// Assertion is in the mock expectation.
		$this->assertTrue( true );
	}

	/**
	 * Test maybe_invalidate_cache skips custom post types.
	 */
	public function test_maybe_invalidate_cache_skips_custom_post_types(): void {
		Functions\expect( 'wp_is_post_revision' )
			->once()
			->andReturn( false );

		Functions\expect( 'delete_transient' )
			->never();

		$post              = new \WP_Post();
		$post->post_status = 'publish';
		$post->post_type   = 'product';

		$generator = new Generator();
		$generator->maybe_invalidate_cache( 1, $post );

		// Assertion is in the mock expectation.
		$this->assertTrue( true );
	}
}

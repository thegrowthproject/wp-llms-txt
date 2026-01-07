/**
 * E2E tests for TGP LLMs.txt endpoints.
 *
 * These tests verify that the core endpoints work correctly,
 * catching critical errors like instantiation failures.
 *
 * @package TGP_LLMs_Txt
 */

const { test, expect, createPost, deletePost } = require( './fixtures' );

test.describe( 'LLMs.txt endpoint', () => {
	test( '/llms.txt returns valid response', async ( { page } ) => {
		const response = await page.request.get( '/llms.txt' );

		// Should return 200, not 500 (critical error).
		expect( response.ok() ).toBeTruthy();
		expect( response.status() ).toBe( 200 );

		// Should be plain text.
		const contentType = response.headers()[ 'content-type' ];
		expect( contentType ).toContain( 'text/plain' );

		// Should contain expected content structure.
		const content = await response.text();
		expect( content ).toContain( '# ' ); // Site name header.
		expect( content ).toContain( 'llms.txt' );
		expect( content ).toContain( 'https://llmstxt.org/' ); // Standard reference.
	} );

	test( '/llms.txt contains rate limit headers', async ( { page } ) => {
		const response = await page.request.get( '/llms.txt' );

		expect( response.headers()[ 'x-ratelimit-limit' ] ).toBeDefined();
		expect( response.headers()[ 'x-ratelimit-remaining' ] ).toBeDefined();
		expect( response.headers()[ 'x-ratelimit-reset' ] ).toBeDefined();
	} );

	test( '/llms.txt is cached', async ( { page } ) => {
		const response = await page.request.get( '/llms.txt' );

		const cacheControl = response.headers()[ 'cache-control' ];
		expect( cacheControl ).toContain( 'public' );
		expect( cacheControl ).toContain( 'max-age=' );
	} );
} );

test.describe( 'Markdown endpoints', () => {
	let testPost;

	test.beforeAll( async () => {
		testPost = await createPost(
			'Endpoint Test Post',
			'<!-- wp:paragraph --><p>Test content for endpoint testing.</p><!-- /wp:paragraph -->'
		);
	} );

	test.afterAll( async () => {
		if ( testPost ) {
			await deletePost( testPost.id );
		}
	} );

	test( '.md endpoint returns valid markdown', async ( { page } ) => {
		// Get the .md URL from the post URL.
		const mdUrl = testPost.url.replace( /\/$/, '' ) + '.md';

		const response = await page.request.get( mdUrl );

		// Should return 200, not 500 (critical error).
		expect( response.ok() ).toBeTruthy();
		expect( response.status() ).toBe( 200 );

		// Should be markdown content type.
		const contentType = response.headers()[ 'content-type' ];
		expect( contentType ).toContain( 'text/markdown' );

		// Should contain the post title and content.
		const content = await response.text();
		expect( content ).toContain( 'Endpoint Test Post' );
		expect( content ).toContain( 'Test content for endpoint testing' );
	} );

	test( '.md endpoint contains frontmatter', async ( { page } ) => {
		const mdUrl = testPost.url.replace( /\/$/, '' ) + '.md';

		const response = await page.request.get( mdUrl );
		const content = await response.text();

		// Should start with YAML frontmatter.
		expect( content ).toMatch( /^---\n/ );
		expect( content ).toContain( 'title:' );
		expect( content ).toContain( 'url:' );
	} );

	test( '.md endpoint contains rate limit headers', async ( { page } ) => {
		const mdUrl = testPost.url.replace( /\/$/, '' ) + '.md';

		const response = await page.request.get( mdUrl );

		expect( response.headers()[ 'x-ratelimit-limit' ] ).toBeDefined();
		expect( response.headers()[ 'x-ratelimit-remaining' ] ).toBeDefined();
	} );

	test( 'Non-existent .md endpoint returns 404', async ( { page } ) => {
		const response = await page.request.get( '/non-existent-post-slug-12345.md' );

		expect( response.status() ).toBe( 404 );
	} );
} );

test.describe( 'Endpoint error handling', () => {
	test( 'Invalid endpoints do not cause 500 errors', async ( { page } ) => {
		// Test various edge cases that shouldn't cause server errors.
		const testPaths = [
			'/llms.txt',
			'/.md',
			'/test.md',
		];

		for ( const path of testPaths ) {
			const response = await page.request.get( path );

			// Should never return 500 (Internal Server Error).
			expect( response.status() ).not.toBe( 500 );
		}
	} );
} );

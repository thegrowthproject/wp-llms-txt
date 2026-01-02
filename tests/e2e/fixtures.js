/**
 * E2E test fixtures and utilities.
 *
 * @package TGP_LLMs_Txt
 */

const { test: base, expect } = require( '@playwright/test' );
const { execSync } = require( 'child_process' );

const PLUGIN_DIR = __dirname + '/../..';

/**
 * Login to WordPress admin.
 *
 * @param {import('@playwright/test').Page} page - Playwright page.
 */
async function loginAsAdmin( page ) {
	await page.goto( '/wp-login.php' );
	await page.fill( '#user_login', 'admin' );
	await page.fill( '#user_pass', 'password' );
	await page.click( '#wp-submit' );
	await page.waitForURL( /wp-admin/ );
}

/**
 * Create a post via WP-CLI in wp-env.
 *
 * @param {string} title   - Post title.
 * @param {string} content - Post content.
 * @return {Promise<{id: number, url: string}>} Post data.
 */
async function createPost( title, content ) {
	// Create post via wp-cli in wp-env.
	const result = execSync(
		`npm run wp-env run cli -- wp post create --post_title="${ title }" --post_content='${ content }' --post_status=publish --porcelain`,
		{ encoding: 'utf8', cwd: PLUGIN_DIR }
	);

	// Extract the post ID from the output (it's a number on its own line).
	const match = result.match( /^\d+$/m );
	if ( ! match ) {
		throw new Error( `Failed to extract post ID from output: ${ result }` );
	}
	const postId = parseInt( match[ 0 ], 10 );

	// Get post URL.
	const urlResult = execSync(
		`npm run wp-env run cli -- wp post get ${ postId } --field=url`,
		{ encoding: 'utf8', cwd: PLUGIN_DIR }
	);

	// Extract URL from output.
	const urlMatch = urlResult.match( /https?:\/\/[^\s]+/ );
	if ( ! urlMatch ) {
		throw new Error( `Failed to extract URL from output: ${ urlResult }` );
	}

	return { id: postId, url: urlMatch[ 0 ] };
}

/**
 * Delete a post via WP-CLI.
 *
 * @param {number} postId - Post ID.
 */
async function deletePost( postId ) {
	execSync(
		`npm run wp-env run cli -- wp post delete ${ postId } --force`,
		{ encoding: 'utf8', cwd: PLUGIN_DIR }
	);
}

const test = base.extend( {
	/**
	 * Logged-in page fixture.
	 */
	adminPage: async ( { page }, use ) => {
		await loginAsAdmin( page );
		await use( page );
	},
} );

module.exports = { test, expect, loginAsAdmin, createPost, deletePost };

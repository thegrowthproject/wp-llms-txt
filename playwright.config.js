/**
 * Playwright configuration for E2E tests.
 *
 * @package TGP_LLMs_Txt
 */

const { defineConfig, devices } = require( '@playwright/test' );

module.exports = defineConfig( {
	testDir: './tests/e2e',
	fullyParallel: true,
	forbidOnly: !! process.env.CI,
	retries: process.env.CI ? 2 : 0,
	workers: process.env.CI ? 1 : undefined,
	reporter: process.env.CI ? 'github' : 'html',
	use: {
		baseURL: 'http://localhost:8888',
		trace: 'on-first-retry',
		screenshot: 'only-on-failure',
	},
	projects: [
		{
			name: 'chromium',
			use: { ...devices[ 'Desktop Chrome' ] },
		},
	],
	webServer: {
		command: 'npm run wp-env start',
		url: 'http://localhost:8888',
		reuseExistingServer: true,
		timeout: 120000,
	},
} );

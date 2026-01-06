<?php
/**
 * PHPUnit bootstrap file.
 *
 * @package TGP_LLMs_Txt
 */

// Load Composer autoloader.
require_once dirname( __DIR__, 2 ) . '/vendor/autoload.php';

// Load Brain Monkey.
require_once dirname( __DIR__, 2 ) . '/vendor/antecedent/patchwork/Patchwork.php';

use Brain\Monkey;

// Set up Brain Monkey before tests.
Monkey\setUp();

// Define WordPress constants if not defined.
if ( ! defined( 'ABSPATH' ) ) {
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- ABSPATH is a WordPress core constant.
	define( 'ABSPATH', '/tmp/wordpress/' );
}

// Load the helper classes we're testing.
require_once dirname( __DIR__, 2 ) . '/includes/class-svg-sanitizer.php';
require_once dirname( __DIR__, 2 ) . '/includes/class-button-block-renderer.php';
require_once dirname( __DIR__, 2 ) . '/includes/class-pill-block-renderer.php';
require_once dirname( __DIR__, 2 ) . '/includes/class-endpoint-handler.php';

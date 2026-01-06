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

// Define mock WP_Post class for testing.
if ( ! class_exists( 'WP_Post' ) ) {
	/**
	 * Mock WP_Post class for unit testing.
	 */
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound -- Mocking WordPress core class.
	class WP_Post {
		/**
		 * Post ID.
		 *
		 * @var int
		 */
		public $ID = 0;

		/**
		 * Post author ID.
		 *
		 * @var string
		 */
		public $post_author = '0';

		/**
		 * Post date.
		 *
		 * @var string
		 */
		public $post_date = '0000-00-00 00:00:00';

		/**
		 * Post content.
		 *
		 * @var string
		 */
		public $post_content = '';

		/**
		 * Post title.
		 *
		 * @var string
		 */
		public $post_title = '';

		/**
		 * Post excerpt.
		 *
		 * @var string
		 */
		public $post_excerpt = '';

		/**
		 * Post status.
		 *
		 * @var string
		 */
		public $post_status = 'publish';

		/**
		 * Post type.
		 *
		 * @var string
		 */
		public $post_type = 'post';

		/**
		 * Post modified date.
		 *
		 * @var string
		 */
		public $post_modified = '0000-00-00 00:00:00';
	}
}

<?php
/**
 * Plugin Name: TGP LLMs.txt
 * Plugin URI: https://thegrowthproject.com
 * Description: Provides markdown endpoints for AI/LLM consumption. Adds .md URLs, /llms.txt index, and "Copy for LLM" buttons.
 * Version: 1.3.3
 * Requires at least: 6.5
 * Author: The Growth Project
 * Author URI: https://thegrowthproject.com
 * License: GPL v2 or later
 * Text Domain: tgp-llms-txt
 *
 * @package TGP_LLMs_Txt
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'TGP_LLMS_VERSION', '1.3.3' );
define( 'TGP_LLMS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'TGP_LLMS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Load Composer autoloader.
require_once TGP_LLMS_PLUGIN_DIR . 'vendor/autoload.php';

/**
 * Initialize plugin.
 *
 * @return TGP\LLMsTxt\Plugin
 */
function tgp_llms_txt_init(): TGP\LLMsTxt\Plugin {
	return TGP\LLMsTxt\Plugin::get_instance();
}
add_action( 'plugins_loaded', 'tgp_llms_txt_init' );

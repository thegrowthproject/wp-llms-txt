<?php
/**
 * Plugin Uninstall
 *
 * Runs when the plugin is uninstalled via WordPress admin.
 * Cleans up any data stored by the plugin.
 *
 * @package TGP_LLMs_Txt
 */

// Exit if not called by WordPress.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Clean up plugin data on uninstall.
 *
 * This plugin currently does not store any options, transients,
 * or custom database tables. This file is included for best
 * practices and future compatibility.
 *
 * If your site has cached llms.txt responses, they will expire
 * naturally based on their cache headers.
 */

// Flush rewrite rules to remove any custom endpoints.
flush_rewrite_rules();

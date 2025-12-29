<?php
/**
 * Plugin Name: TGP LLMs.txt
 * Plugin URI: https://thegrowthproject.com
 * Description: Provides markdown endpoints for AI/LLM consumption. Adds .md URLs, /llms.txt index, and "Copy for LLM" buttons.
 * Version: 1.0.0
 * Author: The Growth Project
 * Author URI: https://thegrowthproject.com
 * License: GPL v2 or later
 * Text Domain: tgp-llms-txt
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'TGP_LLMS_VERSION', '1.0.0' );
define( 'TGP_LLMS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'TGP_LLMS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Main plugin class
 */
class TGP_LLMs_Txt {

    /**
     * Single instance
     */
    private static $instance = null;

    /**
     * Get instance
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }

    /**
     * Load required files
     */
    private function load_dependencies() {
        require_once TGP_LLMS_PLUGIN_DIR . 'includes/class-markdown-converter.php';
        require_once TGP_LLMS_PLUGIN_DIR . 'includes/class-frontmatter.php';
        require_once TGP_LLMS_PLUGIN_DIR . 'includes/class-endpoint-handler.php';
        require_once TGP_LLMS_PLUGIN_DIR . 'includes/class-llms-txt-generator.php';
        require_once TGP_LLMS_PLUGIN_DIR . 'includes/class-ui-buttons.php';
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Initialize components
        new TGP_Endpoint_Handler();
        new TGP_LLMs_Txt_Generator();
        new TGP_UI_Buttons();

        // Register block
        add_action( 'init', [ $this, 'register_blocks' ] );

        // Activation hook
        register_activation_hook( __FILE__, [ $this, 'activate' ] );
        register_deactivation_hook( __FILE__, [ $this, 'deactivate' ] );
    }

    /**
     * Register Gutenberg blocks
     */
    public function register_blocks() {
        // Register the LLM Buttons block
        register_block_type( TGP_LLMS_PLUGIN_DIR . 'blocks/llm-buttons' );

        // Localize script for frontend
        wp_localize_script( 'tgp-llm-buttons-view-script', 'tgpLlmBlock', [
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'tgp_llm_nonce' ),
        ] );
    }

    /**
     * Plugin activation
     */
    public function activate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }
}

/**
 * Initialize plugin
 */
function tgp_llms_txt_init() {
    return TGP_LLMs_Txt::get_instance();
}
add_action( 'plugins_loaded', 'tgp_llms_txt_init' );

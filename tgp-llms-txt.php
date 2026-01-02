<?php
/**
 * Plugin Name: TGP LLMs.txt
 * Plugin URI: https://thegrowthproject.com.au
 * Description: Provides markdown endpoints for AI/LLM consumption. Adds .md URLs, /llms.txt index, and "Copy for LLM" buttons.
 * Version: 1.2.0
 * Requires at least: 6.5
 * Author: The Growth Project
 * Author URI: https://thegrowthproject.com.au
 * License: GPL v2 or later
 * Text Domain: tgp-llms-txt
 *
 * @package TGP_LLMs_Txt
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'TGP_LLMS_VERSION', '1.2.0' );
define( 'TGP_LLMS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'TGP_LLMS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Main plugin class.
 */
class TGP_LLMs_Txt {

	/**
	 * Single instance.
	 *
	 * @var TGP_LLMs_Txt|null
	 */
	private static $instance = null;

	/**
	 * Get instance.
	 *
	 * @return TGP_LLMs_Txt
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->load_dependencies();
		$this->init_hooks();
	}

	/**
	 * Load required files.
	 */
	private function load_dependencies() {
		// Shared helpers (used by blocks).
		require_once TGP_LLMS_PLUGIN_DIR . 'includes/class-svg-sanitizer.php';
		require_once TGP_LLMS_PLUGIN_DIR . 'includes/class-button-block-renderer.php';
		require_once TGP_LLMS_PLUGIN_DIR . 'includes/class-pill-block-renderer.php';

		// Core functionality.
		require_once TGP_LLMS_PLUGIN_DIR . 'includes/class-markdown-converter.php';
		require_once TGP_LLMS_PLUGIN_DIR . 'includes/class-frontmatter.php';
		require_once TGP_LLMS_PLUGIN_DIR . 'includes/class-endpoint-handler.php';
		require_once TGP_LLMS_PLUGIN_DIR . 'includes/class-llms-txt-generator.php';

		// Self-hosted plugin updates.
		require_once TGP_LLMS_PLUGIN_DIR . 'includes/class-plugin-updater.php';
	}

	/**
	 * Initialize hooks.
	 */
	private function init_hooks() {
		// Initialize components.
		new TGP_Endpoint_Handler();
		new TGP_LLMs_Txt_Generator();
		new TGP_Plugin_Updater();

		// Register block.
		add_action( 'init', [ $this, 'register_blocks' ] );

		// Register button style variations for our blocks.
		// Must run after theme.json is processed (wp_loaded is after init).
		add_action( 'wp_loaded', [ $this, 'register_button_style_variations' ] );

		// Generate CSS for button style variations on our blocks.
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_button_variation_styles' ] );
		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_button_variation_styles' ] );

		// Add data-post-id attributes to Query Loop posts for blog filtering.
		add_filter( 'render_block', [ $this, 'add_post_id_data_attribute' ], 10, 2 );

		// Activation hook.
		register_activation_hook( __FILE__, [ $this, 'activate' ] );
		register_deactivation_hook( __FILE__, [ $this, 'deactivate' ] );
	}

	/**
	 * Register Gutenberg blocks.
	 */
	public function register_blocks() {
		// Register individual button blocks.
		register_block_type( TGP_LLMS_PLUGIN_DIR . 'blocks/copy-button' );
		register_block_type( TGP_LLMS_PLUGIN_DIR . 'blocks/view-button' );

		// Register blog filter blocks.
		register_block_type( TGP_LLMS_PLUGIN_DIR . 'blocks/blog-filters' );
		register_block_type( TGP_LLMS_PLUGIN_DIR . 'blocks/blog-search' );
		register_block_type( TGP_LLMS_PLUGIN_DIR . 'blocks/blog-category-filter' );

		// Register block pattern.
		register_block_pattern(
			'tgp/copy-view-buttons',
			[
				'title'       => __( 'LLM Buttons', 'tgp-llms-txt' ),
				'description' => __( 'Copy for LLM and View as Markdown buttons.', 'tgp-llms-txt' ),
				'categories'  => [ 'buttons' ],
				'keywords'    => [ 'llm', 'markdown', 'ai', 'copy', 'chatgpt' ],
				'content'     => '<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:tgp/copy-button /-->

<!-- wp:tgp/view-button /--></div>
<!-- /wp:buttons -->',
			]
		);
	}

	/**
	 * Register button style variations for our blocks.
	 *
	 * Gets the theme's button style variations and registers them for our blocks.
	 * This runs after theme.json is processed so the variations are available.
	 */
	public function register_button_style_variations() {
		// Get theme's block style variations.
		$variations = WP_Theme_JSON_Resolver::get_style_variations( 'block' );

		if ( empty( $variations ) ) {
			return;
		}

		// Our blocks to add button styles to.
		$our_blocks = [ 'tgp/copy-button', 'tgp/view-button' ];

		// Styles to skip (already defined in our block.json).
		$skip_styles = [ 'fill', 'outline' ];

		foreach ( $variations as $variation ) {
			// Only process variations that target core/button.
			if ( empty( $variation['blockTypes'] ) || ! in_array( 'core/button', $variation['blockTypes'], true ) ) {
				continue;
			}

			$variation_name  = $variation['slug'] ?? ( isset( $variation['title'] ) ? sanitize_title( $variation['title'] ) : '' );
			$variation_label = $variation['title'] ?? $variation_name;

			// Skip if no valid name or if it's a default style.
			if ( empty( $variation_name ) || in_array( $variation_name, $skip_styles, true ) ) {
				continue;
			}

			// Register this variation for each of our blocks.
			foreach ( $our_blocks as $block_name ) {
				register_block_style(
					$block_name,
					[
						'name'  => $variation_name,
						'label' => $variation_label,
					]
				);
			}
		}
	}

	/**
	 * Enqueue CSS for button style variations on our blocks.
	 *
	 * Since theme.json sanitization strips our blocks from variation data,
	 * we generate the CSS ourselves by copying core/button variation styles.
	 *
	 * Also fixes outline style which has low specificity in WordPress core
	 * and gets overridden by global-styles.
	 */
	public function enqueue_button_variation_styles() {
		// Get merged theme.json data.
		$theme_json = WP_Theme_JSON_Resolver::get_merged_data();
		$data       = $theme_json->get_raw_data();

		$css = '';

		// Add outline style fix with higher specificity.
		// WordPress core's outline CSS uses :where() which has 0 specificity,
		// causing global-styles to override the text color to white.
		// This CSS has specificity 0,3,0 which beats global-styles 0,0,1.
		$css .= ".wp-block-button.is-style-outline .wp-block-button__link {\n";
		$css .= "\tcolor: inherit;\n";
		$css .= "\tbackground-color: transparent;\n";
		$css .= "}\n";

		// Check if core/button has variations for other styles.
		if ( isset( $data['styles']['blocks']['core/button']['variations'] ) ) {
			$variations = $data['styles']['blocks']['core/button']['variations'];

			// Generate CSS for each variation on our blocks.
			foreach ( $variations as $variation_name => $variation_data ) {
				// Skip outline (handled above).
				if ( 'outline' === $variation_name ) {
					continue;
				}

				// Extract color values.
				$bg_color   = $variation_data['color']['background'] ?? null;
				$text_color = $variation_data['color']['text'] ?? null;

				if ( ! $bg_color && ! $text_color ) {
					continue;
				}

				// Generate CSS for this variation.
				$selector = ".wp-block-button.is-style-{$variation_name} .wp-block-button__link";
				$styles   = [];

				if ( $bg_color ) {
					$styles[] = "background-color: {$bg_color}";
				}
				if ( $text_color ) {
					$styles[] = "color: {$text_color}";
				}

				if ( ! empty( $styles ) ) {
					$css .= "{$selector} { " . implode( '; ', $styles ) . "; }\n";
				}
			}
		}

		// Enqueue inline CSS.
		wp_register_style( 'tgp-llms-button-variations', false, [], TGP_LLMS_VERSION );
		wp_enqueue_style( 'tgp-llms-button-variations' );
		wp_add_inline_style( 'tgp-llms-button-variations', $css );
	}

	/**
	 * Add data-post-id attribute to Query Loop post template elements.
	 *
	 * This enables the blog-filters block to find and filter posts rendered
	 * by the core Query Loop block.
	 *
	 * @param string $block_content The block content.
	 * @param array  $block         The block data.
	 * @return string Modified block content.
	 */
	public function add_post_id_data_attribute( $block_content, $block ) {
		// Only process core/post-template blocks.
		if ( 'core/post-template' !== $block['blockName'] ) {
			return $block_content;
		}

		// Add data-post-id to each <li> element with post-{id} class.
		$block_content = preg_replace_callback(
			'/<li\s+class="([^"]*\bpost-(\d+)\b[^"]*)"/',
			function ( $matches ) {
				$post_id = $matches[2];
				return '<li data-post-id="' . esc_attr( $post_id ) . '" class="' . $matches[1] . '"';
			},
			$block_content
		);

		return $block_content;
	}

	/**
	 * Plugin activation.
	 */
	public function activate() {
		// Flush rewrite rules.
		flush_rewrite_rules();
	}

	/**
	 * Plugin deactivation.
	 */
	public function deactivate() {
		// Flush rewrite rules.
		flush_rewrite_rules();
	}
}

/**
 * Initialize plugin.
 *
 * @return TGP_LLMs_Txt
 */
function tgp_llms_txt_init() {
	return TGP_LLMs_Txt::get_instance();
}
add_action( 'plugins_loaded', 'tgp_llms_txt_init' );

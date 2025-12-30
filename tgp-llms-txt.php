<?php
/**
 * Plugin Name: TGP LLMs.txt
 * Plugin URI: https://thegrowthproject.com.au
 * Description: Provides markdown endpoints for AI/LLM consumption. Adds .md URLs, /llms.txt index, and "Copy for LLM" buttons.
 * Version: 1.2.0
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
		require_once TGP_LLMS_PLUGIN_DIR . 'includes/class-markdown-converter.php';
		require_once TGP_LLMS_PLUGIN_DIR . 'includes/class-frontmatter.php';
		require_once TGP_LLMS_PLUGIN_DIR . 'includes/class-endpoint-handler.php';
		require_once TGP_LLMS_PLUGIN_DIR . 'includes/class-llms-txt-generator.php';
		require_once TGP_LLMS_PLUGIN_DIR . 'includes/class-ui-buttons.php';
	}

	/**
	 * Initialize hooks.
	 */
	private function init_hooks() {
		// Initialize components.
		new TGP_Endpoint_Handler();
		new TGP_LLMs_Txt_Generator();
		new TGP_UI_Buttons();

		// Register block.
		add_action( 'init', [ $this, 'register_blocks' ] );

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

		// Copy button styles from core/button to our custom button blocks.
		$this->register_button_styles_from_theme();

		// Localize script for frontend copy functionality.
		wp_localize_script(
			'tgp-copy-button-view-script',
			'tgpLlmBlock',
			[
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'tgp_llms_nonce' ),
			]
		);

		// Register block pattern.
		register_block_pattern(
			'tgp/llm-buttons',
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
	 * Register button styles from theme for our custom button blocks.
	 *
	 * This copies any block styles registered for core/button (like Brand, Dark, Light, Tint)
	 * and registers them for our tgp/copy-button and tgp/view-button blocks.
	 */
	private function register_button_styles_from_theme() {
		// Our custom button blocks.
		$our_blocks = [ 'tgp/copy-button', 'tgp/view-button' ];

		// Method 1: Copy PHP-registered styles from core/button.
		$registry      = WP_Block_Styles_Registry::get_instance();
		$button_styles = $registry->get_registered_styles_for_block( 'core/button' );

		foreach ( $button_styles as $style_name => $style_props ) {
			// Skip the default fill/outline styles - we define those in block.json.
			if ( in_array( $style_name, [ 'fill', 'outline' ], true ) ) {
				continue;
			}

			foreach ( $our_blocks as $block_name ) {
				register_block_style( $block_name, $style_props );
			}
		}

		// Method 2: Read JSON-based block style variations from theme.
		$this->register_theme_json_button_styles( $our_blocks );
	}

	/**
	 * Register JSON-based block style variations from the active theme.
	 *
	 * WordPress 6.x themes can define block style variations as JSON files in
	 * styles/blocks/{block-name}/ directory. These aren't in the PHP registry,
	 * so we read them directly and register for our blocks.
	 *
	 * @param array $our_blocks Array of our block names to register styles for.
	 */
	private function register_theme_json_button_styles( $our_blocks ) {
		// Get the active theme's directory.
		$theme_dir = get_stylesheet_directory();

		// Look for button style variations in the theme.
		$button_styles_dir = $theme_dir . '/styles/blocks/button';

		if ( ! is_dir( $button_styles_dir ) ) {
			return;
		}

		// Get all JSON files in the button styles directory.
		$style_files = glob( $button_styles_dir . '/*.json' );

		if ( empty( $style_files ) ) {
			return;
		}

		foreach ( $style_files as $style_file ) {
			$json_content = file_get_contents( $style_file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			if ( ! $json_content ) {
				continue;
			}

			$style_data = json_decode( $json_content, true );
			if ( ! $style_data || ! isset( $style_data['title'], $style_data['slug'] ) ) {
				continue;
			}

			// Check if this style is for core/button.
			if ( ! isset( $style_data['blockTypes'] ) || ! in_array( 'core/button', $style_data['blockTypes'], true ) ) {
				continue;
			}

			// Build the style properties for register_block_style().
			$style_props = [
				'name'  => $style_data['slug'],
				'label' => $style_data['title'],
			];

			// If the style has CSS defined, include it.
			if ( isset( $style_data['styles'] ) ) {
				// Generate inline CSS from the style data.
				$inline_css = $this->generate_style_css( $style_data['slug'], $style_data['styles'] );
				if ( $inline_css ) {
					$style_props['inline_style'] = $inline_css;
				}
			}

			// Register for each of our blocks.
			foreach ( $our_blocks as $block_name ) {
				register_block_style( $block_name, $style_props );
			}
		}
	}

	/**
	 * Generate CSS from block style variation data.
	 *
	 * @param string $slug   The style slug.
	 * @param array  $styles The styles array from the JSON file.
	 * @return string CSS string.
	 */
	private function generate_style_css( $slug, $styles ) {
		$css = '';

		// Handle color styles.
		if ( isset( $styles['color'] ) ) {
			$color_css = [];

			if ( isset( $styles['color']['background'] ) ) {
				$bg = $this->resolve_preset_value( $styles['color']['background'] );
				if ( $bg ) {
					$color_css[] = 'background-color: ' . $bg;
				}
			}

			if ( isset( $styles['color']['text'] ) ) {
				$text = $this->resolve_preset_value( $styles['color']['text'] );
				if ( $text ) {
					$color_css[] = 'color: ' . $text;
				}
			}

			if ( ! empty( $color_css ) ) {
				// Target both our button blocks with this style.
				$css .= '.wp-block-button.is-style-' . $slug . ' .wp-block-button__link { ' . implode( '; ', $color_css ) . '; }';
			}
		}

		return $css;
	}

	/**
	 * Resolve a preset value reference to a CSS custom property.
	 *
	 * @param string $value The value, possibly a preset reference like "var:preset|color|primary".
	 * @return string The resolved CSS value.
	 */
	private function resolve_preset_value( $value ) {
		// Handle var:preset|type|slug format.
		if ( strpos( $value, 'var:' ) === 0 ) {
			$parts = explode( '|', substr( $value, 4 ) );
			if ( count( $parts ) === 3 && 'preset' === $parts[0] ) {
				return 'var(--wp--preset--' . $parts[1] . '--' . $parts[2] . ')';
			}
		}

		// Return as-is if it's a direct value.
		return $value;
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

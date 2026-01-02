<?php
/**
 * Self-hosted plugin updater.
 *
 * Enables automatic updates from GitHub releases using native WordPress hooks.
 *
 * @package TGP_LLMs_Txt
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin Updater class.
 *
 * Hooks into WordPress update system to check for and install updates
 * from a self-hosted JSON manifest (GitHub releases).
 */
class TGP_Plugin_Updater {

	/**
	 * Plugin slug (directory name).
	 *
	 * @var string
	 */
	private $slug = 'tgp-llms-txt';

	/**
	 * Plugin basename (directory/file.php).
	 *
	 * @var string
	 */
	private $basename;

	/**
	 * Current plugin version.
	 *
	 * @var string
	 */
	private $version;

	/**
	 * URL to the update manifest JSON file.
	 *
	 * @var string
	 */
	private $manifest_url;

	/**
	 * Cache key for update data.
	 *
	 * @var string
	 */
	private $cache_key = 'tgp_llms_txt_update_data';

	/**
	 * Cache expiration in seconds (12 hours).
	 *
	 * @var int
	 */
	private $cache_expiration = 43200;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->basename     = plugin_basename( TGP_LLMS_PLUGIN_DIR . 'tgp-llms-txt.php' );
		$this->version      = TGP_LLMS_VERSION;
		$this->manifest_url = 'https://raw.githubusercontent.com/thegrowthproject/wp-llms-txt/main/update-manifest.json';

		$this->init_hooks();
	}

	/**
	 * Initialize hooks.
	 */
	private function init_hooks() {
		// Check for updates when WordPress checks the plugin transient.
		add_filter( 'site_transient_update_plugins', [ $this, 'check_for_update' ] );

		// Provide plugin information for the update details modal.
		add_filter( 'plugins_api', [ $this, 'plugin_info' ], 20, 3 );

		// Clear cache after update.
		add_action( 'upgrader_process_complete', [ $this, 'clear_cache' ], 10, 2 );
	}

	/**
	 * Check for plugin updates.
	 *
	 * @param object $transient The update_plugins transient object.
	 * @return object Modified transient.
	 */
	public function check_for_update( $transient ) {
		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		$remote_data = $this->get_remote_data();

		if ( ! $remote_data ) {
			return $transient;
		}

		// Compare versions.
		if ( version_compare( $this->version, $remote_data->version, '<' ) ) {
			$transient->response[ $this->basename ] = (object) [
				'slug'        => $this->slug,
				'plugin'      => $this->basename,
				'new_version' => $remote_data->version,
				'url'         => $remote_data->homepage ?? '',
				'package'     => $remote_data->download_url,
				'icons'       => (array) ( $remote_data->icons ?? [] ),
				'banners'     => (array) ( $remote_data->banners ?? [] ),
				'tested'      => $remote_data->tested ?? '',
				'requires'    => $remote_data->requires ?? '',
			];
		} else {
			// No update available, but register that we checked.
			$transient->no_update[ $this->basename ] = (object) [
				'slug'        => $this->slug,
				'plugin'      => $this->basename,
				'new_version' => $this->version,
				'url'         => $remote_data->homepage ?? '',
			];
		}

		return $transient;
	}

	/**
	 * Provide plugin information for the WordPress plugin details modal.
	 *
	 * @param false|object|array $result The result object or array.
	 * @param string             $action The API action being performed.
	 * @param object             $args   Plugin API arguments.
	 * @return false|object Plugin information or false.
	 */
	public function plugin_info( $result, $action, $args ) {
		// Only handle plugin_information requests for our plugin.
		if ( 'plugin_information' !== $action || ( $args->slug ?? '' ) !== $this->slug ) {
			return $result;
		}

		$remote_data = $this->get_remote_data();

		if ( ! $remote_data ) {
			return $result;
		}

		return (object) [
			'name'              => $remote_data->name ?? 'TGP LLMs.txt',
			'slug'              => $this->slug,
			'version'           => $remote_data->version,
			'author'            => $remote_data->author ?? '<a href="https://thegrowthproject.com.au">The Growth Project</a>',
			'author_profile'    => $remote_data->author_profile ?? 'https://thegrowthproject.com.au',
			'homepage'          => $remote_data->homepage ?? 'https://github.com/thegrowthproject/wp-llms-txt',
			'requires'          => $remote_data->requires ?? '6.5',
			'tested'            => $remote_data->tested ?? '',
			'requires_php'      => $remote_data->requires_php ?? '8.2',
			'downloaded'        => $remote_data->downloaded ?? 0,
			'last_updated'      => $remote_data->last_updated ?? '',
			'sections'          => (array) ( $remote_data->sections ?? [
				'description' => 'Provides markdown endpoints for AI/LLM consumption.',
				'changelog'   => $remote_data->changelog ?? '',
			] ),
			'download_link'     => $remote_data->download_url,
			'banners'           => (array) ( $remote_data->banners ?? [] ),
			'icons'             => (array) ( $remote_data->icons ?? [] ),
			'external'          => true,
		];
	}

	/**
	 * Get remote update data from manifest.
	 *
	 * @return object|false Remote data object or false on failure.
	 */
	private function get_remote_data() {
		// Check cache first.
		$cached = get_transient( $this->cache_key );

		if ( false !== $cached ) {
			return $cached;
		}

		// Fetch remote manifest.
		$response = wp_remote_get(
			$this->manifest_url,
			[
				'timeout' => 10,
				'headers' => [
					'Accept' => 'application/json',
				],
			]
		);

		if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return false;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body );

		if ( ! $data || empty( $data->version ) ) {
			return false;
		}

		// Cache the result.
		set_transient( $this->cache_key, $data, $this->cache_expiration );

		return $data;
	}

	/**
	 * Clear cached update data after plugin update.
	 *
	 * @param WP_Upgrader $upgrader   The upgrader instance.
	 * @param array       $hook_extra Extra hook arguments.
	 */
	public function clear_cache( $upgrader, $hook_extra ) {
		if ( 'plugin' === ( $hook_extra['type'] ?? '' ) && 'update' === ( $hook_extra['action'] ?? '' ) ) {
			$plugins = $hook_extra['plugins'] ?? [];

			if ( in_array( $this->basename, $plugins, true ) ) {
				delete_transient( $this->cache_key );
			}
		}
	}
}

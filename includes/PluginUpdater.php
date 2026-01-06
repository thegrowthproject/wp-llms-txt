<?php
/**
 * Self-hosted plugin updater.
 *
 * Enables automatic updates from GitHub releases using native WordPress hooks.
 *
 * @package TGP_LLMs_Txt
 */

declare(strict_types=1);

namespace TGP\LLMsTxt;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin Updater class.
 *
 * Hooks into WordPress update system to check for and install updates
 * from a self-hosted JSON manifest (GitHub releases).
 */
class PluginUpdater {

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
	 * Initialize the plugin updater.
	 *
	 * Creates a new instance and registers all WordPress hooks.
	 * Call this method once during plugin initialization.
	 */
	public static function init(): void {
		$instance = new self();
		$instance->register_hooks();
	}

	/**
	 * Constructor.
	 *
	 * Private to enforce use of init() method.
	 * Sets up instance properties only - no side effects.
	 */
	private function __construct() {
		$this->basename     = plugin_basename( TGP_LLMS_PLUGIN_DIR . 'tgp-llms-txt.php' );
		$this->version      = TGP_LLMS_VERSION;
		$this->manifest_url = 'https://raw.githubusercontent.com/thegrowthproject/wp-llms-txt/main/update-manifest.json';
	}

	/**
	 * Register WordPress hooks.
	 */
	private function register_hooks(): void {
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
	 * @param mixed $transient The update_plugins transient object.
	 * @return mixed Modified transient.
	 */
	public function check_for_update( mixed $transient ): mixed {
		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		$remote_data = $this->get_remote_data();

		if ( ! $remote_data ) {
			return $transient;
		}

		// Compare versions.
		if ( version_compare( $this->version, $remote_data->version, '<' ) ) {
			Logger::debug(
				'Plugin update available',
				[
					'current_version' => $this->version,
					'new_version'     => $remote_data->version,
				]
			);

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
	public function plugin_info( mixed $result, string $action, object $args ): mixed {
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
			'author'            => $remote_data->author ?? '<a href="https://thegrowthproject.com">The Growth Project</a>',
			'author_profile'    => $remote_data->author_profile ?? 'https://thegrowthproject.com',
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
	 * Allowed hosts for plugin downloads.
	 *
	 * @var array
	 */
	private $allowed_download_hosts = [
		'github.com',
		'raw.githubusercontent.com',
		'objects.githubusercontent.com',
	];

	/**
	 * Get remote update data from manifest.
	 *
	 * @return object|false Remote data object or false on failure.
	 */
	private function get_remote_data(): object|false {
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

		if ( is_wp_error( $response ) ) {
			Logger::error(
				'Failed to fetch update manifest',
				[
					'url'   => $this->manifest_url,
					'error' => $response->get_error_message(),
				]
			);
			return false;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $response_code ) {
			Logger::warning(
				'Update manifest returned non-200 status',
				[
					'url'         => $this->manifest_url,
					'status_code' => $response_code,
				]
			);
			return false;
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body );

		// Validate manifest structure.
		if ( ! $this->validate_manifest( $data ) ) {
			Logger::warning(
				'Update manifest validation failed',
				[
					'url' => $this->manifest_url,
				]
			);
			return false;
		}

		// Cache the result.
		set_transient( $this->cache_key, $data, $this->cache_expiration );

		return $data;
	}

	/**
	 * Validate the manifest data structure.
	 *
	 * Ensures required fields are present, have correct types, and
	 * download URLs are from trusted domains.
	 *
	 * @param mixed $data The decoded manifest data.
	 * @return bool True if valid, false otherwise.
	 */
	private function validate_manifest( mixed $data ): bool {
		// Must be an object.
		if ( ! is_object( $data ) ) {
			return false;
		}

		// Required fields must exist.
		$required_fields = [ 'version', 'download_url' ];
		foreach ( $required_fields as $field ) {
			if ( ! isset( $data->$field ) ) {
				return false;
			}
		}

		// Version must be a valid semver-like string.
		if ( ! is_string( $data->version ) || ! preg_match( '/^\d+\.\d+(\.\d+)?(-[\w.]+)?(\+[\w.]+)?$/', $data->version ) ) {
			return false;
		}

		// Download URL must be a valid URL.
		if ( ! is_string( $data->download_url ) || ! filter_var( $data->download_url, FILTER_VALIDATE_URL ) ) {
			return false;
		}

		// Download URL must use HTTPS.
		if ( 'https' !== wp_parse_url( $data->download_url, PHP_URL_SCHEME ) ) {
			return false;
		}

		// Download URL must be from an allowed host.
		$download_host = wp_parse_url( $data->download_url, PHP_URL_HOST );
		if ( ! in_array( $download_host, $this->allowed_download_hosts, true ) ) {
			/**
			 * Filter the allowed download hosts for plugin updates.
			 *
			 * @param array  $allowed_hosts Array of allowed hostnames.
			 * @param string $download_host The host from the download URL.
			 */
			$allowed_hosts = apply_filters( 'tgp_llms_txt_allowed_update_hosts', $this->allowed_download_hosts, $download_host );

			if ( ! in_array( $download_host, $allowed_hosts, true ) ) {
				return false;
			}
		}

		// Optional fields validation (if present, must be correct type).
		if ( isset( $data->requires ) && ! is_string( $data->requires ) ) {
			return false;
		}
		if ( isset( $data->requires_php ) && ! is_string( $data->requires_php ) ) {
			return false;
		}
		if ( isset( $data->tested ) && ! is_string( $data->tested ) ) {
			return false;
		}
		if ( isset( $data->homepage ) && ! filter_var( $data->homepage, FILTER_VALIDATE_URL ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Clear cached update data after plugin update.
	 *
	 * @param object $upgrader   The upgrader instance.
	 * @param array  $hook_extra Extra hook arguments.
	 */
	public function clear_cache( object $upgrader, array $hook_extra ): void {
		if ( 'plugin' === ( $hook_extra['type'] ?? '' ) && 'update' === ( $hook_extra['action'] ?? '' ) ) {
			$plugins = $hook_extra['plugins'] ?? [];

			if ( in_array( $this->basename, $plugins, true ) ) {
				delete_transient( $this->cache_key );
			}
		}
	}
}

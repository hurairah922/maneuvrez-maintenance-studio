<?php
/**
 * Live-site Contact Channels renderer.
 *
 * @package MaintenanceModeStudio
 */

namespace Maneuvrez\MaintenanceModeStudio\Frontend;

use Maneuvrez\MaintenanceModeStudio\Components\ContactChannelsComponent;
use Maneuvrez\MaintenanceModeStudio\Support\ContactChannels;
use Maneuvrez\MaintenanceModeStudio\Settings\SettingsRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Conditionally renders the opt-in live-site floating contact button.
 */
class ContactChannelsRenderer {
	/**
	 * Settings repository.
	 *
	 * @var SettingsRepository
	 */
	private $settings_repository;

	/**
	 * Constructor.
	 *
	 * @param SettingsRepository|null $settings_repository Settings repository.
	 */
	public function __construct( $settings_repository = null ) {
		$this->settings_repository = $settings_repository instanceof SettingsRepository ? $settings_repository : new SettingsRepository();
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_footer', array( $this, 'render' ) );
	}

	/**
	 * Enqueue only the small live Contact Channels assets when needed.
	 *
	 * @return void
	 */
	public function enqueue_assets() {
		$settings = $this->get_renderable_settings();

		if ( empty( $settings ) ) {
			return;
		}

		$items = ContactChannels::build_items( $settings );

		wp_enqueue_style(
			'mmsm-contact-channels',
			MMSM_PLUGIN_URL . 'assets/css/contact-channels.css',
			array(),
			$this->get_asset_version( 'assets/css/contact-channels.css' )
		);

		if ( ContactChannels::needs_dashicons( $settings ) || ( count( $items ) > 1 && 'label_only' !== (string) ( $settings['contact_channels_button_display'] ?? '' ) ) ) {
			wp_enqueue_style( 'dashicons' );
		}

		if ( count( $items ) > 1 ) {
			wp_enqueue_script(
				'mmsm-contact-channels',
				MMSM_PLUGIN_URL . 'assets/js/contact-channels.js',
				array(),
				$this->get_asset_version( 'assets/js/contact-channels.js' ),
				true
			);
			wp_script_add_data( 'mmsm-contact-channels', 'defer', true );
		}
	}

	/**
	 * Render the live floating output.
	 *
	 * @return void
	 */
	public function render() {
		$settings = $this->get_renderable_settings();

		if ( empty( $settings ) ) {
			return;
		}

		echo ContactChannelsComponent::render_floating( $settings, 'live' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Return settings only when the current request can render Contact Channels.
	 *
	 * @return array<string,mixed>
	 */
	private function get_renderable_settings() {
		$settings = $this->settings_repository->get_settings();

		if ( empty( $settings['contact_channels_enabled'] ) || 'floating' !== (string) $settings['contact_channels_live_display'] ) {
			return array();
		}

		if ( ! $this->is_allowed_frontend_request() || ! $this->is_allowed_for_current_user( $settings ) ) {
			return array();
		}

		if ( empty( ContactChannels::build_items( $settings ) ) ) {
			return array();
		}

		return $settings;
	}

	/**
	 * Determine whether this request is a normal frontend page.
	 *
	 * @return bool
	 */
	private function is_allowed_frontend_request() {
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
			return false;
		}

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			return false;
		}

		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return false;
		}

		if ( function_exists( 'wp_is_json_request' ) && wp_is_json_request() ) {
			return false;
		}

		if ( function_exists( 'is_feed' ) && is_feed() ) {
			return false;
		}

		if ( function_exists( 'is_embed' ) && is_embed() ) {
			return false;
		}

		global $pagenow;

		if ( 'wp-login.php' === $pagenow ) {
			return false;
		}

		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

		return '' === $request_uri || false === strpos( $request_uri, '/wp-login.php' );
	}

	/**
	 * Apply logged-in visibility rules.
	 *
	 * @param array<string,mixed> $settings Sanitized settings.
	 * @return bool
	 */
	private function is_allowed_for_current_user( array $settings ) {
		$visibility = isset( $settings['contact_channels_logged_in_visibility'] ) ? (string) $settings['contact_channels_logged_in_visibility'] : 'hide_admins';

		if ( 'show_all' === $visibility ) {
			return true;
		}

		if ( ! is_user_logged_in() ) {
			return true;
		}

		if ( 'hide_logged_in' === $visibility ) {
			return false;
		}

		return ! current_user_can( 'manage_options' );
	}

	/**
	 * Resolve an asset version from file modification time.
	 *
	 * @param string $relative_path Asset path relative to plugin root.
	 * @return string
	 */
	private function get_asset_version( $relative_path ) {
		$absolute_path = MMSM_PLUGIN_PATH . ltrim( $relative_path, '/' );

		if ( file_exists( $absolute_path ) ) {
			return (string) filemtime( $absolute_path );
		}

		return MMSM_VERSION;
	}
}

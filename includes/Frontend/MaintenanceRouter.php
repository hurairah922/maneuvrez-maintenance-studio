<?php
/**
 * Frontend request router.
 *
 * @package MaintenanceModeStudio
 */

namespace Maneuvrez\MaintenanceModeStudio\Frontend;

use Maneuvrez\MaintenanceModeStudio\Security\Sanitizer;
use Maneuvrez\MaintenanceModeStudio\Settings\SettingsRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Decides when to render the maintenance page.
 */
class MaintenanceRouter {
	/**
	 * Template renderer.
	 *
	 * @var TemplateRenderer
	 */
	private $renderer;

	/**
	 * Settings repository.
	 *
	 * @var SettingsRepository
	 */
	private $settings_repository;

	/**
	 * Constructor.
	 *
	 * @param TemplateRenderer $renderer Renderer dependency.
	 */
	public function __construct( TemplateRenderer $renderer, $settings_repository = null ) {
		$this->renderer            = $renderer;
		$this->settings_repository = $settings_repository instanceof SettingsRepository ? $settings_repository : new SettingsRepository();
	}

	/**
	 * Register frontend hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'template_redirect', array( $this, 'maybe_render_maintenance_page' ), 0 );
	}

	/**
	 * Render the maintenance page when the current request should be intercepted.
	 *
	 * @return void
	 */
	public function maybe_render_maintenance_page() {
		$settings = $this->get_settings();

		if ( ! $this->is_enabled( $settings ) || $this->should_bypass( $settings ) ) {
			return;
		}

		if ( 'maintenance' === $settings['mode_type'] ) {
			status_header( 503 );
			header( 'Retry-After: 600' );
		} else {
			status_header( 200 );
		}

		nocache_headers();
		$this->renderer->render( $settings );
		exit;
	}

	/**
	 * Determine whether maintenance mode is enabled.
	 *
	 * @param array<string,mixed> $settings Sanitized settings.
	 * @return bool
	 */
	private function is_enabled( array $settings ) {
		return ! empty( $settings['enabled'] );
	}

	/**
	 * Determine whether this request must bypass maintenance mode.
	 *
	 * @return bool
	 */
	private function should_bypass( array $settings ) {
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
			return true;
		}

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			return true;
		}

		if ( $this->is_rest_request() ) {
			return true;
		}

		if ( is_user_logged_in() && current_user_can( 'manage_options' ) ) {
			return true;
		}

		global $pagenow;

		if ( 'wp-login.php' === $pagenow ) {
			return true;
		}

		if ( $this->matches_query_bypass( $settings ) ) {
			return true;
		}

		return $this->matches_public_bypass_url( $settings );
	}

	/**
	 * Determine whether the current request matches the configured preview query.
	 *
	 * @param array<string,mixed> $settings Sanitized settings.
	 * @return bool
	 */
	private function matches_query_bypass( array $settings ) {
		if ( empty( $settings['bypass_query_enabled'] ) ) {
			return false;
		}

		$key   = isset( $settings['bypass_query_key'] ) ? (string) $settings['bypass_query_key'] : '';
		$value = isset( $settings['bypass_query_value'] ) ? (string) $settings['bypass_query_value'] : '';

		if ( '' === $key || '' === $value || ! isset( $_GET[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only query bypass check for request routing.
			return false;
		}

		if ( ! is_scalar( $_GET[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only query bypass check for request routing.
			return false;
		}

		return (string) wp_unslash( $_GET[ $key ] ) === $value; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only query bypass check for request routing.
	}

	/**
	 * Determine whether the current request path is publicly allowlisted.
	 *
	 * @param array<string,mixed> $settings Sanitized settings.
	 * @return bool
	 */
	private function matches_public_bypass_url( array $settings ) {
		if ( empty( $settings['bypass_urls_enabled'] ) ) {
			return false;
		}

		$allowlist = isset( $settings['bypass_urls'] ) && is_array( $settings['bypass_urls'] ) ? $settings['bypass_urls'] : array();

		if ( empty( $allowlist ) ) {
			return false;
		}

		$current_path = $this->get_current_request_path();

		if ( '' === $current_path ) {
			return false;
		}

		return in_array( $current_path, $allowlist, true );
	}

	/**
	 * Detect REST requests using WordPress and fallback markers.
	 *
	 * @return bool
	 */
	private function is_rest_request() {
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return true;
		}

		if ( isset( $_GET['rest_route'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only REST route detection does not process or persist user input.
			return true;
		}

		$request_uri = isset( $_SERVER['REQUEST_URI'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only request path inspection for routing, sanitized before use.
			? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) )
			: '';
		$rest_prefix = trailingslashit( rest_get_url_prefix() );

		return '' !== $request_uri && false !== strpos( $request_uri, '/' . $rest_prefix );
	}

	/**
	 * Normalize the current request path for allowlist comparison.
	 *
	 * @return string
	 */
	private function get_current_request_path() {
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only path inspection for allowlist routing.
			? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) )
			: '';

		if ( '' === $request_uri ) {
			return '';
		}

		$request_path = wp_parse_url( $request_uri, PHP_URL_PATH );

		if ( ! is_string( $request_path ) || '' === $request_path ) {
			$request_path = '/';
		}

		return Sanitizer::normalize_bypass_path( $request_path );
	}

	/**
	 * Load the merged settings for frontend use.
	 *
	 * @return array<string,mixed>
	 */
	private function get_settings() {
		return $this->settings_repository->get_settings();
	}
}

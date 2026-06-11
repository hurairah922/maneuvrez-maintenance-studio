<?php
/**
 * Frontend request router.
 *
 * @package MaintenanceModeStudio
 */

namespace Maneuvrez\MaintenanceModeStudio\Frontend;

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

		if ( ! $this->is_enabled( $settings ) || $this->should_bypass() ) {
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
	private function should_bypass() {
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

		return 'wp-login.php' === $pagenow;
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
	 * Load the merged settings for frontend use.
	 *
	 * @return array<string,mixed>
	 */
	private function get_settings() {
		return $this->settings_repository->get_settings();
	}
}

<?php
/**
 * Frontend request router.
 *
 * @package MaintenanceModeStudio
 */

namespace Maneuvrez\MaintenanceModeStudio\Frontend;

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
	 * Constructor.
	 *
	 * @param TemplateRenderer $renderer Renderer dependency.
	 */
	public function __construct( TemplateRenderer $renderer ) {
		$this->renderer = $renderer;
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
		if ( ! $this->is_enabled() || $this->should_bypass() ) {
			return;
		}

		status_header( 503 );
		nocache_headers();
		header( 'Retry-After: 600' );

		$this->renderer->render();
		exit;
	}

	/**
	 * Determine whether maintenance mode is enabled.
	 *
	 * @return bool
	 */
	private function is_enabled() {
		$settings = get_option( MMSM_SETTINGS_OPTION, array() );

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
		if ( function_exists( 'wp_is_serving_rest_request' ) && wp_is_serving_rest_request() ) {
			return true;
		}

		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return true;
		}

		if ( isset( $_GET['rest_route'] ) ) {
			return true;
		}

		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
		$rest_prefix = trailingslashit( rest_get_url_prefix() );

		return '' !== $request_uri && false !== strpos( $request_uri, '/' . $rest_prefix );
	}
}

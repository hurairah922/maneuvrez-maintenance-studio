<?php
/**
 * Custom login URL routing and protection.
 *
 * @package MaintenanceModeStudio
 */

namespace Maneuvrez\MaintenanceModeStudio\Security;

use Maneuvrez\MaintenanceModeStudio\Settings\SettingsRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Routes a saved custom login slug to core login and protects default entry points.
 */
class LoginUrlManager {
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
	 * Register custom login hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'init', array( $this, 'maybe_block_wp_admin' ), 0 );
		// Route the custom slug after WordPress has parsed the main request.
		add_action( 'wp_loaded', array( $this, 'maybe_route_custom_login' ), 0 );
		add_action( 'login_init', array( $this, 'maybe_block_default_wp_login' ), 0 );
		add_filter( 'login_url', array( $this, 'filter_login_url' ), 10, 3 );
		add_filter( 'site_url', array( $this, 'filter_site_url' ), 10, 4 );
		add_filter( 'network_site_url', array( $this, 'filter_network_site_url' ), 10, 3 );
	}

	/**
	 * Load the real WordPress login flow on the configured custom slug.
	 *
	 * @return void
	 */
	public function maybe_route_custom_login() {
		global $action, $error, $interim_login, $pagenow, $user_login;

		$settings = $this->get_settings();

		if ( ! $this->is_custom_login_active( $settings ) || ! $this->is_custom_login_request( $settings ) ) {
			return;
		}

		$this->prepare_server_globals_for_core_login();
		$error         = '';
		$interim_login = false;
		$user_login    = '';
		$action        = isset( $_REQUEST['action'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Mirroring core login action routing for the custom endpoint.
			? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Mirroring core login action routing for the custom endpoint.
			: 'login';

		require ABSPATH . 'wp-login.php';
		exit;
	}

	/**
	 * Hide direct wp-login.php from logged-out visitors unless core flows require it.
	 *
	 * @return void
	 */
	public function maybe_block_default_wp_login() {
		$settings = $this->get_settings();

		if ( ! $this->is_custom_login_active( $settings ) || ! $this->should_block_default_wp_login_request() ) {
			return;
		}

		$this->render_frontend_not_found();
	}

	/**
	 * Prevent logged-out browser requests to /wp-admin/ from redirecting to login.
	 *
	 * @return void
	 */
	public function maybe_block_wp_admin() {
		$settings = $this->get_settings();

		if ( ! $this->is_custom_login_active( $settings ) || ! $this->should_block_wp_admin_request() ) {
			return;
		}

		if ( 'redirect' === $this->get_custom_login_block_mode( $settings ) ) {
			wp_safe_redirect( $this->get_custom_login_url( $settings ) );
			exit;
		}

		$this->render_admin_not_found();
	}

	/**
	 * Render the blocked admin request through the normal frontend 404 lifecycle.
	 *
	 * @return void
	 */
	public function render_frontend_not_found() {
		if ( ! defined( 'WP_USE_THEMES' ) ) {
			define( 'WP_USE_THEMES', true );
		}

		$this->prepare_frontend_request_context();
		$this->remove_admin_context_callbacks();

		$_SERVER['REQUEST_URI'] = $this->get_internal_not_found_request_uri();
		$_GET                  = array();
		$_POST                 = array();
		$_REQUEST              = array();

		wp();
		$this->prepare_frontend_request_context();
		$this->remove_admin_context_callbacks();
		$this->force_frontend_not_found_status();
		$this->enqueue_frontend_not_found_block_styles();

		require_once ABSPATH . WPINC . '/template-loader.php';
		exit;
	}

	/**
	 * Filter wp_login_url() output to the custom slug.
	 *
	 * @param string $login_url Existing login URL.
	 * @param string $redirect Requested redirect.
	 * @param bool   $force_reauth Whether reauth should be forced.
	 * @return string
	 */
	public function filter_login_url( $login_url, $redirect, $force_reauth ) {
		$settings = $this->get_settings();

		if ( ! $this->is_custom_login_active( $settings ) ) {
			return $login_url;
		}

		$args = array();

		if ( '' !== (string) $redirect ) {
			$args['redirect_to'] = $redirect;
		}

		if ( $force_reauth ) {
			$args['reauth'] = '1';
		}

		return $this->get_custom_login_url( $settings, $args );
	}

	/**
	 * Filter site_url() when WordPress generates wp-login.php URLs.
	 *
	 * @param string   $url Full site URL.
	 * @param string   $path Relative path.
	 * @param string   $scheme URL scheme.
	 * @param int|null $blog_id Blog ID.
	 * @return string
	 */
	public function filter_site_url( $url, $path, $scheme, $blog_id ) {
		unset( $scheme, $blog_id );

		return $this->filter_core_login_url( $url, $path );
	}

	/**
	 * Filter network_site_url() when WordPress generates wp-login.php URLs.
	 *
	 * @param string      $url Full network URL.
	 * @param string      $path Relative path.
	 * @param string|null $scheme URL scheme.
	 * @return string
	 */
	public function filter_network_site_url( $url, $path, $scheme ) {
		unset( $scheme );

		return $this->filter_core_login_url( $url, $path );
	}

	/**
	 * Filter a core login URL to the custom slug when applicable.
	 *
	 * @param string $url Full URL.
	 * @param string $path Relative path.
	 * @return string
	 */
	private function filter_core_login_url( $url, $path ) {
		$settings = $this->get_settings();

		if ( ! $this->is_custom_login_active( $settings ) || ! $this->is_wp_login_path( $path ) ) {
			return $url;
		}

		$parsed_url = wp_parse_url( $url );
		$args       = array();

		if ( is_array( $parsed_url ) && ! empty( $parsed_url['query'] ) ) {
			parse_str( (string) $parsed_url['query'], $args );
		}

		$custom_url = $this->get_custom_login_url( $settings, $args );

		if ( is_array( $parsed_url ) && ! empty( $parsed_url['fragment'] ) ) {
			$custom_url .= '#' . $parsed_url['fragment'];
		}

		return $custom_url;
	}

	/**
	 * Build the public custom login URL.
	 *
	 * @param array<string,mixed>  $settings Sanitized settings.
	 * @param array<string,string> $args Optional query args.
	 * @return string
	 */
	private function get_custom_login_url( array $settings, array $args = array() ) {
		$slug = $this->get_custom_login_slug( $settings );
		$url  = home_url( '/' . trailingslashit( $slug ) );

		if ( empty( $args ) ) {
			return $url;
		}

		return add_query_arg( $args, $url );
	}

	/**
	 * Determine whether custom login routing is active.
	 *
	 * @param array<string,mixed> $settings Sanitized settings.
	 * @return bool
	 */
	private function is_custom_login_active( array $settings ) {
		return ! empty( $settings['custom_login_enabled'] ) && '' !== $this->get_custom_login_slug( $settings );
	}

	/**
	 * Return the sanitized saved custom slug.
	 *
	 * @param array<string,mixed> $settings Sanitized settings.
	 * @return string
	 */
	private function get_custom_login_slug( array $settings ) {
		return isset( $settings['custom_login_slug'] ) ? Sanitizer::sanitize_custom_login_slug( $settings['custom_login_slug'] ) : '';
	}

	/**
	 * Return how logged-out default admin routes should behave.
	 *
	 * @param array<string,mixed> $settings Sanitized settings.
	 * @return string
	 */
	private function get_custom_login_block_mode( array $settings ) {
		if ( isset( $settings['custom_login_block_mode'] ) && 'redirect' === $settings['custom_login_block_mode'] ) {
			return 'redirect';
		}

		return '404';
	}

	/**
	 * Determine whether the current request path matches the custom login slug.
	 *
	 * @param array<string,mixed> $settings Sanitized settings.
	 * @return bool
	 */
	private function is_custom_login_request( array $settings ) {
		if ( $this->is_default_wp_admin_entry_request() ) {
			return false;
		}

		return '/' . $this->get_custom_login_slug( $settings ) === $this->get_current_request_path();
	}

	/**
	 * Determine whether the current request is a direct wp-login.php request.
	 *
	 * @return bool
	 */
	private function is_default_wp_login_request() {
		return '/wp-login.php' === $this->get_current_request_path();
	}

	/**
	 * Decide whether the direct wp-login.php request should be blocked.
	 *
	 * @return bool
	 */
	private function should_block_default_wp_login_request() {
		if ( ! $this->is_default_wp_login_request() || is_user_logged_in() ) {
			return false;
		}

		if ( wp_doing_ajax() || wp_doing_cron() || $this->is_rest_request() || $this->is_wp_cli_request() ) {
			return false;
		}

		return ! $this->should_allow_direct_wp_login_request();
	}

	/**
	 * Keep required core login actions available on the default endpoint.
	 *
	 * @return bool
	 */
	private function should_allow_direct_wp_login_request() {
		$request_method = isset( $_SERVER['REQUEST_METHOD'] )
			? strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) )
			: 'GET';

		if ( 'POST' === $request_method ) {
			return true;
		}

		$action = isset( $_REQUEST['action'] ) && is_scalar( $_REQUEST['action'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only inspection to preserve required core login actions.
			? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only inspection to preserve required core login actions.
			: '';

		if ( in_array( $action, array( 'logout', 'lostpassword', 'retrievepassword', 'rp', 'resetpass', 'postpass' ), true ) ) {
			return true;
		}

		if ( isset( $_GET['checkemail'] ) || isset( $_GET['loggedout'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only inspection to preserve required core login actions.
			return true;
		}

		return isset( $_GET['key'], $_GET['login'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only inspection to preserve required core login actions.
	}

	/**
	 * Decide whether the current /wp-admin/ request should be blocked.
	 *
	 * @return bool
	 */
	private function should_block_wp_admin_request() {
		if ( is_user_logged_in() || wp_doing_ajax() || wp_doing_cron() || $this->is_rest_request() || $this->is_wp_cli_request() ) {
			return false;
		}

		$request_method = isset( $_SERVER['REQUEST_METHOD'] )
			? strtoupper( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) )
			: 'GET';

		if ( 'GET' !== $request_method ) {
			return false;
		}

		return $this->is_default_wp_admin_entry_request();
	}

	/**
	 * Determine whether the current request is a default wp-admin entry route.
	 *
	 * @return bool
	 */
	private function is_default_wp_admin_entry_request() {
		return in_array( $this->get_current_request_path(), array( '/wp-admin', '/wp-admin/index.php' ), true );
	}

	/**
	 * Normalize the current request path relative to home_url().
	 *
	 * @return string
	 */
	private function get_current_request_path() {
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only request path inspection for routing.
			? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only request path inspection for routing.
			: '';

		if ( '' === $request_uri ) {
			return '';
		}

		$request_path = wp_parse_url( $request_uri, PHP_URL_PATH );

		if ( ! is_string( $request_path ) || '' === $request_path ) {
			$request_path = '/';
		}

		$home_path = wp_parse_url( home_url( '/' ), PHP_URL_PATH );
		$home_path = is_string( $home_path ) && '' !== $home_path ? '/' . trim( $home_path, '/' ) : '/';

		if ( '/' !== $home_path && 0 === strpos( $request_path, $home_path . '/' ) ) {
			$request_path = substr( $request_path, strlen( $home_path ) );
		} elseif ( $request_path === $home_path ) {
			$request_path = '/';
		}

		return $this->normalize_request_path( $request_path );
	}

	/**
	 * Normalize a request path without applying maintenance bypass exclusions.
	 *
	 * @param string $path Raw request path.
	 * @return string
	 */
	private function normalize_request_path( $path ) {
		$path = trim( sanitize_text_field( $path ) );

		if ( '' === $path ) {
			return '';
		}

		$path = preg_replace( '#/+#', '/', $path );

		if ( ! is_string( $path ) || '' === $path ) {
			return '';
		}

		$path = '/' . ltrim( $path, '/' );

		return '/' === $path ? $path : untrailingslashit( $path );
	}

	/**
	 * Return an internal missing frontend path for natural 404 rendering.
	 *
	 * @return string
	 */
	private function get_internal_not_found_request_uri() {
		$home_path = wp_parse_url( home_url( '/' ), PHP_URL_PATH );
		$home_path = is_string( $home_path ) && '/' !== $home_path ? '/' . trim( $home_path, '/' ) : '';

		return $home_path . '/mmsm-not-found-' . wp_generate_uuid4();
	}

	/**
	 * Defer admin 404 rendering until WordPress finishes loading core hooks.
	 *
	 * @return void
	 */
	private function render_admin_not_found() {
		if ( did_action( 'wp_loaded' ) ) {
			$this->render_frontend_not_found();
		}

		add_action( 'wp_loaded', array( $this, 'render_frontend_not_found' ), 0 );
	}

	/**
	 * Remove admin-only callbacks before rendering a frontend 404 from wp-admin.
	 *
	 * @return void
	 */
	private function remove_admin_context_callbacks() {
		show_admin_bar( false );
		remove_action( 'wp_body_open', 'wp_admin_bar_render', 0 );
		remove_action( 'wp_footer', 'wp_admin_bar_render', 1000 );
		remove_action( 'in_admin_header', 'wp_admin_bar_render', 0 );
		remove_action( 'template_redirect', '_wp_admin_bar_init', 0 );
		remove_action( 'admin_init', '_wp_admin_bar_init' );
		remove_action( 'admin_bar_menu', 'wp_admin_bar_edit_menu', 80 );
		remove_action( 'wp_head', 'wp_admin_bar_header' );
		remove_action( 'admin_head', 'wp_admin_bar_header' );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'admin_print_styles', 'print_emoji_styles' );
	}

	/**
	 * Make a blocked admin request behave like a frontend request for template rendering.
	 *
	 * @return void
	 */
	private function prepare_frontend_request_context() {
		global $current_screen, $pagenow;

		$pagenow = 'index.php';

		if ( ! class_exists( '\WP_Screen' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-screen.php';
		}

		if ( ! function_exists( 'get_current_screen' ) ) {
			require_once ABSPATH . 'wp-admin/includes/screen.php';
		}

		$current_screen = \WP_Screen::get( 'front' );
	}

	/**
	 * Ensure the synthetic frontend request resolves with a real 404 state.
	 *
	 * @return void
	 */
	private function force_frontend_not_found_status() {
		global $wp_query, $wp_the_query;

		if ( $wp_query instanceof \WP_Query ) {
			$wp_query->set_404();
		}

		if ( $wp_the_query instanceof \WP_Query && $wp_the_query !== $wp_query ) {
			$wp_the_query->set_404();
		}

		status_header( 404 );
		nocache_headers();
	}

	/**
	 * Ensure block-theme 404 templates receive core block styles in the admin bootstrap path.
	 *
	 * @return void
	 */
	private function enqueue_frontend_not_found_block_styles() {
		$version = get_bloginfo( 'version' );
		$styles  = array(
			'wp-block-site-title'      => 'site-title',
			'wp-block-page-list'       => 'page-list',
			'wp-block-navigation'      => 'navigation',
			'wp-block-group'           => 'group',
			'wp-block-image'           => 'image',
			'wp-block-heading'         => 'heading',
			'wp-block-paragraph'       => 'paragraph',
			'wp-block-search'          => 'search',
			'wp-block-columns'         => 'columns',
			'wp-block-spacer'          => 'spacer',
			'wp-block-navigation-link' => 'navigation-link',
		);

		foreach ( $styles as $handle => $block ) {
			if ( ! wp_style_is( $handle, 'registered' ) ) {
				wp_register_style(
					$handle,
					includes_url( 'blocks/' . $block . '/style.min.css' ),
					array(),
					$version
				);
			}

			wp_enqueue_style( $handle );
		}
	}

	/**
	 * Prepare server globals so core login treats the routed request as wp-login.php.
	 *
	 * @return void
	 */
	private function prepare_server_globals_for_core_login() {
		global $pagenow;

		$pagenow                   = 'wp-login.php';
		$_SERVER['SCRIPT_NAME']    = '/wp-login.php';
		$_SERVER['PHP_SELF']       = '/wp-login.php';
		$_SERVER['SCRIPT_FILENAME'] = ABSPATH . 'wp-login.php';
	}

	/**
	 * Determine whether the provided path points to wp-login.php.
	 *
	 * @param string $path Path or URL path string.
	 * @return bool
	 */
	private function is_wp_login_path( $path ) {
		$parsed_path = wp_parse_url( (string) $path, PHP_URL_PATH );
		$parsed_path = is_string( $parsed_path ) ? $parsed_path : (string) $path;

		return 'wp-login.php' === wp_basename( $parsed_path );
	}

	/**
	 * Detect REST requests without relying on frontend template flow.
	 *
	 * @return bool
	 */
	private function is_rest_request() {
		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return true;
		}

		if ( isset( $_GET['rest_route'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only REST detection for request routing.
			return true;
		}

		$request_uri = isset( $_SERVER['REQUEST_URI'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only REST detection for request routing.
			? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only REST detection for request routing.
			: '';

		if ( '' === $request_uri ) {
			return false;
		}

		return false !== strpos( $request_uri, '/' . trailingslashit( rest_get_url_prefix() ) );
	}

	/**
	 * Detect CLI requests safely.
	 *
	 * @return bool
	 */
	private function is_wp_cli_request() {
		return defined( 'WP_CLI' ) && WP_CLI;
	}

	/**
	 * Load normalized plugin settings.
	 *
	 * @return array<string,mixed>
	 */
	private function get_settings() {
		return $this->settings_repository->get_settings();
	}
}

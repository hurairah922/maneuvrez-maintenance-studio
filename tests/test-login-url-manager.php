<?php
/**
 * Login URL manager regression tests.
 *
 * @package MaintenanceModeStudio
 */

use Maneuvrez\MaintenanceModeStudio\Security\LoginUrlManager;
use Maneuvrez\MaintenanceModeStudio\Security\Sanitizer;
use Maneuvrez\MaintenanceModeStudio\Settings\SettingsRepository;

/**
 * Covers custom login slug sanitization and routing helpers.
 */
class Test_MMSM_Login_Url_Manager extends WP_UnitTestCase {
	/**
	 * Preserve globals touched by routing helpers.
	 *
	 * @var array<string,mixed>
	 */
	private $original_get = array();

	/**
	 * Preserve globals touched by routing helpers.
	 *
	 * @var array<string,mixed>
	 */
	private $original_request = array();

	/**
	 * Preserve globals touched by routing helpers.
	 *
	 * @var array<string,mixed>
	 */
	private $original_server = array();

	/**
	 * Preserve globals touched by frontend 404 context helpers.
	 *
	 * @var mixed
	 */
	private $original_current_screen;

	/**
	 * Preserve globals touched by frontend 404 context helpers.
	 *
	 * @var mixed
	 */
	private $original_pagenow;

	/**
	 * Set up fixtures.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		$this->original_get     = $_GET;
		$this->original_request = $_REQUEST;
		$this->original_server  = $_SERVER;

		$this->original_current_screen = isset( $GLOBALS['current_screen'] ) ? $GLOBALS['current_screen'] : null;
		$this->original_pagenow        = isset( $GLOBALS['pagenow'] ) ? $GLOBALS['pagenow'] : null;
	}

	/**
	 * Restore globals after each test.
	 *
	 * @return void
	 */
	public function tear_down() {
		$_GET     = $this->original_get;
		$_REQUEST = $this->original_request;
		$_SERVER  = $this->original_server;

		$GLOBALS['current_screen'] = $this->original_current_screen;
		$GLOBALS['pagenow']        = $this->original_pagenow;

		remove_action( 'wp_body_open', 'wp_admin_bar_render', 0 );
		remove_action( 'wp_footer', 'wp_admin_bar_render', 1000 );
		remove_action( 'template_redirect', '_wp_admin_bar_init', 0 );
		remove_action( 'wp_head', 'wp_admin_bar_header' );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'admin_bar_menu', 'wp_admin_bar_edit_menu', 80 );

		parent::tear_down();
	}

	/**
	 * The custom login slug should be normalized and reserved paths rejected.
	 *
	 * @return void
	 */
	public function test_custom_login_slug_sanitization() {
		$this->assertSame( 'secure-admin', Sanitizer::sanitize_custom_login_slug( '/Secure Admin/' ) );
		$this->assertSame( 'go', Sanitizer::sanitize_custom_login_slug( '/go/' ) );
		$this->assertSame( '', Sanitizer::sanitize_custom_login_slug( 'login' ) );
		$this->assertSame( '', Sanitizer::sanitize_custom_login_slug( 'x' ) );
	}

	/**
	 * wp_login_url() should resolve to the saved custom slug.
	 *
	 * @return void
	 */
	public function test_filter_login_url_uses_custom_slug() {
		$manager  = $this->create_manager();
		$redirect = admin_url();
		$actual   = $manager->filter_login_url( wp_login_url(), $redirect, true );
		$expected = add_query_arg(
			array(
				'redirect_to' => $redirect,
				'reauth'      => '1',
			),
			home_url( '/secure-admin/' )
		);

		$this->assertSame( $expected, $actual );
	}

	/**
	 * Core lost password URLs should inherit the custom slug.
	 *
	 * @return void
	 */
	public function test_filter_site_url_preserves_login_query_args() {
		$manager  = $this->create_manager();
		$actual   = $manager->filter_site_url( site_url( 'wp-login.php?action=lostpassword', 'login' ), 'wp-login.php?action=lostpassword', 'login', null );
		$expected = add_query_arg(
			array(
				'action' => 'lostpassword',
			),
			home_url( '/secure-admin/' )
		);

		$this->assertSame( $expected, $actual );
	}

	/**
	 * Required recovery and logout states should remain allowed on wp-login.php.
	 *
	 * @return void
	 */
	public function test_required_direct_wp_login_states_remain_allowed() {
		$manager = $this->create_manager();

		$_SERVER['REQUEST_METHOD'] = 'GET';
		$_GET['checkemail']        = 'confirm';
		$_REQUEST                  = $_GET;

		$this->assertTrue( $this->invoke_private_method( $manager, 'should_allow_direct_wp_login_request' ) );

		$_GET     = array( 'action' => 'lostpassword' );
		$_REQUEST = $_GET;

		$this->assertTrue( $this->invoke_private_method( $manager, 'should_allow_direct_wp_login_request' ) );

		$_GET     = array();
		$_REQUEST = array();

		$this->assertFalse( $this->invoke_private_method( $manager, 'should_allow_direct_wp_login_request' ) );
	}

	/**
	 * Only the wp-admin index entry points should be blocked for logged-out GETs.
	 *
	 * @return void
	 */
	public function test_wp_admin_block_targets_only_index_paths() {
		$manager = $this->create_manager();

		$_SERVER['REQUEST_METHOD'] = 'GET';
		$_SERVER['REQUEST_URI']    = '/wp-admin/';

		$this->assertTrue( $this->invoke_private_method( $manager, 'should_block_wp_admin_request' ) );

		$_SERVER['REQUEST_URI'] = '/wp-admin/index.php';

		$this->assertTrue( $this->invoke_private_method( $manager, 'should_block_wp_admin_request' ) );

		$_SERVER['REQUEST_URI'] = '/wp-admin/admin-ajax.php';

		$this->assertFalse( $this->invoke_private_method( $manager, 'should_block_wp_admin_request' ) );
	}

	/**
	 * Default admin routes should use 404 mode unless redirect is explicitly saved.
	 *
	 * @return void
	 */
	public function test_admin_block_mode_defaults_to_404() {
		$manager = $this->create_manager();

		$this->assertSame( '404', $this->invoke_private_method( $manager, 'get_custom_login_block_mode', array( array() ) ) );
		$this->assertSame( 'redirect', $this->invoke_private_method( $manager, 'get_custom_login_block_mode', array( array( 'custom_login_block_mode' => 'redirect' ) ) ) );
	}

	/**
	 * Admin-only toolbar callbacks should not run during the synthetic frontend 404.
	 *
	 * @return void
	 */
	public function test_frontend_404_removes_admin_bar_edit_callback() {
		global $show_admin_bar;

		$manager = $this->create_manager();

		show_admin_bar( true );
		add_action( 'wp_body_open', 'wp_admin_bar_render', 0 );
		add_action( 'wp_footer', 'wp_admin_bar_render', 1000 );
		add_action( 'template_redirect', '_wp_admin_bar_init', 0 );
		add_action( 'wp_head', 'wp_admin_bar_header' );
		add_action( 'wp_print_styles', 'print_emoji_styles' );
		add_action( 'admin_bar_menu', 'wp_admin_bar_edit_menu', 80 );

		$this->assertSame( 0, has_action( 'wp_body_open', 'wp_admin_bar_render' ) );
		$this->assertSame( 1000, has_action( 'wp_footer', 'wp_admin_bar_render' ) );
		$this->assertSame( 0, has_action( 'template_redirect', '_wp_admin_bar_init' ) );
		$this->assertSame( 10, has_action( 'wp_head', 'wp_admin_bar_header' ) );
		$this->assertSame( 10, has_action( 'wp_print_styles', 'print_emoji_styles' ) );
		$this->assertSame( 80, has_action( 'admin_bar_menu', 'wp_admin_bar_edit_menu' ) );

		$this->invoke_private_method( $manager, 'remove_admin_context_callbacks' );

		$this->assertFalse( $show_admin_bar );
		$this->assertFalse( has_action( 'wp_body_open', 'wp_admin_bar_render' ) );
		$this->assertFalse( has_action( 'wp_footer', 'wp_admin_bar_render' ) );
		$this->assertFalse( has_action( 'template_redirect', '_wp_admin_bar_init' ) );
		$this->assertFalse( has_action( 'wp_head', 'wp_admin_bar_header' ) );
		$this->assertFalse( has_action( 'wp_print_styles', 'print_emoji_styles' ) );
		$this->assertFalse( has_action( 'admin_bar_menu', 'wp_admin_bar_edit_menu' ) );
	}

	/**
	 * Synthetic admin 404 rendering should use frontend conditionals.
	 *
	 * @return void
	 */
	public function test_frontend_404_sets_front_screen_context() {
		$manager = $this->create_manager();

		$this->invoke_private_method( $manager, 'prepare_frontend_request_context' );

		$this->assertFalse( is_admin() );
		$this->assertSame( 'index.php', $GLOBALS['pagenow'] );
	}

	/**
	 * Create a manager with a fixed active custom login configuration.
	 *
	 * @return LoginUrlManager
	 */
	private function create_manager() {
		$repository = $this->getMockBuilder( SettingsRepository::class )
			->onlyMethods( array( 'get_settings' ) )
			->getMock();

		$repository->method( 'get_settings' )->willReturn(
			array(
				'custom_login_enabled' => 1,
				'custom_login_slug'    => 'secure-admin',
			)
		);

		return new LoginUrlManager( $repository );
	}

	/**
	 * Invoke a private manager helper for focused assertions.
	 *
	 * @param object $object Target object.
	 * @param string $method Method name.
	 * @param array<int,mixed> $args Method arguments.
	 * @return mixed
	 */
	private function invoke_private_method( $object, $method, array $args = array() ) {
		$reflection = new ReflectionMethod( $object, $method );
		$reflection->setAccessible( true );

		return $reflection->invokeArgs( $object, $args );
	}
}

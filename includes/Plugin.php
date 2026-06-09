<?php
/**
 * Core plugin bootstrap class.
 *
 * @package MaintenanceModeStudio
 */

namespace Maneuvrez\MaintenanceModeStudio;

use Maneuvrez\MaintenanceModeStudio\Admin\Admin;
use Maneuvrez\MaintenanceModeStudio\Frontend\MaintenanceRouter;
use Maneuvrez\MaintenanceModeStudio\Frontend\TemplateRenderer;

defined( 'ABSPATH' ) || exit;

/**
 * Orchestrates the Phase 1 admin and frontend services.
 */
class Plugin {
	/**
	 * Admin settings controller.
	 *
	 * @var Admin
	 */
	private $admin;

	/**
	 * Frontend maintenance router.
	 *
	 * @var MaintenanceRouter
	 */
	private $router;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$renderer     = new TemplateRenderer();
		$this->admin  = new Admin();
		$this->router = new MaintenanceRouter( $renderer );
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function run() {
		add_action( 'init', array( $this, 'load_textdomain' ) );

		$this->admin->register();
		$this->router->register();
	}

	/**
	 * Load translation files.
	 *
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			MMSM_TEXT_DOMAIN,
			false,
			dirname( MMSM_PLUGIN_BASENAME ) . '/languages'
		);
	}
}

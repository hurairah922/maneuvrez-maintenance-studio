<?php
/**
 * Core plugin bootstrap class.
 *
 * @package MaintenanceModeStudio
 */

namespace Maneuvrez\MaintenanceModeStudio;

use Maneuvrez\MaintenanceModeStudio\Admin\Admin;
use Maneuvrez\MaintenanceModeStudio\Components\ComponentRegistry;
use Maneuvrez\MaintenanceModeStudio\Frontend\MaintenanceRouter;
use Maneuvrez\MaintenanceModeStudio\Frontend\TemplateRegistry;
use Maneuvrez\MaintenanceModeStudio\Frontend\TemplateRenderer;
use Maneuvrez\MaintenanceModeStudio\Security\Sanitizer;
use Maneuvrez\MaintenanceModeStudio\Settings\SettingsRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Orchestrates the plugin admin and frontend services.
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
		$settings_repository = new SettingsRepository();
		$template_registry   = new TemplateRegistry();
		$component_registry  = new ComponentRegistry();
		$renderer            = new TemplateRenderer( $template_registry, $component_registry, $settings_repository );

		$this->admin  = new Admin( $settings_repository );
		$this->router = new MaintenanceRouter( $renderer, $settings_repository );
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function run() {
		$this->maybe_seed_settings();

		$this->admin->register();
		$this->router->register();
	}

	/**
	 * Ensure the current settings option exists and migrate legacy data once.
	 *
	 * @return void
	 */
	private function maybe_seed_settings() {
		$settings = get_option( MMSM_SETTINGS_OPTION, false );

		if ( false !== $settings ) {
			return;
		}

		$legacy_settings = get_option( MMSM_LEGACY_SETTINGS_OPTION, array() );
		add_option( MMSM_SETTINGS_OPTION, Sanitizer::get_settings( $legacy_settings ) );
	}

}

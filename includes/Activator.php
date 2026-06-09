<?php
/**
 * Plugin activation logic.
 *
 * @package MaintenanceModeStudio
 */

namespace Maneuvrez\MaintenanceModeStudio;

use Maneuvrez\MaintenanceModeStudio\Security\Sanitizer;

defined( 'ABSPATH' ) || exit;

/**
 * Handles activation setup.
 */
class Activator {
	/**
	 * Run activation tasks.
	 *
	 * @return void
	 */
	public static function activate() {
		$settings = get_option( MMSM_SETTINGS_OPTION, false );

		if ( false === $settings ) {
			$legacy_settings = get_option( MMSM_LEGACY_SETTINGS_OPTION, array() );
			add_option( MMSM_SETTINGS_OPTION, Sanitizer::get_settings( $legacy_settings ) );
		} else {
			update_option( MMSM_SETTINGS_OPTION, Sanitizer::get_settings( $settings ) );
		}

		update_option( MMSM_VERSION_OPTION, MMSM_VERSION );
	}
}

<?php
/**
 * Plugin activation logic.
 *
 * @package MaintenanceModeStudio
 */

namespace Maneuvrez\MaintenanceModeStudio;

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
		$settings = get_option( MMSM_SETTINGS_OPTION, array() );

		if ( ! is_array( $settings ) || ! array_key_exists( 'enabled', $settings ) ) {
			add_option(
				MMSM_SETTINGS_OPTION,
				array(
					'enabled' => 0,
				)
			);
		}

		update_option( MMSM_VERSION_OPTION, MMSM_VERSION );
	}
}

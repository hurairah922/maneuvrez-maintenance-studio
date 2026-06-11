<?php
/**
 * Settings repository wrapper.
 *
 * @package MaintenanceModeStudio
 */

namespace Maneuvrez\MaintenanceModeStudio\Settings;

use Maneuvrez\MaintenanceModeStudio\Security\Sanitizer;

defined( 'ABSPATH' ) || exit;

/**
 * Loads normalized plugin settings from the options table.
 */
class SettingsRepository {
	/**
	 * Return sanitized settings.
	 *
	 * @return array<string,mixed>
	 */
	public function get_settings() {
		return Sanitizer::get_settings( get_option( MMSM_SETTINGS_OPTION, array() ) );
	}
}

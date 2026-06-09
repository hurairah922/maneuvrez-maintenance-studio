<?php
/**
 * Plugin deactivation logic.
 *
 * @package MaintenanceModeStudio
 */

namespace Maneuvrez\MaintenanceModeStudio;

defined( 'ABSPATH' ) || exit;

/**
 * Handles deactivation cleanup.
 */
class Deactivator {
	/**
	 * Run deactivation tasks.
	 *
	 * @return void
	 */
	public static function deactivate() {
		// Reserved for future cleanup hooks that should not delete user settings.
	}
}

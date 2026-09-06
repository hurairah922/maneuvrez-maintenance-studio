<?php
/**
 * Plugin deactivation logic.
 *
 * @package MaintenanceModeStudio
 */

namespace Maneuvrez\MaintenanceModeStudio;

use Maneuvrez\MaintenanceModeStudio\Countdown\CountdownScheduler;

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
		( new CountdownScheduler() )->clear_all();
	}
}

<?php
/**
 * Input sanitization helpers.
 *
 * @package MaintenanceModeStudio
 */

namespace Maneuvrez\MaintenanceModeStudio\Security;

defined( 'ABSPATH' ) || exit;

/**
 * Sanitizes plugin settings and request data.
 */
class Sanitizer {
	/**
	 * Sanitize Phase 1 settings payload.
	 *
	 * @param mixed $input Raw request data.
	 * @return array<string,int>
	 */
	public static function sanitize_settings( $input ) {
		$input = is_array( $input ) ? $input : array();

		return array(
			'enabled' => ! empty( $input['enabled'] ) ? 1 : 0,
		);
	}
}

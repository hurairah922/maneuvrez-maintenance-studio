<?php
/**
 * Shared frontend component contract.
 *
 * @package MaintenanceModeStudio
 */

namespace Maneuvrez\MaintenanceModeStudio\Components;

defined( 'ABSPATH' ) || exit;

/**
 * Defines the minimum contract for frontend components.
 */
interface ComponentInterface {
	/**
	 * Return the unique component key.
	 *
	 * @return string
	 */
	public function get_key();

	/**
	 * Return the display label.
	 *
	 * @return string
	 */
	public function get_label();

	/**
	 * Return supported template zones.
	 *
	 * @return array<int,string>
	 */
	public function get_supported_zones();

	/**
	 * Return the component settings schema.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public function get_settings_schema();

	/**
	 * Render the component markup.
	 *
	 * @param array<string,mixed> $settings Normalized settings.
	 * @param array<string,mixed> $context Shared template context.
	 * @return string
	 */
	public function render( array $settings, array $context = array() );
}

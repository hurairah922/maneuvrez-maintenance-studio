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
	 * Default plugin settings.
	 *
	 * @return array<string,int|string>
	 */
	public static function get_default_settings() {
		return array(
			'enabled'           => 0,
			'mode_type'         => 'maintenance',
			'page_title'        => 'Maintenance Mode',
			'message'           => 'Our website is currently undergoing scheduled maintenance. Please check back soon.',
			'theme_mode'        => 'light',
			'primary_color'     => '#2563eb',
			'show_login_button' => 1,
		);
	}

	/**
	 * Read settings and merge them with defaults.
	 *
	 * @param mixed $settings Raw option value.
	 * @return array<string,int|string>
	 */
	public static function get_settings( $settings = null ) {
		if ( ! is_array( $settings ) ) {
			$settings = get_option( MMSM_SETTINGS_OPTION, array() );
		}

		return self::sanitize_settings( wp_parse_args( $settings, self::get_default_settings() ) );
	}

	/**
	 * Sanitize the plugin settings payload.
	 *
	 * @param mixed $input Raw request data.
	 * @return array<string,int|string>
	 */
	public static function sanitize_settings( $input ) {
		$defaults = self::get_default_settings();
		$input    = is_array( $input ) ? $input : array();

		$mode_type = isset( $input['mode_type'] ) ? sanitize_key( $input['mode_type'] ) : $defaults['mode_type'];
		if ( ! in_array( $mode_type, array( 'maintenance', 'coming_soon' ), true ) ) {
			$mode_type = $defaults['mode_type'];
		}

		$page_title = isset( $input['page_title'] ) ? sanitize_text_field( $input['page_title'] ) : '';
		if ( '' === $page_title ) {
			$page_title = $defaults['page_title'];
		}

		$message = isset( $input['message'] ) ? sanitize_textarea_field( $input['message'] ) : '';
		if ( '' === $message ) {
			$message = $defaults['message'];
		}

		$theme_mode = isset( $input['theme_mode'] ) ? sanitize_key( $input['theme_mode'] ) : $defaults['theme_mode'];
		if ( ! in_array( $theme_mode, array( 'light', 'dark' ), true ) ) {
			$theme_mode = $defaults['theme_mode'];
		}

		$primary_color = isset( $input['primary_color'] ) ? sanitize_hex_color( $input['primary_color'] ) : '';
		if ( empty( $primary_color ) ) {
			$primary_color = $defaults['primary_color'];
		}

		return array(
			'enabled'           => ! empty( $input['enabled'] ) ? 1 : 0,
			'mode_type'         => $mode_type,
			'page_title'        => $page_title,
			'message'           => $message,
			'theme_mode'        => $theme_mode,
			'primary_color'     => $primary_color,
			'show_login_button' => ! empty( $input['show_login_button'] ) ? 1 : 0,
		);
	}
}

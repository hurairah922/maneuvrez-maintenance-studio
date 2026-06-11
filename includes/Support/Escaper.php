<?php
/**
 * Lightweight escaping helpers for frontend rendering.
 *
 * @package MaintenanceModeStudio
 */

namespace Maneuvrez\MaintenanceModeStudio\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Centralizes small frontend escaping helpers.
 */
class Escaper {
	/**
	 * Build a safe class list string.
	 *
	 * @param array<int,string> $classes Raw class names.
	 * @return string
	 */
	public static function classes( array $classes ) {
		$classes = array_filter(
			array_map(
				static function ( $class ) {
					return is_string( $class ) ? sanitize_html_class( $class ) : '';
				},
				$classes
			)
		);

		return implode( ' ', $classes );
	}

	/**
	 * Build a safe inline CSS variable declaration string.
	 *
	 * @param array<string,string> $variables Variables keyed without the leading `--`.
	 * @return string
	 */
	public static function css_variables( array $variables ) {
		$declarations = array();

		foreach ( $variables as $key => $value ) {
			$variable_name = preg_replace( '/[^a-z0-9_-]/i', '', (string) $key );
			$variable_value = preg_replace( '/[^#(),.% 0-9a-z_-]/i', '', (string) $value );

			if ( '' === $variable_name || '' === $variable_value ) {
				continue;
			}

			$declarations[] = '--' . $variable_name . ':' . trim( $variable_value );
		}

		return implode( ';', $declarations ) . ( empty( $declarations ) ? '' : ';' );
	}

	/**
	 * Return a validated public URL or an empty string.
	 *
	 * @param string $url Raw URL.
	 * @return string
	 */
	public static function public_url( $url ) {
		$url = esc_url_raw( (string) $url, array( 'http', 'https' ) );

		if ( empty( $url ) || ! wp_http_validate_url( $url ) ) {
			return '';
		}

		return $url;
	}

	/**
	 * Return a validated mailto URL or an empty string.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	public static function email_url( $value ) {
		$value = trim( (string) $value );

		if ( '' === $value ) {
			return '';
		}

		if ( 0 === strpos( strtolower( $value ), 'mailto:' ) ) {
			$email = trim( substr( $value, 7 ) );
		} else {
			$email = $value;
		}

		$email = sanitize_email( $email );

		if ( ! is_email( $email ) ) {
			return '';
		}

		return 'mailto:' . $email;
	}
}

<?php
/**
 * Input sanitization helpers.
 *
 * @package MaintenanceModeStudio
 */

namespace Maneuvrez\MaintenanceModeStudio\Security;

use Maneuvrez\MaintenanceModeStudio\Components\SocialLinksComponent;
use Maneuvrez\MaintenanceModeStudio\Settings\SettingsSchema;
use Maneuvrez\MaintenanceModeStudio\Support\Escaper;

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
		return SettingsSchema::get_default_settings();
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
		$settings = $defaults;

		$settings['enabled']           = ! empty( $input['enabled'] ) ? 1 : 0;
		$settings['show_progress']     = ! empty( $input['show_progress'] ) ? 1 : 0;
		$settings['show_login_button'] = ! empty( $input['show_login_button'] ) ? 1 : 0;

		$settings['mode_type']    = self::sanitize_choice( $input, 'mode_type', array( 'maintenance', 'coming_soon' ), $defaults );
		$settings['template_key'] = self::sanitize_choice( $input, 'template_key', array( 'default' ), $defaults );
		$settings['theme_mode']   = self::sanitize_choice( $input, 'theme_mode', array( 'light', 'dark', 'system' ), $defaults );

		$settings['page_title']             = self::sanitize_text( $input, 'page_title', $defaults );
		$settings['message']                = self::sanitize_textarea( $input, 'message', $defaults );
		$settings['hero_eyebrow']           = self::sanitize_text( $input, 'hero_eyebrow', $defaults, false );
		$settings['primary_action_label']   = self::sanitize_text( $input, 'primary_action_label', $defaults, false );
		$settings['secondary_action_label'] = self::sanitize_text( $input, 'secondary_action_label', $defaults, false );
		$settings['contact_label']          = self::sanitize_text( $input, 'contact_label', $defaults );
		$settings['contact_message']        = self::sanitize_text( $input, 'contact_message', $defaults );
		$settings['status_label']           = self::sanitize_text( $input, 'status_label', $defaults );
		$settings['login_label']            = self::sanitize_text( $input, 'login_label', $defaults );

		$settings['primary_action_url']   = self::sanitize_url( $input, 'primary_action_url' );
		$settings['secondary_action_url'] = self::sanitize_url( $input, 'secondary_action_url' );
		$settings['social_x_url']         = self::sanitize_url( $input, 'social_x_url' );
		$settings['social_instagram_url'] = self::sanitize_url( $input, 'social_instagram_url' );
		$settings['social_facebook_url']  = self::sanitize_url( $input, 'social_facebook_url' );
		$settings['social_linkedin_url']  = self::sanitize_url( $input, 'social_linkedin_url' );

		$settings['contact_email'] = isset( $input['contact_email'] ) ? sanitize_email( $input['contact_email'] ) : '';
		if ( ! is_email( $settings['contact_email'] ) ) {
			$settings['contact_email'] = '';
		}

		$settings['background_color']   = self::sanitize_hex_color_setting( $input, 'background_color', $defaults );
		$settings['surface_color']      = self::sanitize_hex_color_setting( $input, 'surface_color', $defaults );
		$settings['primary_color']      = self::sanitize_hex_color_setting( $input, 'primary_color', $defaults );
		$settings['heading_text_color'] = self::sanitize_hex_color_setting( $input, 'heading_text_color', $defaults );
		$settings['body_text_color']    = self::sanitize_hex_color_setting( $input, 'body_text_color', $defaults );
		$settings['muted_text_color']   = self::sanitize_hex_color_setting( $input, 'muted_text_color', $defaults );
		$settings['link_text_color']    = self::sanitize_hex_color_setting( $input, 'link_text_color', $defaults );
		$settings['button_text_color']  = self::sanitize_hex_color_setting( $input, 'button_text_color', $defaults );
		$settings['border_color']       = self::sanitize_hex_color_setting( $input, 'border_color', $defaults );

		$progress_value = isset( $input['progress_value'] ) ? (int) $input['progress_value'] : (int) $defaults['progress_value'];
		$settings['progress_value'] = max( 0, min( 100, $progress_value ) );

		$settings = self::sanitize_social_items( $input, $settings, $defaults );

		return $settings;
	}

	/**
	 * Sanitize a select/choice field.
	 *
	 * @param array<string,mixed> $input Submitted settings.
	 * @param string              $key Field key.
	 * @param array<int,string>   $allowed Allowed values.
	 * @param array<string,mixed> $defaults Default values.
	 * @return string
	 */
	private static function sanitize_choice( array $input, $key, array $allowed, array $defaults ) {
		$value = isset( $input[ $key ] ) ? sanitize_key( $input[ $key ] ) : $defaults[ $key ];

		if ( ! in_array( $value, $allowed, true ) ) {
			return (string) $defaults[ $key ];
		}

		return $value;
	}

	/**
	 * Sanitize a plain text field.
	 *
	 * @param array<string,mixed> $input Submitted settings.
	 * @param string              $key Field key.
	 * @param array<string,mixed> $defaults Default values.
	 * @param bool                $use_default_when_empty Whether to fall back when empty.
	 * @return string
	 */
	private static function sanitize_text( array $input, $key, array $defaults, $use_default_when_empty = true ) {
		$value = isset( $input[ $key ] ) ? sanitize_text_field( $input[ $key ] ) : '';

		if ( '' === $value && $use_default_when_empty ) {
			return (string) $defaults[ $key ];
		}

		return $value;
	}

	/**
	 * Sanitize a textarea field.
	 *
	 * @param array<string,mixed> $input Submitted settings.
	 * @param string              $key Field key.
	 * @param array<string,mixed> $defaults Default values.
	 * @return string
	 */
	private static function sanitize_textarea( array $input, $key, array $defaults ) {
		$value = isset( $input[ $key ] ) ? sanitize_textarea_field( $input[ $key ] ) : '';

		if ( '' === $value ) {
			return (string) $defaults[ $key ];
		}

		return $value;
	}

	/**
	 * Sanitize a public URL field.
	 *
	 * @param array<string,mixed> $input Submitted settings.
	 * @param string              $key Field key.
	 * @return string
	 */
	private static function sanitize_url( array $input, $key ) {
		if ( ! isset( $input[ $key ] ) ) {
			return '';
		}

		return Escaper::public_url( (string) $input[ $key ] );
	}

	/**
	 * Sanitize a hex color field and fall back to defaults when invalid.
	 *
	 * @param array<string,mixed> $input Submitted settings.
	 * @param string              $key Field key.
	 * @param array<string,mixed> $defaults Default values.
	 * @return string
	 */
	private static function sanitize_hex_color_setting( array $input, $key, array $defaults ) {
		$color = isset( $input[ $key ] ) ? sanitize_hex_color( $input[ $key ] ) : '';

		if ( empty( $color ) ) {
			return (string) $defaults[ $key ];
		}

		return $color;
	}

	/**
	 * Sanitize social item fields with legacy migration fallback.
	 *
	 * @param array<string,mixed> $input Submitted settings.
	 * @param array<string,mixed> $settings Sanitized settings so far.
	 * @param array<string,mixed> $defaults Default settings.
	 * @return array<string,mixed>
	 */
	private static function sanitize_social_items( array $input, array $settings, array $defaults ) {
		$supported_platforms = array_keys( SocialLinksComponent::get_platform_labels() );
		$has_new_social_data = false;

		for ( $index = 1; $index <= 4; $index++ ) {
			$platform_key = 'social_item_' . $index . '_platform';
			$label_key    = 'social_item_' . $index . '_label';
			$url_key      = 'social_item_' . $index . '_url';
			$new_tab_key  = 'social_item_' . $index . '_new_tab';

			$platform = isset( $input[ $platform_key ] ) ? sanitize_key( $input[ $platform_key ] ) : '';
			$label    = isset( $input[ $label_key ] ) ? sanitize_text_field( $input[ $label_key ] ) : '';
			$url      = isset( $input[ $url_key ] ) ? (string) $input[ $url_key ] : '';

			if ( '' !== $platform || '' !== $label || '' !== $url || ! empty( $input[ $new_tab_key ] ) ) {
				$has_new_social_data = true;
			}

			if ( ! in_array( $platform, $supported_platforms, true ) ) {
				$platform = '';
			}

			$settings[ $platform_key ] = $platform;
			$settings[ $label_key ]    = $label;
			$settings[ $new_tab_key ]  = ! empty( $input[ $new_tab_key ] ) ? 1 : 0;

			if ( 'email' === $platform ) {
				$settings[ $url_key ] = Escaper::email_url( $url );
			} else {
				$settings[ $url_key ] = Escaper::public_url( $url );
			}
		}

		if ( $has_new_social_data ) {
			return $settings;
		}

		$legacy_map = array(
			1 => array( 'platform' => 'x', 'url_key' => 'social_x_url' ),
			2 => array( 'platform' => 'instagram', 'url_key' => 'social_instagram_url' ),
			3 => array( 'platform' => 'facebook', 'url_key' => 'social_facebook_url' ),
			4 => array( 'platform' => 'linkedin', 'url_key' => 'social_linkedin_url' ),
		);

		foreach ( $legacy_map as $index => $legacy ) {
			$legacy_url = self::sanitize_url( $input, $legacy['url_key'] );

			if ( '' === $legacy_url ) {
				continue;
			}

			$settings[ 'social_item_' . $index . '_platform' ] = $legacy['platform'];
			$settings[ 'social_item_' . $index . '_label' ]    = '';
			$settings[ 'social_item_' . $index . '_url' ]      = $legacy_url;
			$settings[ 'social_item_' . $index . '_new_tab' ]  = 0;
		}

		return $settings;
	}
}

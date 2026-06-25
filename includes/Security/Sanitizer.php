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
	 * @return array<string,mixed>
	 */
	public static function get_default_settings() {
		return SettingsSchema::get_default_settings();
	}

	/**
	 * Read settings and merge them with defaults.
	 *
	 * @param mixed $settings Raw option value.
	 * @return array<string,mixed>
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
	 * @return array<string,mixed>
	 */
	public static function sanitize_settings( $input ) {
		$defaults = self::get_default_settings();
		$input    = is_array( $input ) ? $input : array();
		$settings = $defaults;

		$settings['enabled']             = ! empty( $input['enabled'] ) ? 1 : 0;
		$settings['show_footer_section'] = ! empty( $input['show_footer_section'] ) ? 1 : 0;
		$settings['show_progress']       = ! empty( $input['show_progress'] ) ? 1 : 0;
		$settings['show_login_button']   = ! empty( $input['show_login_button'] ) ? 1 : 0;
		$settings['bypass_query_enabled'] = ! empty( $input['bypass_query_enabled'] ) ? 1 : 0;
		$settings['bypass_urls_enabled']  = ! empty( $input['bypass_urls_enabled'] ) ? 1 : 0;
		$settings['delete_data_on_uninstall'] = ! empty( $input['delete_data_on_uninstall'] ) ? 1 : 0;

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
		$settings['bypass_query_key']       = self::sanitize_bypass_query_key( isset( $input['bypass_query_key'] ) ? $input['bypass_query_key'] : $defaults['bypass_query_key'] );
		$settings['bypass_query_value']     = self::sanitize_bypass_query_value( isset( $input['bypass_query_value'] ) ? $input['bypass_query_value'] : $defaults['bypass_query_value'] );

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
		$settings['bypass_urls']    = self::sanitize_bypass_urls( isset( $input['bypass_urls'] ) ? $input['bypass_urls'] : $defaults['bypass_urls'] );

		$settings = self::sanitize_social_items( $input, $settings, $defaults );

		return $settings;
	}

	/**
	 * Sanitize a temporary bypass query key.
	 *
	 * @param mixed $value Raw setting value.
	 * @return string
	 */
	public static function sanitize_bypass_query_key( $value ) {
		$value = trim( sanitize_text_field( (string) $value ) );
		$value = preg_replace( '/[^A-Za-z0-9_-]/', '', $value );

		if ( ! is_string( $value ) ) {
			return '';
		}

		return substr( $value, 0, 60 );
	}

	/**
	 * Sanitize a temporary bypass query value.
	 *
	 * @param mixed $value Raw setting value.
	 * @return string
	 */
	public static function sanitize_bypass_query_value( $value ) {
		$value = trim( sanitize_text_field( (string) $value ) );
		$value = preg_replace( '/[^A-Za-z0-9_-]/', '', $value );

		if ( ! is_string( $value ) ) {
			return '';
		}

		return substr( $value, 0, 128 );
	}

	/**
	 * Sanitize public maintenance bypass paths.
	 *
	 * @param mixed $value Raw textarea or array payload.
	 * @return array<int,string>
	 */
	public static function sanitize_bypass_urls( $value ) {
		if ( is_array( $value ) ) {
			$entries = $value;
		} else {
			$entries = preg_split( '/\r\n|\r|\n/', (string) $value );
		}

		if ( ! is_array( $entries ) ) {
			return array();
		}

		$normalized = array();

		foreach ( $entries as $entry ) {
			$path = self::normalize_bypass_url_entry( (string) $entry );

			if ( '' === $path ) {
				continue;
			}

			$normalized[ $path ] = true;
		}

		return array_keys( $normalized );
	}

	/**
	 * Normalize a configured bypass URL or path into a canonical path.
	 *
	 * @param string $entry Raw URL or path entry.
	 * @return string
	 */
	public static function normalize_bypass_url_entry( $entry ) {
		$entry = trim( sanitize_text_field( $entry ) );

		if ( '' === $entry || false !== strpos( $entry, '*' ) ) {
			return '';
		}

		$path = '';

		if ( preg_match( '#^[a-z][a-z0-9+.-]*://#i', $entry ) ) {
			$entry_parts = wp_parse_url( $entry );

			if ( ! is_array( $entry_parts ) || ! self::is_same_site_bypass_url( $entry_parts ) ) {
				return '';
			}

			$path = isset( $entry_parts['path'] ) ? (string) $entry_parts['path'] : '/';
		} elseif ( 0 === strpos( $entry, '/' ) ) {
			$entry_parts = wp_parse_url( $entry );
			$path        = is_array( $entry_parts ) && isset( $entry_parts['path'] ) ? (string) $entry_parts['path'] : $entry;
		} else {
			return '';
		}

		return self::normalize_bypass_path( $path );
	}

	/**
	 * Normalize a request path for exact bypass comparisons.
	 *
	 * @param string $path Raw request path.
	 * @return string
	 */
	public static function normalize_bypass_path( $path ) {
		$path = trim( sanitize_text_field( $path ) );

		if ( '' === $path ) {
			return '';
		}

		$path_parts = wp_parse_url( $path );
		$path       = is_array( $path_parts ) && isset( $path_parts['path'] ) ? (string) $path_parts['path'] : $path;
		$path       = preg_replace( '#/+#', '/', $path );

		if ( ! is_string( $path ) || '' === $path ) {
			return '';
		}

		$path = '/' . ltrim( $path, '/' );
		$path = '/' === $path ? $path : untrailingslashit( $path );

		if ( self::is_disallowed_bypass_path( $path ) ) {
			return '';
		}

		return $path;
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
	 * Determine whether an absolute bypass URL belongs to the current site.
	 *
	 * @param array<string,mixed> $entry_parts Parsed URL parts.
	 * @return bool
	 */
	private static function is_same_site_bypass_url( array $entry_parts ) {
		$home_parts = wp_parse_url( home_url( '/' ) );

		if ( ! is_array( $home_parts ) || empty( $entry_parts['host'] ) || empty( $home_parts['host'] ) ) {
			return false;
		}

		if ( strtolower( (string) $entry_parts['host'] ) !== strtolower( (string) $home_parts['host'] ) ) {
			return false;
		}

		$entry_port = isset( $entry_parts['port'] ) ? (int) $entry_parts['port'] : 0;
		$home_port  = isset( $home_parts['port'] ) ? (int) $home_parts['port'] : 0;

		return 0 === $entry_port || 0 === $home_port || $entry_port === $home_port;
	}

	/**
	 * Block non-frontend routes from being stored as public bypass paths.
	 *
	 * @param string $path Normalized path.
	 * @return bool
	 */
	private static function is_disallowed_bypass_path( $path ) {
		$rest_base = '/' . trim( rest_get_url_prefix(), '/' );

		if ( '/wp-login.php' === $path || '/wp-signup.php' === $path || '/wp-activate.php' === $path || '/wp-cron.php' === $path || '/xmlrpc.php' === $path ) {
			return true;
		}

		if ( 0 === strpos( $path, '/wp-admin' ) || $path === $rest_base || 0 === strpos( $path, $rest_base . '/' ) ) {
			return true;
		}

		return false;
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
		$icon_sources        = array_keys( SocialLinksComponent::get_icon_source_labels() );
		$icon_libraries      = SocialLinksComponent::get_icon_libraries();
		$has_new_social_data = false;
		$social_links        = array();

		if (
			array_key_exists( 'social_links', $input )
			&& is_array( $input['social_links'] )
			&& ( ! empty( $input['social_links'] ) || ! self::has_legacy_social_data( $input ) )
		) {
			$has_new_social_data = true;

			foreach ( $input['social_links'] as $item ) {
				if ( ! is_array( $item ) ) {
					continue;
				}

				$platform       = isset( $item['platform'] ) ? sanitize_key( $item['platform'] ) : '';
				$url            = isset( $item['url'] ) ? (string) $item['url'] : '';
				$custom_name    = isset( $item['custom_name'] ) ? sanitize_text_field( $item['custom_name'] ) : '';
				$custom_icon_id = isset( $item['custom_icon_id'] ) ? absint( $item['custom_icon_id'] ) : 0;
				$icon_source    = isset( $item['icon_source'] ) ? sanitize_key( $item['icon_source'] ) : '';
				$icon_library   = isset( $item['icon_library'] ) ? sanitize_key( $item['icon_library'] ) : '';
				$icon_value     = isset( $item['icon_value'] ) ? sanitize_key( $item['icon_value'] ) : '';
				$icon_color     = isset( $item['icon_color'] ) ? sanitize_hex_color( $item['icon_color'] ) : '';
				$open_new_tab   = ! empty( $item['open_new_tab'] ) ? 1 : 0;

				if ( ! in_array( $platform, $supported_platforms, true ) ) {
					continue;
				}

				if ( 'email' === $platform ) {
					$url = Escaper::email_url( $url );
				} else {
					$url = Escaper::public_url( $url );
				}

				if ( '' === $url ) {
					continue;
				}

				if ( 'custom' !== $platform ) {
					$custom_name = '';
				}

				if ( '' === $icon_source ) {
					$icon_source = $custom_icon_id > 0 ? 'upload' : 'platform';
				}

				if ( ! in_array( $icon_source, $icon_sources, true ) ) {
					$icon_source = 'platform';
				}

				if ( 'upload' === $icon_source ) {
					$custom_icon_id = self::sanitize_social_icon_attachment_id( $custom_icon_id );
					$icon_library   = '';
					$icon_value     = '';
				} elseif ( 'library' === $icon_source ) {
					$custom_icon_id = 0;

					if ( ! isset( $icon_libraries[ $icon_library ] ) ) {
						$icon_source  = 'platform';
						$icon_library = '';
						$icon_value   = '';
					} else {
						$icon_choices = isset( $icon_libraries[ $icon_library ]['icons'] ) && is_array( $icon_libraries[ $icon_library ]['icons'] )
							? array_keys( $icon_libraries[ $icon_library ]['icons'] )
							: array();

						if ( ! in_array( $icon_value, $icon_choices, true ) ) {
							$icon_source  = 'platform';
							$icon_library = '';
							$icon_value   = '';
						}
					}
				} else {
					$custom_icon_id = 0;
					$icon_library   = '';
					$icon_value     = '';
				}

				$social_links[] = array(
					'platform'       => $platform,
					'url'            => $url,
					'custom_name'    => $custom_name,
					'custom_icon_id' => $custom_icon_id,
					'icon_source'    => $icon_source,
					'icon_library'   => $icon_library,
					'icon_value'     => $icon_value,
					'icon_color'     => $icon_color ? $icon_color : '',
					'open_new_tab'   => $open_new_tab,
				);
			}

			$settings['social_links'] = $social_links;
			return self::sync_legacy_social_fields( $settings, $social_links, $defaults );
		}

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

			if ( '' !== $platform && '' !== $settings[ $url_key ] ) {
				$social_links[] = array(
					'platform'       => $platform,
					'url'            => (string) $settings[ $url_key ],
					'custom_name'    => 'custom' === $platform ? $label : '',
					'custom_icon_id' => 0,
					'icon_source'    => 'platform',
					'icon_library'   => '',
					'icon_value'     => '',
					'icon_color'     => '',
					'open_new_tab'   => $settings[ $new_tab_key ],
				);
			}
		}

		if ( $has_new_social_data ) {
			$settings['social_links'] = $social_links;
			return self::sync_legacy_social_fields( $settings, $social_links, $defaults );
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
			$social_links[]                                      = array(
				'platform'       => $legacy['platform'],
				'url'            => $legacy_url,
				'custom_name'    => '',
				'custom_icon_id' => 0,
				'icon_source'    => 'platform',
				'icon_library'   => '',
				'icon_value'     => '',
				'icon_color'     => '',
				'open_new_tab'   => 0,
			);
		}

		$settings['social_links'] = $social_links;

		return self::sync_legacy_social_fields( $settings, $social_links, $defaults );
	}

	/**
	 * Determine whether the payload still contains legacy social values.
	 *
	 * @param array<string,mixed> $input Settings payload.
	 * @return bool
	 */
	private static function has_legacy_social_data( array $input ) {
		$legacy_keys = array(
			'social_x_url',
			'social_instagram_url',
			'social_facebook_url',
			'social_linkedin_url',
		);

		foreach ( $legacy_keys as $legacy_key ) {
			if ( ! empty( $input[ $legacy_key ] ) ) {
				return true;
			}
		}

		for ( $index = 1; $index <= 4; $index++ ) {
			$platform_key = 'social_item_' . $index . '_platform';
			$label_key    = 'social_item_' . $index . '_label';
			$url_key      = 'social_item_' . $index . '_url';
			$new_tab_key  = 'social_item_' . $index . '_new_tab';

			if (
				! empty( $input[ $platform_key ] )
				|| ! empty( $input[ $label_key ] )
				|| ! empty( $input[ $url_key ] )
				|| ! empty( $input[ $new_tab_key ] )
			) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Validate a custom social icon attachment id.
	 *
	 * @param int $attachment_id Attachment id.
	 * @return int
	 */
	private static function sanitize_social_icon_attachment_id( $attachment_id ) {
		if ( $attachment_id <= 0 ) {
			return 0;
		}

		$mime_type = get_post_mime_type( $attachment_id );
		$allowed   = array(
			'image/png',
			'image/jpeg',
			'image/webp',
		);

		if ( ! in_array( $mime_type, $allowed, true ) ) {
			return 0;
		}

		return $attachment_id;
	}

	/**
	 * Keep legacy social fields synchronized for backward compatibility.
	 *
	 * @param array<string,mixed> $settings Current settings.
	 * @param array<int,array<string,mixed>> $social_links Sanitized social links.
	 * @param array<string,mixed> $defaults Default settings.
	 * @return array<string,mixed>
	 */
	private static function sync_legacy_social_fields( array $settings, array $social_links, array $defaults ) {
		$legacy_social_fields = array(
			'social_x_url',
			'social_instagram_url',
			'social_facebook_url',
			'social_linkedin_url',
		);

		foreach ( $legacy_social_fields as $legacy_key ) {
			$settings[ $legacy_key ] = (string) $defaults[ $legacy_key ];
		}

		for ( $index = 1; $index <= 4; $index++ ) {
			$settings[ 'social_item_' . $index . '_platform' ] = '';
			$settings[ 'social_item_' . $index . '_label' ]    = '';
			$settings[ 'social_item_' . $index . '_url' ]      = '';
			$settings[ 'social_item_' . $index . '_new_tab' ]  = 0;
		}

		$legacy_url_map = array(
			'x'         => 'social_x_url',
			'instagram' => 'social_instagram_url',
			'facebook'  => 'social_facebook_url',
			'linkedin'  => 'social_linkedin_url',
		);

		foreach ( array_values( $social_links ) as $index => $social_link ) {
			if ( $index >= 4 ) {
				break;
			}

			$row_index = $index + 1;
			$platform  = isset( $social_link['platform'] ) ? (string) $social_link['platform'] : '';
			$url       = isset( $social_link['url'] ) ? (string) $social_link['url'] : '';
			$name      = isset( $social_link['custom_name'] ) ? (string) $social_link['custom_name'] : '';
			$new_tab   = ! empty( $social_link['open_new_tab'] ) ? 1 : 0;

			$settings[ 'social_item_' . $row_index . '_platform' ] = $platform;
			$settings[ 'social_item_' . $row_index . '_label' ]    = 'custom' === $platform ? $name : '';
			$settings[ 'social_item_' . $row_index . '_url' ]      = $url;
			$settings[ 'social_item_' . $row_index . '_new_tab' ]  = $new_tab;

			if ( isset( $legacy_url_map[ $platform ] ) ) {
				$settings[ $legacy_url_map[ $platform ] ] = $url;
			}
		}

		return $settings;
	}
}

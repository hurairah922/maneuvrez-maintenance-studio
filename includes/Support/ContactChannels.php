<?php
/**
 * Contact Channels helper methods.
 *
 * @package MaintenanceModeStudio
 */

namespace Maneuvrez\MaintenanceModeStudio\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Builds safe Contact Channels data for admin, sanitization, and rendering.
 */
class ContactChannels {
	/**
	 * Return supported channel labels.
	 *
	 * @return array<string,string>
	 */
	public static function get_channel_type_labels() {
		return array(
			'whatsapp'   => __( 'WhatsApp', 'maneuvrez-maintenance-studio' ),
			'messenger'  => __( 'Messenger', 'maneuvrez-maintenance-studio' ),
			'phone'      => __( 'Phone', 'maneuvrez-maintenance-studio' ),
			'email'      => __( 'Email', 'maneuvrez-maintenance-studio' ),
			'directions' => __( 'Directions', 'maneuvrez-maintenance-studio' ),
			'custom'     => __( 'Custom Link', 'maneuvrez-maintenance-studio' ),
		);
	}

	/**
	 * Return default visitor-facing labels.
	 *
	 * @return array<string,string>
	 */
	public static function get_default_labels() {
		return array(
			'whatsapp'   => __( 'Chat on WhatsApp', 'maneuvrez-maintenance-studio' ),
			'messenger'  => __( 'Message on Messenger', 'maneuvrez-maintenance-studio' ),
			'phone'      => __( 'Call Now', 'maneuvrez-maintenance-studio' ),
			'email'      => __( 'Email Us', 'maneuvrez-maintenance-studio' ),
			'directions' => __( 'Get Directions', 'maneuvrez-maintenance-studio' ),
			'custom'     => __( 'Open Link', 'maneuvrez-maintenance-studio' ),
		);
	}

	/**
	 * Return icon source labels.
	 *
	 * @return array<string,string>
	 */
	public static function get_icon_source_labels() {
		return array(
			'default'   => __( 'Default icon', 'maneuvrez-maintenance-studio' ),
			'dashicons' => __( 'Choose from Dashicons', 'maneuvrez-maintenance-studio' ),
			'none'      => __( 'No icon', 'maneuvrez-maintenance-studio' ),
		);
	}

	/**
	 * Return supported Dashicon choices.
	 *
	 * @return array<string,string>
	 */
	public static function get_dashicon_choices() {
		return array(
			'whatsapp'       => __( 'WhatsApp', 'maneuvrez-maintenance-studio' ),
			'facebook'       => __( 'Facebook / Messenger', 'maneuvrez-maintenance-studio' ),
			'format-chat'    => __( 'Chat', 'maneuvrez-maintenance-studio' ),
			'testimonial'    => __( 'Message', 'maneuvrez-maintenance-studio' ),
			'phone'          => __( 'Phone', 'maneuvrez-maintenance-studio' ),
			'email'          => __( 'Email', 'maneuvrez-maintenance-studio' ),
			'email-alt'      => __( 'Email Alt', 'maneuvrez-maintenance-studio' ),
			'location'       => __( 'Location', 'maneuvrez-maintenance-studio' ),
			'location-alt'   => __( 'Location Alt', 'maneuvrez-maintenance-studio' ),
			'admin-links'    => __( 'Link', 'maneuvrez-maintenance-studio' ),
			'external'       => __( 'External', 'maneuvrez-maintenance-studio' ),
			'admin-site'     => __( 'Site', 'maneuvrez-maintenance-studio' ),
			'info'           => __( 'Info', 'maneuvrez-maintenance-studio' ),
			'sos'            => __( 'Support', 'maneuvrez-maintenance-studio' ),
			'businessperson' => __( 'Business', 'maneuvrez-maintenance-studio' ),
			'groups'         => __( 'Groups', 'maneuvrez-maintenance-studio' ),
			'clock'          => __( 'Clock', 'maneuvrez-maintenance-studio' ),
			'calendar-alt'   => __( 'Calendar', 'maneuvrez-maintenance-studio' ),
			'megaphone'      => __( 'Announcement', 'maneuvrez-maintenance-studio' ),
		);
	}

	/**
	 * Return useful dial-code choices.
	 *
	 * @return array<string,string>
	 */
	public static function get_country_codes() {
		return array(
			''     => __( 'Use number as entered', 'maneuvrez-maintenance-studio' ),
			'+92'  => __( 'Pakistan +92', 'maneuvrez-maintenance-studio' ),
			'+971' => __( 'United Arab Emirates +971', 'maneuvrez-maintenance-studio' ),
			'+1'   => __( 'United States / Canada +1', 'maneuvrez-maintenance-studio' ),
			'+44'  => __( 'United Kingdom +44', 'maneuvrez-maintenance-studio' ),
			'+61'  => __( 'Australia +61', 'maneuvrez-maintenance-studio' ),
			'+966' => __( 'Saudi Arabia +966', 'maneuvrez-maintenance-studio' ),
			'+974' => __( 'Qatar +974', 'maneuvrez-maintenance-studio' ),
			'+968' => __( 'Oman +968', 'maneuvrez-maintenance-studio' ),
			'+965' => __( 'Kuwait +965', 'maneuvrez-maintenance-studio' ),
			'+973' => __( 'Bahrain +973', 'maneuvrez-maintenance-studio' ),
			'+91'  => __( 'India +91', 'maneuvrez-maintenance-studio' ),
			'+880' => __( 'Bangladesh +880', 'maneuvrez-maintenance-studio' ),
		);
	}

	/**
	 * Normalize a dial code.
	 *
	 * @param mixed $value Raw dial code.
	 * @return string
	 */
	public static function normalize_country_code( $value ) {
		$value = trim( (string) $value );

		if ( '' === $value ) {
			return '';
		}

		$value = '+' . preg_replace( '/\D+/', '', $value );

		if ( isset( self::get_country_codes()[ $value ] ) ) {
			return $value;
		}

		return '';
	}

	/**
	 * Normalize a phone-like value for storage.
	 *
	 * @param mixed  $value Raw phone value.
	 * @param string $country_code Selected country code.
	 * @return string
	 */
	public static function normalize_phone_value( $value, $country_code = '' ) {
		$value        = trim( (string) $value );
		$country_code = self::normalize_country_code( $country_code );

		if ( '' === $value ) {
			return '';
		}

		if ( '' !== $country_code ) {
			$country_digits = preg_replace( '/\D+/', '', $country_code );
			$local_number   = preg_replace( '/\D+/', '', $value );
			$local_number   = is_string( $local_number ) ? $local_number : '';

			if ( '' !== $country_digits && 0 === strpos( $local_number, (string) $country_digits ) ) {
				return '+' . $local_number;
			}

			$local_number = ltrim( $local_number, '0' );

			if ( '' === $local_number ) {
				return '';
			}

			return $country_code . $local_number;
		}

		if ( 0 === strpos( $value, '+' ) ) {
			$digits = preg_replace( '/\D+/', '', $value );

			return '' === $digits ? '' : '+' . $digits;
		}

		$digits = preg_replace( '/\D+/', '', $value );

		return is_string( $digits ) ? $digits : '';
	}

	/**
	 * Return the default Dashicon slug for a channel type.
	 *
	 * @param string $type Channel type.
	 * @return string
	 */
	public static function get_default_icon_slug( $type ) {
		$map = array(
			'whatsapp'   => 'whatsapp',
			'messenger'  => 'facebook',
			'phone'      => 'phone',
			'email'      => 'email-alt',
			'directions' => 'location-alt',
			'custom'     => 'admin-links',
		);

		return isset( $map[ $type ] ) ? $map[ $type ] : 'format-chat';
	}

	/**
	 * Build display-ready items from sanitized settings.
	 *
	 * @param array<string,mixed> $settings Plugin settings.
	 * @return array<int,array<string,mixed>>
	 */
	public static function build_items( array $settings ) {
		$items    = array();
		$raw_rows = isset( $settings['contact_channels_items'] ) && is_array( $settings['contact_channels_items'] )
			? $settings['contact_channels_items']
			: array();

		foreach ( $raw_rows as $raw_item ) {
			if ( ! is_array( $raw_item ) ) {
				continue;
			}

			$type = isset( $raw_item['type'] ) ? sanitize_key( $raw_item['type'] ) : '';

			if ( ! isset( self::get_channel_type_labels()[ $type ] ) ) {
				continue;
			}

			$url = self::build_item_url( $type, $raw_item );

			if ( '' === $url ) {
				continue;
			}

			$label       = isset( $raw_item['label'] ) ? trim( (string) $raw_item['label'] ) : '';
			$label       = '' !== $label ? $label : self::get_default_labels()[ $type ];
			$icon_source = isset( $raw_item['icon_source'] ) ? sanitize_key( $raw_item['icon_source'] ) : 'default';
			$icon_slug   = '';

			if ( 'none' !== $icon_source ) {
				$icon_slug = self::get_default_icon_slug( $type );

				if ( 'dashicons' === $icon_source && ! empty( $raw_item['icon_value'] ) ) {
					$maybe_icon = sanitize_key( $raw_item['icon_value'] );
					$icon_slug  = isset( self::get_dashicon_choices()[ $maybe_icon ] ) ? $maybe_icon : $icon_slug;
				}
			}

			$items[] = array(
				'type'        => $type,
				'label'       => $label,
				'url'         => $url,
				'icon_class'  => '' === $icon_slug ? '' : 'dashicons dashicons-' . $icon_slug,
				'new_tab'     => self::should_open_new_tab( $type, ! empty( $raw_item['open_new_tab'] ) ),
				'brand_class' => 'mmsm-contact-channel-' . $type,
			);
		}

		return $items;
	}

	/**
	 * Determine whether any built item uses Dashicons.
	 *
	 * @param array<string,mixed> $settings Plugin settings.
	 * @return bool
	 */
	public static function needs_dashicons( array $settings ) {
		foreach ( self::build_items( $settings ) as $item ) {
			if ( ! empty( $item['icon_class'] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Build a safe URL for a channel row.
	 *
	 * @param string              $type Channel type.
	 * @param array<string,mixed> $item Channel item.
	 * @return string
	 */
	public static function build_item_url( $type, array $item ) {
		$value = isset( $item['value'] ) ? (string) $item['value'] : '';

		if ( 'whatsapp' === $type ) {
			$digits = preg_replace( '/\D+/', '', $value );

			if ( ! is_string( $digits ) || strlen( $digits ) < 7 ) {
				return '';
			}

			$url     = 'https://wa.me/' . $digits;
			$message = isset( $item['prefilled_message'] ) ? trim( (string) $item['prefilled_message'] ) : '';

			if ( '' !== $message ) {
				$url = add_query_arg( 'text', $message, $url );
			}

			return esc_url_raw( $url, array( 'https' ) );
		}

		if ( 'phone' === $type ) {
			$phone = self::normalize_phone_value( $value );

			if ( '' === $phone || 0 !== strpos( $phone, '+' ) ) {
				return '';
			}

			return 'tel:' . $phone;
		}

		if ( 'email' === $type ) {
			return Escaper::email_url( $value );
		}

		if ( 'messenger' === $type ) {
			return self::normalize_messenger_url( $value );
		}

		if ( 'directions' === $type || 'custom' === $type ) {
			return Escaper::public_url( $value );
		}

		return '';
	}

	/**
	 * Normalize a Messenger username or URL.
	 *
	 * @param string $value Raw value.
	 * @return string
	 */
	private static function normalize_messenger_url( $value ) {
		$value = trim( $value );

		if ( '' === $value ) {
			return '';
		}

		if ( preg_match( '#^https?://#i', $value ) ) {
			$url   = Escaper::public_url( $value );
			$parts = '' !== $url ? wp_parse_url( $url ) : false;
			$host  = is_array( $parts ) && ! empty( $parts['host'] ) ? strtolower( (string) $parts['host'] ) : '';

			if ( ! in_array( $host, array( 'm.me', 'www.m.me', 'messenger.com', 'www.messenger.com', 'facebook.com', 'www.facebook.com', 'fb.com', 'www.fb.com' ), true ) ) {
				return '';
			}

			return $url;
		}

		$username = preg_replace( '/[^A-Za-z0-9._-]/', '', $value );
		$username = is_string( $username ) ? trim( $username, '.-_' ) : '';

		if ( '' === $username || strlen( $username ) > 80 ) {
			return '';
		}

		return 'https://m.me/' . rawurlencode( $username );
	}

	/**
	 * Determine target behavior by channel type.
	 *
	 * @param string $type Channel type.
	 * @param bool   $requested Whether new tab was requested.
	 * @return bool
	 */
	private static function should_open_new_tab( $type, $requested ) {
		if ( in_array( $type, array( 'phone', 'email' ), true ) ) {
			return false;
		}

		return (bool) $requested;
	}
}

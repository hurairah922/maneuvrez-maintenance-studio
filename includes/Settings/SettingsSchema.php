<?php
/**
 * Settings schema for persisted plugin options.
 *
 * @package MaintenanceModeStudio
 */

namespace Maneuvrez\MaintenanceModeStudio\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Defines the persisted settings fields and defaults.
 */
class SettingsSchema {
	/**
	 * Return all persisted settings fields.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public static function get_fields() {
		$social_fields = array();

		for ( $index = 1; $index <= 4; $index++ ) {
			$social_fields[ 'social_item_' . $index . '_platform' ] = array(
				'type'    => 'select',
				'default' => '',
			);
			$social_fields[ 'social_item_' . $index . '_label' ]    = array(
				'type'    => 'text',
				'default' => '',
			);
			$social_fields[ 'social_item_' . $index . '_url' ]      = array(
				'type'    => 'text',
				'default' => '',
			);
			$social_fields[ 'social_item_' . $index . '_new_tab' ]  = array(
				'type'    => 'checkbox',
				'default' => 0,
			);
		}

		return array(
			'countdowns'             => array(
				'type'    => 'repeater',
				'default' => self::get_default_countdowns(),
			),
			'enabled'                => array(
				'type'    => 'checkbox',
				'default' => 0,
			),
			'mode_type'              => array(
				'type'    => 'select',
				'default' => 'maintenance',
				'choices' => array( 'maintenance', 'coming_soon' ),
			),
			'template_key'           => array(
				'type'    => 'select',
				'default' => 'default',
				'choices' => array( 'default' ),
			),
			'page_title'             => array(
				'type'    => 'text',
				'default' => "We'll be back soon",
			),
			'message'                => array(
				'type'    => 'textarea',
				'default' => 'Our site is getting a quick update. Please check back shortly.',
			),
			'hero_eyebrow'           => array(
				'type'    => 'text',
				'default' => '',
			),
			'primary_action_label'   => array(
				'type'    => 'text',
				'default' => '',
			),
			'primary_action_url'     => array(
				'type'    => 'url',
				'default' => '',
			),
			'secondary_action_label' => array(
				'type'    => 'text',
				'default' => '',
			),
			'secondary_action_url'   => array(
				'type'    => 'url',
				'default' => '',
			),
			'theme_mode'             => array(
				'type'    => 'select',
				'default' => 'light',
				'choices' => array( 'light', 'dark', 'system' ),
			),
			'background_color'       => array(
				'type'    => 'color',
				'default' => '#f8fafc',
			),
			'surface_color'          => array(
				'type'    => 'color',
				'default' => '#ffffff',
			),
			'primary_color'          => array(
				'type'    => 'color',
				'default' => '#2563eb',
			),
			'heading_text_color'     => array(
				'type'    => 'color',
				'default' => '#0f172a',
			),
			'body_text_color'        => array(
				'type'    => 'color',
				'default' => '#334155',
			),
			'muted_text_color'       => array(
				'type'    => 'color',
				'default' => '#64748b',
			),
			'link_text_color'        => array(
				'type'    => 'color',
				'default' => '#2563eb',
			),
			'button_text_color'      => array(
				'type'    => 'color',
				'default' => '#ffffff',
			),
			'border_color'           => array(
				'type'    => 'color',
				'default' => '#e2e8f0',
			),
			'contact_label'          => array(
				'type'    => 'text',
				'default' => 'Need help?',
			),
			'contact_message'        => array(
				'type'    => 'text',
				'default' => 'Contact us for urgent requests.',
			),
			'contact_email'          => array(
				'type'    => 'email',
				'default' => '',
			),
			'contact_channels_enabled' => array(
				'type'    => 'checkbox',
				'default' => 0,
			),
			'contact_channels_maintenance_display' => array(
				'type'    => 'select',
				'default' => 'inside',
				'choices' => array( 'off', 'inside', 'floating', 'both' ),
			),
			'contact_channels_live_display' => array(
				'type'    => 'select',
				'default' => 'off',
				'choices' => array( 'off', 'floating' ),
			),
			'contact_channels_logged_in_visibility' => array(
				'type'    => 'select',
				'default' => 'hide_admins',
				'choices' => array( 'show_all', 'hide_admins', 'hide_logged_in' ),
			),
			'contact_channels_display_style' => array(
				'type'    => 'select',
				'default' => 'auto',
				'choices' => array( 'auto', 'row', 'reveal' ),
			),
			'contact_channels_heading' => array(
				'type'    => 'text',
				'default' => 'Need help?',
			),
			'contact_channels_description' => array(
				'type'    => 'text',
				'default' => 'Contact us while the site is being updated.',
			),
			'contact_channels_primary_label' => array(
				'type'    => 'text',
				'default' => 'Contact Us',
			),
			'contact_channels_position' => array(
				'type'    => 'select',
				'default' => 'bottom_right',
				'choices' => array( 'bottom_right', 'bottom_left', 'top_right', 'top_left' ),
			),
			'contact_channels_button_shape' => array(
				'type'    => 'select',
				'default' => 'rounded',
				'choices' => array( 'rounded', 'pill', 'circle', 'square' ),
			),
			'contact_channels_button_display' => array(
				'type'    => 'select',
				'default' => 'icon_label',
				'choices' => array( 'icon_label', 'icon_only', 'label_only' ),
			),
			'contact_channels_color_mode' => array(
				'type'    => 'select',
				'default' => 'theme',
				'choices' => array( 'theme', 'brand', 'custom' ),
			),
			'contact_channels_background_color' => array(
				'type'    => 'color',
				'default' => '',
			),
			'contact_channels_text_color' => array(
				'type'    => 'color',
				'default' => '',
			),
			'contact_channels_icon_color' => array(
				'type'    => 'color',
				'default' => '',
			),
			'contact_channels_hover_background_color' => array(
				'type'    => 'color',
				'default' => '',
			),
			'contact_channels_hover_text_color' => array(
				'type'    => 'color',
				'default' => '',
			),
			'contact_channels_items' => array(
				'type'    => 'repeater',
				'default' => array(),
			),
			'show_footer_section'    => array(
				'type'    => 'checkbox',
				'default' => 1,
			),
			'status_label'           => array(
				'type'    => 'text',
				'default' => 'Maintenance in progress',
			),
			'show_progress'          => array(
				'type'    => 'checkbox',
				'default' => 1,
			),
			'progress_value'         => array(
				'type'    => 'number',
				'default' => 65,
				'min'     => 0,
				'max'     => 100,
			),
			'show_login_button'      => array(
				'type'    => 'checkbox',
				'default' => 1,
			),
			'custom_login_enabled'    => array(
				'type'    => 'checkbox',
				'default' => 0,
			),
			'custom_login_slug'       => array(
				'type'    => 'text',
				'default' => '',
			),
			'custom_login_block_mode' => array(
				'type'    => 'select',
				'default' => '404',
				'choices' => array( '404', 'redirect' ),
			),
			'bypass_query_enabled'   => array(
				'type'    => 'checkbox',
				'default' => 0,
			),
			'bypass_query_key'       => array(
				'type'    => 'text',
				'default' => 'mmsm_preview',
			),
			'bypass_query_value'     => array(
				'type'    => 'text',
				'default' => '',
			),
			'bypass_urls_enabled'    => array(
				'type'    => 'checkbox',
				'default' => 0,
			),
			'bypass_urls'            => array(
				'type'    => 'array',
				'default' => array(),
			),
			'delete_data_on_uninstall' => array(
				'type'    => 'checkbox',
				'default' => 0,
			),
			'login_label'            => array(
				'type'    => 'text',
				'default' => 'Admin login',
			),
			'social_links_display'   => array(
				'type'    => 'select',
				'default' => 'icon_label',
				'choices' => array( 'icon_label', 'icon_only' ),
			),
			'social_links'           => array(
				'type'    => 'repeater',
				'default' => array(),
			),
			// Legacy social URL fields are preserved for safe migration.
			'social_x_url'           => array(
				'type'    => 'url',
				'default' => '',
			),
			'social_instagram_url'   => array(
				'type'    => 'url',
				'default' => '',
			),
			'social_facebook_url'    => array(
				'type'    => 'url',
				'default' => '',
			),
			'social_linkedin_url'    => array(
				'type'    => 'url',
				'default' => '',
			),
		) + $social_fields;
	}

	/**
	 * Return the normalized default countdown instance map.
	 *
	 * The keyed shape allows future instances to reuse the countdown domain
	 * without exposing a multi-countdown interface in the first release.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public static function get_default_countdowns() {
		return array(
			'maintenance' => self::get_default_countdown_instance(),
		);
	}

	/**
	 * Return the normalized defaults for a countdown instance.
	 *
	 * @return array<string,mixed>
	 */
	public static function get_default_countdown_instance() {
		return array(
			'enabled'          => 0,
			'target_timestamp' => 0,
			'heading'          => 'Launching in',
			'description'      => '',
			'show_days'        => 1,
			'show_hours'       => 1,
			'show_minutes'     => 1,
			'show_seconds'     => 1,
			'expiry_action'    => 'hold_zero',
			'finished_message' => '',
			'animation_style'  => 'slide',
			'color_mode'       => 'theme',
			'background_color' => '',
			'number_color'     => '',
			'label_color'      => '',
			'border_color'     => '',
		);
	}

	/**
	 * Return default settings.
	 *
	 * @return array<string,mixed>
	 */
	public static function get_default_settings() {
		$defaults = array();

		foreach ( self::get_fields() as $key => $field ) {
			$defaults[ $key ] = $field['default'];
		}

		return $defaults;
	}
}

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
			'delete_data_on_uninstall' => array(
				'type'    => 'checkbox',
				'default' => 0,
			),
			'login_label'            => array(
				'type'    => 'text',
				'default' => 'Admin login',
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

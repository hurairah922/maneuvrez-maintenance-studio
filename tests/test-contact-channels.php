<?php
/**
 * Contact Channels regression tests.
 *
 * @package MaintenanceModeStudio
 */

use Maneuvrez\MaintenanceModeStudio\Components\ContactChannelsComponent;
use Maneuvrez\MaintenanceModeStudio\Security\Sanitizer;
use Maneuvrez\MaintenanceModeStudio\Support\ContactChannels;

/**
 * Covers Contact Channels sanitization and URL building.
 */
class Test_MMSM_Contact_Channels extends WP_UnitTestCase {
	/**
	 * WhatsApp should normalize selected country code plus local number.
	 *
	 * @return void
	 */
	public function test_whatsapp_country_code_normalizes_to_wa_me_url() {
		$settings = Sanitizer::sanitize_settings(
			array(
				'contact_channels_enabled' => 1,
				'contact_channels_items'   => array(
					array(
						'type'              => 'whatsapp',
						'country_code'      => '+92',
						'value'             => '0300 1234567',
						'prefilled_message' => 'Hello there',
						'label'             => 'Chat now',
					),
				),
			)
		);

		$items = ContactChannels::build_items( $settings );

		$this->assertCount( 1, $items );
		$this->assertSame( 'https://wa.me/923001234567?text=Hello+there', $items[0]['url'] );
	}

	/**
	 * Resaving an already-normalized number with the same country code should not duplicate it.
	 *
	 * @return void
	 */
	public function test_country_code_is_not_duplicated_on_resave() {
		$settings = Sanitizer::sanitize_settings(
			array(
				'contact_channels_items' => array(
					array(
						'type'         => 'phone',
						'country_code' => '+92',
						'value'        => '+923001234567',
					),
				),
			)
		);

		$this->assertSame( '+923001234567', $settings['contact_channels_items'][0]['value'] );
	}

	/**
	 * Floating buttons should support all four screen corners.
	 *
	 * @return void
	 */
	public function test_top_floating_positions_are_allowed() {
		$top_left = Sanitizer::sanitize_settings(
			array(
				'contact_channels_position' => 'top_left',
			)
		);
		$top_right = Sanitizer::sanitize_settings(
			array(
				'contact_channels_position' => 'top_right',
			)
		);

		$this->assertSame( 'top_left', $top_left['contact_channels_position'] );
		$this->assertSame( 'top_right', $top_right['contact_channels_position'] );
	}

	/**
	 * A single floating channel should still use the configured floating button label.
	 *
	 * @return void
	 */
	public function test_single_floating_channel_uses_primary_label() {
		$settings = Sanitizer::sanitize_settings(
			array(
				'contact_channels_enabled'       => 1,
				'contact_channels_primary_label' => 'Contact Us',
				'contact_channels_items'         => array(
					array(
						'type'         => 'whatsapp',
						'country_code' => '+92',
						'value'        => '0300 1234567',
						'label'        => 'Chat on WhatsApp',
					),
				),
			)
		);

		$markup = ContactChannelsComponent::render_floating( $settings, 'live' );

		$this->assertStringContainsString( 'Contact Us', $markup );
		$this->assertStringNotContainsString( 'Chat on WhatsApp', $markup );
	}

	/**
	 * Selected Dashicons should survive sanitization and become the rendered icon class.
	 *
	 * @return void
	 */
	public function test_selected_dashicon_is_used_for_rendered_item() {
		$settings = Sanitizer::sanitize_settings(
			array(
				'contact_channels_items' => array(
					array(
						'type'         => 'email',
						'value'        => 'hello@example.com',
						'icon_source'  => 'dashicons',
						'icon_library' => 'dashicons',
						'icon_value'   => 'megaphone',
					),
				),
			)
		);

		$items = ContactChannels::build_items( $settings );

		$this->assertSame( 'dashicons', $settings['contact_channels_items'][0]['icon_source'] );
		$this->assertSame( 'megaphone', $settings['contact_channels_items'][0]['icon_value'] );
		$this->assertSame( 'dashicons dashicons-megaphone', $items[0]['icon_class'] );
	}

	/**
	 * Bare local WhatsApp values should not be stored without a country code.
	 *
	 * @return void
	 */
	public function test_whatsapp_rejects_local_number_without_country_code() {
		$settings = Sanitizer::sanitize_settings(
			array(
				'contact_channels_items' => array(
					array(
						'type'  => 'whatsapp',
						'value' => '0300 1234567',
					),
				),
			)
		);

		$this->assertSame( array(), $settings['contact_channels_items'] );
	}

	/**
	 * Phone rows should build tel links from international numbers.
	 *
	 * @return void
	 */
	public function test_phone_builds_tel_url() {
		$settings = Sanitizer::sanitize_settings(
			array(
				'contact_channels_items' => array(
					array(
						'type'  => 'phone',
						'value' => '+92 (300) 123-4567',
					),
				),
			)
		);

		$items = ContactChannels::build_items( $settings );

		$this->assertCount( 1, $items );
		$this->assertSame( 'tel:+923001234567', $items[0]['url'] );
	}

	/**
	 * Invalid emails and unsafe custom URLs should be removed.
	 *
	 * @return void
	 */
	public function test_invalid_email_and_custom_url_are_removed() {
		$settings = Sanitizer::sanitize_settings(
			array(
				'contact_channels_items' => array(
					array(
						'type'  => 'email',
						'value' => 'not-an-email',
					),
					array(
						'type'  => 'custom',
						'value' => 'javascript:alert(1)',
					),
				),
			)
		);

		$this->assertSame( array(), $settings['contact_channels_items'] );
	}

	/**
	 * Unknown types and invalid custom colors should be discarded.
	 *
	 * @return void
	 */
	public function test_invalid_type_and_color_are_removed() {
		$settings = Sanitizer::sanitize_settings(
			array(
				'contact_channels_background_color' => 'not-a-color',
				'contact_channels_items'            => array(
					array(
						'type'  => 'telegram',
						'value' => 'example',
					),
				),
			)
		);

		$this->assertSame( '', $settings['contact_channels_background_color'] );
		$this->assertSame( array(), $settings['contact_channels_items'] );
	}
}

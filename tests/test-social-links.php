<?php
/**
 * Social Links regression tests.
 *
 * @package MaintenanceModeStudio
 */

use Maneuvrez\MaintenanceModeStudio\Components\SocialLinksComponent;
use Maneuvrez\MaintenanceModeStudio\Security\Sanitizer;

/**
 * Covers Social Links icon sanitization and rendering.
 */
class Test_MMSM_Social_Links extends WP_UnitTestCase {
	/**
	 * Selected Dashicons should survive sanitization and render as Dashicon markup.
	 *
	 * @return void
	 */
	public function test_selected_dashicon_renders_for_social_link() {
		$settings = Sanitizer::sanitize_settings(
			array(
				'social_links' => array(
					array(
						'platform'     => 'website',
						'url'          => 'https://example.com',
						'icon_source'  => 'library',
						'icon_library' => 'dashicons',
						'icon_value'   => 'external',
					),
				),
			)
		);

		$markup = ( new SocialLinksComponent() )->render( $settings );

		$this->assertSame( 'library', $settings['social_links'][0]['icon_source'] );
		$this->assertSame( 'dashicons', $settings['social_links'][0]['icon_library'] );
		$this->assertSame( 'external', $settings['social_links'][0]['icon_value'] );
		$this->assertStringContainsString( 'dashicons dashicons-external', $markup );
	}

	/**
	 * Platform default icons should render from bundled WordPress Dashicons.
	 *
	 * @return void
	 */
	public function test_platform_default_uses_dashicon_markup() {
		$settings = Sanitizer::sanitize_settings(
			array(
				'social_links' => array(
					array(
						'platform'    => 'facebook',
						'url'         => 'https://facebook.com/example',
						'icon_source' => 'platform',
					),
				),
			)
		);

		$markup = ( new SocialLinksComponent() )->render( $settings );

		$this->assertSame( 'platform', $settings['social_links'][0]['icon_source'] );
		$this->assertStringContainsString( 'dashicons dashicons-facebook-alt', $markup );
	}

	/**
	 * Social display style should support icon-only links.
	 *
	 * @return void
	 */
	public function test_social_links_can_render_icon_only() {
		$settings = Sanitizer::sanitize_settings(
			array(
				'social_links_display' => 'icon_only',
				'social_links'         => array(
					array(
						'platform'    => 'email',
						'url'         => 'hello@example.com',
						'icon_source' => 'platform',
					),
				),
			)
		);

		$markup = ( new SocialLinksComponent() )->render( $settings );

		$this->assertSame( 'icon_only', $settings['social_links_display'] );
		$this->assertStringContainsString( 'mmsm-social-display-icon_only', $markup );
		$this->assertStringContainsString( 'aria-label="Email"', $markup );
	}

	/**
	 * Invalid social display values should fall back to icon plus label.
	 *
	 * @return void
	 */
	public function test_invalid_social_links_display_falls_back() {
		$settings = Sanitizer::sanitize_settings(
			array(
				'social_links_display' => 'label_only',
			)
		);

		$this->assertSame( 'icon_label', $settings['social_links_display'] );
	}

	/**
	 * Invalid library choices should not render broken Dashicon classes.
	 *
	 * @return void
	 */
	public function test_invalid_dashicon_falls_back_to_platform_icon() {
		$settings = Sanitizer::sanitize_settings(
			array(
				'social_links' => array(
					array(
						'platform'     => 'github',
						'url'          => 'https://github.com/example',
						'icon_source'  => 'library',
						'icon_library' => 'dashicons',
						'icon_value'   => 'not-a-real-icon',
					),
				),
			)
		);

		$markup = ( new SocialLinksComponent() )->render( $settings );

		$this->assertSame( 'platform', $settings['social_links'][0]['icon_source'] );
		$this->assertSame( '', $settings['social_links'][0]['icon_library'] );
		$this->assertSame( '', $settings['social_links'][0]['icon_value'] );
		$this->assertStringContainsString( 'dashicons dashicons-admin-links', $markup );
		$this->assertStringNotContainsString( 'dashicons-not-a-real-icon', $markup );
	}
}

<?php
/**
 * Settings ownership and preservation regression tests.
 *
 * @package MaintenanceModeStudio
 */

use Maneuvrez\MaintenanceModeStudio\Admin\Admin;
use Maneuvrez\MaintenanceModeStudio\Countdown\CountdownService;
use Maneuvrez\MaintenanceModeStudio\Security\Sanitizer;
use Maneuvrez\MaintenanceModeStudio\Settings\SettingsRepository;
use Maneuvrez\MaintenanceModeStudio\Settings\SettingsSchema;

/**
 * Proves that the current tab-based save contract cannot erase unrelated data.
 */
class Test_MMSM_Settings_Preservation extends WP_UnitTestCase {
	/**
	 * Original request data restored after each test.
	 *
	 * @var array<string,mixed>
	 */
	private $original_post = array();

	/**
	 * Original query data restored after each test.
	 *
	 * @var array<string,mixed>
	 */
	private $original_get = array();

	/**
	 * Create an administrator for the settings callback.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		$this->original_post = $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Test setup preserves request globals; production submissions use a nonce.
		$this->original_get  = $_GET; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Test setup preserves request globals; production tab selection is read-only.
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		delete_option( MMSM_SETTINGS_OPTION );
	}

	/**
	 * Restore request and option state.
	 *
	 * @return void
	 */
	public function tear_down() {
		$_POST = $this->original_post;
		$_GET  = $this->original_get;
		delete_option( MMSM_SETTINGS_OPTION );

		parent::tear_down();
	}

	/**
	 * Every schema key must belong to exactly one current task area.
	 *
	 * @return void
	 */
	public function test_current_area_ownership_covers_schema_exactly_once() {
		$owned_keys = array();

		foreach ( array_keys( $this->get_tab_payloads( $this->build_custom_settings() ) ) as $tab ) {
			$owned_keys = array_merge( $owned_keys, $this->get_tab_field_keys( $tab ) );
		}

		$this->assertSame( array(), array_diff( array_keys( SettingsSchema::get_fields() ), $owned_keys ), 'Schema keys are missing from tab ownership.' );
		$this->assertSame( array(), array_diff( $owned_keys, array_keys( SettingsSchema::get_fields() ) ), 'Tab ownership contains keys outside the schema.' );
		$this->assertSame( count( $owned_keys ), count( array_unique( $owned_keys ) ), 'A setting key is owned by more than one tab.' );
	}

	/**
	 * Saving any current area leaves every other area's normalized values intact.
	 *
	 * @return void
	 */
	public function test_each_current_area_save_preserves_every_unowned_setting() {
		$baseline = $this->build_custom_settings();

		foreach ( $this->get_tab_payloads( $baseline ) as $tab => $payload ) {
			update_option( MMSM_SETTINGS_OPTION, $baseline );
			$before = ( new SettingsRepository() )->get_settings();
			$saved  = $this->save_tab( $tab, $payload );
			$owned  = array_flip( $this->get_tab_field_keys( $tab ) );

			foreach ( $before as $key => $value ) {
				if ( isset( $owned[ $key ] ) ) {
					continue;
				}

				$this->assertSame( $value, $saved[ $key ], sprintf( '%s changed while saving the %s tab.', $key, $tab ) );
			}
		}
	}

	/**
	 * Missing checkbox and repeater controls clear only their active owner group.
	 *
	 * @return void
	 */
	public function test_omitted_controls_are_scoped_to_the_active_tab() {
		$baseline = $this->build_custom_settings();

		update_option( MMSM_SETTINGS_OPTION, $baseline );
		$access_payload = $this->get_tab_payloads( $baseline )['access_visibility'];
		unset( $access_payload['enabled'] );
		$saved = $this->save_tab(
			'access_visibility',
			$access_payload,
			array(
				'mmsm_contact_channels_present' => '1',
				'mmsm_social_links_present'     => '1',
			)
		);

		$this->assertSame( 0, $saved['enabled'] );
		$this->assertSame( $baseline['contact_channels_items'], $saved['contact_channels_items'] );
		$this->assertSame( $baseline['social_links'], $saved['social_links'] );
		$this->assertSame( 1, $saved['show_progress'] );

		update_option( MMSM_SETTINGS_OPTION, $baseline );
		$maintenance_payload = $this->get_tab_payloads( $baseline )['maintenance_page'];
		unset( $maintenance_payload['social_links'] );
		$saved = $this->save_tab(
			'maintenance_page',
			$maintenance_payload,
			array( 'mmsm_social_links_present' => '1' )
		);

		$this->assertSame( array(), $saved['social_links'] );
		$this->assertSame( $baseline['contact_channels_items'], $saved['contact_channels_items'] );

		update_option( MMSM_SETTINGS_OPTION, $baseline );
		$maintenance_payload = $this->get_tab_payloads( $baseline )['maintenance_page'];
		unset( $maintenance_payload['contact_channels_enabled'], $maintenance_payload['contact_channels_items'] );
		$saved = $this->save_tab(
			'maintenance_page',
			$maintenance_payload,
			array( 'mmsm_contact_channels_present' => '1' )
		);

		$this->assertSame( 0, $saved['contact_channels_enabled'] );
		$this->assertSame( array(), $saved['contact_channels_items'] );
		$this->assertSame( $baseline['social_links'], $saved['social_links'] );
	}

	/**
	 * Disabling optional features keeps submitted child configuration available.
	 *
	 * @return void
	 */
	public function test_disabled_optional_features_preserve_their_child_values() {
		$baseline = $this->build_custom_settings();
		$payloads = $this->get_tab_payloads( $baseline );

		update_option( MMSM_SETTINGS_OPTION, $baseline );
		$countdown_payload = $payloads['maintenance_page'];
		unset( $countdown_payload['countdowns']['maintenance']['enabled'] );
		$saved = $this->save_tab( 'maintenance_page', $countdown_payload );

		$this->assertSame( 0, $saved['countdowns']['maintenance']['enabled'] );
		$this->assertSame( 'Planned launch', $saved['countdowns']['maintenance']['heading'] );
		$this->assertSame( 'show_message', $saved['countdowns']['maintenance']['expiry_action'] );
		$this->assertSame( '#123456', $saved['countdowns']['maintenance']['number_color'] );

		update_option( MMSM_SETTINGS_OPTION, $baseline );
		$contact_payload = $payloads['maintenance_page'];
		unset( $contact_payload['contact_channels_enabled'] );
		$saved = $this->save_tab( 'maintenance_page', $contact_payload, array( 'mmsm_contact_channels_present' => '1' ) );

		$this->assertSame( 0, $saved['contact_channels_enabled'] );
		$this->assertSame( $baseline['contact_channels_items'], $saved['contact_channels_items'] );
		$this->assertSame( '#123456', $saved['contact_channels_background_color'] );

		update_option( MMSM_SETTINGS_OPTION, $baseline );
		$advanced_payload = $payloads['access_visibility'];
		unset( $advanced_payload['custom_login_enabled'] );
		$saved = $this->save_tab( 'access_visibility', $advanced_payload );

		$this->assertSame( 0, $saved['custom_login_enabled'] );
		$this->assertSame( 'private-entry', $saved['custom_login_slug'] );
		$this->assertSame( 'redirect', $saved['custom_login_block_mode'] );

		update_option( MMSM_SETTINGS_OPTION, $baseline );
		$components_payload = $payloads['maintenance_page'];
		unset( $components_payload['show_progress'] );
		$saved = $this->save_tab( 'maintenance_page', $components_payload );

		$this->assertSame( 0, $saved['show_progress'] );
		$this->assertSame( 42, $saved['progress_value'] );
	}

	/**
	 * Collapsed editor cards keep their fields in the form and preserve values.
	 *
	 * @return void
	 */
	public function test_collapsed_maintenance_cards_keep_submittable_saved_values() {
		$baseline = $this->build_custom_settings();
		update_option( MMSM_SETTINGS_OPTION, $baseline );

		$admin = new Admin();
		$admin->register_settings();
		$method = new ReflectionMethod( Admin::class, 'render_maintenance_page_editor' );
		$method->setAccessible( true );

		ob_start();
		$method->invoke( $admin );
		$output = (string) ob_get_clean();

		$this->assertSame( 6, substr_count( $output, 'data-optional-card=' ) );
		$this->assertStringContainsString( '<details class="mmsm-card-disclosure"', $output );
		$this->assertStringContainsString( 'aria-expanded="false"', $output );
		$this->assertStringContainsString( 'aria-controls="mmsm-countdown-editor"', $output );
		$this->assertStringContainsString( 'Text above heading', $output );
		$this->assertStringContainsString( 'When the countdown ends', $output );
		$this->assertStringContainsString( 'mmsm_settings[secondary_action_url]', $output );
		$this->assertStringContainsString( 'mmsm_settings[countdowns][maintenance][heading]', $output );
		$this->assertStringContainsString( 'mmsm_settings[contact_channels_items]', $output );
		$this->assertStringContainsString( 'mmsm_settings[social_links]', $output );
		$this->assertStringContainsString( 'mmsm_settings[login_label]', $output );
		$this->assertStringNotContainsString( ' disabled=', $output );
		$this->assertSame( 1, substr_count( $output, 'id="mmsm-contact-channels-enabled"' ) );
		$this->assertLessThan( strpos( $output, 'mmsm-optional-sections' ), strpos( $output, 'mmsm_settings[primary_action_url]' ) );
		$this->assertLessThan( strpos( $output, 'Customize countdown' ), strpos( $output, 'When the countdown ends' ) );

		$saved = $this->save_tab( 'maintenance_page', $this->get_tab_payloads( $baseline )['maintenance_page'] );
		$this->assertSame( $baseline['secondary_action_url'], $saved['secondary_action_url'] );
		$this->assertSame( $baseline['countdowns']['maintenance']['heading'], $saved['countdowns']['maintenance']['heading'] );
		$this->assertSame( $baseline['contact_channels_items'], $saved['contact_channels_items'] );
		$this->assertSame( $baseline['social_links'], $saved['social_links'] );
		$this->assertSame( $baseline['login_label'], $saved['login_label'] );
	}

	/**
	 * Incomplete action pairs cannot replace a valid saved action.
	 *
	 * @return void
	 */
	public function test_incomplete_action_pair_keeps_previously_saved_pair() {
		$baseline = $this->build_custom_settings();
		$payload  = $this->get_tab_payloads( $baseline )['maintenance_page'];

		update_option( MMSM_SETTINGS_OPTION, $baseline );
		$payload['primary_action_label']   = '';
		$payload['primary_action_url']     = 'https://example.com/orphaned';
		$payload['secondary_action_label'] = 'Incomplete secondary';
		$payload['secondary_action_url']   = '';
		$saved                             = $this->save_tab( 'maintenance_page', $payload );

		$this->assertSame( $baseline['primary_action_label'], $saved['primary_action_label'] );
		$this->assertSame( $baseline['primary_action_url'], $saved['primary_action_url'] );
		$this->assertSame( $baseline['secondary_action_label'], $saved['secondary_action_label'] );
		$this->assertSame( $baseline['secondary_action_url'], $saved['secondary_action_url'] );

		update_option( MMSM_SETTINGS_OPTION, $baseline );
		$payload                         = $this->get_tab_payloads( $baseline )['maintenance_page'];
		$payload['primary_action_label'] = 'Invalid destination';
		$payload['primary_action_url']   = '/relative-path';
		$saved                           = $this->save_tab( 'maintenance_page', $payload );

		$this->assertSame( $baseline['primary_action_label'], $saved['primary_action_label'] );
		$this->assertSame( $baseline['primary_action_url'], $saved['primary_action_url'] );
	}

	/**
	 * Sparse pre-schema settings gain defaults without losing recognized values.
	 *
	 * @return void
	 */
	public function test_old_sparse_settings_normalize_without_data_loss() {
		$old_settings = array(
			'enabled'              => 1,
			'page_title'           => 'Legacy maintenance title',
			'message'              => 'Legacy maintenance message',
			'primary_color'        => '#112233',
			'contact_email'        => 'legacy@example.com',
			'social_x_url'         => 'https://x.com/legacy-account',
			'custom_login_enabled' => 1,
			'custom_login_slug'    => 'legacy-entry',
			'bypass_urls_enabled'  => 1,
			'bypass_urls'          => array( '/status/' ),
		);
		$normalized   = Sanitizer::get_settings( $old_settings );

		$this->assertSame( array_keys( SettingsSchema::get_fields() ), array_keys( $normalized ) );
		$this->assertSame( 'Legacy maintenance title', $normalized['page_title'] );
		$this->assertSame( 'Legacy maintenance message', $normalized['message'] );
		$this->assertSame( '#112233', $normalized['primary_color'] );
		$this->assertSame( 'legacy@example.com', $normalized['contact_email'] );
		$this->assertSame( 'legacy-entry', $normalized['custom_login_slug'] );
		$this->assertSame( array( '/status' ), $normalized['bypass_urls'] );
		$this->assertSame( 'x', $normalized['social_links'][0]['platform'] );
		$this->assertSame( 'https://x.com/legacy-account', $normalized['social_links'][0]['url'] );
	}

	/**
	 * Current navigation exposes four areas and legacy query values resolve safely.
	 *
	 * @return void
	 */
	public function test_navigation_areas_and_legacy_tab_routing() {
		$admin       = new Admin();
		$tabs_method = new ReflectionMethod( Admin::class, 'get_tabs' );
		$tabs_method->setAccessible( true );

		$this->assertSame( array( 'maintenance_page', 'design', 'access_visibility', 'advanced' ), array_keys( $tabs_method->invoke( $admin ) ) );

		$routes = array(
			'general'          => 'maintenance_page',
			'template'         => 'maintenance_page',
			'components'       => 'maintenance_page',
			'countdown'        => 'maintenance_page',
			'contact_channels' => 'maintenance_page',
			'social_links'     => 'maintenance_page',
			'design'           => 'design',
			'advanced'         => 'advanced',
			'unknown-area'     => 'maintenance_page',
		);
		$active_method = new ReflectionMethod( Admin::class, 'get_active_tab' );
		$active_method->setAccessible( true );

		foreach ( $routes as $requested => $expected ) {
			$_GET['tab'] = $requested;
			$this->assertSame( $expected, $active_method->invoke( $admin ), sprintf( '%s did not route to %s.', $requested, $expected ) );
		}
	}

	/**
	 * Maintenance Page and Design use the same real-template preview shell.
	 *
	 * @return void
	 */
	public function test_maintenance_and_design_share_real_template_preview() {
		$admin  = new Admin();
		$method = new ReflectionMethod( Admin::class, 'render_full_page_preview' );
		$method->setAccessible( true );

		foreach ( array( 'maintenance_page', 'design' ) as $area ) {
			ob_start();
			$method->invoke( $admin, $area );
			$output = (string) ob_get_clean();

			$this->assertSame( 1, substr_count( $output, 'data-page-preview' ) );
			$this->assertStringContainsString( 'data-preview-area="' . $area . '"', $output );
			$this->assertStringContainsString( 'data-preview-expand', $output );
			$this->assertStringContainsString( 'aria-expanded="false"', $output );
			$this->assertStringContainsString( 'data-preview-close hidden', $output );
			$this->assertStringContainsString( 'data-preview-responsive-toolbar hidden', $output );
			$this->assertStringContainsString( 'data-preview-device-frame', $output );
			$this->assertStringContainsString( 'data-preview-width', $output );
			$this->assertStringContainsString( 'data-preview-height', $output );
			$this->assertStringContainsString( 'data-public-preview-frame', $output );
			$this->assertStringContainsString( 'action=mmsm_render_page_preview', $output );
			$this->assertStringContainsString( 'sandbox="allow-scripts"', $output );
			$this->assertSame( 8, substr_count( $output, 'data-preview-resize=' ) );
			$this->assertStringNotContainsString( 'data-preview-zoom', $output );
			$this->assertStringNotContainsString( 'data-countdown-admin-preview', $output );
		}
	}

	/**
	 * Return a normalized, non-default-heavy fixture spanning all ownership groups.
	 *
	 * @return array<string,mixed>
	 */
	private function build_custom_settings() {
		$settings = Sanitizer::get_default_settings();

		foreach ( SettingsSchema::get_fields() as $key => $field ) {
			switch ( $field['type'] ) {
				case 'checkbox':
					$settings[ $key ] = 1;
					break;
				case 'color':
					$settings[ $key ] = '#123456';
					break;
				case 'number':
					$settings[ $key ] = 42;
					break;
			}
		}

		$settings['mode_type']                             = 'coming_soon';
		$settings['theme_mode']                            = 'dark';
		$settings['page_title']                            = 'Configured title';
		$settings['message']                               = 'Configured message';
		$settings['hero_eyebrow']                          = 'Configured eyebrow';
		$settings['primary_action_label']                  = 'Primary action';
		$settings['primary_action_url']                    = 'https://example.com/primary';
		$settings['secondary_action_label']                = 'Secondary action';
		$settings['secondary_action_url']                  = 'https://example.com/secondary';
		$settings['status_label']                          = 'Configured status';
		$settings['contact_label']                         = 'Configured contact';
		$settings['contact_message']                       = 'Configured contact message';
		$settings['contact_email']                         = 'owner@example.com';
		$settings['custom_login_slug']                     = 'private-entry';
		$settings['custom_login_block_mode']               = 'redirect';
		$settings['bypass_query_key']                      = 'access_key';
		$settings['bypass_query_value']                    = 'access_value';
		$settings['bypass_urls']                           = array( '/public-status' );
		$settings['contact_channels_maintenance_display']  = 'both';
		$settings['contact_channels_live_display']         = 'floating';
		$settings['contact_channels_logged_in_visibility'] = 'show_all';
		$settings['contact_channels_display_style']        = 'reveal';
		$settings['contact_channels_heading']              = 'Contact heading';
		$settings['contact_channels_description']          = 'Contact description';
		$settings['contact_channels_primary_label']        = 'Open contacts';
		$settings['contact_channels_position']             = 'top_left';
		$settings['contact_channels_button_shape']         = 'pill';
		$settings['contact_channels_button_display']       = 'icon_only';
		$settings['contact_channels_color_mode']           = 'custom';
		$settings['contact_channels_items']                = array(
			array(
				'type'              => 'email',
				'country_code'      => '',
				'value'             => 'support@example.com',
				'label'             => 'Email support',
				'prefilled_message' => '',
				'icon_source'       => 'default',
				'icon_library'      => 'dashicons',
				'icon_value'        => '',
				'open_new_tab'      => 1,
			),
		);
		$settings['countdowns']['maintenance']             = array(
			'enabled'          => 1,
			'target_timestamp' => time() + DAY_IN_SECONDS,
			'heading'          => 'Planned launch',
			'description'      => 'A configured countdown',
			'show_days'        => 1,
			'show_hours'       => 1,
			'show_minutes'     => 1,
			'show_seconds'     => 1,
			'expiry_action'    => 'show_message',
			'finished_message' => 'We are live.',
			'animation_style'  => 'pulse',
			'animation_scope'  => 'both',
			'color_mode'       => 'custom',
			'background_color' => '#123456',
			'number_color'     => '#123456',
			'label_color'      => '#123456',
			'border_color'     => '#123456',
		);
		$settings['social_links_display']                  = 'icon_only';
		$settings['social_links']                          = array(
			array(
				'platform'       => 'x',
				'url'            => 'https://x.com/example',
				'custom_name'    => '',
				'custom_icon_id' => 0,
				'icon_source'    => 'platform',
				'icon_library'   => '',
				'icon_value'     => '',
				'icon_color'     => '#123456',
				'open_new_tab'   => 1,
			),
			array(
				'platform'       => 'instagram',
				'url'            => 'https://instagram.com/example',
				'custom_name'    => '',
				'custom_icon_id' => 0,
				'icon_source'    => 'platform',
				'icon_library'   => '',
				'icon_value'     => '',
				'icon_color'     => '#123456',
				'open_new_tab'   => 1,
			),
			array(
				'platform'       => 'facebook',
				'url'            => 'https://facebook.com/example',
				'custom_name'    => '',
				'custom_icon_id' => 0,
				'icon_source'    => 'platform',
				'icon_library'   => '',
				'icon_value'     => '',
				'icon_color'     => '#123456',
				'open_new_tab'   => 1,
			),
			array(
				'platform'       => 'linkedin',
				'url'            => 'https://linkedin.com/company/example',
				'custom_name'    => '',
				'custom_icon_id' => 0,
				'icon_source'    => 'platform',
				'icon_library'   => '',
				'icon_value'     => '',
				'icon_color'     => '#123456',
				'open_new_tab'   => 1,
			),
		);

		return Sanitizer::get_settings( $settings );
	}

	/**
	 * Build realistic payloads for all four task areas.
	 *
	 * @param array<string,mixed> $settings Normalized fixture.
	 * @return array<string,array<string,mixed>>
	 */
	private function get_tab_payloads( array $settings ) {
		$payloads = array();

		foreach ( array( 'maintenance_page', 'design', 'access_visibility', 'advanced' ) as $tab ) {
			$payloads[ $tab ] = array_intersect_key( $settings, array_flip( $this->get_tab_field_keys( $tab ) ) );
		}

		foreach ( array( 'social_x_url', 'social_instagram_url', 'social_facebook_url', 'social_linkedin_url' ) as $legacy_key ) {
			unset( $payloads['maintenance_page'][ $legacy_key ] );
		}

		for ( $index = 1; $index <= 4; $index++ ) {
			foreach ( array( 'platform', 'label', 'url', 'new_tab' ) as $suffix ) {
				unset( $payloads['maintenance_page'][ 'social_item_' . $index . '_' . $suffix ] );
			}
		}

		$service                   = new CountdownService();
		$countdown                 = $settings['countdowns']['maintenance'];
		$countdown['target_local'] = $service->format_local_datetime( $countdown['target_timestamp'], wp_timezone() );
		unset( $countdown['target_timestamp'] );
		$payloads['maintenance_page']['countdowns'] = array( 'maintenance' => $countdown );

		return $payloads;
	}

	/**
	 * Run the production settings callback for one tab.
	 *
	 * @param string              $tab Tab key.
	 * @param array<string,mixed> $payload Submitted option value.
	 * @param array<string,mixed> $post Extra request markers.
	 * @return array<string,mixed>
	 */
	private function save_tab( $tab, array $payload, array $post = array() ) {
		$_POST = array_merge(
			array(
				'mmsm_settings_nonce'    => wp_create_nonce( 'mmsm_save_settings' ),
				'mmsm_active_tab'        => $tab,
				'mmsm_navigation_version' => 'areas',
			),
			$post
		);

		return ( new Admin() )->sanitize_settings( $payload );
	}

	/**
	 * Read the production ownership list without changing its visibility.
	 *
	 * @param string $tab Tab key.
	 * @return array<int,string>
	 */
	private function get_tab_field_keys( $tab ) {
		$method = new ReflectionMethod( Admin::class, 'get_tab_field_keys' );
		$method->setAccessible( true );

		return $method->invoke( new Admin(), $tab );
	}
}

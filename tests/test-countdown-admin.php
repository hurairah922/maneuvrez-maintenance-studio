<?php
/**
 * Countdown administrator settings tests.
 *
 * @package MaintenanceModeStudio
 */

use Maneuvrez\MaintenanceModeStudio\Admin\Admin;
use Maneuvrez\MaintenanceModeStudio\Countdown\CountdownService;
use Maneuvrez\MaintenanceModeStudio\Security\Sanitizer;

/**
 * Covers countdown field sanitization and isolated tab saves.
 */
class Test_MMSM_Countdown_Admin extends WP_UnitTestCase {
	/** @var mixed */
	private $original_timezone_string;

	/** @var mixed */
	private $original_gmt_offset;

	/**
	 * Prepare an administrator and deterministic site timezone.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		$this->original_timezone_string = get_option( 'timezone_string' );
		$this->original_gmt_offset      = get_option( 'gmt_offset' );

		update_option( 'timezone_string', 'Asia/Karachi' );
		update_option( 'gmt_offset', 5 );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
	}

	/**
	 * Restore global state changed by each test.
	 *
	 * @return void
	 */
	public function tear_down() {
		update_option( 'timezone_string', $this->original_timezone_string );
		update_option( 'gmt_offset', $this->original_gmt_offset );
		delete_option( MMSM_SETTINGS_OPTION );
		unset( $_POST['mmsm_settings_nonce'], $_POST['mmsm_active_tab'] );

		parent::tear_down();
	}

	/**
	 * Appearance values use explicit allowlists and valid optional hex colors.
	 *
	 * @return void
	 */
	public function test_countdown_appearance_values_are_sanitized() {
		$countdowns = Sanitizer::sanitize_countdowns(
			array(
				'maintenance' => array(
					'animation_style'  => 'spin',
					'color_mode'       => 'automatic',
					'background_color' => '#123abc',
					'number_color'     => 'red',
					'label_color'      => '#ABC',
					'border_color'     => array( '#000000' ),
				),
			)
		);
		$countdown = $countdowns['maintenance'];

		$this->assertSame( 'slide', $countdown['animation_style'] );
		$this->assertSame( 'theme', $countdown['color_mode'] );
		$this->assertSame( '#123abc', $countdown['background_color'] );
		$this->assertSame( '', $countdown['number_color'] );
		$this->assertSame( '#ABC', $countdown['label_color'] );
		$this->assertSame( '', $countdown['border_color'] );
	}

	/**
	 * A Countdown-tab save converts site-local time and preserves other tabs.
	 *
	 * @return void
	 */
	public function test_countdown_tab_save_preserves_unowned_settings() {
		$existing                  = Sanitizer::get_default_settings();
		$existing['page_title']    = 'Existing title';
		$existing['theme_mode']    = 'dark';
		$existing['contact_email'] = 'owner@example.com';
		update_option( MMSM_SETTINGS_OPTION, $existing );

		$service = new CountdownService();
		$target  = time() + DAY_IN_SECONDS;
		$local   = $service->format_local_datetime( $target, wp_timezone() );
		$admin   = new Admin( null, $service );

		$_POST['mmsm_settings_nonce'] = wp_create_nonce( 'mmsm_save_settings' );
		$_POST['mmsm_active_tab']     = 'countdown';

		$saved = $admin->sanitize_settings(
			array(
				'countdowns' => array(
					'maintenance' => array(
						'enabled'         => 1,
						'target_local'    => $local,
						'heading'         => 'Opening soon',
						'description'     => 'Please check back.',
						'show_days'       => 1,
						'show_hours'      => 1,
						'show_minutes'    => 1,
						'show_seconds'    => 1,
						'expiry_action'   => 'hide',
						'animation_style' => 'pulse',
						'color_mode'      => 'theme',
					),
				),
			)
		);

		$this->assertSame( 'Existing title', $saved['page_title'] );
		$this->assertSame( 'dark', $saved['theme_mode'] );
		$this->assertSame( 'owner@example.com', $saved['contact_email'] );
		$this->assertSame( $service->parse_local_datetime( $local, wp_timezone() ), $saved['countdowns']['maintenance']['target_timestamp'] );
		$this->assertSame( 'Opening soon', $saved['countdowns']['maintenance']['heading'] );
	}

	/**
	 * Enabled countdowns cannot replace saved values with an edited past target.
	 *
	 * @return void
	 */
	public function test_past_target_keeps_previous_countdown_settings() {
		$existing = Sanitizer::get_default_settings();
		$existing['countdowns']['maintenance']['enabled']          = 1;
		$existing['countdowns']['maintenance']['target_timestamp'] = time() + DAY_IN_SECONDS;
		$existing['countdowns']['maintenance']['heading']          = 'Saved heading';
		update_option( MMSM_SETTINGS_OPTION, $existing );

		$_POST['mmsm_settings_nonce'] = wp_create_nonce( 'mmsm_save_settings' );
		$_POST['mmsm_active_tab']     = 'countdown';

		$saved = ( new Admin() )->sanitize_settings(
			array(
				'countdowns' => array(
					'maintenance' => array(
						'enabled'       => 1,
						'target_local'  => '2020-01-01T12:00',
						'heading'       => 'Should not save',
						'show_seconds'  => 1,
						'expiry_action' => 'hold_zero',
					),
				),
			)
		);

		$this->assertSame( 'Saved heading', $saved['countdowns']['maintenance']['heading'] );
		$this->assertSame( $existing['countdowns']['maintenance']['target_timestamp'], $saved['countdowns']['maintenance']['target_timestamp'] );
	}
}

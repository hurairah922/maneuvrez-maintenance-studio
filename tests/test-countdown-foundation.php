<?php
/**
 * Countdown foundation tests.
 *
 * @package MaintenanceModeStudio
 */

use Maneuvrez\MaintenanceModeStudio\Countdown\CountdownService;
use Maneuvrez\MaintenanceModeStudio\Security\Sanitizer;
use Maneuvrez\MaintenanceModeStudio\Settings\SettingsSchema;

/**
 * Covers countdown defaults, sanitization, instance validation, and state math.
 */
class Test_MMSM_Countdown_Foundation extends WP_UnitTestCase {
	/**
	 * Service under test.
	 *
	 * @var CountdownService
	 */
	private $service;

	/**
	 * Create the service for each test.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();
		$this->service = new CountdownService();
	}

	/**
	 * Upgrades receive one disabled, unconfigured maintenance instance.
	 *
	 * @return void
	 */
	public function test_countdown_defaults_are_backward_compatible() {
		$settings = Sanitizer::get_settings( array() );

		$this->assertSame( SettingsSchema::get_default_countdowns(), $settings['countdowns'] );
		$this->assertSame( 0, $settings['countdowns']['maintenance']['enabled'] );
		$this->assertSame( 0, $settings['countdowns']['maintenance']['target_timestamp'] );
	}

	/**
	 * Nested values are explicitly normalized and unknown instances are removed.
	 *
	 * @return void
	 */
	public function test_countdown_normalization_is_allowlisted() {
		$settings = Sanitizer::sanitize_settings(
			array(
				'countdowns' => array(
					'maintenance' => array(
						'enabled'          => 'yes',
						'target_timestamp' => '12345',
						'heading'          => '<strong>Launch</strong>',
						'description'      => "Line one\nLine two",
						'show_days'        => 0,
						'show_hours'       => 0,
						'show_minutes'     => 0,
						'show_seconds'     => 0,
						'expiry_action'    => 'not_allowed',
						'finished_message' => '<em>Done</em>',
					),
					'campaign' => array(
						'enabled' => 1,
					),
				),
			)
		);

		$this->assertSame( array( 'maintenance' ), array_keys( $settings['countdowns'] ) );
		$this->assertSame( 1, $settings['countdowns']['maintenance']['enabled'] );
		$this->assertSame( 12345, $settings['countdowns']['maintenance']['target_timestamp'] );
		$this->assertSame( 'Launch', $settings['countdowns']['maintenance']['heading'] );
		$this->assertSame( "Line one\nLine two", $settings['countdowns']['maintenance']['description'] );
		$this->assertSame( 'hold_zero', $settings['countdowns']['maintenance']['expiry_action'] );
		$this->assertSame( 'Done', $settings['countdowns']['maintenance']['finished_message'] );
		$this->assertSame( 1, $settings['countdowns']['maintenance']['show_days'] );
		$this->assertSame( 1, $settings['countdowns']['maintenance']['show_hours'] );
		$this->assertSame( 1, $settings['countdowns']['maintenance']['show_minutes'] );
		$this->assertSame( 1, $settings['countdowns']['maintenance']['show_seconds'] );
	}

	/**
	 * Invalid and negative targets normalize to the unconfigured sentinel.
	 *
	 * @return void
	 */
	public function test_invalid_target_normalizes_to_zero() {
		$negative = Sanitizer::sanitize_countdowns(
			array(
				'maintenance' => array(
					'enabled'          => 1,
					'target_timestamp' => -100,
				),
			)
		);
		$malformed = Sanitizer::sanitize_countdowns(
			array(
				'maintenance' => array(
					'enabled'          => 1,
					'target_timestamp' => '123seconds',
				),
			)
		);

		$this->assertSame( 0, $negative['maintenance']['target_timestamp'] );
		$this->assertSame( 0, $malformed['maintenance']['target_timestamp'] );
	}

	/**
	 * State resolution distinguishes all four domain states at exact boundaries.
	 *
	 * @return void
	 */
	public function test_state_and_remaining_seconds_calculation() {
		$defaults = SettingsSchema::get_default_countdown_instance();
		$now      = 1000;

		$this->assertSame( CountdownService::STATE_UNCONFIGURED, $this->service->get_state( $defaults, $now ) );

		$disabled                     = $defaults;
		$disabled['target_timestamp'] = 1100;
		$this->assertSame( CountdownService::STATE_DISABLED, $this->service->get_state( $disabled, $now ) );

		$scheduled            = $disabled;
		$scheduled['enabled'] = 1;
		$this->assertSame( CountdownService::STATE_SCHEDULED, $this->service->get_state( $scheduled, $now ) );
		$this->assertSame( 100, $this->service->get_remaining_seconds( $scheduled, $now ) );

		$this->assertSame( CountdownService::STATE_EXPIRED, $this->service->get_state( $scheduled, 1100 ) );
		$this->assertSame( 0, $this->service->get_remaining_seconds( $scheduled, 1100 ) );
	}

	/**
	 * Site-local input converts to a real instant and formats back consistently.
	 *
	 * @return void
	 */
	public function test_site_timezone_datetime_round_trip() {
		$timezone  = new DateTimeZone( 'Asia/Karachi' );
		$timestamp = $this->service->parse_local_datetime( '2026-08-09T15:30', $timezone );

		$this->assertSame( 1786271400, $timestamp );
		$this->assertSame( '2026-08-09T15:30', $this->service->format_local_datetime( $timestamp, $timezone ) );
		$this->assertSame( 0, $this->service->parse_local_datetime( '2026-02-30T15:30', $timezone ) );
	}

	/**
	 * DST-observing site time uses the correct seasonal offset and rejects gaps.
	 *
	 * @return void
	 */
	public function test_dst_timezone_datetime_round_trip() {
		$timezone = new DateTimeZone( 'America/New_York' );
		$summer   = $this->service->parse_local_datetime( '2026-07-15T12:00', $timezone );
		$winter   = $this->service->parse_local_datetime( '2026-01-15T12:00', $timezone );

		$this->assertSame( '2026-07-15T12:00', $this->service->format_local_datetime( $summer, $timezone ) );
		$this->assertSame( '2026-01-15T12:00', $this->service->format_local_datetime( $winter, $timezone ) );
		$this->assertSame( -14400, $timezone->getOffset( new DateTimeImmutable( '@' . $summer ) ) );
		$this->assertSame( -18000, $timezone->getOffset( new DateTimeImmutable( '@' . $winter ) ) );
		$this->assertSame( 0, $this->service->parse_local_datetime( '2026-03-08T02:30', $timezone ) );
	}

	/**
	 * Unknown instance identifiers fail closed.
	 *
	 * @return void
	 */
	public function test_unknown_instance_key_is_rejected() {
		$this->expectException( InvalidArgumentException::class );

		$this->service->get_instance( Sanitizer::get_settings( array() ), 'campaign' );
	}
}

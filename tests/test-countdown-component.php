<?php
/**
 * Frontend countdown component tests.
 *
 * @package MaintenanceModeStudio
 */

use Maneuvrez\MaintenanceModeStudio\Components\ComponentRegistry;
use Maneuvrez\MaintenanceModeStudio\Components\CountdownComponent;
use Maneuvrez\MaintenanceModeStudio\Frontend\TemplateRegistry;
use Maneuvrez\MaintenanceModeStudio\Security\Sanitizer;

/**
 * Covers component registration and server-rendered countdown states.
 */
class Test_MMSM_Countdown_Component extends WP_UnitTestCase {
	/**
	 * Component under test.
	 *
	 * @var CountdownComponent
	 */
	private $component;

	/**
	 * Create the component for each test.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();
		$this->component = new CountdownComponent();
	}

	/**
	 * Countdown is registered in the main zone after hero.
	 *
	 * @return void
	 */
	public function test_countdown_is_registered_and_positioned() {
		$registry = new ComponentRegistry();
		$template = ( new TemplateRegistry() )->resolve( 'default' );

		$this->assertTrue( $registry->has( 'countdown' ) );
		$this->assertTrue( $registry->supports_zone( 'countdown', 'main' ) );
		$this->assertSame( array( 'hero', 'countdown', 'status_progress' ), array_slice( $template['layout']['main'], 0, 3 ) );
		$this->assertSame( 'assets/js/countdown.js', $template['asset_sources']['scripts']['mmsm-countdown'] );
	}

	/**
	 * Disabled and unconfigured instances render nothing.
	 *
	 * @return void
	 */
	public function test_disabled_and_unconfigured_instances_render_nothing() {
		$unconfigured = Sanitizer::get_settings( array() );
		$disabled     = $this->get_settings(
			array(
				'enabled'          => 0,
				'target_timestamp' => 2000,
			)
		);

		$this->assertSame( '', $this->component->render( $unconfigured, array( 'current_timestamp' => 1000 ) ) );
		$this->assertSame( '', $this->component->render( $disabled, array( 'current_timestamp' => 1000 ) ) );
	}

	/**
	 * Scheduled markup contains escaped copy and conventional segmented values.
	 *
	 * @return void
	 */
	public function test_scheduled_countdown_renders_accessible_values() {
		$settings = $this->get_settings(
			array(
				'enabled'          => 1,
				'target_timestamp' => 91061,
				'heading'          => '<b>Launching soon</b>',
				'description'      => '<script>alert(1)</script>Ready when you are.',
				'show_minutes'     => 0,
			)
		);
		$markup   = $this->component->render( $settings, array( 'current_timestamp' => 1000 ) );

		$this->assertStringContainsString( 'data-state="scheduled"', $markup );
		$this->assertStringContainsString( 'role="timer" aria-live="off"', $markup );
		$this->assertStringContainsString( 'data-mmsm-countdown-value="days">01</span>', $markup );
		$this->assertStringContainsString( 'data-mmsm-countdown-value="hours">01</span>', $markup );
		$this->assertStringContainsString( 'data-mmsm-countdown-value="seconds">01</span>', $markup );
		$this->assertStringNotContainsString( 'data-mmsm-countdown-value="minutes"', $markup );
		$this->assertStringContainsString( 'Launching soon', $markup );
		$this->assertStringNotContainsString( '<script>', $markup );
	}

	/**
	 * Hold-zero and disable-mode actions render a non-mutating zero state.
	 *
	 * @return void
	 */
	public function test_expired_zero_presentations_render_zero() {
		foreach ( array( 'hold_zero', 'disable_mode' ) as $action ) {
			$settings = $this->get_settings(
				array(
					'enabled'          => 1,
					'target_timestamp' => 1000,
					'expiry_action'    => $action,
				)
			);
			$markup = $this->component->render( $settings, array( 'current_timestamp' => 1000 ) );

			$this->assertStringContainsString( 'data-state="expired"', $markup );
			$this->assertSame( 4, substr_count( $markup, '>00</span>' ) );
		}
	}

	/**
	 * Hide produces no markup and show-message produces escaped completion copy.
	 *
	 * @return void
	 */
	public function test_expired_hide_and_message_presentations() {
		$hidden = $this->get_settings(
			array(
				'enabled'          => 1,
				'target_timestamp' => 1000,
				'expiry_action'    => 'hide',
			)
		);
		$message = $this->get_settings(
			array(
				'enabled'          => 1,
				'target_timestamp' => 1000,
				'expiry_action'    => 'show_message',
				'finished_message' => '<b>We are live!</b>',
			)
		);
		$markup  = $this->component->render( $message, array( 'current_timestamp' => 1000 ) );

		$this->assertSame( '', $this->component->render( $hidden, array( 'current_timestamp' => 1000 ) ) );
		$this->assertStringContainsString( 'data-mmsm-countdown-finished', $markup );
		$this->assertStringContainsString( 'We are live!', $markup );
		$this->assertStringNotContainsString( '<b>', $markup );
		$this->assertMatchesRegularExpression( '/hidden[^>]*data-mmsm-countdown-values/', $markup );
	}

	/**
	 * Build normalized settings with a maintenance countdown override.
	 *
	 * @param array<string,mixed> $instance Countdown instance values.
	 * @return array<string,mixed>
	 */
	private function get_settings( array $instance ) {
		return Sanitizer::get_settings(
			array(
				'countdowns' => array(
					'maintenance' => array_merge(
						Sanitizer::get_default_settings()['countdowns']['maintenance'],
						$instance
					),
				),
			)
		);
	}
}

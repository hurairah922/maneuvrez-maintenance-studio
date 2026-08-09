<?php
/**
 * Frontend countdown component tests.
 *
 * @package MaintenanceModeStudio
 */

use Maneuvrez\MaintenanceModeStudio\Components\ComponentRegistry;
use Maneuvrez\MaintenanceModeStudio\Components\CountdownComponent;
use Maneuvrez\MaintenanceModeStudio\Frontend\TemplateRegistry;
use Maneuvrez\MaintenanceModeStudio\Frontend\TemplateRenderer;
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
		$this->assertMatchesRegularExpression( '/data-mmsm-countdown-value="days"><span[^>]*>01<\/span>/', $markup );
		$this->assertMatchesRegularExpression( '/data-mmsm-countdown-value="hours"><span[^>]*>01<\/span>/', $markup );
		$this->assertMatchesRegularExpression( '/data-mmsm-countdown-value="seconds"><span[^>]*>01<\/span>/', $markup );
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
	 * Appearance choices produce scoped classes and sanitized CSS variables.
	 *
	 * @return void
	 */
	public function test_custom_appearance_is_scoped_to_countdown_markup() {
		$settings = $this->get_settings(
			array(
				'enabled'          => 1,
				'target_timestamp' => 2000,
				'animation_style'  => 'flip',
				'animation_scope'  => 'both',
				'color_mode'       => 'custom',
				'background_color' => '#123abc',
				'number_color'     => '#ffffff',
			)
		);
		$markup = $this->component->render( $settings, array( 'current_timestamp' => 1000 ) );

		$this->assertStringContainsString( 'mmsm-countdown-animation-flip', $markup );
		$this->assertStringContainsString( 'mmsm-countdown-scope-both', $markup );
		$this->assertStringContainsString( 'mmsm-countdown-color-custom', $markup );
		$this->assertStringContainsString( '--mmsm-countdown-bg: #123abc', $markup );
		$this->assertStringContainsString( '--mmsm-countdown-number: #ffffff', $markup );
	}

	/**
	 * The ticking script is omitted unless a future countdown is visible.
	 *
	 * @return void
	 */
	public function test_countdown_script_loads_only_for_scheduled_state() {
		$renderer = new TemplateRenderer();
		wp_dequeue_script( 'mmsm-countdown' );
		$disabled = $this->get_settings(
			array(
				'enabled'          => 0,
				'target_timestamp' => time() + HOUR_IN_SECONDS,
			)
		);

		ob_start();
		$renderer->render( $disabled );
		ob_end_clean();
		$this->assertFalse( wp_script_is( 'mmsm-countdown', 'enqueued' ) );

		$scheduled = $this->get_settings(
			array(
				'enabled'          => 1,
				'target_timestamp' => time() + HOUR_IN_SECONDS,
			)
		);

		ob_start();
		$renderer->render( $scheduled );
		ob_end_clean();
		$this->assertTrue( wp_script_is( 'mmsm-countdown', 'enqueued' ) );
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

<?php
/**
 * Frontend countdown component.
 *
 * @package MaintenanceModeStudio
 */

namespace Maneuvrez\MaintenanceModeStudio\Components;

use Maneuvrez\MaintenanceModeStudio\Countdown\CountdownService;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the configured maintenance countdown without lifecycle side effects.
 */
class CountdownComponent implements ComponentInterface {
	/**
	 * Countdown domain service.
	 *
	 * @var CountdownService
	 */
	private $countdown_service;

	/**
	 * Constructor.
	 *
	 * @param CountdownService|null $countdown_service Countdown domain service.
	 */
	public function __construct( $countdown_service = null ) {
		$this->countdown_service = $countdown_service instanceof CountdownService ? $countdown_service : new CountdownService();
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_key() {
		return 'countdown';
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_label() {
		return __( 'Countdown', 'maneuvrez-maintenance-studio' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_supported_zones() {
		return array( 'main' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_settings_schema() {
		return array();
	}

	/**
	 * {@inheritDoc}
	 */
	public function render( array $settings, array $context = array() ) {
		$now          = isset( $context['current_timestamp'] ) ? (int) $context['current_timestamp'] : null;
		$public_state = $this->countdown_service->get_public_state( $settings, CountdownService::INSTANCE_MAINTENANCE, $now );
		$state        = (string) $public_state['state'];
		$instance     = $public_state['instance'];
		$action       = (string) $public_state['expiry_action'];

		if ( in_array( $state, array( CountdownService::STATE_UNCONFIGURED, CountdownService::STATE_DISABLED ), true ) ) {
			return '';
		}

		if ( CountdownService::STATE_EXPIRED === $state && 'hide' === $action ) {
			return '';
		}

		$heading          = trim( (string) $instance['heading'] );
		$description      = trim( (string) $instance['description'] );
		$finished_message = trim( (string) $instance['finished_message'] );
		$is_message       = CountdownService::STATE_EXPIRED === $state && 'show_message' === $action;
		$remaining        = CountdownService::STATE_SCHEDULED === $state ? (int) $public_state['remaining_seconds'] : 0;
		$units            = $this->get_visible_units( $instance, $remaining );
		$animation_style  = in_array( $instance['animation_style'], array( 'none', 'pulse', 'slide' ), true ) ? (string) $instance['animation_style'] : 'slide';
		$color_mode       = 'custom' === $instance['color_mode'] ? 'custom' : 'theme';
		$component_style  = 'custom' === $color_mode ? $this->get_custom_style( $instance ) : '';

		if ( '' === $finished_message ) {
			$finished_message = __( "We're live.", 'maneuvrez-maintenance-studio' );
		}

		ob_start();
		?>
		<section
			class="<?php echo esc_attr( 'mmsm-component mmsm-component-countdown mmsm-countdown-units-' . count( $units ) . ' mmsm-countdown-animation-' . $animation_style . ' mmsm-countdown-color-' . $color_mode ); ?>"
			aria-label="<?php echo esc_attr( '' !== $heading ? $heading : __( 'Countdown', 'maneuvrez-maintenance-studio' ) ); ?>"
			<?php if ( '' !== $component_style ) : ?>style="<?php echo esc_attr( $component_style ); ?>"<?php endif; ?>
			data-mmsm-countdown
			data-instance="<?php echo esc_attr( CountdownService::INSTANCE_MAINTENANCE ); ?>"
			data-target-timestamp="<?php echo esc_attr( (string) $public_state['target_timestamp'] ); ?>"
			data-expiry-action="<?php echo esc_attr( $action ); ?>"
			data-state="<?php echo esc_attr( $state ); ?>"
		>
			<?php if ( '' !== $heading ) : ?>
				<h2 class="mmsm-countdown-heading"><?php echo esc_html( $heading ); ?></h2>
			<?php endif; ?>
			<?php if ( '' !== $description ) : ?>
				<p class="mmsm-countdown-description"><?php echo esc_html( $description ); ?></p>
			<?php endif; ?>

			<div class="mmsm-countdown-grid" role="timer" aria-live="off"<?php echo $is_message ? ' hidden' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> data-mmsm-countdown-values>
				<?php foreach ( $units as $unit_key => $unit ) : ?>
					<div class="mmsm-countdown-unit" role="group" aria-label="<?php echo esc_attr( $unit['label'] ); ?>">
						<span class="mmsm-countdown-value" data-mmsm-countdown-value="<?php echo esc_attr( $unit_key ); ?>"><?php echo esc_html( $this->format_value( $unit['value'] ) ); ?></span>
						<span class="mmsm-countdown-label"><?php echo esc_html( $unit['label'] ); ?></span>
					</div>
				<?php endforeach; ?>
			</div>

			<?php if ( 'show_message' === $action ) : ?>
				<p class="mmsm-countdown-finished" role="status" aria-live="polite"<?php echo $is_message ? '' : ' hidden'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> data-mmsm-countdown-finished>
					<?php echo esc_html( $finished_message ); ?>
				</p>
			<?php endif; ?>
		</section>
		<?php

		return (string) ob_get_clean();
	}

	/**
	 * Build conventional segmented values for enabled units.
	 *
	 * Hours remain the hour remainder even when days are hidden.
	 *
	 * @param array<string,mixed> $instance Normalized countdown instance.
	 * @param int                 $remaining Remaining whole seconds.
	 * @return array<string,array<string,mixed>>
	 */
	private function get_visible_units( array $instance, $remaining ) {
		$days      = (int) floor( $remaining / DAY_IN_SECONDS );
		$hours     = (int) floor( ( $remaining % DAY_IN_SECONDS ) / HOUR_IN_SECONDS );
		$minutes   = (int) floor( ( $remaining % HOUR_IN_SECONDS ) / MINUTE_IN_SECONDS );
		$seconds   = (int) ( $remaining % MINUTE_IN_SECONDS );
		$available = array(
			'days'    => array( 'value' => $days, 'label' => __( 'Days', 'maneuvrez-maintenance-studio' ) ),
			'hours'   => array( 'value' => $hours, 'label' => __( 'Hours', 'maneuvrez-maintenance-studio' ) ),
			'minutes' => array( 'value' => $minutes, 'label' => __( 'Minutes', 'maneuvrez-maintenance-studio' ) ),
			'seconds' => array( 'value' => $seconds, 'label' => __( 'Seconds', 'maneuvrez-maintenance-studio' ) ),
		);
		$visible   = array();

		foreach ( $available as $key => $unit ) {
			if ( ! empty( $instance[ 'show_' . $key ] ) ) {
				$visible[ $key ] = $unit;
			}
		}

		return $visible;
	}

	/**
	 * Pad values below ten without limiting larger day counts.
	 *
	 * @param int $value Unit value.
	 * @return string
	 */
	private function format_value( $value ) {
		return str_pad( (string) max( 0, (int) $value ), 2, '0', STR_PAD_LEFT );
	}

	/**
	 * Build scoped CSS variables for optional custom countdown colors.
	 *
	 * @param array<string,mixed> $instance Normalized countdown instance.
	 * @return string
	 */
	private function get_custom_style( array $instance ) {
		$map = array(
			'background_color' => '--mmsm-countdown-bg',
			'number_color'     => '--mmsm-countdown-number',
			'label_color'      => '--mmsm-countdown-label',
			'border_color'     => '--mmsm-countdown-border',
		);
		$declarations = array();

		foreach ( $map as $setting_key => $css_variable ) {
			$color = isset( $instance[ $setting_key ] ) ? sanitize_hex_color( (string) $instance[ $setting_key ] ) : '';

			if ( $color ) {
				$declarations[] = $css_variable . ': ' . $color;
			}
		}

		return implode( '; ', $declarations );
	}
}

<?php
/**
 * Status and progress component.
 *
 * @package MaintenanceModeStudio
 */

namespace Maneuvrez\MaintenanceModeStudio\Components;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the live status block.
 */
class StatusProgressComponent implements ComponentInterface {
	/**
	 * {@inheritDoc}
	 */
	public function get_key() {
		return 'status_progress';
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_label() {
		return __( 'Status and progress', 'maneuvrez-maintenance-studio' );
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
		return array(
			array(
				'key'      => 'status_label',
				'label'    => __( 'Status label', 'maneuvrez-maintenance-studio' ),
				'type'     => 'text',
				'default'  => 'Maintenance in progress',
				'required' => false,
			),
			array(
				'key'      => 'show_progress',
				'label'    => __( 'Show progress', 'maneuvrez-maintenance-studio' ),
				'type'     => 'checkbox',
				'default'  => 1,
				'required' => false,
			),
			array(
				'key'      => 'progress_value',
				'label'    => __( 'Progress value', 'maneuvrez-maintenance-studio' ),
				'type'     => 'number',
				'default'  => 65,
				'required' => false,
				'allowed'  => array( 'min' => 0, 'max' => 100 ),
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function render( array $settings, array $context = array() ) {
		$label    = trim( (string) ( $settings['status_label'] ?? '' ) );
		$progress = isset( $settings['progress_value'] ) ? (int) $settings['progress_value'] : 65;
		$progress = max( 0, min( 100, $progress ) );

		if ( '' === $label ) {
			$label = 'Maintenance in progress';
		}

		ob_start();
		?>
		<section class="mmsm-component mmsm-component-status" aria-label="<?php echo esc_attr__( 'Status update', 'maneuvrez-maintenance-studio' ); ?>">
			<div class="mmsm-status-chip">
				<span class="mmsm-status-dot" aria-hidden="true"></span>
				<span><?php echo esc_html( $label ); ?></span>
			</div>
			<?php if ( ! empty( $settings['show_progress'] ) ) : ?>
				<div class="mmsm-progress-wrap">
					<div class="mmsm-progress-meta">
						<span><?php echo esc_html__( 'Update progress', 'maneuvrez-maintenance-studio' ); ?></span>
						<span><?php echo esc_html( $progress ); ?>%</span>
					</div>
					<progress class="mmsm-progress" max="100" value="<?php echo esc_attr( (string) $progress ); ?>">
						<?php echo esc_html( $progress ); ?>%
					</progress>
				</div>
			<?php endif; ?>
		</section>
		<?php

		return (string) ob_get_clean();
	}
}

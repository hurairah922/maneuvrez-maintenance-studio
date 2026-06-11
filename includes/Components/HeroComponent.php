<?php
/**
 * Hero component.
 *
 * @package MaintenanceModeStudio
 */

namespace Maneuvrez\MaintenanceModeStudio\Components;

use Maneuvrez\MaintenanceModeStudio\Support\Escaper;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the page headline and optional actions.
 */
class HeroComponent implements ComponentInterface {
	/**
	 * {@inheritDoc}
	 */
	public function get_key() {
		return 'hero';
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_label() {
		return __( 'Hero', 'maintenance-mode-studio' );
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
				'key'      => 'hero_eyebrow',
				'label'    => __( 'Eyebrow', 'maintenance-mode-studio' ),
				'type'     => 'text',
				'default'  => '',
				'required' => false,
			),
			array(
				'key'      => 'page_title',
				'label'    => __( 'Title', 'maintenance-mode-studio' ),
				'type'     => 'text',
				'default'  => "We'll be back soon",
				'required' => true,
			),
			array(
				'key'      => 'message',
				'label'    => __( 'Message', 'maintenance-mode-studio' ),
				'type'     => 'textarea',
				'default'  => 'Our site is getting a quick update. Please check back shortly.',
				'required' => true,
			),
			array(
				'key'      => 'primary_action_label',
				'label'    => __( 'Primary action label', 'maintenance-mode-studio' ),
				'type'     => 'text',
				'default'  => '',
				'required' => false,
			),
			array(
				'key'      => 'primary_action_url',
				'label'    => __( 'Primary action URL', 'maintenance-mode-studio' ),
				'type'     => 'url',
				'default'  => '',
				'required' => false,
			),
			array(
				'key'      => 'secondary_action_label',
				'label'    => __( 'Secondary action label', 'maintenance-mode-studio' ),
				'type'     => 'text',
				'default'  => '',
				'required' => false,
			),
			array(
				'key'      => 'secondary_action_url',
				'label'    => __( 'Secondary action URL', 'maintenance-mode-studio' ),
				'type'     => 'url',
				'default'  => '',
				'required' => false,
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function render( array $settings, array $context = array() ) {
		$eyebrow = trim( (string) ( $settings['hero_eyebrow'] ?? '' ) );
		$title   = trim( (string) ( $settings['page_title'] ?? '' ) );
		$message = trim( (string) ( $settings['message'] ?? '' ) );

		if ( '' === $title ) {
			$title = "We'll be back soon";
		}

		if ( '' === $message ) {
			$message = 'Our site is getting a quick update. Please check back shortly.';
		}

		$actions = array_filter(
			array(
				$this->build_action(
					(string) ( $settings['primary_action_label'] ?? '' ),
					(string) ( $settings['primary_action_url'] ?? '' ),
					'mmsm-button-primary'
				),
				$this->build_action(
					(string) ( $settings['secondary_action_label'] ?? '' ),
					(string) ( $settings['secondary_action_url'] ?? '' ),
					'mmsm-button-secondary'
				),
			)
		);

		ob_start();
		?>
		<section class="mmsm-component mmsm-component-hero">
			<?php if ( '' !== $eyebrow ) : ?>
				<p class="mmsm-eyebrow"><?php echo esc_html( $eyebrow ); ?></p>
			<?php endif; ?>
			<h1 id="mmsm-title" class="mmsm-hero-title"><?php echo esc_html( $title ); ?></h1>
			<p class="mmsm-hero-message"><?php echo esc_html( $message ); ?></p>
			<?php if ( ! empty( $actions ) ) : ?>
				<div class="mmsm-actions">
					<?php foreach ( $actions as $action ) : ?>
						<a class="<?php echo esc_attr( $action['class'] ); ?>" href="<?php echo esc_url( $action['url'] ); ?>">
							<?php echo esc_html( $action['label'] ); ?>
						</a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>
		</section>
		<?php

		return (string) ob_get_clean();
	}

	/**
	 * Build an action payload when the URL is valid.
	 *
	 * @param string $label Button label.
	 * @param string $url Button URL.
	 * @param string $variant CSS variant class.
	 * @return array<string,string>|null
	 */
	private function build_action( $label, $url, $variant ) {
		$url = Escaper::public_url( $url );

		if ( '' === $url ) {
			return null;
		}

		$label = trim( $label );

		if ( '' === $label ) {
			$label = __( 'Learn more', 'maintenance-mode-studio' );
		}

		return array(
			'class' => Escaper::classes( array( 'mmsm-button', $variant ) ),
			'label' => $label,
			'url'   => $url,
		);
	}
}

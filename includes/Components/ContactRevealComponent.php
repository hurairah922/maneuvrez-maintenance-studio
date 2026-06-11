<?php
/**
 * Contact reveal component.
 *
 * @package MaintenanceModeStudio
 */

namespace Maneuvrez\MaintenanceModeStudio\Components;

defined( 'ABSPATH' ) || exit;

/**
 * Renders contact guidance and an optional email link.
 */
class ContactRevealComponent implements ComponentInterface {
	/**
	 * {@inheritDoc}
	 */
	public function get_key() {
		return 'contact_reveal';
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_label() {
		return __( 'Contact reveal', 'maintenance-mode-studio' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_supported_zones() {
		return array( 'main', 'footer' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_settings_schema() {
		return array(
			array(
				'key'      => 'contact_label',
				'label'    => __( 'Contact label', 'maintenance-mode-studio' ),
				'type'     => 'text',
				'default'  => 'Need help?',
				'required' => false,
			),
			array(
				'key'      => 'contact_message',
				'label'    => __( 'Contact message', 'maintenance-mode-studio' ),
				'type'     => 'text',
				'default'  => 'Contact us for urgent requests.',
				'required' => false,
			),
			array(
				'key'      => 'contact_email',
				'label'    => __( 'Contact email', 'maintenance-mode-studio' ),
				'type'     => 'email',
				'default'  => '',
				'required' => false,
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function render( array $settings, array $context = array() ) {
		$label   = trim( (string) ( $settings['contact_label'] ?? '' ) );
		$message = trim( (string) ( $settings['contact_message'] ?? '' ) );
		$email   = sanitize_email( (string) ( $settings['contact_email'] ?? '' ) );

		if ( '' === $label ) {
			$label = 'Need help?';
		}

		if ( '' === $message ) {
			$message = 'Contact us for urgent requests.';
		}

		ob_start();
		?>
		<section class="mmsm-component mmsm-component-contact">
			<h2 class="mmsm-section-title"><?php echo esc_html( $label ); ?></h2>
			<p class="mmsm-section-text"><?php echo esc_html( $message ); ?></p>
			<?php if ( is_email( $email ) ) : ?>
				<p class="mmsm-contact-link-wrap">
					<a class="mmsm-inline-link" href="<?php echo esc_url( 'mailto:' . $email ); ?>">
						<?php echo esc_html( $email ); ?>
					</a>
				</p>
			<?php endif; ?>
		</section>
		<?php

		return (string) ob_get_clean();
	}
}

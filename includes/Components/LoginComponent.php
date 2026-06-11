<?php
/**
 * Login component.
 *
 * @package MaintenanceModeStudio
 */

namespace Maneuvrez\MaintenanceModeStudio\Components;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the administrator login shortcut.
 */
class LoginComponent implements ComponentInterface {
	/**
	 * {@inheritDoc}
	 */
	public function get_key() {
		return 'login';
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_label() {
		return __( 'Login', 'maintenance-mode-studio' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_supported_zones() {
		return array( 'footer' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_settings_schema() {
		return array(
			array(
				'key'      => 'show_login_button',
				'label'    => __( 'Show login button', 'maintenance-mode-studio' ),
				'type'     => 'checkbox',
				'default'  => 1,
				'required' => false,
			),
			array(
				'key'      => 'login_label',
				'label'    => __( 'Login label', 'maintenance-mode-studio' ),
				'type'     => 'text',
				'default'  => 'Admin login',
				'required' => false,
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function render( array $settings, array $context = array() ) {
		if ( empty( $settings['show_login_button'] ) ) {
			return '';
		}

		$login_url = isset( $context['login_url'] ) ? (string) $context['login_url'] : wp_login_url();
		$label     = trim( (string) ( $settings['login_label'] ?? '' ) );

		if ( '' === $label ) {
			$label = 'Admin login';
		}

		ob_start();
		?>
		<div class="mmsm-component mmsm-component-login">
			<a class="mmsm-button mmsm-button-tertiary" href="<?php echo esc_url( $login_url ); ?>">
				<?php echo esc_html( $label ); ?>
			</a>
		</div>
		<?php

		return (string) ob_get_clean();
	}
}

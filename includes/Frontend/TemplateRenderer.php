<?php
/**
 * Default maintenance template renderer.
 *
 * @package MaintenanceModeStudio
 */

namespace Maneuvrez\MaintenanceModeStudio\Frontend;

use Maneuvrez\MaintenanceModeStudio\Security\Sanitizer;

defined( 'ABSPATH' ) || exit;

/**
 * Loads the default public template with a small view context.
 */
class TemplateRenderer {
	/**
	 * Render the default template.
	 *
	 * @param array<string,int|string> $settings Sanitized settings.
	 * @return void
	 */
	public function render( array $settings = array() ) {
		$settings = Sanitizer::get_settings( $settings );

		$mode_label = 'coming_soon' === $settings['mode_type']
			? __( 'Coming Soon', MMSM_TEXT_DOMAIN )
			: __( 'Maintenance Mode Active', MMSM_TEXT_DOMAIN );

		$wrapper_classes = array(
			'mmsm-shell',
			'mmsm-public-template',
			'mmsm-theme-' . sanitize_html_class( $settings['theme_mode'] ),
			'mmsm-mode-' . sanitize_html_class( $settings['mode_type'] ),
		);

		$context = array(
			'charset'           => get_bloginfo( 'charset' ),
			'language'          => get_bloginfo( 'language' ),
			'site_name'         => get_bloginfo( 'name' ),
			'title'             => $settings['page_title'],
			'message'           => $settings['message'],
			'status'            => $mode_label,
			'login_url'         => wp_login_url(),
			'show_login_button' => ! empty( $settings['show_login_button'] ),
			'wrapper_class'     => implode( ' ', $wrapper_classes ),
			'wrapper_style'     => '--mmsm-primary: ' . $settings['primary_color'] . ';',
			'styles_handle'     => 'mmsm-public',
			'script_handle'     => 'mmsm-public',
		);

		require MMSM_PLUGIN_PATH . 'public/templates/default.php';
	}
}

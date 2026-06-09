<?php
/**
 * Default maintenance template renderer.
 *
 * @package MaintenanceModeStudio
 */

namespace Maneuvrez\MaintenanceModeStudio\Frontend;

defined( 'ABSPATH' ) || exit;

/**
 * Loads the default public template with a small view context.
 */
class TemplateRenderer {
	/**
	 * Render the default template.
	 *
	 * @return void
	 */
	public function render() {
		// Future template selection can swap this context builder and template path.
		$context = array(
			'charset'    => get_bloginfo( 'charset' ),
			'language'   => get_bloginfo( 'language' ),
			'site_name'  => get_bloginfo( 'name' ),
			'title'      => __( 'We\'ll be back soon.', MMSM_TEXT_DOMAIN ),
			'message'    => __( 'The site is getting a careful refresh. Please check back again shortly.', MMSM_TEXT_DOMAIN ),
			'status'     => __( 'Maintenance Mode Active', MMSM_TEXT_DOMAIN ),
			'login_url'  => wp_login_url(),
			'styles_url' => MMSM_PLUGIN_URL . 'public/assets/public.css',
			'script_url' => MMSM_PLUGIN_URL . 'public/assets/public.js',
		);

		require MMSM_PLUGIN_PATH . 'public/templates/default.php';
	}
}

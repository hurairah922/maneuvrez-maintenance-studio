<?php
/**
 * Frontend template registry.
 *
 * @package MaintenanceModeStudio
 */

namespace Maneuvrez\MaintenanceModeStudio\Frontend;

defined( 'ABSPATH' ) || exit;

/**
 * Stores available public templates and their metadata.
 */
class TemplateRegistry {
	/**
	 * Return all templates.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public function all() {
		return array(
			'default' => array(
				'key'         => 'default',
				'name'        => __( 'Default', 'maintenance-mode-studio' ),
				'description' => __( 'A polished maintenance page with reusable status, contact, social, and login sections.', 'maintenance-mode-studio' ),
				'file'        => MMSM_PLUGIN_PATH . 'templates/public/default.php',
				'zones'       => array( 'main', 'footer' ),
					'assets'      => array(
						'styles'  => array( 'mmsm-public-template-default' ),
						'scripts' => array( 'mmsm-public-template-default' ),
					),
					'asset_sources' => array(
						'styles'  => array(
							'mmsm-public-template-default' => 'assets/css/public-template-default.css',
						),
						'scripts' => array(
							'mmsm-public-template-default' => 'assets/js/public-template-default.js',
						),
					),
					'layout'      => array(
					'main'   => array( 'hero', 'status_progress', 'contact_reveal' ),
					'footer' => array( 'social_links', 'login' ),
				),
			),
		);
	}

	/**
	 * Resolve a template safely, falling back to the default template.
	 *
	 * @param string $key Requested template key.
	 * @return array<string,mixed>
	 */
	public function resolve( $key ) {
		$templates = $this->all();

		if ( isset( $templates[ $key ] ) ) {
			return $templates[ $key ];
		}

		return $templates['default'];
	}
}

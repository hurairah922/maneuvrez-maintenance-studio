<?php
/**
 * Plugin Name: Maintenance Mode Studio
 * Plugin URI: https://abuhurarrah.com/plugins/maintenance-mode-studio
 * Description: Create interactive maintenance, coming soon, launch, and private site pages with games, forms, contact options, social links, login access, and modern responsive animations.
 * Version: 0.1.0
 * Author: Abu Hurarrah
 * Author URI: https://abuhurarrah.com
 * Text Domain: maintenance-mode-studio
 * Domain Path: /languages
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

/**
 * Created by Abu Hurarrah.
 * Creator URI: https://abuhurarrah.com
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'MMSM_VERSION' ) ) {
	define( 'MMSM_VERSION', '0.1.0' );
}

if ( ! defined( 'MMSM_PLUGIN_FILE' ) ) {
	define( 'MMSM_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'MMSM_PLUGIN_BASENAME' ) ) {
	define( 'MMSM_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
}

if ( ! defined( 'MMSM_PLUGIN_PATH' ) ) {
	define( 'MMSM_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'MMSM_PLUGIN_URL' ) ) {
	define( 'MMSM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'MMSM_TEXT_DOMAIN' ) ) {
	define( 'MMSM_TEXT_DOMAIN', 'maintenance-mode-studio' );
}

if ( ! defined( 'MMSM_SETTINGS_OPTION' ) ) {
	define( 'MMSM_SETTINGS_OPTION', 'maintenance_mode_settings' );
}

if ( ! defined( 'MMSM_LEGACY_SETTINGS_OPTION' ) ) {
	define( 'MMSM_LEGACY_SETTINGS_OPTION', 'mmsm_settings' );
}

if ( ! defined( 'MMSM_VERSION_OPTION' ) ) {
	define( 'MMSM_VERSION_OPTION', 'mmsm_version' );
}

require_once MMSM_PLUGIN_PATH . 'includes/Security/Sanitizer.php';
require_once MMSM_PLUGIN_PATH . 'includes/Admin/Admin.php';
require_once MMSM_PLUGIN_PATH . 'includes/Frontend/TemplateRenderer.php';
require_once MMSM_PLUGIN_PATH . 'includes/Frontend/MaintenanceRouter.php';
require_once MMSM_PLUGIN_PATH . 'includes/Activator.php';
require_once MMSM_PLUGIN_PATH . 'includes/Deactivator.php';
require_once MMSM_PLUGIN_PATH . 'includes/Plugin.php';

register_activation_hook(
	__FILE__,
	array( Maneuvrez\MaintenanceModeStudio\Activator::class, 'activate' )
);

register_deactivation_hook(
	__FILE__,
	array( Maneuvrez\MaintenanceModeStudio\Deactivator::class, 'deactivate' )
);

/**
 * Bootstrap the Phase 1 plugin shell.
 *
 * Future Pro extension points should compose around the Plugin class rather than
 * branching from this entry file.
 */
function mmsm_run_plugin() {
	$plugin = new Maneuvrez\MaintenanceModeStudio\Plugin();
	$plugin->run();
}

mmsm_run_plugin();

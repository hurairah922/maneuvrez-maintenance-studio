<?php
/**
 * Uninstall Maintenance Mode Studio.
 *
 * @package MaintenanceModeStudio
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'mmsm_settings' );
delete_option( 'mmsm_version' );

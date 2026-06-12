<?php
/**
 * Uninstall Maintenance Mode Studio.
 *
 * @package MaintenanceModeStudio
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

$mmsm_should_delete_data = ! empty( get_option( 'mmsm_remove_data_on_uninstall', 0 ) );

if ( ! $mmsm_should_delete_data ) {
	$mmsm_settings = get_option( 'maintenance_mode_settings', array() );
	$mmsm_should_delete_data = is_array( $mmsm_settings ) && ! empty( $mmsm_settings['delete_data_on_uninstall'] );
}

if ( ! $mmsm_should_delete_data ) {
	return;
}

delete_option( 'maintenance_mode_settings' );
delete_option( 'mmsm_settings' );
delete_option( 'mmsm_version' );
delete_option( 'mmsm_remove_data_on_uninstall' );
delete_option( 'mmsm_uninstall_feedback_log' );

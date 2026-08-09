<?php
/**
 * Countdown one-time scheduling and lifecycle reconciliation.
 *
 * @package MaintenanceModeStudio
 */

namespace Maneuvrez\MaintenanceModeStudio\Countdown;

use Maneuvrez\MaintenanceModeStudio\Settings\SettingsRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Keeps one exact-target cron event per supported countdown instance.
 */
class CountdownScheduler {
	const CRON_HOOK = 'mmsm_countdown_expiry';

	/** @var SettingsRepository */
	private $settings_repository;

	/** @var CountdownService */
	private $countdown_service;

	/**
	 * Constructor.
	 *
	 * @param SettingsRepository|null $settings_repository Settings repository.
	 * @param CountdownService|null    $countdown_service Countdown domain service.
	 */
	public function __construct( $settings_repository = null, $countdown_service = null ) {
		$this->settings_repository = $settings_repository instanceof SettingsRepository ? $settings_repository : new SettingsRepository();
		$this->countdown_service   = $countdown_service instanceof CountdownService ? $countdown_service : new CountdownService( $this->settings_repository );
	}

	/**
	 * Register lifecycle hooks.
	 *
	 * Request reconciliation runs before MaintenanceRouter at priority 0.
	 *
	 * @return void
	 */
	public function register() {
		add_action( self::CRON_HOOK, array( $this, 'handle_expiry_event' ), 10, 1 );
		add_action( 'init', array( $this, 'ensure_future_events' ), 1 );
		add_action( 'template_redirect', array( $this, 'reconcile_request' ), -100 );
		add_action( 'update_option_' . MMSM_SETTINGS_OPTION, array( $this, 'handle_settings_updated' ), 10, 3 );
		add_action( 'add_option_' . MMSM_SETTINGS_OPTION, array( $this, 'handle_settings_added' ), 10, 2 );
		add_action( 'mmsm_countdown_reconciled', array( $this, 'clear_instance' ), 10, 1 );
	}

	/**
	 * Repair a missing future event after an update or unexpected cron loss.
	 *
	 * Healthy and non-required schedules produce no write, so normal requests do
	 * not repeatedly reschedule the event.
	 *
	 * @return void
	 */
	public function ensure_future_events() {
		foreach ( $this->countdown_service->get_supported_instance_keys() as $instance_key ) {
			$status = $this->get_status( $instance_key );

			if ( 'missing' === $status['status'] ) {
				$this->sync_instance( $instance_key );
			}
		}
	}

	/**
	 * Reconcile state-changing expiry actions on the next normal request.
	 *
	 * @return void
	 */
	public function reconcile_request() {
		$this->countdown_service->reconcile_due_instances();
	}

	/**
	 * Route cron through the same domain reconciliation used by requests.
	 *
	 * @param string $instance_key Countdown instance identifier.
	 * @return void
	 */
	public function handle_expiry_event( $instance_key ) {
		$instance_key = sanitize_key( (string) $instance_key );

		if ( ! $this->countdown_service->is_supported_instance( $instance_key ) ) {
			return;
		}

		$this->countdown_service->reconcile_instance( $instance_key );
		$this->sync_instance( $instance_key );
	}

	/**
	 * Synchronize events after the plugin settings option changes.
	 *
	 * @param mixed  $old_value Previous option value.
	 * @param mixed  $new_value New option value.
	 * @param string $option Option name.
	 * @return void
	 */
	public function handle_settings_updated( $old_value, $new_value, $option ) {
		$old_countdowns = is_array( $old_value ) && isset( $old_value['countdowns'] ) ? $old_value['countdowns'] : array();
		$new_countdowns = is_array( $new_value ) && isset( $new_value['countdowns'] ) ? $new_value['countdowns'] : array();

		if ( $old_countdowns !== $new_countdowns ) {
			$this->sync_all( is_array( $new_value ) ? $new_value : null );
		}
	}

	/**
	 * Synchronize events after first-time option creation.
	 *
	 * @param string $option Option name.
	 * @param mixed  $value New option value.
	 * @return void
	 */
	public function handle_settings_added( $option, $value ) {
		$this->sync_all( is_array( $value ) ? $value : null );
	}

	/**
	 * Synchronize every supported countdown instance.
	 *
	 * @param array<string,mixed>|null $settings Optional already-loaded settings.
	 * @return void
	 */
	public function sync_all( $settings = null ) {
		$settings = is_array( $settings ) ? $settings : $this->settings_repository->get_settings();

		foreach ( $this->countdown_service->get_supported_instance_keys() as $instance_key ) {
			$this->sync_instance( $instance_key, $settings );
		}
	}

	/**
	 * Replace an instance's events with one exact-target event when applicable.
	 *
	 * @param string                   $instance_key Countdown instance identifier.
	 * @param array<string,mixed>|null $settings Optional already-loaded settings.
	 * @param int|null                 $now Current timestamp override.
	 * @return bool Whether a future event exists after synchronization.
	 */
	public function sync_instance( $instance_key, $settings = null, $now = null ) {
		if ( ! $this->countdown_service->is_supported_instance( $instance_key ) ) {
			return false;
		}

		$settings = is_array( $settings ) ? $settings : $this->settings_repository->get_settings();
		$instance = $this->countdown_service->get_instance( $settings, $instance_key );
		$args     = array( $instance_key );

		$this->clear_instance( $instance_key );

		if ( ! $this->countdown_service->is_scheduled( $instance, $now ) ) {
			return false;
		}

		return true === wp_schedule_single_event( (int) $instance['target_timestamp'], self::CRON_HOOK, $args );
	}

	/**
	 * Clear every event belonging to one instance.
	 *
	 * @param string $instance_key Countdown instance identifier.
	 * @return int|false Number removed or false on failure.
	 */
	public function clear_instance( $instance_key ) {
		if ( ! $this->countdown_service->is_supported_instance( $instance_key ) ) {
			return false;
		}

		return wp_clear_scheduled_hook( self::CRON_HOOK, array( $instance_key ) );
	}

	/**
	 * Clear all plugin-owned countdown events.
	 *
	 * @return void
	 */
	public function clear_all() {
		foreach ( $this->countdown_service->get_supported_instance_keys() as $instance_key ) {
			$this->clear_instance( $instance_key );
		}
	}

	/**
	 * Report the current schedule without running an expiry action.
	 *
	 * @param string $instance_key Countdown instance identifier.
	 * @return array<string,mixed>
	 */
	public function get_status( $instance_key ) {
		if ( ! $this->countdown_service->is_supported_instance( $instance_key ) ) {
			return array( 'status' => 'unsupported', 'scheduled_timestamp' => 0, 'target_timestamp' => 0 );
		}

		$settings  = $this->settings_repository->get_settings();
		$instance  = $this->countdown_service->get_instance( $settings, $instance_key );
		$scheduled = wp_next_scheduled( self::CRON_HOOK, array( $instance_key ) );
		$target    = (int) $instance['target_timestamp'];
		$expected  = $this->countdown_service->is_scheduled( $instance );

		return array(
			'status'              => $expected ? ( (int) $scheduled === $target ? 'healthy' : 'missing' ) : 'not_required',
			'scheduled_timestamp' => $scheduled ? (int) $scheduled : 0,
			'target_timestamp'    => $target,
		);
	}
}

<?php
/**
 * Countdown expiry lifecycle tests.
 *
 * @package MaintenanceModeStudio
 */

use Maneuvrez\MaintenanceModeStudio\Countdown\CountdownScheduler;
use Maneuvrez\MaintenanceModeStudio\Countdown\CountdownService;
use Maneuvrez\MaintenanceModeStudio\Deactivator;
use Maneuvrez\MaintenanceModeStudio\Security\Sanitizer;
use Maneuvrez\MaintenanceModeStudio\Settings\SettingsRepository;

/**
 * Covers one-time scheduling and state-changing expiry reconciliation.
 */
class Test_MMSM_Countdown_Lifecycle extends WP_UnitTestCase {
	/** @var SettingsRepository */
	private $repository;

	/** @var CountdownService */
	private $service;

	/** @var CountdownScheduler */
	private $scheduler;

	/**
	 * Create isolated lifecycle services.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		$this->repository = new SettingsRepository();
		$this->service    = new CountdownService( $this->repository );
		$this->scheduler  = new CountdownScheduler( $this->repository, $this->service );
		$this->scheduler->clear_all();
		delete_option( MMSM_SETTINGS_OPTION );
	}

	/**
	 * Clear options and cron state after each test.
	 *
	 * @return void
	 */
	public function tear_down() {
		$this->scheduler->clear_all();
		delete_option( MMSM_SETTINGS_OPTION );

		parent::tear_down();
	}

	/**
	 * Repeated synchronization leaves exactly one matching event.
	 *
	 * @return void
	 */
	public function test_scheduler_prevents_duplicate_events() {
		$target = time() + DAY_IN_SECONDS;
		$this->save_countdown( $target, 'hold_zero' );

		$this->scheduler->sync_instance( CountdownService::INSTANCE_MAINTENANCE );
		$this->scheduler->sync_instance( CountdownService::INSTANCE_MAINTENANCE );

		$this->assertSame( $target, wp_next_scheduled( CountdownScheduler::CRON_HOOK, array( CountdownService::INSTANCE_MAINTENANCE ) ) );
		$this->assertSame( 1, $this->count_matching_events() );
	}

	/**
	 * A changed target removes the old event and installs the new event.
	 *
	 * @return void
	 */
	public function test_scheduler_reschedules_changed_target() {
		$old_target = time() + DAY_IN_SECONDS;
		$new_target = time() + ( 2 * DAY_IN_SECONDS );
		$this->save_countdown( $old_target, 'hold_zero' );
		$this->scheduler->sync_all();

		$this->save_countdown( $new_target, 'hold_zero' );
		$this->scheduler->sync_all();

		$this->assertSame( $new_target, wp_next_scheduled( CountdownScheduler::CRON_HOOK, array( CountdownService::INSTANCE_MAINTENANCE ) ) );
		$this->assertSame( 1, $this->count_matching_events() );
	}

	/**
	 * Runtime health reconciliation repairs a missing event without duplicates.
	 *
	 * @return void
	 */
	public function test_runtime_check_repairs_missing_future_event() {
		$target = time() + DAY_IN_SECONDS;
		$this->save_countdown( $target, 'hold_zero' );
		$this->scheduler->clear_all();

		$this->scheduler->ensure_future_events();
		$this->scheduler->ensure_future_events();

		$this->assertSame( $target, wp_next_scheduled( CountdownScheduler::CRON_HOOK, array( CountdownService::INSTANCE_MAINTENANCE ) ) );
		$this->assertSame( 1, $this->count_matching_events() );
	}

	/**
	 * An old callback cannot disable maintenance for a replacement future target.
	 *
	 * @return void
	 */
	public function test_stale_event_rechecks_current_configuration() {
		$future_target = time() + DAY_IN_SECONDS;
		$this->save_countdown( $future_target, 'disable_mode' );

		$this->scheduler->handle_expiry_event( CountdownService::INSTANCE_MAINTENANCE );
		$settings = $this->repository->get_settings();

		$this->assertSame( 1, $settings['enabled'] );
		$this->assertSame( $future_target, wp_next_scheduled( CountdownScheduler::CRON_HOOK, array( CountdownService::INSTANCE_MAINTENANCE ) ) );
	}

	/**
	 * Request reconciliation disables overdue maintenance without WP-Cron.
	 *
	 * @return void
	 */
	public function test_request_fallback_disables_overdue_maintenance() {
		$this->save_countdown( time() - MINUTE_IN_SECONDS, 'disable_mode' );

		$this->scheduler->reconcile_request();
		$settings = $this->repository->get_settings();

		$this->assertSame( 0, $settings['enabled'] );
		$this->assertSame( 0, $settings['countdowns']['maintenance']['enabled'] );
		$this->assertSame( 'Preserved title', $settings['page_title'] );
		$this->assertSame( 1, $settings['contact_channels_enabled'] );
		$this->assertSame( 'floating', $settings['contact_channels_live_display'] );
	}

	/**
	 * A consumed auto-disable action cannot disable a later activation.
	 *
	 * @return void
	 */
	public function test_reactivation_stays_enabled_after_auto_disable_is_consumed() {
		$this->save_countdown( time() - MINUTE_IN_SECONDS, 'disable_mode' );
		$this->assertTrue( $this->service->reconcile_instance( CountdownService::INSTANCE_MAINTENANCE ) );

		$settings            = $this->repository->get_settings();
		$settings['enabled'] = 1;
		update_option( MMSM_SETTINGS_OPTION, $settings );

		$this->assertFalse( $this->service->reconcile_instance( CountdownService::INSTANCE_MAINTENANCE ) );
		$this->assertSame( 1, $this->repository->get_settings()['enabled'] );
	}

	/**
	 * Presentation-only expiry actions do not change maintenance settings.
	 *
	 * @return void
	 */
	public function test_presentation_expiry_actions_have_no_persistent_side_effect() {
		foreach ( array( 'hold_zero', 'hide', 'show_message' ) as $action ) {
			$this->save_countdown( time() - MINUTE_IN_SECONDS, $action );

			$this->assertFalse( $this->service->reconcile_instance( CountdownService::INSTANCE_MAINTENANCE ) );
			$this->assertSame( 1, $this->repository->get_settings()['enabled'] );
		}
	}

	/**
	 * Repeating reconciliation does not repeat the option write.
	 *
	 * @return void
	 */
	public function test_disable_mode_is_idempotent() {
		$this->save_countdown( time() - MINUTE_IN_SECONDS, 'disable_mode' );
		$writes = 0;
		$counter = static function () use ( &$writes ) {
			++$writes;
		};
		add_action( 'update_option_' . MMSM_SETTINGS_OPTION, $counter, 10, 0 );

		$this->service->reconcile_instance( CountdownService::INSTANCE_MAINTENANCE );
		$this->service->reconcile_instance( CountdownService::INSTANCE_MAINTENANCE );

		remove_action( 'update_option_' . MMSM_SETTINGS_OPTION, $counter );
		$this->assertSame( 1, $writes );
	}

	/**
	 * Lifecycle hooks run before the maintenance router and accept one arg.
	 *
	 * @return void
	 */
	public function test_lifecycle_hook_priorities() {
		$this->scheduler->register();

		$this->assertSame( 1, has_action( 'init', array( $this->scheduler, 'ensure_future_events' ) ) );
		$this->assertSame( -100, has_action( 'template_redirect', array( $this->scheduler, 'reconcile_request' ) ) );
		$this->assertSame( 10, has_action( CountdownScheduler::CRON_HOOK, array( $this->scheduler, 'handle_expiry_event' ) ) );
	}

	/**
	 * Plugin deactivation clears its exact instance events without settings loss.
	 *
	 * @return void
	 */
	public function test_deactivation_clears_countdown_events() {
		$this->save_countdown( time() + DAY_IN_SECONDS, 'hold_zero' );
		$this->scheduler->sync_all();

		Deactivator::deactivate();

		$this->assertFalse( wp_next_scheduled( CountdownScheduler::CRON_HOOK, array( CountdownService::INSTANCE_MAINTENANCE ) ) );
		$this->assertSame( 'Preserved title', $this->repository->get_settings()['page_title'] );
	}

	/**
	 * Store a normalized enabled countdown fixture.
	 *
	 * @param int    $target Target timestamp.
	 * @param string $action Expiry action.
	 * @return void
	 */
	private function save_countdown( $target, $action ) {
		$settings = Sanitizer::get_default_settings();
		$settings['enabled'] = 1;
		$settings['page_title'] = 'Preserved title';
		$settings['contact_channels_enabled']      = 1;
		$settings['contact_channels_live_display'] = 'floating';
		$settings['countdowns']['maintenance']['enabled']          = 1;
		$settings['countdowns']['maintenance']['target_timestamp'] = (int) $target;
		$settings['countdowns']['maintenance']['expiry_action']    = $action;

		update_option( MMSM_SETTINGS_OPTION, $settings );
	}

	/**
	 * Count exact hook/argument matches in WordPress's cron array.
	 *
	 * @return int
	 */
	private function count_matching_events() {
		$count = 0;
		$crons = _get_cron_array();

		foreach ( $crons as $hooks ) {
			if ( empty( $hooks[ CountdownScheduler::CRON_HOOK ] ) ) {
				continue;
			}

			foreach ( $hooks[ CountdownScheduler::CRON_HOOK ] as $event ) {
				if ( array( CountdownService::INSTANCE_MAINTENANCE ) === $event['args'] ) {
					++$count;
				}
			}
		}

		return $count;
	}
}

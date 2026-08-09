<?php
/**
 * Countdown state and time calculations.
 *
 * @package MaintenanceModeStudio
 */

namespace Maneuvrez\MaintenanceModeStudio\Countdown;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use Maneuvrez\MaintenanceModeStudio\Settings\SettingsSchema;

defined( 'ABSPATH' ) || exit;

/**
 * Provides pure domain operations for named countdown instances.
 */
class CountdownService {
	const INSTANCE_MAINTENANCE = 'maintenance';

	const STATE_UNCONFIGURED = 'unconfigured';
	const STATE_DISABLED     = 'disabled';
	const STATE_SCHEDULED    = 'scheduled';
	const STATE_EXPIRED      = 'expired';

	/**
	 * Return supported internal instance identifiers.
	 *
	 * @return array<int,string>
	 */
	public function get_supported_instance_keys() {
		return array( self::INSTANCE_MAINTENANCE );
	}

	/**
	 * Determine whether an internal instance identifier is supported.
	 *
	 * @param string $instance_key Countdown instance identifier.
	 * @return bool
	 */
	public function is_supported_instance( $instance_key ) {
		return in_array( $instance_key, $this->get_supported_instance_keys(), true );
	}

	/**
	 * Read a normalized named instance from normalized plugin settings.
	 *
	 * @param array<string,mixed> $settings Normalized plugin settings.
	 * @param string              $instance_key Countdown instance identifier.
	 * @return array<string,mixed>
	 * @throws InvalidArgumentException When the instance identifier is unknown.
	 */
	public function get_instance( array $settings, $instance_key ) {
		$this->assert_supported_instance( $instance_key );

		$defaults   = SettingsSchema::get_default_countdown_instance();
		$countdowns = isset( $settings['countdowns'] ) && is_array( $settings['countdowns'] ) ? $settings['countdowns'] : array();
		$instance   = isset( $countdowns[ $instance_key ] ) && is_array( $countdowns[ $instance_key ] ) ? $countdowns[ $instance_key ] : array();

		return array_merge( $defaults, $instance );
	}

	/**
	 * Determine whether an instance has a valid target.
	 *
	 * @param array<string,mixed> $instance Normalized countdown instance.
	 * @return bool
	 */
	public function is_configured( array $instance ) {
		return isset( $instance['target_timestamp'] ) && (int) $instance['target_timestamp'] > 0;
	}

	/**
	 * Resolve the domain state for an instance.
	 *
	 * @param array<string,mixed> $instance Normalized countdown instance.
	 * @param int|null            $now Current Unix timestamp override.
	 * @return string
	 */
	public function get_state( array $instance, $now = null ) {
		if ( ! $this->is_configured( $instance ) ) {
			return self::STATE_UNCONFIGURED;
		}

		if ( empty( $instance['enabled'] ) ) {
			return self::STATE_DISABLED;
		}

		$now = null === $now ? time() : (int) $now;

		if ( (int) $instance['target_timestamp'] <= $now ) {
			return self::STATE_EXPIRED;
		}

		return self::STATE_SCHEDULED;
	}

	/**
	 * Determine whether an enabled countdown is scheduled in the future.
	 *
	 * @param array<string,mixed> $instance Normalized countdown instance.
	 * @param int|null            $now Current Unix timestamp override.
	 * @return bool
	 */
	public function is_scheduled( array $instance, $now = null ) {
		return self::STATE_SCHEDULED === $this->get_state( $instance, $now );
	}

	/**
	 * Determine whether an enabled countdown has reached its target.
	 *
	 * @param array<string,mixed> $instance Normalized countdown instance.
	 * @param int|null            $now Current Unix timestamp override.
	 * @return bool
	 */
	public function is_expired( array $instance, $now = null ) {
		return self::STATE_EXPIRED === $this->get_state( $instance, $now );
	}

	/**
	 * Return remaining whole seconds without producing negative values.
	 *
	 * @param array<string,mixed> $instance Normalized countdown instance.
	 * @param int|null            $now Current Unix timestamp override.
	 * @return int
	 */
	public function get_remaining_seconds( array $instance, $now = null ) {
		$now = null === $now ? time() : (int) $now;

		if ( ! $this->is_scheduled( $instance, $now ) ) {
			return 0;
		}

		return max( 0, (int) $instance['target_timestamp'] - $now );
	}

	/**
	 * Build non-mutating state for a future frontend component.
	 *
	 * @param array<string,mixed> $settings Normalized plugin settings.
	 * @param string              $instance_key Countdown instance identifier.
	 * @param int|null            $now Current Unix timestamp override.
	 * @return array<string,mixed>
	 */
	public function get_public_state( array $settings, $instance_key, $now = null ) {
		$now      = null === $now ? time() : (int) $now;
		$instance = $this->get_instance( $settings, $instance_key );
		$state    = $this->get_state( $instance, $now );

		return array(
			'instance_key'      => $instance_key,
			'state'             => $state,
			'target_timestamp'  => (int) $instance['target_timestamp'],
			'remaining_seconds' => $this->get_remaining_seconds( $instance, $now ),
			'expiry_action'     => (string) $instance['expiry_action'],
			'instance'          => $instance,
		);
	}

	/**
	 * Parse a datetime-local value in the WordPress site timezone.
	 *
	 * @param string            $value Admin-compatible local date/time value.
	 * @param DateTimeZone|null $timezone Timezone override for deterministic tests.
	 * @return int Unix timestamp, or zero when invalid.
	 */
	public function parse_local_datetime( $value, $timezone = null ) {
		$timezone = $timezone instanceof DateTimeZone ? $timezone : wp_timezone();
		$value    = trim( (string) $value );

		foreach ( array( 'Y-m-d\\TH:i', 'Y-m-d\\TH:i:s' ) as $format ) {
			$date = DateTimeImmutable::createFromFormat( '!' . $format, $value, $timezone );

			if ( ! $date instanceof DateTimeImmutable || $date->format( $format ) !== $value ) {
				continue;
			}

			$errors = DateTimeImmutable::getLastErrors();
			if ( is_array( $errors ) && ( $errors['warning_count'] > 0 || $errors['error_count'] > 0 ) ) {
				continue;
			}

			return max( 0, $date->getTimestamp() );
		}

		return 0;
	}

	/**
	 * Format a Unix target for a datetime-local control in the site timezone.
	 *
	 * @param int               $timestamp Unix target timestamp.
	 * @param DateTimeZone|null $timezone Timezone override for deterministic tests.
	 * @return string
	 */
	public function format_local_datetime( $timestamp, $timezone = null ) {
		$timestamp = (int) $timestamp;

		if ( $timestamp <= 0 ) {
			return '';
		}

		$timezone = $timezone instanceof DateTimeZone ? $timezone : wp_timezone();

		return wp_date( 'Y-m-d\\TH:i', $timestamp, $timezone );
	}

	/**
	 * Reject unknown instance identifiers before they reach domain operations.
	 *
	 * @param string $instance_key Countdown instance identifier.
	 * @return void
	 * @throws InvalidArgumentException When the instance identifier is unknown.
	 */
	private function assert_supported_instance( $instance_key ) {
		if ( ! $this->is_supported_instance( $instance_key ) ) {
			throw new InvalidArgumentException( 'Unsupported countdown instance.' );
		}
	}
}

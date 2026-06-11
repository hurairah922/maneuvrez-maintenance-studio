<?php
/**
 * Frontend component registry.
 *
 * @package MaintenanceModeStudio
 */

namespace Maneuvrez\MaintenanceModeStudio\Components;

defined( 'ABSPATH' ) || exit;

/**
 * Registers and renders reusable frontend components.
 */
class ComponentRegistry {
	/**
	 * Registered components keyed by component key.
	 *
	 * @var array<string,ComponentInterface>
	 */
	private $components = array();

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->register( new HeroComponent() );
		$this->register( new StatusProgressComponent() );
		$this->register( new ContactRevealComponent() );
		$this->register( new SocialLinksComponent() );
		$this->register( new LoginComponent() );
	}

	/**
	 * Register a component.
	 *
	 * @param ComponentInterface $component Component instance.
	 * @return void
	 */
	public function register( ComponentInterface $component ) {
		$this->components[ $component->get_key() ] = $component;
	}

	/**
	 * Return all registered components.
	 *
	 * @return array<string,ComponentInterface>
	 */
	public function all() {
		return $this->components;
	}

	/**
	 * Determine whether a component exists.
	 *
	 * @param string $key Component key.
	 * @return bool
	 */
	public function has( $key ) {
		return isset( $this->components[ $key ] );
	}

	/**
	 * Return component metadata.
	 *
	 * @param string $key Component key.
	 * @return array<string,mixed>|null
	 */
	public function get_metadata( $key ) {
		if ( ! $this->has( $key ) ) {
			return null;
		}

		$component = $this->components[ $key ];

		return array(
			'key'             => $component->get_key(),
			'label'           => $component->get_label(),
			'zones'           => $component->get_supported_zones(),
			'settings_schema' => $component->get_settings_schema(),
		);
	}

	/**
	 * Check zone compatibility.
	 *
	 * @param string $key Component key.
	 * @param string $zone Zone key.
	 * @return bool
	 */
	public function supports_zone( $key, $zone ) {
		if ( ! $this->has( $key ) ) {
			return false;
		}

		return in_array( $zone, $this->components[ $key ]->get_supported_zones(), true );
	}

	/**
	 * Render a component safely.
	 *
	 * @param string              $key Component key.
	 * @param string              $zone Zone key.
	 * @param array<string,mixed> $settings Normalized settings.
	 * @param array<string,mixed> $context Shared context.
	 * @return string
	 */
	public function render( $key, $zone, array $settings, array $context = array() ) {
		if ( ! $this->supports_zone( $key, $zone ) ) {
			return '';
		}

		return $this->components[ $key ]->render( $settings, $context );
	}
}

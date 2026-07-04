<?php
/**
 * Bypass rule regression tests.
 *
 * @package MaintenanceModeStudio
 */

use Maneuvrez\MaintenanceModeStudio\Frontend\MaintenanceRouter;
use Maneuvrez\MaintenanceModeStudio\Frontend\TemplateRenderer;
use Maneuvrez\MaintenanceModeStudio\Security\Sanitizer;

/**
 * Covers the temporary query bypass and public allowlist rules.
 */
class Test_MMSM_Bypass_Rules extends WP_UnitTestCase {
	/**
	 * Preserve globals touched by router checks.
	 *
	 * @var array<string,mixed>
	 */
	private $original_get = array();

	/**
	 * Preserve globals touched by router checks.
	 *
	 * @var array<string,mixed>
	 */
	private $original_server = array();

	/**
	 * Set up test fixtures.
	 *
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		$this->original_get    = $_GET;
		$this->original_server = $_SERVER;
	}

	/**
	 * Restore globals after each test.
	 *
	 * @return void
	 */
	public function tear_down() {
		$_GET    = $this->original_get;
		$_SERVER = $this->original_server;

		parent::tear_down();
	}

	/**
	 * The query bypass should require an exact saved value.
	 *
	 * @return void
	 */
	public function test_query_bypass_requires_exact_value() {
		$router = $this->create_router();

		$_GET['mmsm_preview'] = 'abc123';

		$this->assertTrue(
			$this->invoke_private_method(
				$router,
				'matches_query_bypass',
				array(
					array(
						'bypass_query_enabled' => 1,
						'bypass_query_key'     => 'mmsm_preview',
						'bypass_query_value'   => 'abc123',
					),
				)
			)
		);

		$this->assertFalse(
			$this->invoke_private_method(
				$router,
				'matches_query_bypass',
				array(
					array(
						'bypass_query_enabled' => 1,
						'bypass_query_key'     => 'mmsm_preview',
						'bypass_query_value'   => 'wrong',
					),
				)
			)
		);
	}

	/**
	 * Same-site absolute allowlist entries should normalize to a canonical path.
	 *
	 * @return void
	 */
	public function test_allowlist_entries_normalize_to_same_site_paths() {
		$path = Sanitizer::normalize_bypass_url_entry( home_url( '/about/?preview=1' ) );

		$this->assertSame( '/about', $path );
	}

	/**
	 * External allowlist entries must be rejected.
	 *
	 * @return void
	 */
	public function test_allowlist_rejects_external_urls() {
		$entries = Sanitizer::sanitize_bypass_urls(
			implode(
				"\n",
				array(
					home_url( '/about/' ),
					'https://example.org/about/',
				)
			)
		);

		$this->assertSame( array( '/about' ), $entries );
	}

	/**
	 * Public allowlist matching should treat trailing slashes consistently.
	 *
	 * @return void
	 */
	public function test_allowlist_matching_treats_trailing_slash_as_equivalent() {
		$router = $this->create_router();

		$_SERVER['REQUEST_URI'] = '/contact/?ref=preview';

		$this->assertTrue(
			$this->invoke_private_method(
				$router,
				'matches_public_bypass_url',
				array(
					array(
						'bypass_urls_enabled' => 1,
						'bypass_urls'         => array( '/contact' ),
					),
				)
			)
		);
	}

	/**
	 * Create a router with a mocked renderer dependency.
	 *
	 * @return MaintenanceRouter
	 */
	private function create_router() {
		$renderer = $this->createMock( TemplateRenderer::class );

		return new MaintenanceRouter( $renderer );
	}

	/**
	 * Invoke a private router helper for focused rule tests.
	 *
	 * @param object              $object Target object.
	 * @param string              $method Method name.
	 * @param array<int,mixed>    $args Method args.
	 * @return mixed
	 */
	private function invoke_private_method( $object, $method, array $args = array() ) {
		$reflection = new ReflectionMethod( $object, $method );
		$reflection->setAccessible( true );

		return $reflection->invokeArgs( $object, $args );
	}
}

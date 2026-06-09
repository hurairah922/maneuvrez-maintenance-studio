<?php
/**
 * Admin settings page controller.
 *
 * @package MaintenanceModeStudio
 */

namespace Maneuvrez\MaintenanceModeStudio\Admin;

use Maneuvrez\MaintenanceModeStudio\Security\Sanitizer;

defined( 'ABSPATH' ) || exit;

/**
 * Handles the Phase 1 admin UI and settings persistence.
 */
class Admin {
	/**
	 * Settings page slug.
	 *
	 * @var string
	 */
	private $page_slug = 'maintenance-mode-studio';

	/**
	 * Register admin hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_post_mmsm_save_settings', array( $this, 'handle_save' ) );
	}

	/**
	 * Add the settings page under Settings.
	 *
	 * @return void
	 */
	public function add_settings_page() {
		add_options_page(
			__( 'Maintenance Mode Studio', MMSM_TEXT_DOMAIN ),
			__( 'Maintenance Mode Studio', MMSM_TEXT_DOMAIN ),
			'manage_options',
			$this->page_slug,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Render the Phase 1 settings page.
	 *
	 * @return void
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$settings = get_option( MMSM_SETTINGS_OPTION, array() );
		$enabled  = ! empty( $settings['enabled'] );
		$updated  = isset( $_GET['mmsm-updated'] ) ? sanitize_text_field( wp_unslash( $_GET['mmsm-updated'] ) ) : '';
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Maintenance Mode Studio', MMSM_TEXT_DOMAIN ); ?></h1>

			<?php if ( '1' === $updated ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php echo esc_html__( 'Settings saved.', MMSM_TEXT_DOMAIN ); ?></p>
				</div>
			<?php endif; ?>

			<p><?php echo esc_html__( 'Phase 1 provides a safe maintenance mode toggle and the default public page.', MMSM_TEXT_DOMAIN ); ?></p>

			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
				<input type="hidden" name="action" value="mmsm_save_settings" />
				<?php wp_nonce_field( 'mmsm_save_settings' ); ?>

				<table class="form-table" role="presentation">
					<tbody>
						<tr>
							<th scope="row">
								<label for="mmsm-enabled"><?php echo esc_html__( 'Enable maintenance mode', MMSM_TEXT_DOMAIN ); ?></label>
							</th>
							<td>
								<label for="mmsm-enabled">
									<input
										type="checkbox"
										id="mmsm-enabled"
										name="mmsm_settings[enabled]"
										value="1"
										<?php checked( $enabled ); ?>
									/>
									<?php echo esc_html__( 'Show the maintenance page to logged-out visitors.', MMSM_TEXT_DOMAIN ); ?>
								</label>
								<p class="description"><?php echo esc_html__( 'Administrators keep normal site access while this is enabled.', MMSM_TEXT_DOMAIN ); ?></p>
							</td>
						</tr>
					</tbody>
				</table>

				<?php submit_button( __( 'Save Settings', MMSM_TEXT_DOMAIN ) ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Save settings from the admin form.
	 *
	 * @return void
	 */
	public function handle_save() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to perform this action.', MMSM_TEXT_DOMAIN ) );
		}

		check_admin_referer( 'mmsm_save_settings' );

		$raw_settings = isset( $_POST['mmsm_settings'] ) ? wp_unslash( $_POST['mmsm_settings'] ) : array();
		$settings     = Sanitizer::sanitize_settings( $raw_settings );

		update_option( MMSM_SETTINGS_OPTION, $settings );

		$redirect_url = add_query_arg(
			array(
				'page'         => $this->page_slug,
				'mmsm-updated' => '1',
			),
			admin_url( 'options-general.php' )
		);

		wp_safe_redirect( $redirect_url );
		exit;
	}
}

<?php
/**
 * Admin settings page controller.
 *
 * @package MaintenanceModeStudio
 */

namespace Maneuvrez\MaintenanceModeStudio\Admin;

use Maneuvrez\MaintenanceModeStudio\Components\SocialLinksComponent;
use Maneuvrez\MaintenanceModeStudio\Security\Sanitizer;
use Maneuvrez\MaintenanceModeStudio\Settings\SettingsRepository;

defined( 'ABSPATH' ) || exit;

/**
 * Handles the settings UI, registration, and admin assets.
 */
class Admin {
	/**
	 * Settings repository.
	 *
	 * @var SettingsRepository
	 */
	private $settings_repository;

	/**
	 * Settings group slug.
	 *
	 * @var string
	 */
	private $settings_group = 'mmsm_settings_group';

	/**
	 * Settings page slug.
	 *
	 * @var string
	 */
	private $page_slug = 'maintenance-mode-studio';

	/**
	 * Settings page hook suffix.
	 *
	 * @var string
	 */
	private $page_hook = '';

	/**
	 * Constructor.
	 *
	 * @param SettingsRepository|null $settings_repository Settings repository.
	 */
	public function __construct( $settings_repository = null ) {
		$this->settings_repository = $settings_repository instanceof SettingsRepository ? $settings_repository : new SettingsRepository();
	}

	/**
	 * Register admin hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_footer-plugins.php', array( $this, 'render_uninstall_feedback_modal' ) );
		add_action( 'wp_ajax_mmsm_capture_uninstall_feedback', array( $this, 'handle_uninstall_feedback_request' ) );
		add_filter( 'plugin_action_links_' . MMSM_PLUGIN_BASENAME, array( $this, 'filter_plugin_action_links' ) );
	}

	/**
	 * Add the settings page under Settings.
	 *
	 * @return void
	 */
	public function add_settings_page() {
		$this->page_hook = add_options_page(
			__( 'Maintenance Mode Studio', 'maintenance-mode-studio' ),
			__( 'Maintenance Mode Studio', 'maintenance-mode-studio' ),
			'manage_options',
			$this->page_slug,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Register plugin settings, sections, and fields.
	 *
	 * @return void
	 */
	public function register_settings() {
		register_setting(
			$this->settings_group,
			MMSM_SETTINGS_OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'default'           => Sanitizer::get_default_settings(),
			)
		);

		add_settings_section(
			'mmsm_general_section',
			__( 'General', 'maintenance-mode-studio' ),
			array( $this, 'render_general_section' ),
			$this->page_slug
		);

		add_settings_field(
			'mmsm_enabled',
			__( 'Enable maintenance mode', 'maintenance-mode-studio' ),
			array( $this, 'render_enabled_field' ),
			$this->page_slug,
			'mmsm_general_section'
		);

		add_settings_field(
			'mmsm_page_title',
			__( 'Page Title', 'maintenance-mode-studio' ),
			array( $this, 'render_page_title_field' ),
			$this->page_slug,
			'mmsm_general_section'
		);

		add_settings_field(
			'mmsm_message',
			__( 'Message', 'maintenance-mode-studio' ),
			array( $this, 'render_message_field' ),
			$this->page_slug,
			'mmsm_general_section'
		);

		add_settings_section(
			'mmsm_template_section',
			__( 'Template', 'maintenance-mode-studio' ),
			array( $this, 'render_template_section' ),
			$this->page_slug
		);

		add_settings_field(
			'mmsm_mode_type',
			__( 'Mode Type', 'maintenance-mode-studio' ),
			array( $this, 'render_mode_type_field' ),
			$this->page_slug,
			'mmsm_template_section'
		);

		add_settings_field(
			'mmsm_template_key',
			__( 'Template', 'maintenance-mode-studio' ),
			array( $this, 'render_template_key_field' ),
			$this->page_slug,
			'mmsm_template_section'
		);

		add_settings_section(
			'mmsm_design_section',
			__( 'Design', 'maintenance-mode-studio' ),
			array( $this, 'render_design_section' ),
			$this->page_slug
		);

		add_settings_field(
			'mmsm_theme_mode',
			__( 'Theme Mode', 'maintenance-mode-studio' ),
			array( $this, 'render_theme_mode_field' ),
			$this->page_slug,
			'mmsm_design_section'
		);

		add_settings_field(
			'mmsm_primary_color',
			__( 'Primary Color', 'maintenance-mode-studio' ),
			array( $this, 'render_primary_color_field' ),
			$this->page_slug,
			'mmsm_design_section'
		);

		add_settings_field(
			'mmsm_background_color',
			__( 'Background Color', 'maintenance-mode-studio' ),
			array( $this, 'render_background_color_field' ),
			$this->page_slug,
			'mmsm_design_section'
		);

		add_settings_field(
			'mmsm_surface_color',
			__( 'Surface Color', 'maintenance-mode-studio' ),
			array( $this, 'render_surface_color_field' ),
			$this->page_slug,
			'mmsm_design_section'
		);

		add_settings_field(
			'mmsm_heading_text_color',
			__( 'Heading Text Color', 'maintenance-mode-studio' ),
			array( $this, 'render_heading_text_color_field' ),
			$this->page_slug,
			'mmsm_design_section'
		);

		add_settings_field(
			'mmsm_body_text_color',
			__( 'Body Text Color', 'maintenance-mode-studio' ),
			array( $this, 'render_body_text_color_field' ),
			$this->page_slug,
			'mmsm_design_section'
		);

		add_settings_field(
			'mmsm_muted_text_color',
			__( 'Muted Text Color', 'maintenance-mode-studio' ),
			array( $this, 'render_muted_text_color_field' ),
			$this->page_slug,
			'mmsm_design_section'
		);

		add_settings_field(
			'mmsm_link_text_color',
			__( 'Link Text Color', 'maintenance-mode-studio' ),
			array( $this, 'render_link_text_color_field' ),
			$this->page_slug,
			'mmsm_design_section'
		);

		add_settings_field(
			'mmsm_button_text_color',
			__( 'Button Text Color', 'maintenance-mode-studio' ),
			array( $this, 'render_button_text_color_field' ),
			$this->page_slug,
			'mmsm_design_section'
		);

		add_settings_field(
			'mmsm_border_color',
			__( 'Border Color', 'maintenance-mode-studio' ),
			array( $this, 'render_border_color_field' ),
			$this->page_slug,
			'mmsm_design_section'
		);

		add_settings_section(
			'mmsm_components_section',
			__( 'Components', 'maintenance-mode-studio' ),
			array( $this, 'render_components_section' ),
			$this->page_slug
		);

		add_settings_field(
			'mmsm_hero_eyebrow',
			__( 'Hero Eyebrow', 'maintenance-mode-studio' ),
			array( $this, 'render_hero_eyebrow_field' ),
			$this->page_slug,
			'mmsm_components_section'
		);

		add_settings_field(
			'mmsm_primary_action_label',
			__( 'Primary Action Label', 'maintenance-mode-studio' ),
			array( $this, 'render_primary_action_label_field' ),
			$this->page_slug,
			'mmsm_components_section'
		);

		add_settings_field(
			'mmsm_primary_action_url',
			__( 'Primary Action URL', 'maintenance-mode-studio' ),
			array( $this, 'render_primary_action_url_field' ),
			$this->page_slug,
			'mmsm_components_section'
		);

		add_settings_field(
			'mmsm_secondary_action_label',
			__( 'Secondary Action Label', 'maintenance-mode-studio' ),
			array( $this, 'render_secondary_action_label_field' ),
			$this->page_slug,
			'mmsm_components_section'
		);

		add_settings_field(
			'mmsm_secondary_action_url',
			__( 'Secondary Action URL', 'maintenance-mode-studio' ),
			array( $this, 'render_secondary_action_url_field' ),
			$this->page_slug,
			'mmsm_components_section'
		);

		add_settings_field(
			'mmsm_status_label',
			__( 'Status Label', 'maintenance-mode-studio' ),
			array( $this, 'render_status_label_field' ),
			$this->page_slug,
			'mmsm_components_section'
		);

		add_settings_field(
			'mmsm_show_progress',
			__( 'Show Progress', 'maintenance-mode-studio' ),
			array( $this, 'render_show_progress_field' ),
			$this->page_slug,
			'mmsm_components_section'
		);

		add_settings_field(
			'mmsm_progress_value',
			__( 'Progress Value', 'maintenance-mode-studio' ),
			array( $this, 'render_progress_value_field' ),
			$this->page_slug,
			'mmsm_components_section'
		);

		add_settings_field(
			'mmsm_contact_label',
			__( 'Contact Label', 'maintenance-mode-studio' ),
			array( $this, 'render_contact_label_field' ),
			$this->page_slug,
			'mmsm_components_section'
		);

		add_settings_field(
			'mmsm_contact_message',
			__( 'Contact Message', 'maintenance-mode-studio' ),
			array( $this, 'render_contact_message_field' ),
			$this->page_slug,
			'mmsm_components_section'
		);

		add_settings_field(
			'mmsm_contact_email',
			__( 'Contact Email', 'maintenance-mode-studio' ),
			array( $this, 'render_contact_email_field' ),
			$this->page_slug,
			'mmsm_components_section'
		);

		add_settings_section(
			'mmsm_social_links_section',
			__( 'Social Links', 'maintenance-mode-studio' ),
			array( $this, 'render_social_links_section' ),
			$this->page_slug
		);

		add_settings_field(
			'mmsm_social_links',
			__( 'Social Items', 'maintenance-mode-studio' ),
			array( $this, 'render_social_links_field' ),
			$this->page_slug,
			'mmsm_social_links_section'
		);

		add_settings_section(
			'mmsm_advanced_section',
			__( 'Advanced', 'maintenance-mode-studio' ),
			array( $this, 'render_advanced_section' ),
			$this->page_slug
		);

		add_settings_field(
			'mmsm_show_login_button',
			__( 'Show Login Button', 'maintenance-mode-studio' ),
			array( $this, 'render_show_login_button_field' ),
			$this->page_slug,
			'mmsm_advanced_section'
		);

		add_settings_field(
			'mmsm_show_footer_section',
			__( 'Show Footer Section', 'maintenance-mode-studio' ),
			array( $this, 'render_show_footer_section_field' ),
			$this->page_slug,
			'mmsm_advanced_section'
		);

		add_settings_field(
			'mmsm_delete_data_on_uninstall',
			__( 'Data Removal on Uninstall', 'maintenance-mode-studio' ),
			array( $this, 'render_delete_data_on_uninstall_field' ),
			$this->page_slug,
			'mmsm_advanced_section'
		);

		add_settings_field(
			'mmsm_login_label',
			__( 'Login Label', 'maintenance-mode-studio' ),
			array( $this, 'render_login_label_field' ),
			$this->page_slug,
			'mmsm_advanced_section'
		);
	}

	/**
	 * Render the settings page.
	 *
	 * @return void
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$active_tab = $this->get_active_tab();
		?>
		<div class="wrap mmsm-settings-page">
			<h1><?php echo esc_html__( 'Maintenance Mode Studio', 'maintenance-mode-studio' ); ?></h1>
			<p class="mmsm-settings-intro">
				<?php echo esc_html__( 'Configure the maintenance page template, core copy, and a few reusable components without editing code.', 'maintenance-mode-studio' ); ?>
			</p>

			<?php settings_errors( MMSM_SETTINGS_OPTION ); ?>
			<nav class="nav-tab-wrapper mmsm-settings-tabs" aria-label="<?php echo esc_attr__( 'Maintenance Mode Studio settings sections', 'maintenance-mode-studio' ); ?>">
				<?php foreach ( $this->get_tabs() as $tab_key => $tab ) : ?>
					<a
						href="<?php echo esc_url( $this->get_tab_url( $tab_key ) ); ?>"
						class="<?php echo esc_attr( 'nav-tab' . ( $active_tab === $tab_key ? ' nav-tab-active' : '' ) ); ?>"
					>
						<?php echo esc_html( $tab['label'] ); ?>
					</a>
				<?php endforeach; ?>
			</nav>

			<form action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>" method="post">
				<?php
					settings_fields( $this->settings_group );
					wp_nonce_field( 'mmsm_save_settings', 'mmsm_settings_nonce' );
					?>
					<input type="hidden" name="_wp_http_referer" value="<?php echo esc_attr( $this->get_tab_url( $active_tab ) ); ?>" />
					<input type="hidden" name="mmsm_active_tab" value="<?php echo esc_attr( $active_tab ); ?>" />
					<?php
					$this->render_active_tab();
				submit_button( __( 'Save Settings', 'maintenance-mode-studio' ) );
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Enqueue admin-only assets for the settings page.
	 *
	 * @param string $hook_suffix Current admin screen hook.
	 * @return void
	 */
	public function enqueue_assets( $hook_suffix ) {
		if ( $hook_suffix === $this->page_hook ) {
			wp_enqueue_style(
				'mmsm-admin-settings',
				MMSM_PLUGIN_URL . 'admin/assets/admin.css',
				array(),
				$this->get_asset_version( 'admin/assets/admin.css' )
			);

			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_media();

			wp_enqueue_script(
				'mmsm-admin-settings-script',
				MMSM_PLUGIN_URL . 'admin/assets/admin.js',
				array( 'jquery', 'wp-color-picker', 'wp-i18n' ),
				$this->get_asset_version( 'admin/assets/admin.js' ),
				true
			);

			wp_set_script_translations(
				'mmsm-admin-settings-script',
				'maintenance-mode-studio'
			);

			return;
		}

		if ( 'plugins.php' !== $hook_suffix || ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		wp_enqueue_style(
			'mmsm-admin-settings',
			MMSM_PLUGIN_URL . 'admin/assets/admin.css',
			array(),
			$this->get_asset_version( 'admin/assets/admin.css' )
		);

		wp_enqueue_script(
			'mmsm-plugin-feedback-script',
			MMSM_PLUGIN_URL . 'admin/assets/plugin-feedback.js',
			array( 'jquery' ),
			$this->get_asset_version( 'admin/assets/plugin-feedback.js' ),
			true
		);

		wp_localize_script(
			'mmsm-plugin-feedback-script',
			'mmsmPluginFeedback',
			array(
				'ajaxUrl'           => admin_url( 'admin-ajax.php' ),
				'nonce'             => wp_create_nonce( 'mmsm_capture_uninstall_feedback' ),
				'removeDataDefault' => $this->is_remove_data_enabled() ? '1' : '0',
			)
		);
	}

	/**
	 * Sanitize saved settings via the shared helper.
	 *
	 * @param mixed $input Raw option payload.
	 * @return array<string,mixed>
	 */
	public function sanitize_settings( $input ) {
		$input    = is_array( $input ) ? $input : array();
		$existing = $this->settings_repository->get_settings();

		if ( ! current_user_can( 'manage_options' ) ) {
			add_settings_error(
				MMSM_SETTINGS_OPTION,
				'mmsm_settings_capability_error',
				esc_html__( 'You are not allowed to update these settings.', 'maintenance-mode-studio' ),
				'error'
			);

			return $existing;
		}

		$nonce = isset( $_POST['mmsm_settings_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['mmsm_settings_nonce'] ) ) : '';

		if ( '' === $nonce || ! wp_verify_nonce( $nonce, 'mmsm_save_settings' ) ) {
			add_settings_error(
				MMSM_SETTINGS_OPTION,
				'mmsm_settings_nonce_error',
				esc_html__( 'The settings request could not be verified. Please try again.', 'maintenance-mode-studio' ),
				'error'
			);

			return $existing;
		}

		$active_tab = isset( $_POST['mmsm_active_tab'] ) ? sanitize_key( wp_unslash( $_POST['mmsm_active_tab'] ) ) : 'general';
		$tab_keys   = $this->get_tab_field_keys( $active_tab );

		foreach ( $tab_keys as $tab_key ) {
			unset( $existing[ $tab_key ] );
		}

		if ( 'social_links' === $active_tab && isset( $_POST['mmsm_social_links_present'] ) && ! isset( $input['social_links'] ) ) {
			$input['social_links'] = array();
		}

		$mmsm_sanitized_settings = Sanitizer::sanitize_settings( array_merge( $existing, $input ) );
		update_option( MMSM_REMOVE_DATA_OPTION, ! empty( $mmsm_sanitized_settings['delete_data_on_uninstall'] ) ? 1 : 0, false );

		return $mmsm_sanitized_settings;
	}

	/**
	 * Add uninstall feedback triggers to this plugin row actions.
	 *
	 * @param array<string,string> $actions Plugin action links.
	 * @return array<string,string>
	 */
	public function filter_plugin_action_links( array $actions ) {
		if ( current_user_can( 'manage_options' ) ) {
			$mmsm_settings_link = sprintf(
				'<a href="%1$s">%2$s</a>',
				esc_url( admin_url( 'options-general.php?page=' . $this->page_slug ) ),
				esc_html__( 'Settings', 'maintenance-mode-studio' )
			);

			$actions = array_merge(
				array(
					'settings' => $mmsm_settings_link,
				),
				$actions
			);
		}

		if ( isset( $actions['deactivate'] ) ) {
			$actions['deactivate'] = $this->decorate_plugin_action_link( $actions['deactivate'], 'deactivate' );
		}

		if ( isset( $actions['delete'] ) ) {
			$actions['delete'] = $this->decorate_plugin_action_link( $actions['delete'], 'delete' );
		}

		return $actions;
	}

	/**
	 * Render the plugins screen uninstall feedback modal.
	 *
	 * @return void
	 */
	public function render_uninstall_feedback_modal() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		$reasons = $this->get_uninstall_feedback_reasons();
		?>
		<div id="mmsm-uninstall-feedback-modal" class="mmsm-uninstall-feedback-modal is-hidden" aria-hidden="true">
			<div class="mmsm-uninstall-feedback-backdrop"></div>
			<div class="mmsm-uninstall-feedback-dialog" role="dialog" aria-modal="true" aria-labelledby="mmsm-uninstall-feedback-title">
				<button type="button" class="mmsm-uninstall-feedback-close" aria-label="<?php echo esc_attr__( 'Close uninstall feedback prompt', 'maintenance-mode-studio' ); ?>">
					<span aria-hidden="true">&times;</span>
				</button>

				<h2 id="mmsm-uninstall-feedback-title"><?php echo esc_html__( 'Before you go, would you like to share quick feedback?', 'maintenance-mode-studio' ); ?></h2>
				<p><?php echo esc_html__( 'This step is optional and will not block deactivation or deletion.', 'maintenance-mode-studio' ); ?></p>

				<div class="mmsm-uninstall-feedback-section">
					<p class="mmsm-uninstall-feedback-label"><?php echo esc_html__( 'Why are you removing this plugin?', 'maintenance-mode-studio' ); ?></p>
					<div class="mmsm-uninstall-feedback-reasons">
						<?php foreach ( $reasons as $reason_key => $reason_label ) : ?>
							<label class="mmsm-uninstall-feedback-choice">
								<input type="radio" name="mmsm_uninstall_reason" value="<?php echo esc_attr( $reason_key ); ?>" />
								<span><?php echo esc_html( $reason_label ); ?></span>
							</label>
						<?php endforeach; ?>
					</div>
				</div>

				<div class="mmsm-uninstall-feedback-section mmsm-uninstall-feedback-other is-hidden">
					<label for="mmsm-uninstall-feedback-details" class="mmsm-uninstall-feedback-label"><?php echo esc_html__( 'Anything else you want to share?', 'maintenance-mode-studio' ); ?></label>
					<textarea id="mmsm-uninstall-feedback-details" rows="4" class="widefat"></textarea>
				</div>

				<div class="mmsm-uninstall-feedback-section">
					<p class="mmsm-uninstall-feedback-label"><?php echo esc_html__( 'Do you also want to remove plugin data when uninstalling?', 'maintenance-mode-studio' ); ?></p>
					<label class="mmsm-uninstall-feedback-choice">
						<input type="radio" name="mmsm_remove_data" value="0" <?php checked( ! $this->is_remove_data_enabled() ); ?> />
						<span><?php echo esc_html__( 'Keep plugin data', 'maintenance-mode-studio' ); ?></span>
					</label>
					<label class="mmsm-uninstall-feedback-choice">
						<input type="radio" name="mmsm_remove_data" value="1" <?php checked( $this->is_remove_data_enabled() ); ?> />
						<span><?php echo esc_html__( 'Remove plugin data on uninstall', 'maintenance-mode-studio' ); ?></span>
					</label>
				</div>

				<p class="mmsm-uninstall-feedback-note"><?php echo esc_html__( 'Feedback is stored locally on this site only when you choose to submit it. Nothing is sent externally by default.', 'maintenance-mode-studio' ); ?></p>

				<div class="mmsm-uninstall-feedback-actions">
					<button type="button" class="button-link mmsm-uninstall-feedback-cancel"><?php echo esc_html__( 'Cancel', 'maintenance-mode-studio' ); ?></button>
					<button type="button" class="button mmsm-uninstall-feedback-skip"><?php echo esc_html__( 'Skip and continue', 'maintenance-mode-studio' ); ?></button>
					<button type="button" class="button button-primary mmsm-uninstall-feedback-submit"><?php echo esc_html__( 'Submit feedback and continue', 'maintenance-mode-studio' ); ?></button>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Store uninstall feedback and the remove-data preference.
	 *
	 * @return void
	 */
	public function handle_uninstall_feedback_request() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			wp_send_json_error( array( 'message' => __( 'You are not allowed to manage plugins.', 'maintenance-mode-studio' ) ), 403 );
		}

		check_ajax_referer( 'mmsm_capture_uninstall_feedback', 'nonce' );

		$mmsm_remove_data = isset( $_POST['remove_data'] ) && '1' === sanitize_text_field( wp_unslash( $_POST['remove_data'] ) );
		$mmsm_skip_feedback = isset( $_POST['skip_feedback'] ) && '1' === sanitize_text_field( wp_unslash( $_POST['skip_feedback'] ) );
		$mmsm_reason = isset( $_POST['reason'] ) ? sanitize_key( wp_unslash( $_POST['reason'] ) ) : '';
		$mmsm_details = isset( $_POST['details'] ) ? sanitize_textarea_field( wp_unslash( $_POST['details'] ) ) : '';
		$mmsm_plugin_action = isset( $_POST['plugin_action'] ) ? sanitize_key( wp_unslash( $_POST['plugin_action'] ) ) : 'deactivate';

		$this->sync_remove_data_preference( $mmsm_remove_data );

		if ( $mmsm_skip_feedback ) {
			wp_send_json_success();
		}

		$mmsm_allowed_reasons = array_keys( $this->get_uninstall_feedback_reasons() );
		$mmsm_feedback_reason = in_array( $mmsm_reason, $mmsm_allowed_reasons, true ) ? $mmsm_reason : '';

		if ( '' === $mmsm_feedback_reason && '' === $mmsm_details ) {
			wp_send_json_success();
		}

		$mmsm_feedback_log = get_option( MMSM_UNINSTALL_FEEDBACK_OPTION, array() );
		$mmsm_feedback_log = is_array( $mmsm_feedback_log ) ? $mmsm_feedback_log : array();
		$mmsm_feedback_log[] = array(
			'reason'        => $mmsm_feedback_reason,
			'details'       => $mmsm_details,
			'plugin_action' => in_array( $mmsm_plugin_action, array( 'deactivate', 'delete' ), true ) ? $mmsm_plugin_action : 'deactivate',
			'created_at'    => current_time( 'mysql' ),
		);

		if ( count( $mmsm_feedback_log ) > 20 ) {
			$mmsm_feedback_log = array_slice( $mmsm_feedback_log, -20 );
		}

		update_option( MMSM_UNINSTALL_FEEDBACK_OPTION, $mmsm_feedback_log, false );

		wp_send_json_success();
	}

	/**
	 * Render the general section description.
	 *
	 * @return void
	 */
	public function render_general_section() {
		echo '<p>' . esc_html__( 'These settings control the core public experience shown to logged-out visitors.', 'maintenance-mode-studio' ) . '</p>';
	}

	/**
	 * Render the appearance section description.
	 *
	 * @return void
	 */
	public function render_template_section() {
		echo '<p>' . esc_html__( 'Pick the public template shell and the maintenance mode presentation style.', 'maintenance-mode-studio' ) . '</p>';
	}

	/**
	 * Render the design section description.
	 *
	 * @return void
	 */
	public function render_design_section() {
		echo '<p>' . esc_html__( 'Use WordPress color pickers for the safe theme color roles that drive light, dark, and system modes.', 'maintenance-mode-studio' ) . '</p>';
	}

	/**
	 * Render the component section description.
	 *
	 * @return void
	 */
	public function render_components_section() {
		echo '<p>' . esc_html__( 'These optional settings feed the hero, status, and contact components rendered by the default template.', 'maintenance-mode-studio' ) . '</p>';
	}

	/**
	 * Render the social links section description.
	 *
	 * @return void
	 */
	public function render_social_links_section() {
		echo '<p>' . esc_html__( 'Choose up to four social or contact destinations with safe platform icons, labels, and URLs.', 'maintenance-mode-studio' ) . '</p>';
	}

	/**
	 * Render the advanced section description.
	 *
	 * @return void
	 */
	public function render_advanced_section() {
		echo '<p>' . esc_html__( 'Control optional access, login affordances, and uninstall cleanup without affecting administrator bypass behavior.', 'maintenance-mode-studio' ) . '</p>';
	}

	/**
	 * Render the enabled field.
	 *
	 * @return void
	 */
	public function render_enabled_field() {
		$settings = $this->get_settings();
		?>
		<label for="mmsm-enabled">
			<input
				type="checkbox"
				id="mmsm-enabled"
				name="<?php echo esc_attr( MMSM_SETTINGS_OPTION ); ?>[enabled]"
				value="1"
				<?php checked( 1, (int) $settings['enabled'] ); ?>
			/>
			<?php echo esc_html__( 'Show the maintenance page to logged-out visitors.', 'maintenance-mode-studio' ); ?>
		</label>
		<p class="description"><?php echo esc_html__( 'Administrators keep normal site access while this is enabled.', 'maintenance-mode-studio' ); ?></p>
		<?php
	}

	/**
	 * Render the mode type field.
	 *
	 * @return void
	 */
	public function render_mode_type_field() {
		$settings = $this->get_settings();
		?>
		<select id="mmsm-mode-type" name="<?php echo esc_attr( MMSM_SETTINGS_OPTION ); ?>[mode_type]">
			<option value="maintenance" <?php selected( $settings['mode_type'], 'maintenance' ); ?>>
				<?php echo esc_html__( 'Maintenance', 'maintenance-mode-studio' ); ?>
			</option>
			<option value="coming_soon" <?php selected( $settings['mode_type'], 'coming_soon' ); ?>>
				<?php echo esc_html__( 'Coming Soon', 'maintenance-mode-studio' ); ?>
			</option>
		</select>
		<p class="description"><?php echo esc_html__( 'Choose whether the public page should show as maintenance mode or coming soon mode.', 'maintenance-mode-studio' ); ?></p>
		<?php
	}

	/**
	 * Render the page title field.
	 *
	 * @return void
	 */
	public function render_page_title_field() {
		$settings = $this->get_settings();
		?>
		<input
			type="text"
			class="regular-text"
			id="mmsm-page-title"
			name="<?php echo esc_attr( MMSM_SETTINGS_OPTION ); ?>[page_title]"
			value="<?php echo esc_attr( $settings['page_title'] ); ?>"
		/>
		<p class="description"><?php echo esc_html__( 'This title appears as the main heading on the public page.', 'maintenance-mode-studio' ); ?></p>
		<?php
	}

	/**
	 * Render the message field.
	 *
	 * @return void
	 */
	public function render_message_field() {
		$settings = $this->get_settings();
		?>
		<textarea
			class="large-text"
			rows="5"
			id="mmsm-message"
			name="<?php echo esc_attr( MMSM_SETTINGS_OPTION ); ?>[message]"
		><?php echo esc_textarea( $settings['message'] ); ?></textarea>
		<p class="description"><?php echo esc_html__( 'Plain text only in this phase.', 'maintenance-mode-studio' ); ?></p>
		<?php
	}

	/**
	 * Render the login button visibility field.
	 *
	 * @return void
	 */
	public function render_show_login_button_field() {
		$settings = $this->get_settings();
		?>
		<label for="mmsm-show-login-button">
			<input
				type="checkbox"
				id="mmsm-show-login-button"
				name="<?php echo esc_attr( MMSM_SETTINGS_OPTION ); ?>[show_login_button]"
				value="1"
				<?php checked( 1, (int) $settings['show_login_button'] ); ?>
			/>
			<?php echo esc_html__( 'Display the default login button on the public page.', 'maintenance-mode-studio' ); ?>
		</label>
		<p class="description"><?php echo esc_html__( 'Turn this off if visitors should not see a login shortcut.', 'maintenance-mode-studio' ); ?></p>
		<?php
	}

	/**
	 * Render the theme mode field.
	 *
	 * @return void
	 */
	public function render_theme_mode_field() {
		$settings = $this->get_settings();
		?>
		<select id="mmsm-theme-mode" name="<?php echo esc_attr( MMSM_SETTINGS_OPTION ); ?>[theme_mode]">
			<option value="light" <?php selected( $settings['theme_mode'], 'light' ); ?>>
				<?php echo esc_html__( 'Light', 'maintenance-mode-studio' ); ?>
			</option>
			<option value="dark" <?php selected( $settings['theme_mode'], 'dark' ); ?>>
				<?php echo esc_html__( 'Dark', 'maintenance-mode-studio' ); ?>
			</option>
			<option value="system" <?php selected( $settings['theme_mode'], 'system' ); ?>>
				<?php echo esc_html__( 'System', 'maintenance-mode-studio' ); ?>
			</option>
		</select>
		<p class="description"><?php echo esc_html__( 'Choose a light, dark, or system-following visual style for the active template.', 'maintenance-mode-studio' ); ?></p>
		<?php
	}

	/**
	 * Render the template field.
	 *
	 * @return void
	 */
	public function render_template_key_field() {
		$settings = $this->get_settings();
		?>
		<select id="mmsm-template-key" name="<?php echo esc_attr( MMSM_SETTINGS_OPTION ); ?>[template_key]">
			<option value="default" <?php selected( $settings['template_key'], 'default' ); ?>>
				<?php echo esc_html__( 'Default', 'maintenance-mode-studio' ); ?>
			</option>
		</select>
		<p class="description"><?php echo esc_html__( 'This release includes one polished default template.', 'maintenance-mode-studio' ); ?></p>
		<?php
	}

	/**
	 * Render the primary color field.
	 *
	 * @return void
	 */
	public function render_primary_color_field() {
		$this->render_color_picker_input(
			'primary_color',
			'mmsm-primary-color',
			__( 'Accent color used for buttons, focus accents, and status styling.', 'maintenance-mode-studio' )
		);
	}

	/**
	 * Render the background color field.
	 *
	 * @return void
	 */
	public function render_background_color_field() {
		$this->render_color_picker_input(
			'background_color',
			'mmsm-background-color',
			__( 'Page background color. Invalid values fall back to the theme default.', 'maintenance-mode-studio' )
		);
	}

	/**
	 * Render the surface color field.
	 *
	 * @return void
	 */
	public function render_surface_color_field() {
		$this->render_color_picker_input(
			'surface_color',
			'mmsm-surface-color',
			__( 'Card and panel surface color.', 'maintenance-mode-studio' )
		);
	}

	/**
	 * Render the heading text color field.
	 *
	 * @return void
	 */
	public function render_heading_text_color_field() {
		$this->render_color_picker_input(
			'heading_text_color',
			'mmsm-heading-text-color',
			__( 'Main heading and section title color.', 'maintenance-mode-studio' )
		);
	}

	/**
	 * Render the body text color field.
	 *
	 * @return void
	 */
	public function render_body_text_color_field() {
		$this->render_color_picker_input(
			'body_text_color',
			'mmsm-body-text-color',
			__( 'Primary body copy color for messages and descriptions.', 'maintenance-mode-studio' )
		);
	}

	/**
	 * Render the muted text color field.
	 *
	 * @return void
	 */
	public function render_muted_text_color_field() {
		$this->render_color_picker_input(
			'muted_text_color',
			'mmsm-muted-text-color',
			__( 'Secondary copy color for quieter supporting text.', 'maintenance-mode-studio' )
		);
	}

	/**
	 * Render the link text color field.
	 *
	 * @return void
	 */
	public function render_link_text_color_field() {
		$this->render_color_picker_input(
			'link_text_color',
			'mmsm-link-text-color',
			__( 'Link and social label color.', 'maintenance-mode-studio' )
		);
	}

	/**
	 * Render the button text color field.
	 *
	 * @return void
	 */
	public function render_button_text_color_field() {
		$this->render_color_picker_input(
			'button_text_color',
			'mmsm-button-text-color',
			__( 'Text color shown on primary buttons.', 'maintenance-mode-studio' )
		);
	}

	/**
	 * Render the border color field.
	 *
	 * @return void
	 */
	public function render_border_color_field() {
		$this->render_color_picker_input(
			'border_color',
			'mmsm-border-color',
			__( 'Border color for cards, pills, and link chips.', 'maintenance-mode-studio' )
		);
	}

	/**
	 * Render the hero eyebrow field.
	 *
	 * @return void
	 */
	public function render_hero_eyebrow_field() {
		$this->render_text_input(
			'hero_eyebrow',
			'mmsm-hero-eyebrow',
			__( 'Optional short label above the page title.', 'maintenance-mode-studio' )
		);
	}

	/**
	 * Render the primary action label field.
	 *
	 * @return void
	 */
	public function render_primary_action_label_field() {
		$this->render_text_input(
			'primary_action_label',
			'mmsm-primary-action-label',
			__( 'Leave blank to hide the primary action, or pair it with a valid URL.', 'maintenance-mode-studio' )
		);
	}

	/**
	 * Render the primary action URL field.
	 *
	 * @return void
	 */
	public function render_primary_action_url_field() {
		$this->render_url_input(
			'primary_action_url',
			'mmsm-primary-action-url',
			__( 'Use a full public URL such as https://example.com/status.', 'maintenance-mode-studio' )
		);
	}

	/**
	 * Render the secondary action label field.
	 *
	 * @return void
	 */
	public function render_secondary_action_label_field() {
		$this->render_text_input(
			'secondary_action_label',
			'mmsm-secondary-action-label',
			__( 'Optional secondary action label.', 'maintenance-mode-studio' )
		);
	}

	/**
	 * Render the secondary action URL field.
	 *
	 * @return void
	 */
	public function render_secondary_action_url_field() {
		$this->render_url_input(
			'secondary_action_url',
			'mmsm-secondary-action-url',
			__( 'Use a full public URL or leave blank.', 'maintenance-mode-studio' )
		);
	}

	/**
	 * Render the status label field.
	 *
	 * @return void
	 */
	public function render_status_label_field() {
		$this->render_text_input(
			'status_label',
			'mmsm-status-label',
			__( 'Shown above the progress bar component.', 'maintenance-mode-studio' )
		);
	}

	/**
	 * Render the show progress field.
	 *
	 * @return void
	 */
	public function render_show_progress_field() {
		$settings = $this->get_settings();
		?>
		<label for="mmsm-show-progress">
			<input
				type="checkbox"
				id="mmsm-show-progress"
				name="<?php echo esc_attr( MMSM_SETTINGS_OPTION ); ?>[show_progress]"
				value="1"
				<?php checked( 1, (int) $settings['show_progress'] ); ?>
			/>
			<?php echo esc_html__( 'Display the status progress bar.', 'maintenance-mode-studio' ); ?>
		</label>
		<p class="description"><?php echo esc_html__( 'Turn this off if you only want the status text.', 'maintenance-mode-studio' ); ?></p>
		<?php
	}

	/**
	 * Render the progress value field.
	 *
	 * @return void
	 */
	public function render_progress_value_field() {
		$settings = $this->get_settings();
		?>
		<input
			type="number"
			class="small-text"
			id="mmsm-progress-value"
			name="<?php echo esc_attr( MMSM_SETTINGS_OPTION ); ?>[progress_value]"
			min="0"
			max="100"
			value="<?php echo esc_attr( (string) $settings['progress_value'] ); ?>"
		/>
		<p class="description"><?php echo esc_html__( 'A number between 0 and 100.', 'maintenance-mode-studio' ); ?></p>
		<?php
	}

	/**
	 * Render the contact label field.
	 *
	 * @return void
	 */
	public function render_contact_label_field() {
		$this->render_text_input(
			'contact_label',
			'mmsm-contact-label',
			__( 'Short heading for the contact block.', 'maintenance-mode-studio' )
		);
	}

	/**
	 * Render the contact message field.
	 *
	 * @return void
	 */
	public function render_contact_message_field() {
		$this->render_text_input(
			'contact_message',
			'mmsm-contact-message',
			__( 'Explain when visitors should reach out.', 'maintenance-mode-studio' )
		);
	}

	/**
	 * Render the contact email field.
	 *
	 * @return void
	 */
	public function render_contact_email_field() {
		$settings = $this->get_settings();
		?>
		<input
			type="email"
			class="regular-text"
			id="mmsm-contact-email"
			name="<?php echo esc_attr( MMSM_SETTINGS_OPTION ); ?>[contact_email]"
			value="<?php echo esc_attr( $settings['contact_email'] ); ?>"
			placeholder="support@example.com"
		/>
		<p class="description"><?php echo esc_html__( 'Only valid email addresses are rendered publicly.', 'maintenance-mode-studio' ); ?></p>
		<?php
	}

	/**
	 * Render the login label field.
	 *
	 * @return void
	 */
	public function render_login_label_field() {
		$this->render_text_input(
			'login_label',
			'mmsm-login-label',
			__( 'Used when the login button is enabled.', 'maintenance-mode-studio' )
		);
	}

	/**
	 * Render the footer visibility field.
	 *
	 * @return void
	 */
	public function render_show_footer_section_field() {
		$settings = $this->get_settings();
		?>
		<label for="mmsm-show-footer-section">
			<input
				type="checkbox"
				id="mmsm-show-footer-section"
				name="<?php echo esc_attr( MMSM_SETTINGS_OPTION ); ?>[show_footer_section]"
				value="1"
				<?php checked( 1, (int) $settings['show_footer_section'] ); ?>
			/>
			<?php echo esc_html__( 'Render the footer panel under the main card.', 'maintenance-mode-studio' ); ?>
		</label>
		<p class="description"><?php echo esc_html__( 'Turn this off to hide the footer meta, social links, and login shortcut area.', 'maintenance-mode-studio' ); ?></p>
		<?php
	}

	/**
	 * Render the uninstall cleanup preference field.
	 *
	 * @return void
	 */
	public function render_delete_data_on_uninstall_field() {
		$mmsm_remove_data_enabled = $this->is_remove_data_enabled();
		?>
		<label for="mmsm-delete-data-on-uninstall">
			<input
				type="checkbox"
				id="mmsm-delete-data-on-uninstall"
				name="<?php echo esc_attr( MMSM_SETTINGS_OPTION ); ?>[delete_data_on_uninstall]"
				value="1"
				<?php checked( true, $mmsm_remove_data_enabled ); ?>
			/>
			<?php echo esc_html__( 'Delete plugin settings when the plugin is removed.', 'maintenance-mode-studio' ); ?>
		</label>
		<p class="description"><?php echo esc_html__( 'Leave this unchecked to keep your settings for a future reinstall. Check it only if you want uninstall to permanently remove plugin data.', 'maintenance-mode-studio' ); ?></p>
		<?php
	}

	/**
	 * Render the social links repeater field.
	 *
	 * @return void
	 */
	public function render_social_links_field() {
		$settings      = $this->get_settings();
		$social_links  = isset( $settings['social_links'] ) && is_array( $settings['social_links'] ) ? array_values( $settings['social_links'] ) : array();
		$default_item  = $this->get_default_social_item();
		$platforms     = SocialLinksComponent::get_platform_labels();

		if ( empty( $social_links ) ) {
			$social_links = array( $default_item );
		}
		?>
		<input type="hidden" name="mmsm_social_links_present" value="1" />
		<div class="mmsm-social-links-builder" data-next-index="<?php echo esc_attr( (string) count( $social_links ) ); ?>">
			<div class="mmsm-social-links-list">
				<?php foreach ( $social_links as $index => $social_item ) : ?>
					<?php $this->render_social_link_row( $index, is_array( $social_item ) ? $social_item : $default_item, $platforms ); ?>
				<?php endforeach; ?>
			</div>
			<p>
				<button type="button" class="button button-secondary mmsm-add-social-item"><?php echo esc_html__( 'Add more', 'maintenance-mode-studio' ); ?></button>
			</p>
			<script type="text/template" class="mmsm-social-item-template">
				<?php $this->render_social_link_row( '__INDEX__', $default_item, $platforms ); ?>
			</script>
			<p class="description"><?php echo esc_html__( 'Known platforms use built-in labels. Choose Custom only when you need a custom name and uploaded image icon.', 'maintenance-mode-studio' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Read settings with defaults applied.
	 *
	 * @return array<string,mixed>
	 */
	private function get_settings() {
		return $this->settings_repository->get_settings();
	}

	/**
	 * Render a text input.
	 *
	 * @param string $key Field key.
	 * @param string $id Input id.
	 * @param string $description Help text.
	 * @return void
	 */
	private function render_text_input( $key, $id, $description ) {
		$settings = $this->get_settings();
		?>
		<input
			type="text"
			class="regular-text"
			id="<?php echo esc_attr( $id ); ?>"
			name="<?php echo esc_attr( MMSM_SETTINGS_OPTION ); ?>[<?php echo esc_attr( $key ); ?>]"
			value="<?php echo esc_attr( (string) $settings[ $key ] ); ?>"
		/>
		<p class="description"><?php echo esc_html( $description ); ?></p>
		<?php
	}

	/**
	 * Render a URL input.
	 *
	 * @param string $key Field key.
	 * @param string $id Input id.
	 * @param string $description Help text.
	 * @return void
	 */
	private function render_url_input( $key, $id, $description ) {
		$settings = $this->get_settings();
		?>
		<input
			type="url"
			class="regular-text code"
			id="<?php echo esc_attr( $id ); ?>"
			name="<?php echo esc_attr( MMSM_SETTINGS_OPTION ); ?>[<?php echo esc_attr( $key ); ?>]"
			value="<?php echo esc_attr( (string) $settings[ $key ] ); ?>"
			placeholder="https://"
		/>
		<p class="description"><?php echo esc_html( $description ); ?></p>
		<?php
	}

	/**
	 * Render a WordPress color picker input.
	 *
	 * @param string $key Field key.
	 * @param string $id Input id.
	 * @param string $description Help text.
	 * @return void
	 */
	private function render_color_picker_input( $key, $id, $description ) {
		$settings = $this->get_settings();
		?>
		<input
			type="text"
			class="mmsm-color-picker"
			id="<?php echo esc_attr( $id ); ?>"
			name="<?php echo esc_attr( MMSM_SETTINGS_OPTION ); ?>[<?php echo esc_attr( $key ); ?>]"
			value="<?php echo esc_attr( (string) $settings[ $key ] ); ?>"
			data-default-color="<?php echo esc_attr( (string) Sanitizer::get_default_settings()[ $key ] ); ?>"
		/>
		<p class="description"><?php echo esc_html( $description ); ?></p>
		<?php
	}

	/**
	 * Render a single social link repeater row.
	 *
	 * @param int|string               $index Row index.
	 * @param array<string,int|string> $item Social item values.
	 * @param array<string,string>     $platforms Supported platforms.
	 * @return void
	 */
	private function render_social_link_row( $index, array $item, array $platforms ) {
		$platform        = isset( $item['platform'] ) ? (string) $item['platform'] : 'facebook';
		$url             = isset( $item['url'] ) ? (string) $item['url'] : '';
		$custom_name     = isset( $item['custom_name'] ) ? (string) $item['custom_name'] : '';
		$custom_icon_id  = isset( $item['custom_icon_id'] ) ? absint( $item['custom_icon_id'] ) : 0;
		$icon_source     = isset( $item['icon_source'] ) ? (string) $item['icon_source'] : 'platform';
		$icon_library    = isset( $item['icon_library'] ) ? (string) $item['icon_library'] : 'dashicons';
		$icon_value      = isset( $item['icon_value'] ) ? (string) $item['icon_value'] : 'share';
		$icon_color      = isset( $item['icon_color'] ) ? (string) $item['icon_color'] : '';
		$open_new_tab    = ! empty( $item['open_new_tab'] );
		$custom_icon_url = $custom_icon_id > 0 ? wp_get_attachment_url( $custom_icon_id ) : '';
		$is_custom       = 'custom' === $platform;
		$is_upload       = 'upload' === $icon_source;
		$is_library      = 'library' === $icon_source;
		$icon_sources    = SocialLinksComponent::get_icon_source_labels();
		$icon_libraries  = SocialLinksComponent::get_icon_libraries();
		$dashicons       = SocialLinksComponent::get_dashicon_choices();
		?>
		<div class="mmsm-social-item-group" data-social-item>
			<div class="mmsm-social-item-toolbar">
				<strong><?php echo esc_html__( 'Social item', 'maintenance-mode-studio' ); ?></strong>
				<button type="button" class="button-link-delete mmsm-remove-social-item"><?php echo esc_html__( 'Remove', 'maintenance-mode-studio' ); ?></button>
			</div>
			<p>
				<label><?php echo esc_html__( 'Platform', 'maintenance-mode-studio' ); ?></label><br />
				<select
					class="mmsm-social-platform-select"
					name="<?php echo esc_attr( MMSM_SETTINGS_OPTION ); ?>[social_links][<?php echo esc_attr( (string) $index ); ?>][platform]"
				>
					<?php foreach ( $platforms as $platform_key => $platform_label ) : ?>
						<option value="<?php echo esc_attr( $platform_key ); ?>" <?php selected( $platform, $platform_key ); ?>>
							<?php echo esc_html( $platform_label ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</p>
			<p>
				<label><?php echo esc_html__( 'URL or Email', 'maintenance-mode-studio' ); ?></label><br />
				<input
					type="text"
					class="regular-text code"
					name="<?php echo esc_attr( MMSM_SETTINGS_OPTION ); ?>[social_links][<?php echo esc_attr( (string) $index ); ?>][url]"
					value="<?php echo esc_attr( $url ); ?>"
					placeholder="https://example.com or hello@example.com"
				/>
			</p>
			<div class="mmsm-social-custom-fields<?php echo $is_custom ? '' : ' is-hidden'; ?>" data-custom-fields>
				<p>
					<label><?php echo esc_html__( 'Custom Platform Name', 'maintenance-mode-studio' ); ?></label><br />
					<input
						type="text"
						class="regular-text"
						name="<?php echo esc_attr( MMSM_SETTINGS_OPTION ); ?>[social_links][<?php echo esc_attr( (string) $index ); ?>][custom_name]"
						value="<?php echo esc_attr( $custom_name ); ?>"
					/>
				</p>
			</div>
			<div class="mmsm-social-icon-picker">
				<p>
					<label><?php echo esc_html__( 'Icon Source', 'maintenance-mode-studio' ); ?></label><br />
					<select
						class="mmsm-social-icon-source-select"
						name="<?php echo esc_attr( MMSM_SETTINGS_OPTION ); ?>[social_links][<?php echo esc_attr( (string) $index ); ?>][icon_source]"
					>
						<?php foreach ( $icon_sources as $source_key => $source_label ) : ?>
							<option value="<?php echo esc_attr( $source_key ); ?>" <?php selected( $icon_source, $source_key ); ?>>
								<?php echo esc_html( $source_label ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</p>
				<div class="mmsm-social-icon-library-fields<?php echo $is_library ? '' : ' is-hidden'; ?>" data-icon-library-fields>
					<p>
						<label><?php echo esc_html__( 'Icon Library', 'maintenance-mode-studio' ); ?></label><br />
						<select
							class="mmsm-social-icon-library-select"
							name="<?php echo esc_attr( MMSM_SETTINGS_OPTION ); ?>[social_links][<?php echo esc_attr( (string) $index ); ?>][icon_library]"
						>
							<?php foreach ( $icon_libraries as $library_key => $library ) : ?>
								<option value="<?php echo esc_attr( $library_key ); ?>" <?php selected( $icon_library, $library_key ); ?>>
									<?php echo esc_html( isset( $library['label'] ) ? (string) $library['label'] : $library_key ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</p>
					<p>
						<label><?php echo esc_html__( 'Library Icon', 'maintenance-mode-studio' ); ?></label><br />
						<select
							class="mmsm-social-icon-value-select"
							name="<?php echo esc_attr( MMSM_SETTINGS_OPTION ); ?>[social_links][<?php echo esc_attr( (string) $index ); ?>][icon_value]"
						>
							<?php foreach ( $dashicons as $dashicon_key => $dashicon_label ) : ?>
								<option value="<?php echo esc_attr( $dashicon_key ); ?>" <?php selected( $icon_value, $dashicon_key ); ?>>
									<?php echo esc_html( $dashicon_label ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</p>
				</div>
				<div class="mmsm-social-icon-upload-fields<?php echo $is_upload ? '' : ' is-hidden'; ?>" data-icon-upload-fields>
					<input
						type="hidden"
						class="mmsm-social-icon-id"
						name="<?php echo esc_attr( MMSM_SETTINGS_OPTION ); ?>[social_links][<?php echo esc_attr( (string) $index ); ?>][custom_icon_id]"
						value="<?php echo esc_attr( (string) $custom_icon_id ); ?>"
					/>
					<div class="mmsm-social-icon-preview-wrap">
						<img
							class="mmsm-social-icon-preview<?php echo empty( $custom_icon_url ) ? ' is-hidden' : ''; ?>"
							src="<?php echo esc_url( ! empty( $custom_icon_url ) ? $custom_icon_url : '' ); ?>"
							alt=""
						/>
					</div>
					<p>
						<button type="button" class="button mmsm-upload-social-icon"><?php echo esc_html__( 'Choose icon', 'maintenance-mode-studio' ); ?></button>
						<button type="button" class="button-link-delete mmsm-remove-social-icon<?php echo 0 === $custom_icon_id ? ' is-hidden' : ''; ?>"><?php echo esc_html__( 'Remove icon', 'maintenance-mode-studio' ); ?></button>
					</p>
					<p class="description"><?php echo esc_html__( 'Uploaded icons use the media library. PNG, JPG, and WEBP are accepted.', 'maintenance-mode-studio' ); ?></p>
				</div>
				<p>
					<label><?php echo esc_html__( 'Icon Color', 'maintenance-mode-studio' ); ?></label><br />
					<input
						type="text"
						class="mmsm-color-picker mmsm-social-icon-color-picker"
						name="<?php echo esc_attr( MMSM_SETTINGS_OPTION ); ?>[social_links][<?php echo esc_attr( (string) $index ); ?>][icon_color]"
						value="<?php echo esc_attr( $icon_color ); ?>"
						data-default-color=""
					/>
				</p>
				<p class="description"><?php echo esc_html__( 'Applies to built-in and library icons. Uploaded image icons keep their original colors.', 'maintenance-mode-studio' ); ?></p>
			</div>
			<p>
				<label>
					<input
						type="checkbox"
						name="<?php echo esc_attr( MMSM_SETTINGS_OPTION ); ?>[social_links][<?php echo esc_attr( (string) $index ); ?>][open_new_tab]"
						value="1"
						<?php checked( $open_new_tab ); ?>
					/>
					<?php echo esc_html__( 'Open in a new tab when supported.', 'maintenance-mode-studio' ); ?>
				</label>
			</p>
		</div>
		<?php
	}

	/**
	 * Return the default admin social row.
	 *
	 * @return array<string,int|string>
	 */
	private function get_default_social_item() {
		return array(
			'platform'       => 'facebook',
			'url'            => '',
			'custom_name'    => '',
			'custom_icon_id' => 0,
			'icon_source'    => 'platform',
			'icon_library'   => 'dashicons',
			'icon_value'     => 'share',
			'icon_color'     => '',
			'open_new_tab'   => 1,
		);
	}

	/**
	 * Return available settings tabs.
	 *
	 * @return array<string,array<string,string>>
	 */
	private function get_tabs() {
		return array(
			'general'      => array(
				'label'   => __( 'General', 'maintenance-mode-studio' ),
				'section' => 'mmsm_general_section',
			),
			'template'     => array(
				'label'   => __( 'Template', 'maintenance-mode-studio' ),
				'section' => 'mmsm_template_section',
			),
			'design'       => array(
				'label'   => __( 'Design', 'maintenance-mode-studio' ),
				'section' => 'mmsm_design_section',
			),
			'components'   => array(
				'label'   => __( 'Components', 'maintenance-mode-studio' ),
				'section' => 'mmsm_components_section',
			),
			'social_links' => array(
				'label'   => __( 'Social Links', 'maintenance-mode-studio' ),
				'section' => 'mmsm_social_links_section',
			),
			'advanced'     => array(
				'label'   => __( 'Advanced', 'maintenance-mode-studio' ),
				'section' => 'mmsm_advanced_section',
			),
		);
	}

	/**
	 * Return the currently requested tab, falling back safely.
	 *
	 * @return string
	 */
	private function get_active_tab() {
		$tabs = $this->get_tabs();
		$tab  = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'general'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only tab selection for admin UI state, sanitized and not persisted.

		if ( ! isset( $tabs[ $tab ] ) ) {
			return 'general';
		}

		return $tab;
	}

	/**
	 * Render the currently active tab section.
	 *
	 * @return void
	 */
	private function render_active_tab() {
		$tabs       = $this->get_tabs();
		$active_tab = $this->get_active_tab();
		$section_id = $tabs[ $active_tab ]['section'];

		$this->render_section_fields( $section_id );
	}

	/**
	 * Render a registered section title, description, and fields.
	 *
	 * @param string $section_id Settings section id.
	 * @return void
	 */
	private function render_section_fields( $section_id ) {
		global $wp_settings_sections, $wp_settings_fields;

		if ( ! isset( $wp_settings_sections[ $this->page_slug ][ $section_id ] ) ) {
			return;
		}

		$section = $wp_settings_sections[ $this->page_slug ][ $section_id ];
		$has_fields = ! empty( $wp_settings_fields[ $this->page_slug ][ $section_id ] );
		?>
		<div class="mmsm-settings-panel">
			<?php if ( ! empty( $section['title'] ) ) : ?>
				<h2 class="title"><?php echo esc_html( $section['title'] ); ?></h2>
			<?php endif; ?>
			<?php
			if ( ! empty( $section['callback'] ) ) {
				call_user_func( $section['callback'], $section );
			}
			?>
			<?php if ( $has_fields ) : ?>
				<table class="form-table" role="presentation">
					<?php do_settings_fields( $this->page_slug, $section_id ); ?>
				</table>
			<?php else : ?>
				<p class="description"><?php echo esc_html__( 'No extra settings are available in this tab yet.', 'maintenance-mode-studio' ); ?></p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Build a settings tab URL.
	 *
	 * @param string $tab_key Tab key.
	 * @return string
	 */
	private function get_tab_url( $tab_key ) {
		return add_query_arg(
			array(
				'page' => $this->page_slug,
				'tab'  => $tab_key,
			),
			admin_url( 'options-general.php' )
		);
	}

	/**
	 * Resolve an asset version from file modification time with a safe fallback.
	 *
	 * @param string $relative_path Asset path relative to the plugin root.
	 * @return string
	 */
	private function get_asset_version( $relative_path ) {
		$absolute_path = MMSM_PLUGIN_PATH . ltrim( $relative_path, '/' );

		if ( file_exists( $absolute_path ) ) {
			return (string) filemtime( $absolute_path );
		}

		return MMSM_VERSION;
	}

	/**
	 * Add modal trigger attributes to a plugin action link.
	 *
	 * @param string $markup Existing action link markup.
	 * @param string $action Plugin action key.
	 * @return string
	 */
	private function decorate_plugin_action_link( $markup, $action ) {
		$action = in_array( $action, array( 'deactivate', 'delete' ), true ) ? $action : 'deactivate';

		if ( false !== strpos( $markup, 'mmsm-uninstall-feedback-trigger' ) ) {
			return $markup;
		}

		if ( false !== strpos( $markup, 'class=' ) ) {
			$decorated = preg_replace(
				'/class=(["\'])(.*?)\1/',
				'class=$1$2 mmsm-uninstall-feedback-trigger$1 data-mmsm-plugin-action="' . esc_attr( $action ) . '"',
				$markup,
				1
			);

			return is_string( $decorated ) ? $decorated : $markup;
		}

		$decorated = preg_replace(
			'/<a\s/',
			'<a class="mmsm-uninstall-feedback-trigger" data-mmsm-plugin-action="' . esc_attr( $action ) . '" ',
			$markup,
			1
		);

		return is_string( $decorated ) ? $decorated : $markup;
	}

	/**
	 * Return uninstall feedback reason labels.
	 *
	 * @return array<string,string>
	 */
	private function get_uninstall_feedback_reasons() {
		return array(
			'no_longer_needed'      => __( 'I no longer need the plugin', 'maintenance-mode-studio' ),
			'did_not_work'          => __( 'The plugin did not work as expected', 'maintenance-mode-studio' ),
			'caused_issue'          => __( 'The plugin caused an issue on my site', 'maintenance-mode-studio' ),
			'missing_features'      => __( 'The plugin is missing features I need', 'maintenance-mode-studio' ),
			'too_difficult'         => __( 'The plugin is too difficult to use', 'maintenance-mode-studio' ),
			'found_alternative'     => __( 'I found a better alternative', 'maintenance-mode-studio' ),
			'troubleshooting'       => __( 'I am troubleshooting temporarily', 'maintenance-mode-studio' ),
			'other'                 => __( 'Other', 'maintenance-mode-studio' ),
		);
	}

	/**
	 * Determine whether data removal is currently enabled.
	 *
	 * @return bool
	 */
	private function is_remove_data_enabled() {
		$mmsm_remove_data = get_option( MMSM_REMOVE_DATA_OPTION, null );

		if ( null !== $mmsm_remove_data ) {
			return ! empty( $mmsm_remove_data );
		}

		$mmsm_settings = get_option( MMSM_SETTINGS_OPTION, array() );

		return is_array( $mmsm_settings ) && ! empty( $mmsm_settings['delete_data_on_uninstall'] );
	}

	/**
	 * Save the uninstall data-removal preference in both supported locations.
	 *
	 * @param bool $enabled Whether plugin data should be removed on uninstall.
	 * @return void
	 */
	private function sync_remove_data_preference( $enabled ) {
		$mmsm_enabled = $enabled ? 1 : 0;

		update_option( MMSM_REMOVE_DATA_OPTION, $mmsm_enabled, false );
	}

	/**
	 * Return the top-level setting keys owned by a settings tab.
	 *
	 * @param string $tab_key Tab key.
	 * @return array<int,string>
	 */
	private function get_tab_field_keys( $tab_key ) {
		$map = array(
			'general'      => array(
				'enabled',
				'page_title',
				'message',
			),
			'template'     => array(
				'mode_type',
				'template_key',
			),
			'design'       => array(
				'theme_mode',
				'primary_color',
				'background_color',
				'surface_color',
				'heading_text_color',
				'body_text_color',
				'muted_text_color',
				'link_text_color',
				'button_text_color',
				'border_color',
			),
			'components'   => array(
				'hero_eyebrow',
				'primary_action_label',
				'primary_action_url',
				'secondary_action_label',
				'secondary_action_url',
				'status_label',
				'show_progress',
				'progress_value',
				'contact_label',
				'contact_message',
				'contact_email',
			),
			'social_links' => array(
				'social_links',
				'social_x_url',
				'social_instagram_url',
				'social_facebook_url',
				'social_linkedin_url',
				'social_item_1_platform',
				'social_item_1_label',
				'social_item_1_url',
				'social_item_1_new_tab',
				'social_item_2_platform',
				'social_item_2_label',
				'social_item_2_url',
				'social_item_2_new_tab',
				'social_item_3_platform',
				'social_item_3_label',
				'social_item_3_url',
				'social_item_3_new_tab',
				'social_item_4_platform',
				'social_item_4_label',
				'social_item_4_url',
				'social_item_4_new_tab',
			),
			'advanced'     => array(
				'show_login_button',
				'show_footer_section',
				'delete_data_on_uninstall',
				'login_label',
			),
		);

		return isset( $map[ $tab_key ] ) ? $map[ $tab_key ] : array();
	}
}

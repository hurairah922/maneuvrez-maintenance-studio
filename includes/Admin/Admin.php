<?php
/**
 * Admin settings page controller.
 *
 * @package MaintenanceModeStudio
 */

namespace Maneuvrez\MaintenanceModeStudio\Admin;

use Maneuvrez\MaintenanceModeStudio\Components\SocialLinksComponent;
use Maneuvrez\MaintenanceModeStudio\Countdown\CountdownScheduler;
use Maneuvrez\MaintenanceModeStudio\Countdown\CountdownService;
use Maneuvrez\MaintenanceModeStudio\Frontend\TemplateRegistry;
use Maneuvrez\MaintenanceModeStudio\Frontend\TemplateRenderer;
use Maneuvrez\MaintenanceModeStudio\Security\Sanitizer;
use Maneuvrez\MaintenanceModeStudio\Settings\SettingsRepository;
use Maneuvrez\MaintenanceModeStudio\Support\ContactChannels;

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
	 * Countdown domain service.
	 *
	 * @var CountdownService
	 */
	private $countdown_service;

	/**
	 * Countdown lifecycle scheduler.
	 *
	 * @var CountdownScheduler
	 */
	private $countdown_scheduler;

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
	private $page_slug = 'maneuvrez-maintenance-studio';

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
	 * @param CountdownService|null    $countdown_service Countdown domain service.
	 * @param CountdownScheduler|null  $countdown_scheduler Countdown scheduler.
	 */
	public function __construct( $settings_repository = null, $countdown_service = null, $countdown_scheduler = null ) {
		$this->settings_repository = $settings_repository instanceof SettingsRepository ? $settings_repository : new SettingsRepository();
		$this->countdown_service   = $countdown_service instanceof CountdownService ? $countdown_service : new CountdownService( $this->settings_repository );
		$this->countdown_scheduler = $countdown_scheduler instanceof CountdownScheduler ? $countdown_scheduler : new CountdownScheduler( $this->settings_repository, $this->countdown_service );
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
		add_action( 'wp_ajax_mmsm_countdown_schedule_check', array( $this, 'handle_countdown_schedule_check' ) );
		add_action( 'wp_ajax_mmsm_render_page_preview', array( $this, 'handle_page_preview_request' ) );
		add_filter( 'plugin_action_links_' . MMSM_PLUGIN_BASENAME, array( $this, 'filter_plugin_action_links' ) );
	}

	/**
	 * Add the settings page under Settings.
	 *
	 * @return void
	 */
	public function add_settings_page() {
		$this->page_hook = add_options_page(
			__( 'Maneuvrez Maintenance Studio', 'maneuvrez-maintenance-studio' ),
			__( 'Maneuvrez Maintenance Studio', 'maneuvrez-maintenance-studio' ),
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
			__( 'General', 'maneuvrez-maintenance-studio' ),
			array( $this, 'render_general_section' ),
			$this->page_slug
		);

		add_settings_field(
			'mmsm_enabled',
			__( 'Enable maintenance mode', 'maneuvrez-maintenance-studio' ),
			array( $this, 'render_enabled_field' ),
			$this->page_slug,
			'mmsm_general_section'
		);

		add_settings_field(
			'mmsm_page_title',
			__( 'Page title', 'maneuvrez-maintenance-studio' ),
			array( $this, 'render_page_title_field' ),
			$this->page_slug,
			'mmsm_general_section'
		);

		add_settings_field(
			'mmsm_message',
			__( 'Message', 'maneuvrez-maintenance-studio' ),
			array( $this, 'render_message_field' ),
			$this->page_slug,
			'mmsm_general_section'
		);

		add_settings_section(
			'mmsm_template_section',
			__( 'Template', 'maneuvrez-maintenance-studio' ),
			array( $this, 'render_template_section' ),
			$this->page_slug
		);

		add_settings_field(
			'mmsm_mode_type',
			__( 'Page type', 'maneuvrez-maintenance-studio' ),
			array( $this, 'render_mode_type_field' ),
			$this->page_slug,
			'mmsm_template_section'
		);

		add_settings_field(
			'mmsm_template_key',
			__( 'Template', 'maneuvrez-maintenance-studio' ),
			array( $this, 'render_template_key_field' ),
			$this->page_slug,
			'mmsm_template_section'
		);

		add_settings_section(
			'mmsm_design_section',
			__( 'Design', 'maneuvrez-maintenance-studio' ),
			array( $this, 'render_design_section' ),
			$this->page_slug
		);

		add_settings_field(
			'mmsm_theme_mode',
			__( 'Theme Mode', 'maneuvrez-maintenance-studio' ),
			array( $this, 'render_theme_mode_field' ),
			$this->page_slug,
			'mmsm_design_section'
		);

		add_settings_field(
			'mmsm_primary_color',
			__( 'Primary Color', 'maneuvrez-maintenance-studio' ),
			array( $this, 'render_primary_color_field' ),
			$this->page_slug,
			'mmsm_design_section'
		);

		add_settings_field(
			'mmsm_background_color',
			__( 'Background Color', 'maneuvrez-maintenance-studio' ),
			array( $this, 'render_background_color_field' ),
			$this->page_slug,
			'mmsm_design_section'
		);

		add_settings_field(
			'mmsm_surface_color',
			__( 'Surface Color', 'maneuvrez-maintenance-studio' ),
			array( $this, 'render_surface_color_field' ),
			$this->page_slug,
			'mmsm_design_section'
		);

		add_settings_field(
			'mmsm_heading_text_color',
			__( 'Heading Text Color', 'maneuvrez-maintenance-studio' ),
			array( $this, 'render_heading_text_color_field' ),
			$this->page_slug,
			'mmsm_design_section'
		);

		add_settings_field(
			'mmsm_body_text_color',
			__( 'Body Text Color', 'maneuvrez-maintenance-studio' ),
			array( $this, 'render_body_text_color_field' ),
			$this->page_slug,
			'mmsm_design_section'
		);

		add_settings_field(
			'mmsm_muted_text_color',
			__( 'Muted Text Color', 'maneuvrez-maintenance-studio' ),
			array( $this, 'render_muted_text_color_field' ),
			$this->page_slug,
			'mmsm_design_section'
		);

		add_settings_field(
			'mmsm_link_text_color',
			__( 'Link Text Color', 'maneuvrez-maintenance-studio' ),
			array( $this, 'render_link_text_color_field' ),
			$this->page_slug,
			'mmsm_design_section'
		);

		add_settings_field(
			'mmsm_button_text_color',
			__( 'Button Text Color', 'maneuvrez-maintenance-studio' ),
			array( $this, 'render_button_text_color_field' ),
			$this->page_slug,
			'mmsm_design_section'
		);

		add_settings_field(
			'mmsm_border_color',
			__( 'Border Color', 'maneuvrez-maintenance-studio' ),
			array( $this, 'render_border_color_field' ),
			$this->page_slug,
			'mmsm_design_section'
		);

		add_settings_section(
			'mmsm_components_section',
			__( 'Components', 'maneuvrez-maintenance-studio' ),
			array( $this, 'render_components_section' ),
			$this->page_slug
		);

		add_settings_field(
			'mmsm_hero_eyebrow',
			__( 'Text above heading', 'maneuvrez-maintenance-studio' ),
			array( $this, 'render_hero_eyebrow_field' ),
			$this->page_slug,
			'mmsm_components_section'
		);

		add_settings_field(
			'mmsm_primary_action_label',
			__( 'Button label', 'maneuvrez-maintenance-studio' ),
			array( $this, 'render_primary_action_label_field' ),
			$this->page_slug,
			'mmsm_components_section'
		);

		add_settings_field(
			'mmsm_primary_action_url',
			__( 'Button URL', 'maneuvrez-maintenance-studio' ),
			array( $this, 'render_primary_action_url_field' ),
			$this->page_slug,
			'mmsm_components_section'
		);

		add_settings_field(
			'mmsm_secondary_action_label',
			__( 'Button label', 'maneuvrez-maintenance-studio' ),
			array( $this, 'render_secondary_action_label_field' ),
			$this->page_slug,
			'mmsm_components_section'
		);

		add_settings_field(
			'mmsm_secondary_action_url',
			__( 'Button URL', 'maneuvrez-maintenance-studio' ),
			array( $this, 'render_secondary_action_url_field' ),
			$this->page_slug,
			'mmsm_components_section'
		);

		add_settings_field(
			'mmsm_status_label',
			__( 'Status text', 'maneuvrez-maintenance-studio' ),
			array( $this, 'render_status_label_field' ),
			$this->page_slug,
			'mmsm_components_section'
		);

		add_settings_field(
			'mmsm_show_progress',
			__( 'Show Progress', 'maneuvrez-maintenance-studio' ),
			array( $this, 'render_show_progress_field' ),
			$this->page_slug,
			'mmsm_components_section'
		);

		add_settings_field(
			'mmsm_progress_value',
			__( 'Progress percentage', 'maneuvrez-maintenance-studio' ),
			array( $this, 'render_progress_value_field' ),
			$this->page_slug,
			'mmsm_components_section',
			array(
				'class' => 'mmsm-progress-value-dependent',
			)
		);

		add_settings_field(
			'mmsm_contact_label',
			__( 'Contact Label', 'maneuvrez-maintenance-studio' ),
			array( $this, 'render_contact_label_field' ),
			$this->page_slug,
			'mmsm_components_section'
		);

		add_settings_field(
			'mmsm_contact_message',
			__( 'Contact Message', 'maneuvrez-maintenance-studio' ),
			array( $this, 'render_contact_message_field' ),
			$this->page_slug,
			'mmsm_components_section'
		);

		add_settings_field(
			'mmsm_contact_email',
			__( 'Contact Email', 'maneuvrez-maintenance-studio' ),
			array( $this, 'render_contact_email_field' ),
			$this->page_slug,
			'mmsm_components_section'
		);

		add_settings_section(
			'mmsm_countdown_section',
			__( 'Countdown', 'maneuvrez-maintenance-studio' ),
			array( $this, 'render_countdown_section' ),
			$this->page_slug
		);

		add_settings_field(
			'mmsm_countdown_enabled',
			__( 'Enable countdown', 'maneuvrez-maintenance-studio' ),
			array( $this, 'render_countdown_enabled_field' ),
			$this->page_slug,
			'mmsm_countdown_section'
		);

		add_settings_field(
			'mmsm_countdown_target',
			__( 'Target date and time', 'maneuvrez-maintenance-studio' ),
			array( $this, 'render_countdown_target_field' ),
			$this->page_slug,
			'mmsm_countdown_section'
		);

		add_settings_field(
			'mmsm_countdown_copy',
			__( 'Display copy', 'maneuvrez-maintenance-studio' ),
			array( $this, 'render_countdown_copy_field' ),
			$this->page_slug,
			'mmsm_countdown_section'
		);

		add_settings_field(
			'mmsm_countdown_units',
			__( 'Visible time units', 'maneuvrez-maintenance-studio' ),
			array( $this, 'render_countdown_units_field' ),
			$this->page_slug,
			'mmsm_countdown_section'
		);

		add_settings_field(
			'mmsm_countdown_expiry',
			__( 'When the countdown ends', 'maneuvrez-maintenance-studio' ),
			array( $this, 'render_countdown_expiry_field' ),
			$this->page_slug,
			'mmsm_countdown_section'
		);

		add_settings_field(
			'mmsm_countdown_finished_message',
			__( 'Finished message', 'maneuvrez-maintenance-studio' ),
			array( $this, 'render_countdown_finished_message_field' ),
			$this->page_slug,
			'mmsm_countdown_section',
			array(
				'class' => 'mmsm-countdown-finished-dependent',
			)
		);

		add_settings_field(
			'mmsm_countdown_appearance',
			__( 'Counter appearance', 'maneuvrez-maintenance-studio' ),
			array( $this, 'render_countdown_appearance_field' ),
			$this->page_slug,
			'mmsm_countdown_section'
		);

		add_settings_section(
			'mmsm_contact_channels_section',
			__( 'Contact Channels', 'maneuvrez-maintenance-studio' ),
			array( $this, 'render_contact_channels_section' ),
			$this->page_slug
		);

		add_settings_field(
			'mmsm_contact_channels_enabled',
			__( 'Enable Contact Channels', 'maneuvrez-maintenance-studio' ),
			array( $this, 'render_contact_channels_enabled_field' ),
			$this->page_slug,
			'mmsm_contact_channels_section'
		);

		add_settings_field(
			'mmsm_contact_channels',
			__( 'Contact Channels', 'maneuvrez-maintenance-studio' ),
			array( $this, 'render_contact_channels_field' ),
			$this->page_slug,
			'mmsm_contact_channels_section',
			array(
				'class' => 'mmsm-contact-channels-field-row',
			)
		);

		add_settings_section(
			'mmsm_social_links_section',
			__( 'Social Links', 'maneuvrez-maintenance-studio' ),
			array( $this, 'render_social_links_section' ),
			$this->page_slug
		);

		add_settings_field(
			'mmsm_social_links',
			__( 'Social Items', 'maneuvrez-maintenance-studio' ),
			array( $this, 'render_social_links_field' ),
			$this->page_slug,
			'mmsm_social_links_section'
		);

		add_settings_section(
			'mmsm_advanced_section',
			__( 'Advanced', 'maneuvrez-maintenance-studio' ),
			array( $this, 'render_advanced_section' ),
			$this->page_slug
		);

		add_settings_field(
			'mmsm_show_login_button',
			__( 'Show Login Button', 'maneuvrez-maintenance-studio' ),
			array( $this, 'render_show_login_button_field' ),
			$this->page_slug,
			'mmsm_advanced_section'
		);

		add_settings_field(
			'mmsm_show_footer_section',
			__( 'Show Footer Section', 'maneuvrez-maintenance-studio' ),
			array( $this, 'render_show_footer_section_field' ),
			$this->page_slug,
			'mmsm_advanced_section'
		);

		add_settings_field(
			'mmsm_custom_login_enabled',
			__( 'Enable Custom Login URL', 'maneuvrez-maintenance-studio' ),
			array( $this, 'render_custom_login_enabled_field' ),
			$this->page_slug,
			'mmsm_advanced_section'
		);

		add_settings_field(
			'mmsm_custom_login_slug',
			__( 'Custom Login Slug', 'maneuvrez-maintenance-studio' ),
			array( $this, 'render_custom_login_slug_field' ),
			$this->page_slug,
			'mmsm_advanced_section',
			array(
				'class' => 'mmsm-custom-login-dependent',
			)
		);

		add_settings_field(
			'mmsm_custom_login_block_mode',
			__( 'Default Admin Routes', 'maneuvrez-maintenance-studio' ),
			array( $this, 'render_custom_login_block_mode_field' ),
			$this->page_slug,
			'mmsm_advanced_section',
			array(
				'class' => 'mmsm-custom-login-dependent',
			)
		);

		add_settings_field(
			'mmsm_bypass_query_enabled',
			__( 'Testing Bypass', 'maneuvrez-maintenance-studio' ),
			array( $this, 'render_bypass_query_enabled_field' ),
			$this->page_slug,
			'mmsm_advanced_section'
		);

		add_settings_field(
			'mmsm_bypass_query_settings',
			__( 'Query Parameter', 'maneuvrez-maintenance-studio' ),
			array( $this, 'render_bypass_query_settings_field' ),
			$this->page_slug,
			'mmsm_advanced_section',
			array(
				'class' => 'mmsm-bypass-query-dependent',
			)
		);

		add_settings_field(
			'mmsm_bypass_urls_enabled',
			__( 'Public URL Allowlist', 'maneuvrez-maintenance-studio' ),
			array( $this, 'render_bypass_urls_enabled_field' ),
			$this->page_slug,
			'mmsm_advanced_section'
		);

		add_settings_field(
			'mmsm_bypass_urls',
			__( 'Allowlist URLs', 'maneuvrez-maintenance-studio' ),
			array( $this, 'render_bypass_urls_field' ),
			$this->page_slug,
			'mmsm_advanced_section',
			array(
				'class' => 'mmsm-bypass-urls-dependent',
			)
		);

		add_settings_field(
			'mmsm_delete_data_on_uninstall',
			__( 'Data Removal on Uninstall', 'maneuvrez-maintenance-studio' ),
			array( $this, 'render_delete_data_on_uninstall_field' ),
			$this->page_slug,
			'mmsm_advanced_section'
		);

		add_settings_field(
			'mmsm_login_label',
			__( 'Login Label', 'maneuvrez-maintenance-studio' ),
			array( $this, 'render_login_label_field' ),
			$this->page_slug,
			'mmsm_advanced_section',
			array(
				'class' => 'mmsm-login-label-dependent',
			)
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
		$settings   = $this->settings_repository->get_settings();
		$is_enabled = ! empty( $settings['enabled'] );
		$status_text = $is_enabled ? __( 'On', 'maneuvrez-maintenance-studio' ) : __( 'Off', 'maneuvrez-maintenance-studio' );
		$status_help = $is_enabled ? __( 'Logged-out visitors see the maintenance page.', 'maneuvrez-maintenance-studio' ) : __( 'Your normal site is visible to visitors.', 'maneuvrez-maintenance-studio' );
		?>
		<div class="wrap mmsm-settings-page">
			<div class="mmsm-settings-header">
				<div>
					<span class="mmsm-settings-kicker"><?php echo esc_html__( 'Maintenance Mode Studio', 'maneuvrez-maintenance-studio' ); ?></span>
					<h1><?php echo esc_html__( 'Maneuvrez Maintenance Studio', 'maneuvrez-maintenance-studio' ); ?></h1>
					<p class="mmsm-settings-intro">
						<?php echo esc_html__( 'Configure the maintenance page template, core copy, and reusable visitor components without editing code.', 'maneuvrez-maintenance-studio' ); ?>
					</p>
				</div>
				<div class="<?php echo esc_attr( 'mmsm-saved-status ' . ( $is_enabled ? 'is-on' : 'is-off' ) ); ?>" role="status" aria-label="<?php echo esc_attr__( 'Saved maintenance mode and editor status', 'maneuvrez-maintenance-studio' ); ?>">
					<span><?php echo esc_html__( 'Saved status', 'maneuvrez-maintenance-studio' ); ?></span>
					<strong><?php echo esc_html( $status_text ); ?></strong>
					<small><?php echo esc_html( $status_help ); ?></small>
					<span class="mmsm-edit-status" data-settings-edit-status aria-live="polite"><?php echo esc_html__( 'All editor changes are saved.', 'maneuvrez-maintenance-studio' ); ?></span>
				</div>
			</div>

			<?php settings_errors( MMSM_SETTINGS_OPTION ); ?>

			<div class="mmsm-settings-shell">
				<nav class="mmsm-settings-sidebar" aria-label="<?php echo esc_attr__( 'Maneuvrez Maintenance Studio settings sections', 'maneuvrez-maintenance-studio' ); ?>">
					<?php foreach ( $this->get_tabs() as $tab_key => $tab ) : ?>
						<a
							href="<?php echo esc_url( $this->get_tab_url( $tab_key ) ); ?>"
							class="<?php echo esc_attr( 'mmsm-settings-nav-item' . ( $active_tab === $tab_key ? ' is-active' : '' ) ); ?>"
							<?php if ( $active_tab === $tab_key ) : ?>aria-current="page"<?php endif; ?>
						>
							<span class="<?php echo esc_attr( 'dashicons ' . $tab['icon'] ); ?>" aria-hidden="true"></span>
							<span><?php echo esc_html( $tab['label'] ); ?></span>
						</a>
					<?php endforeach; ?>
				</nav>

				<form class="mmsm-settings-content" action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>" method="post">
					<?php
						settings_fields( $this->settings_group );
						wp_nonce_field( 'mmsm_save_settings', 'mmsm_settings_nonce' );
						?>
						<input type="hidden" name="_wp_http_referer" value="<?php echo esc_attr( $this->get_tab_url( $active_tab ) ); ?>" />
						<input type="hidden" name="mmsm_active_tab" value="<?php echo esc_attr( $active_tab ); ?>" />
						<input type="hidden" name="mmsm_navigation_version" value="areas" />
						<?php if ( 'advanced' === $active_tab ) : ?>
							<input type="hidden" name="<?php echo esc_attr( MMSM_SETTINGS_OPTION ); ?>[template_key]" value="<?php echo esc_attr( (string) $settings['template_key'] ); ?>" />
						<?php endif; ?>
						<?php
						if ( in_array( $active_tab, array( 'maintenance_page', 'design' ), true ) ) {
							?>
							<div class="mmsm-editor-workspace">
								<div class="mmsm-settings-stack">
									<?php $this->render_active_tab(); ?>
								</div>
								<?php $this->render_full_page_preview( $active_tab ); ?>
							</div>
							<?php
						} else {
							$this->render_active_tab();
						}
					submit_button( __( 'Save Settings', 'maneuvrez-maintenance-studio' ), 'primary', 'submit', true, array( 'class' => 'mmsm-settings-save-button' ) );
					?>
				</form>
			</div>
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
				'maneuvrez-maintenance-studio'
			);

			wp_localize_script(
				'mmsm-admin-settings-script',
				'mmsmCountdownAdmin',
				array(
					'ajaxUrl' => admin_url( 'admin-ajax.php' ),
					'nonce'   => wp_create_nonce( 'mmsm_countdown_schedule_check' ),
				)
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
				esc_html__( 'You are not allowed to update these settings.', 'maneuvrez-maintenance-studio' ),
				'error'
			);

			return $existing;
		}

		$nonce = isset( $_POST['mmsm_settings_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['mmsm_settings_nonce'] ) ) : '';

		if ( '' === $nonce || ! wp_verify_nonce( $nonce, 'mmsm_save_settings' ) ) {
			add_settings_error(
				MMSM_SETTINGS_OPTION,
				'mmsm_settings_nonce_error',
				esc_html__( 'The settings request could not be verified. Please try again.', 'maneuvrez-maintenance-studio' ),
				'error'
			);

			return $existing;
		}

		$requested_tab = isset( $_POST['mmsm_active_tab'] ) ? sanitize_key( wp_unslash( $_POST['mmsm_active_tab'] ) ) : 'maintenance_page';
		$area_request  = isset( $_POST['mmsm_navigation_version'] ) && 'areas' === sanitize_key( wp_unslash( $_POST['mmsm_navigation_version'] ) );
		$active_tab    = $area_request ? $this->normalize_area_key( $requested_tab ) : $requested_tab;
		$tab_keys      = $area_request ? $this->get_tab_field_keys( $active_tab ) : $this->get_legacy_tab_field_keys( $active_tab );

		if ( ( $area_request && 'maintenance_page' === $active_tab ) || ( ! $area_request && 'countdown' === $active_tab ) ) {
			$input['countdowns'] = $this->prepare_countdowns_for_save( $input, $existing );
		}

		if ( $area_request && 'maintenance_page' === $active_tab ) {
			$input = $this->prepare_action_pairs_for_save( $input, $existing );
		}

		if ( ( $area_request && 'access_visibility' === $active_tab ) || ( ! $area_request && 'general' === $active_tab ) ) {
			$existing = $this->prepare_maintenance_activation( $input, $existing );
		}

		foreach ( $tab_keys as $tab_key ) {
			unset( $existing[ $tab_key ] );
		}

		if ( ( 'maintenance_page' === $active_tab || 'social_links' === $active_tab ) && isset( $_POST['mmsm_social_links_present'] ) && ! isset( $input['social_links'] ) ) {
			$input['social_links'] = array();
		}

		if ( ( 'maintenance_page' === $active_tab || 'contact_channels' === $active_tab ) && isset( $_POST['mmsm_contact_channels_present'] ) && ! isset( $input['contact_channels_items'] ) ) {
			$input['contact_channels_items'] = array();
		}

		$input = array_intersect_key( $input, array_flip( $tab_keys ) );

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
				esc_html__( 'Settings', 'maneuvrez-maintenance-studio' )
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
				<button type="button" class="mmsm-uninstall-feedback-close" aria-label="<?php echo esc_attr__( 'Close uninstall feedback prompt', 'maneuvrez-maintenance-studio' ); ?>">
					<span aria-hidden="true">&times;</span>
				</button>

				<h2 id="mmsm-uninstall-feedback-title"><?php echo esc_html__( 'Before you go, would you like to share quick feedback?', 'maneuvrez-maintenance-studio' ); ?></h2>
				<p><?php echo esc_html__( 'This step is optional and will not block deactivation or deletion.', 'maneuvrez-maintenance-studio' ); ?></p>

				<div class="mmsm-uninstall-feedback-section">
					<p class="mmsm-uninstall-feedback-label"><?php echo esc_html__( 'Why are you removing this plugin?', 'maneuvrez-maintenance-studio' ); ?></p>
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
					<label for="mmsm-uninstall-feedback-details" class="mmsm-uninstall-feedback-label"><?php echo esc_html__( 'Anything else you want to share?', 'maneuvrez-maintenance-studio' ); ?></label>
					<textarea id="mmsm-uninstall-feedback-details" rows="4" class="widefat"></textarea>
				</div>

				<div class="mmsm-uninstall-feedback-section">
					<p class="mmsm-uninstall-feedback-label"><?php echo esc_html__( 'Do you also want to remove plugin data when uninstalling?', 'maneuvrez-maintenance-studio' ); ?></p>
					<label class="mmsm-uninstall-feedback-choice">
						<input type="radio" name="mmsm_remove_data" value="0" <?php checked( ! $this->is_remove_data_enabled() ); ?> />
						<span><?php echo esc_html__( 'Keep plugin data', 'maneuvrez-maintenance-studio' ); ?></span>
					</label>
					<label class="mmsm-uninstall-feedback-choice">
						<input type="radio" name="mmsm_remove_data" value="1" <?php checked( $this->is_remove_data_enabled() ); ?> />
						<span><?php echo esc_html__( 'Remove plugin data on uninstall', 'maneuvrez-maintenance-studio' ); ?></span>
					</label>
				</div>

				<p class="mmsm-uninstall-feedback-note"><?php echo esc_html__( 'Feedback is stored locally on this site only when you choose to submit it. Nothing is sent externally by default.', 'maneuvrez-maintenance-studio' ); ?></p>

				<div class="mmsm-uninstall-feedback-actions">
					<button type="button" class="button-link mmsm-uninstall-feedback-cancel"><?php echo esc_html__( 'Cancel', 'maneuvrez-maintenance-studio' ); ?></button>
					<button type="button" class="button mmsm-uninstall-feedback-skip"><?php echo esc_html__( 'Skip and continue', 'maneuvrez-maintenance-studio' ); ?></button>
					<button type="button" class="button button-primary mmsm-uninstall-feedback-submit"><?php echo esc_html__( 'Submit feedback and continue', 'maneuvrez-maintenance-studio' ); ?></button>
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
			wp_send_json_error( array( 'message' => __( 'You are not allowed to manage plugins.', 'maneuvrez-maintenance-studio' ) ), 403 );
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
		echo '<p>' . esc_html__( 'These settings control the core public experience shown to logged-out visitors.', 'maneuvrez-maintenance-studio' ) . '</p>';
	}

	/**
	 * Render the appearance section description.
	 *
	 * @return void
	 */
	public function render_template_section() {
		echo '<p>' . esc_html__( 'Pick the public template shell and the maintenance mode presentation style.', 'maneuvrez-maintenance-studio' ) . '</p>';
	}

	/**
	 * Render the design section description.
	 *
	 * @return void
	 */
	public function render_design_section() {
		echo '<p>' . esc_html__( 'Use the full-page preview to check every color role and appearance mode together.', 'maneuvrez-maintenance-studio' ) . '</p>';
	}

	/**
	 * Render the shared real-template maintenance-page preview.
	 *
	 * @param string $active_tab Active task area.
	 * @return void
	 */
	private function render_full_page_preview( $active_tab ) {
		$preview_nonce = wp_create_nonce( 'mmsm_render_page_preview' );
		$preview_url   = add_query_arg(
			array(
				'action' => 'mmsm_render_page_preview',
				'nonce'  => $preview_nonce,
			),
			admin_url( 'admin-ajax.php' )
		);
		?>
		<aside class="mmsm-full-page-preview" data-page-preview data-preview-area="<?php echo esc_attr( $active_tab ); ?>" data-preview-url="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" data-preview-nonce="<?php echo esc_attr( $preview_nonce ); ?>" aria-labelledby="mmsm-full-page-preview-title">
			<div class="mmsm-full-page-preview-header">
				<div>
					<strong id="mmsm-full-page-preview-title"><?php echo esc_html__( 'Full page preview', 'maneuvrez-maintenance-studio' ); ?></strong>
					<span><?php echo esc_html__( 'Rendered with the public template and updated as you edit', 'maneuvrez-maintenance-studio' ); ?></span>
				</div>
				<div class="mmsm-full-page-preview-actions">
					<button type="button" class="button button-secondary" data-preview-expand aria-expanded="false">
						<?php echo esc_html__( 'Expand preview', 'maneuvrez-maintenance-studio' ); ?>
					</button>
					<button type="button" class="button button-secondary" data-preview-close hidden>
						<?php echo esc_html__( 'Close preview', 'maneuvrez-maintenance-studio' ); ?>
					</button>
				</div>
			</div>
			<div class="mmsm-preview-responsive-toolbar" data-preview-responsive-toolbar hidden>
				<div class="mmsm-preview-presets" role="group" aria-label="<?php echo esc_attr__( 'Preview size presets', 'maneuvrez-maintenance-studio' ); ?>">
					<button type="button" class="button" data-preview-preset="desktop" aria-pressed="true"><?php echo esc_html__( 'Desktop', 'maneuvrez-maintenance-studio' ); ?></button>
					<button type="button" class="button" data-preview-preset="tablet" aria-pressed="false"><?php echo esc_html__( 'Tablet', 'maneuvrez-maintenance-studio' ); ?></button>
					<button type="button" class="button" data-preview-preset="mobile" aria-pressed="false"><?php echo esc_html__( 'Mobile', 'maneuvrez-maintenance-studio' ); ?></button>
				</div>
				<label>
					<span><?php echo esc_html__( 'Width', 'maneuvrez-maintenance-studio' ); ?></span>
					<input type="number" min="320" max="2560" step="1" value="1440" data-preview-width aria-label="<?php echo esc_attr__( 'Preview width in pixels', 'maneuvrez-maintenance-studio' ); ?>" />
				</label>
				<label>
					<span><?php echo esc_html__( 'Height', 'maneuvrez-maintenance-studio' ); ?></span>
					<input type="number" min="400" max="1600" step="1" value="900" data-preview-height aria-label="<?php echo esc_attr__( 'Preview height in pixels', 'maneuvrez-maintenance-studio' ); ?>" />
				</label>
				<span class="mmsm-preview-resize-help"><?php echo esc_html__( 'Drag any edge or corner to resize.', 'maneuvrez-maintenance-studio' ); ?></span>
			</div>
			<div class="mmsm-preview-canvas" data-preview-canvas>
				<div class="mmsm-preview-device-frame" data-preview-device-frame>
					<iframe class="mmsm-public-preview-frame" data-public-preview-frame src="<?php echo esc_url( $preview_url ); ?>" title="<?php echo esc_attr__( 'Rendered maintenance page preview', 'maneuvrez-maintenance-studio' ); ?>" sandbox="allow-scripts" referrerpolicy="same-origin"></iframe>
					<?php foreach ( array( 'n', 'ne', 'e', 'se', 's', 'sw', 'w', 'nw' ) as $resize_direction ) : ?>
						<span class="mmsm-preview-resize-handle is-<?php echo esc_attr( $resize_direction ); ?>" data-preview-resize="<?php echo esc_attr( $resize_direction ); ?>" aria-hidden="true"></span>
					<?php endforeach; ?>
				</div>
			</div>
		</aside>
		<?php
	}

	/**
	 * Render the real public template in an authenticated preview response.
	 *
	 * Posted settings are merged only into the active area's saved values and
	 * are never persisted by this endpoint.
	 *
	 * @return void
	 */
	public function handle_page_preview_request() {
		if ( ! current_user_can( 'manage_options' ) ) {
			status_header( 403 );
			wp_die( esc_html__( 'You are not allowed to preview this page.', 'maneuvrez-maintenance-studio' ) );
		}

		check_ajax_referer( 'mmsm_render_page_preview', 'nonce' );
		$settings = $this->settings_repository->get_settings();

		if ( 'POST' === ( isset( $_SERVER['REQUEST_METHOD'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) : '' ) ) {
			$input      = isset( $_POST[ MMSM_SETTINGS_OPTION ] ) && is_array( $_POST[ MMSM_SETTINGS_OPTION ] ) ? wp_unslash( $_POST[ MMSM_SETTINGS_OPTION ] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Structured settings are sanitized by Sanitizer::sanitize_settings() below.
			$active_tab = isset( $_POST['mmsm_active_tab'] ) ? $this->normalize_area_key( sanitize_key( wp_unslash( $_POST['mmsm_active_tab'] ) ) ) : 'maintenance_page';
			$area_keys  = $this->get_tab_field_keys( $active_tab );

			if ( 'maintenance_page' === $active_tab && isset( $input['countdowns'] ) ) {
				$input['countdowns'] = $this->prepare_countdowns_for_save( $input, $settings );
			}

			if ( 'maintenance_page' === $active_tab && isset( $_POST['mmsm_social_links_present'] ) && ! isset( $input['social_links'] ) ) {
				$input['social_links'] = array();
			}

			if ( 'maintenance_page' === $active_tab && isset( $_POST['mmsm_contact_channels_present'] ) && ! isset( $input['contact_channels_items'] ) ) {
				$input['contact_channels_items'] = array();
			}

			foreach ( $area_keys as $area_key ) {
				unset( $settings[ $area_key ] );
			}

			$input    = array_intersect_key( $input, array_flip( $area_keys ) );
			$settings = Sanitizer::sanitize_settings( array_merge( $settings, $input ) );
		}

		nocache_headers();
		header( 'Content-Type: text/html; charset=' . get_option( 'blog_charset' ) );
		( new TemplateRenderer() )->render( $settings );
		wp_die();
	}

	/**
	 * Render the component section description.
	 *
	 * @return void
	 */
	public function render_components_section() {
		echo '<p>' . esc_html__( 'These optional settings feed the hero, status, and contact components rendered by the default template.', 'maneuvrez-maintenance-studio' ) . '</p>';
	}

	/**
	 * Render the countdown section description and scheduling health controls.
	 *
	 * @return void
	 */
	public function render_countdown_section() {
		$schedule = $this->countdown_scheduler->get_status( CountdownService::INSTANCE_MAINTENANCE );
		?>
		<p><?php echo esc_html__( 'Schedule one countdown for the maintenance or coming-soon page. The selected time uses the WordPress site timezone.', 'maneuvrez-maintenance-studio' ); ?></p>
		<div class="mmsm-countdown-schedule-check">
			<strong><?php echo esc_html__( 'Server scheduling check', 'maneuvrez-maintenance-studio' ); ?></strong>
			<p data-countdown-schedule-result>
				<?php
				if ( 'healthy' === $schedule['status'] ) {
					echo esc_html__( 'The one-time expiry event is scheduled correctly.', 'maneuvrez-maintenance-studio' );
				} elseif ( 'missing' === $schedule['status'] ) {
					echo esc_html__( 'The countdown needs a scheduling repair.', 'maneuvrez-maintenance-studio' );
				} else {
					echo esc_html__( 'No future expiry event is currently required.', 'maneuvrez-maintenance-studio' );
				}
				?>
			</p>
			<button type="button" class="button" data-countdown-schedule-check><?php echo esc_html__( 'Check expiry scheduling', 'maneuvrez-maintenance-studio' ); ?></button>
			<p class="description"><?php echo esc_html__( 'This safely verifies and repairs the one-time event. It does not fast-forward the countdown or turn off maintenance mode.', 'maneuvrez-maintenance-studio' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Verify and repair the one-time countdown event for an administrator.
	 *
	 * @return void
	 */
	public function handle_countdown_schedule_check() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'You are not allowed to check countdown scheduling.', 'maneuvrez-maintenance-studio' ) ), 403 );
		}

		check_ajax_referer( 'mmsm_countdown_schedule_check', 'nonce' );

		$this->countdown_scheduler->sync_instance( CountdownService::INSTANCE_MAINTENANCE );
		$status = $this->countdown_scheduler->get_status( CountdownService::INSTANCE_MAINTENANCE );

		if ( 'healthy' === $status['status'] ) {
			$message = sprintf(
				/* translators: %s: scheduled date and time in the WordPress site timezone. */
				esc_html__( 'Scheduling is healthy. The one-time event is set for %s.', 'maneuvrez-maintenance-studio' ),
				wp_date( 'Y-m-d H:i:s T', (int) $status['scheduled_timestamp'], wp_timezone() )
			);
		} elseif ( 'not_required' === $status['status'] ) {
			$message = esc_html__( 'Scheduling is healthy. No future expiry event is required for the current countdown settings.', 'maneuvrez-maintenance-studio' );
		} else {
			$message = esc_html__( 'WordPress could not schedule the expiry event. Check that WP-Cron is available, then try again.', 'maneuvrez-maintenance-studio' );
		}

		wp_send_json_success( array( 'message' => $message ) );
	}

	/**
	 * Render the countdown enable control.
	 *
	 * @return void
	 */
	public function render_countdown_enabled_field() {
		$countdown = $this->get_maintenance_countdown();
		?>
		<label for="mmsm-countdown-enabled">
			<input type="checkbox" id="mmsm-countdown-enabled" name="<?php echo esc_attr( MMSM_SETTINGS_OPTION ); ?>[countdowns][maintenance][enabled]" value="1" <?php checked( 1, (int) $countdown['enabled'] ); ?> />
			<?php echo esc_html__( 'Show the countdown on the maintenance page.', 'maneuvrez-maintenance-studio' ); ?>
		</label>
		<p class="description"><?php echo esc_html__( 'Maintenance mode itself is controlled in Access & Visibility.', 'maneuvrez-maintenance-studio' ); ?></p>
		<?php
	}

	/**
	 * Render the site-local countdown target.
	 *
	 * @return void
	 */
	public function render_countdown_target_field() {
		$countdown = $this->get_maintenance_countdown();
		$timezone  = wp_timezone();
		$local     = $this->countdown_service->format_local_datetime( (int) $countdown['target_timestamp'], $timezone );
		$offset    = $timezone->getOffset( new \DateTimeImmutable( 'now', $timezone ) );
		?>
		<input type="datetime-local" id="mmsm-countdown-target" name="<?php echo esc_attr( MMSM_SETTINGS_OPTION ); ?>[countdowns][maintenance][target_local]" value="<?php echo esc_attr( $local ); ?>" data-saved-local="<?php echo esc_attr( $local ); ?>" data-saved-timestamp="<?php echo esc_attr( (string) $countdown['target_timestamp'] ); ?>" data-site-timezone="<?php echo esc_attr( $timezone->getName() ); ?>" data-site-offset="<?php echo esc_attr( (string) $offset ); ?>" />
		<p class="description">
			<?php
			echo esc_html(
				sprintf(
					/* translators: 1: WordPress timezone name, 2: current local site time. */
					__( 'Timezone: %1$s. Current site time: %2$s. The saved value is converted to a universal timestamp.', 'maneuvrez-maintenance-studio' ),
					$timezone->getName(),
					wp_date( 'Y-m-d H:i T', null, $timezone )
				)
			);
			?>
		</p>
		<?php
	}

	/**
	 * Render editable heading and description fields.
	 *
	 * @return void
	 */
	public function render_countdown_copy_field() {
		$countdown = $this->get_maintenance_countdown();
		?>
		<p><label for="mmsm-countdown-heading"><strong><?php echo esc_html__( 'Heading', 'maneuvrez-maintenance-studio' ); ?></strong></label><br />
		<input type="text" class="regular-text" id="mmsm-countdown-heading" name="<?php echo esc_attr( MMSM_SETTINGS_OPTION ); ?>[countdowns][maintenance][heading]" value="<?php echo esc_attr( (string) $countdown['heading'] ); ?>" /></p>
		<p><label for="mmsm-countdown-description"><strong><?php echo esc_html__( 'Description', 'maneuvrez-maintenance-studio' ); ?></strong></label><br />
		<textarea class="large-text" rows="3" id="mmsm-countdown-description" name="<?php echo esc_attr( MMSM_SETTINGS_OPTION ); ?>[countdowns][maintenance][description]"><?php echo esc_textarea( (string) $countdown['description'] ); ?></textarea></p>
		<p class="description"><?php echo esc_html__( 'Both fields are optional and accept plain text only.', 'maneuvrez-maintenance-studio' ); ?></p>
		<?php
	}

	/**
	 * Render independent time-unit controls.
	 *
	 * @return void
	 */
	public function render_countdown_units_field() {
		$countdown = $this->get_maintenance_countdown();
		$units     = array(
			'days'    => __( 'Days', 'maneuvrez-maintenance-studio' ),
			'hours'   => __( 'Hours', 'maneuvrez-maintenance-studio' ),
			'minutes' => __( 'Minutes', 'maneuvrez-maintenance-studio' ),
			'seconds' => __( 'Seconds', 'maneuvrez-maintenance-studio' ),
		);
		?>
		<div class="mmsm-countdown-unit-controls">
			<?php foreach ( $units as $unit => $label ) : ?>
				<label><input type="checkbox" name="<?php echo esc_attr( MMSM_SETTINGS_OPTION ); ?>[countdowns][maintenance][show_<?php echo esc_attr( $unit ); ?>]" value="1" <?php checked( 1, (int) $countdown[ 'show_' . $unit ] ); ?> /> <?php echo esc_html( $label ); ?></label>
			<?php endforeach; ?>
		</div>
		<p class="description"><?php echo esc_html__( 'Keep at least one unit visible. Hours use the conventional 0–23 remainder even when days are hidden.', 'maneuvrez-maintenance-studio' ); ?></p>
		<?php
	}

	/**
	 * Render the expiry-action selector with plain-language explanations.
	 *
	 * @return void
	 */
	public function render_countdown_expiry_field() {
		$countdown = $this->get_maintenance_countdown();
		?>
		<select id="mmsm-countdown-expiry-action" name="<?php echo esc_attr( MMSM_SETTINGS_OPTION ); ?>[countdowns][maintenance][expiry_action]">
			<option value="hold_zero" <?php selected( $countdown['expiry_action'], 'hold_zero' ); ?>><?php echo esc_html__( 'Keep showing 00:00:00:00', 'maneuvrez-maintenance-studio' ); ?></option>
			<option value="hide" <?php selected( $countdown['expiry_action'], 'hide' ); ?>><?php echo esc_html__( 'Hide the countdown', 'maneuvrez-maintenance-studio' ); ?></option>
			<option value="show_message" <?php selected( $countdown['expiry_action'], 'show_message' ); ?>><?php echo esc_html__( 'Show a message', 'maneuvrez-maintenance-studio' ); ?></option>
			<option value="disable_mode" <?php selected( $countdown['expiry_action'], 'disable_mode' ); ?>><?php echo esc_html__( 'Turn off maintenance mode automatically', 'maneuvrez-maintenance-studio' ); ?></option>
		</select>
		<ul class="mmsm-countdown-expiry-help">
			<li><?php echo esc_html__( 'Keep showing 00:00:00:00 leaves the timer visible at zero.', 'maneuvrez-maintenance-studio' ); ?></li>
			<li><?php echo esc_html__( 'Hide removes only the countdown; maintenance mode stays on.', 'maneuvrez-maintenance-studio' ); ?></li>
			<li><?php echo esc_html__( 'Show a message replaces the timer with your finished message.', 'maneuvrez-maintenance-studio' ); ?></li>
			<li><?php echo esc_html__( 'Turn off maintenance mode publishes the normal site after server-side expiry processing.', 'maneuvrez-maintenance-studio' ); ?></li>
		</ul>
		<?php
	}

	/**
	 * Render completion copy used by the show-message action.
	 *
	 * @return void
	 */
	public function render_countdown_finished_message_field() {
		$countdown = $this->get_maintenance_countdown();
		?>
		<textarea class="large-text" rows="3" id="mmsm-countdown-finished-message" name="<?php echo esc_attr( MMSM_SETTINGS_OPTION ); ?>[countdowns][maintenance][finished_message]"><?php echo esc_textarea( (string) $countdown['finished_message'] ); ?></textarea>
		<p class="description"><?php echo esc_html__( 'Required when “Show a message” is selected. Plain text only.', 'maneuvrez-maintenance-studio' ); ?></p>
		<?php
	}

	/**
	 * Render simple animation and color choices.
	 *
	 * @return void
	 */
	public function render_countdown_appearance_field() {
		$countdown = $this->get_maintenance_countdown();
		?>
		<p><label for="mmsm-countdown-animation"><strong><?php echo esc_html__( 'Animation style', 'maneuvrez-maintenance-studio' ); ?></strong></label><br />
		<select id="mmsm-countdown-animation" name="<?php echo esc_attr( MMSM_SETTINGS_OPTION ); ?>[countdowns][maintenance][animation_style]">
			<option value="none" <?php selected( $countdown['animation_style'], 'none' ); ?>><?php echo esc_html__( 'None', 'maneuvrez-maintenance-studio' ); ?></option>
			<option value="fade" <?php selected( $countdown['animation_style'], 'fade' ); ?>><?php echo esc_html__( 'Soft fade', 'maneuvrez-maintenance-studio' ); ?></option>
			<option value="slide" <?php selected( $countdown['animation_style'], 'slide' ); ?>><?php echo esc_html__( 'Smooth slide', 'maneuvrez-maintenance-studio' ); ?></option>
			<option value="flip" <?php selected( $countdown['animation_style'], 'flip' ); ?>><?php echo esc_html__( 'Flip card', 'maneuvrez-maintenance-studio' ); ?></option>
			<option value="pulse" <?php selected( $countdown['animation_style'], 'pulse' ); ?>><?php echo esc_html__( 'Soft pulse', 'maneuvrez-maintenance-studio' ); ?></option>
			<option value="bounce" <?php selected( $countdown['animation_style'], 'bounce' ); ?>><?php echo esc_html__( 'Bounce', 'maneuvrez-maintenance-studio' ); ?></option>
			<option value="roll" <?php selected( $countdown['animation_style'], 'roll' ); ?>><?php echo esc_html__( 'Rolling digit', 'maneuvrez-maintenance-studio' ); ?></option>
		</select></p>
		<p><label for="mmsm-countdown-animation-scope"><strong><?php echo esc_html__( 'Animate', 'maneuvrez-maintenance-studio' ); ?></strong></label><br />
		<select id="mmsm-countdown-animation-scope" name="<?php echo esc_attr( MMSM_SETTINGS_OPTION ); ?>[countdowns][maintenance][animation_scope]">
			<option value="digits" <?php selected( $countdown['animation_scope'], 'digits' ); ?>><?php echo esc_html__( 'Digits only', 'maneuvrez-maintenance-studio' ); ?></option>
			<option value="cards" <?php selected( $countdown['animation_scope'], 'cards' ); ?>><?php echo esc_html__( 'Timer cards', 'maneuvrez-maintenance-studio' ); ?></option>
			<option value="both" <?php selected( $countdown['animation_scope'], 'both' ); ?>><?php echo esc_html__( 'Digits and cards', 'maneuvrez-maintenance-studio' ); ?></option>
		</select></p>
		<p><label for="mmsm-countdown-color-mode"><strong><?php echo esc_html__( 'Colors', 'maneuvrez-maintenance-studio' ); ?></strong></label><br />
		<select id="mmsm-countdown-color-mode" name="<?php echo esc_attr( MMSM_SETTINGS_OPTION ); ?>[countdowns][maintenance][color_mode]">
			<option value="theme" <?php selected( $countdown['color_mode'], 'theme' ); ?>><?php echo esc_html__( 'Follow maintenance-page theme', 'maneuvrez-maintenance-studio' ); ?></option>
			<option value="custom" <?php selected( $countdown['color_mode'], 'custom' ); ?>><?php echo esc_html__( 'Custom countdown colors', 'maneuvrez-maintenance-studio' ); ?></option>
		</select></p>
		<div class="<?php echo esc_attr( 'mmsm-countdown-custom-colors' . ( 'custom' === $countdown['color_mode'] ? '' : ' is-hidden' ) ); ?>">
			<?php
			$colors = array(
				'background_color' => __( 'Cell background', 'maneuvrez-maintenance-studio' ),
				'number_color'     => __( 'Number color', 'maneuvrez-maintenance-studio' ),
				'label_color'      => __( 'Unit-label color', 'maneuvrez-maintenance-studio' ),
				'border_color'     => __( 'Border color', 'maneuvrez-maintenance-studio' ),
			);
			foreach ( $colors as $key => $label ) :
				?>
				<label><span><?php echo esc_html( $label ); ?></span><input type="text" class="mmsm-color-picker" name="<?php echo esc_attr( MMSM_SETTINGS_OPTION ); ?>[countdowns][maintenance][<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( (string) $countdown[ $key ] ); ?>" data-default-color="" /></label>
			<?php endforeach; ?>
		</div>
		<p class="description"><?php echo esc_html__( 'Choose whether the style moves the changing digits, their timer cards, or both. Decorative motion is automatically disabled when a visitor prefers reduced motion.', 'maneuvrez-maintenance-studio' ); ?></p>
		<p class="description"><?php echo esc_html__( 'Theme colors are the recommended default.', 'maneuvrez-maintenance-studio' ); ?></p>
		<?php
	}

	/**
	 * Render the Contact Channels section description.
	 *
	 * @return void
	 */
	public function render_contact_channels_section() {
		echo '<p>' . esc_html__( 'Let visitors contact you while your site is being updated, with an optional live-site floating button after maintenance mode is off.', 'maneuvrez-maintenance-studio' ) . '</p>';
		echo '<p class="description">' . esc_html__( 'These links open visitor apps or pages such as WhatsApp, Messenger, phone, email, or directions. No chat scripts, SDKs, or tracking pixels are loaded by the plugin.', 'maneuvrez-maintenance-studio' ) . '</p>';
	}

	/**
	 * Render the Contact Channels master visibility control.
	 *
	 * @return void
	 */
	public function render_contact_channels_enabled_field() {
		$settings = $this->get_settings();
		?>
		<label for="mmsm-contact-channels-enabled">
			<input
				type="checkbox"
				id="mmsm-contact-channels-enabled"
				name="<?php echo esc_attr( MMSM_SETTINGS_OPTION ); ?>[contact_channels_enabled]"
				value="1"
				<?php checked( 1, (int) $settings['contact_channels_enabled'] ); ?>
			/>
			<?php echo esc_html__( 'Show configured contact buttons.', 'maneuvrez-maintenance-studio' ); ?>
		</label>
		<?php
	}

	/**
	 * Render the social links section description.
	 *
	 * @return void
	 */
	public function render_social_links_section() {
		echo '<p>' . esc_html__( 'Choose up to four social or contact destinations with safe platform icons, labels, and URLs.', 'maneuvrez-maintenance-studio' ) . '</p>';
	}

	/**
	 * Render the advanced section description.
	 *
	 * @return void
	 */
	public function render_advanced_section() {
		echo '<p>' . esc_html__( 'Control optional login-entry protection, testing bypasses, and uninstall cleanup without affecting administrator bypass behavior.', 'maneuvrez-maintenance-studio' ) . '</p>';
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
			<?php echo esc_html__( 'Show the maintenance page to logged-out visitors.', 'maneuvrez-maintenance-studio' ); ?>
		</label>
		<p class="description"><?php echo esc_html__( 'Administrators keep normal site access while this is enabled. To test the maintenance page, open the site in a private window or log out.', 'maneuvrez-maintenance-studio' ); ?></p>
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
				<?php echo esc_html__( 'Maintenance', 'maneuvrez-maintenance-studio' ); ?>
			</option>
			<option value="coming_soon" <?php selected( $settings['mode_type'], 'coming_soon' ); ?>>
				<?php echo esc_html__( 'Coming Soon', 'maneuvrez-maintenance-studio' ); ?>
			</option>
		</select>
		<p class="description"><?php echo esc_html__( 'Choose whether the public page should show as maintenance mode or coming soon mode.', 'maneuvrez-maintenance-studio' ); ?></p>
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
		<p class="description"><?php echo esc_html__( 'This title appears as the main heading on the public page.', 'maneuvrez-maintenance-studio' ); ?></p>
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
		<p class="description"><?php echo esc_html__( 'Plain text only in this phase.', 'maneuvrez-maintenance-studio' ); ?></p>
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
			<?php echo esc_html__( 'Display the default login button on the public page.', 'maneuvrez-maintenance-studio' ); ?>
		</label>
		<p class="description"><?php echo esc_html__( 'Turn this off if visitors should not see a login shortcut.', 'maneuvrez-maintenance-studio' ); ?></p>
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
				<?php echo esc_html__( 'Light', 'maneuvrez-maintenance-studio' ); ?>
			</option>
			<option value="dark" <?php selected( $settings['theme_mode'], 'dark' ); ?>>
				<?php echo esc_html__( 'Dark', 'maneuvrez-maintenance-studio' ); ?>
			</option>
			<option value="system" <?php selected( $settings['theme_mode'], 'system' ); ?>>
				<?php echo esc_html__( 'System', 'maneuvrez-maintenance-studio' ); ?>
			</option>
		</select>
		<p class="description"><?php echo esc_html__( 'Choose a light, dark, or system-following visual style for the active template.', 'maneuvrez-maintenance-studio' ); ?></p>
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
				<?php echo esc_html__( 'Default', 'maneuvrez-maintenance-studio' ); ?>
			</option>
		</select>
		<p class="description"><?php echo esc_html__( 'This release includes one polished default template.', 'maneuvrez-maintenance-studio' ); ?></p>
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
			__( 'Accent color used for buttons, focus accents, and status styling.', 'maneuvrez-maintenance-studio' )
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
			__( 'Page background color. Invalid values fall back to the theme default.', 'maneuvrez-maintenance-studio' )
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
			__( 'Card and panel surface color.', 'maneuvrez-maintenance-studio' )
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
			__( 'Main heading and section title color.', 'maneuvrez-maintenance-studio' )
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
			__( 'Primary body copy color for messages and descriptions.', 'maneuvrez-maintenance-studio' )
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
			__( 'Secondary copy color for quieter supporting text.', 'maneuvrez-maintenance-studio' )
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
			__( 'Link and social label color.', 'maneuvrez-maintenance-studio' )
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
			__( 'Text color shown on primary buttons.', 'maneuvrez-maintenance-studio' )
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
			__( 'Border color for cards, pills, and link chips.', 'maneuvrez-maintenance-studio' )
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
			__( 'Optional short label above the page title.', 'maneuvrez-maintenance-studio' )
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
			__( 'Leave blank to hide the primary action, or pair it with a valid URL.', 'maneuvrez-maintenance-studio' )
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
			__( 'Use a full public URL such as https://example.com/status.', 'maneuvrez-maintenance-studio' )
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
			__( 'Optional secondary action label.', 'maneuvrez-maintenance-studio' )
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
			__( 'Use a full public URL or leave blank.', 'maneuvrez-maintenance-studio' )
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
			__( 'Shown above the progress bar component.', 'maneuvrez-maintenance-studio' )
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
			<?php echo esc_html__( 'Display the status progress bar.', 'maneuvrez-maintenance-studio' ); ?>
		</label>
		<p class="description"><?php echo esc_html__( 'Turn this off if you only want the status text.', 'maneuvrez-maintenance-studio' ); ?></p>
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
		<p class="description"><?php echo esc_html__( 'A number between 0 and 100.', 'maneuvrez-maintenance-studio' ); ?></p>
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
			__( 'Short heading for the contact block.', 'maneuvrez-maintenance-studio' )
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
			__( 'Explain when visitors should reach out.', 'maneuvrez-maintenance-studio' )
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
		<p class="description"><?php echo esc_html__( 'Only valid email addresses are rendered publicly.', 'maneuvrez-maintenance-studio' ); ?></p>
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
			__( 'Used when the login button is enabled.', 'maneuvrez-maintenance-studio' )
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
			<?php echo esc_html__( 'Render the footer panel under the main card.', 'maneuvrez-maintenance-studio' ); ?>
		</label>
		<p class="description"><?php echo esc_html__( 'Turn this off to hide the footer meta, social links, and login shortcut area.', 'maneuvrez-maintenance-studio' ); ?></p>
		<?php
	}

	/**
	 * Render the custom login toggle.
	 *
	 * @return void
	 */
	public function render_custom_login_enabled_field() {
		$settings = $this->get_settings();
		?>
		<label for="mmsm-custom-login-enabled">
			<input
				type="checkbox"
				id="mmsm-custom-login-enabled"
				name="<?php echo esc_attr( MMSM_SETTINGS_OPTION ); ?>[custom_login_enabled]"
				value="1"
				<?php checked( 1, (int) $settings['custom_login_enabled'] ); ?>
			/>
			<?php echo esc_html__( 'Serve the WordPress login screen from a custom public URL.', 'maneuvrez-maintenance-studio' ); ?>
		</label>
		<p class="description"><?php echo esc_html__( 'When enabled with a valid slug, logged-out visitors will no longer see the default wp-login.php or wp-admin login entry points.', 'maneuvrez-maintenance-studio' ); ?></p>
		<?php
	}

	/**
	 * Render the custom login slug field and preview.
	 *
	 * @return void
	 */
	public function render_custom_login_slug_field() {
		$settings    = $this->get_settings();
		$slug        = isset( $settings['custom_login_slug'] ) ? (string) $settings['custom_login_slug'] : '';
		$preview_url = $this->get_custom_login_preview_url( $slug );
		?>
		<input
			type="text"
			class="regular-text code"
			id="mmsm-custom-login-slug"
			name="<?php echo esc_attr( MMSM_SETTINGS_OPTION ); ?>[custom_login_slug]"
			value="<?php echo esc_attr( $slug ); ?>"
			placeholder="secure-admin"
			data-home-url="<?php echo esc_url( home_url( '/' ) ); ?>"
			spellcheck="false"
		/>
		<p class="description"><?php echo esc_html__( 'Use 2 to 60 lowercase letters, numbers, or hyphens. Slashes are removed automatically and reserved WordPress paths are rejected.', 'maneuvrez-maintenance-studio' ); ?></p>
		<p class="description">
			<strong><?php echo esc_html__( 'Preview URL:', 'maneuvrez-maintenance-studio' ); ?></strong>
			<code class="mmsm-custom-login-preview"><?php echo esc_html( $preview_url ); ?></code>
		</p>
		<p class="description"><?php echo esc_html__( 'Save this URL before enabling. If you forget it, disable the plugin via FTP or WP-CLI.', 'maneuvrez-maintenance-studio' ); ?></p>
		<?php
	}

	/**
	 * Render the default admin route behavior field.
	 *
	 * @return void
	 */
	public function render_custom_login_block_mode_field() {
		$settings = $this->get_settings();
		$mode     = isset( $settings['custom_login_block_mode'] ) ? (string) $settings['custom_login_block_mode'] : '404';
		?>
		<select id="mmsm-custom-login-block-mode" name="<?php echo esc_attr( MMSM_SETTINGS_OPTION ); ?>[custom_login_block_mode]">
			<option value="404" <?php selected( $mode, '404' ); ?>><?php echo esc_html__( 'Show site 404 page', 'maneuvrez-maintenance-studio' ); ?></option>
			<option value="redirect" <?php selected( $mode, 'redirect' ); ?>><?php echo esc_html__( 'Redirect to custom login URL', 'maneuvrez-maintenance-studio' ); ?></option>
		</select>
		<p class="description"><?php echo esc_html__( 'Controls logged-out visits to /wp-admin, /wp-admin/, and /wp-admin/index.php while the custom login URL is active.', 'maneuvrez-maintenance-studio' ); ?></p>
		<?php
	}

	/**
	 * Render the query-parameter bypass toggle.
	 *
	 * @return void
	 */
	public function render_bypass_query_enabled_field() {
		$settings = $this->get_settings();
		?>
		<label for="mmsm-bypass-query-enabled">
			<input
				type="checkbox"
				id="mmsm-bypass-query-enabled"
				name="<?php echo esc_attr( MMSM_SETTINGS_OPTION ); ?>[bypass_query_enabled]"
				value="1"
				<?php checked( 1, (int) $settings['bypass_query_enabled'] ); ?>
			/>
			<?php echo esc_html__( 'Allow one-request frontend previews with an exact query key and value.', 'maneuvrez-maintenance-studio' ); ?>
		</label>
		<p class="description"><?php echo esc_html__( 'This is a lightweight testing bypass only. It does not create sessions or persistent visitor access.', 'maneuvrez-maintenance-studio' ); ?></p>
		<?php
	}

	/**
	 * Render the query-parameter bypass settings row and preview.
	 *
	 * @return void
	 */
	public function render_bypass_query_settings_field() {
		$settings    = $this->get_settings();
		$home_url    = home_url( '/' );
		$key         = isset( $settings['bypass_query_key'] ) ? (string) $settings['bypass_query_key'] : '';
		$value       = isset( $settings['bypass_query_value'] ) ? (string) $settings['bypass_query_value'] : '';
		$preview_url = $this->get_bypass_query_preview_url( $key, $value );
		?>
		<div class="mmsm-bypass-query-builder" data-home-url="<?php echo esc_url( $home_url ); ?>">
			<div class="mmsm-bypass-query-row">
				<input
					type="text"
					class="regular-text code mmsm-bypass-query-base"
					value="<?php echo esc_attr( $home_url ); ?>"
					readonly
				/>
				<input
					type="text"
					class="small-text code mmsm-bypass-query-key"
					id="mmsm-bypass-query-key"
					name="<?php echo esc_attr( MMSM_SETTINGS_OPTION ); ?>[bypass_query_key]"
					value="<?php echo esc_attr( $key ); ?>"
					placeholder="mmsm_preview"
					spellcheck="false"
				/>
				<input
					type="text"
					class="regular-text code mmsm-bypass-query-value"
					id="mmsm-bypass-query-value"
					name="<?php echo esc_attr( MMSM_SETTINGS_OPTION ); ?>[bypass_query_value]"
					value="<?php echo esc_attr( $value ); ?>"
					placeholder="abc123"
					spellcheck="false"
				/>
				<button type="button" class="button button-secondary mmsm-generate-bypass-query"><?php echo esc_html__( 'Generate', 'maneuvrez-maintenance-studio' ); ?></button>
			</div>
			<p class="description"><?php echo esc_html__( 'Use letters, numbers, underscores, and hyphens only. Append this query string to any frontend URL to preview the real page while maintenance mode is active.', 'maneuvrez-maintenance-studio' ); ?></p>
			<p class="mmsm-bypass-query-preview-wrap">
				<strong><?php echo esc_html__( 'Homepage preview:', 'maneuvrez-maintenance-studio' ); ?></strong>
				<code class="mmsm-bypass-query-preview"><?php echo esc_html( $preview_url ); ?></code>
			</p>
		</div>
		<?php
	}

	/**
	 * Render the public allowlist toggle.
	 *
	 * @return void
	 */
	public function render_bypass_urls_enabled_field() {
		$settings = $this->get_settings();
		?>
		<label for="mmsm-bypass-urls-enabled">
			<input
				type="checkbox"
				id="mmsm-bypass-urls-enabled"
				name="<?php echo esc_attr( MMSM_SETTINGS_OPTION ); ?>[bypass_urls_enabled]"
				value="1"
				<?php checked( 1, (int) $settings['bypass_urls_enabled'] ); ?>
			/>
			<?php echo esc_html__( 'Allow selected public frontend URLs to bypass maintenance mode for everyone.', 'maneuvrez-maintenance-studio' ); ?>
		</label>
		<p class="description"><?php echo esc_html__( 'Use this only for exact public pages that should remain visible while the rest of the site stays in maintenance mode.', 'maneuvrez-maintenance-studio' ); ?></p>
		<?php
	}

	/**
	 * Render the public allowlist textarea.
	 *
	 * @return void
	 */
	public function render_bypass_urls_field() {
		$settings    = $this->get_settings();
		$bypass_urls = isset( $settings['bypass_urls'] ) && is_array( $settings['bypass_urls'] ) ? $settings['bypass_urls'] : array();
		?>
		<textarea
			class="large-text code"
			rows="6"
			id="mmsm-bypass-urls"
			name="<?php echo esc_attr( MMSM_SETTINGS_OPTION ); ?>[bypass_urls]"
			placeholder="/about/&#10;/contact/&#10;<?php echo esc_attr( home_url( '/status/' ) ); ?>"
		><?php echo esc_textarea( implode( "\n", $bypass_urls ) ); ?></textarea>
		<p class="description"><?php echo esc_html__( 'Enter one same-site URL or site-relative path per line. These URLs are public and will bypass maintenance mode for everyone. Use exact frontend paths only.', 'maneuvrez-maintenance-studio' ); ?></p>
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
			<?php echo esc_html__( 'Delete plugin settings when the plugin is removed.', 'maneuvrez-maintenance-studio' ); ?>
		</label>
		<p class="description"><?php echo esc_html__( 'Leave this unchecked to keep your settings for a future reinstall. Check it only if you want uninstall to permanently remove plugin data.', 'maneuvrez-maintenance-studio' ); ?></p>
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
			<div class="mmsm-social-links-intro">
				<div>
					<span class="mmsm-settings-kicker"><?php echo esc_html__( 'Footer links', 'maneuvrez-maintenance-studio' ); ?></span>
					<h3><?php echo esc_html__( 'Social Links', 'maneuvrez-maintenance-studio' ); ?></h3>
					<p class="description"><?php echo esc_html__( 'Choose the platform and destination first. Icon overrides stay grouped under each row so the default path remains simple.', 'maneuvrez-maintenance-studio' ); ?></p>
				</div>
				<button type="button" class="button button-primary mmsm-add-social-item"><?php echo esc_html__( 'Add link', 'maneuvrez-maintenance-studio' ); ?></button>
			</div>
			<div class="mmsm-social-links-display-panel">
				<?php
				$this->render_select_field(
					'social_links_display',
					'mmsm-social-links-display',
					__( 'Display style', 'maneuvrez-maintenance-studio' ),
					array(
						'icon_label' => __( 'Icon + label', 'maneuvrez-maintenance-studio' ),
						'icon_only'  => __( 'Icon only', 'maneuvrez-maintenance-studio' ),
					),
					(string) $settings['social_links_display']
				);
				?>
				<p class="description"><?php echo esc_html__( 'Icon-only keeps the label available to screen readers while hiding it visually.', 'maneuvrez-maintenance-studio' ); ?></p>
			</div>
			<div class="mmsm-social-links-list">
				<?php foreach ( $social_links as $index => $social_item ) : ?>
					<?php $this->render_social_link_row( $index, is_array( $social_item ) ? $social_item : $default_item, $platforms ); ?>
				<?php endforeach; ?>
			</div>
			<script type="text/template" class="mmsm-social-item-template">
				<?php $this->render_social_link_row( '__INDEX__', $default_item, $platforms ); ?>
			</script>
		</div>
		<?php
	}

	/**
	 * Render the Contact Channels settings.
	 *
	 * @return void
	 */
	public function render_contact_channels_field() {
		$settings      = $this->get_settings();
		$items         = isset( $settings['contact_channels_items'] ) && is_array( $settings['contact_channels_items'] ) ? array_values( $settings['contact_channels_items'] ) : array();
		$default_item  = $this->get_default_contact_channel_item();
		$channel_types = ContactChannels::get_channel_type_labels();

		if ( empty( $items ) ) {
			$items = array( $default_item );
		}
		?>
		<input type="hidden" name="mmsm_contact_channels_present" value="1" />
		<div class="mmsm-contact-channels-builder" data-next-index="<?php echo esc_attr( (string) count( $items ) ); ?>">
			<div class="mmsm-contact-channels-hero">
				<div>
					<span class="mmsm-contact-channels-eyebrow"><?php echo esc_html__( 'Visitor contact buttons', 'maneuvrez-maintenance-studio' ); ?></span>
					<h3><?php echo esc_html__( 'Build a clear contact path', 'maneuvrez-maintenance-studio' ); ?></h3>
					<p data-contact-channels-summary><?php echo esc_html__( 'Turn them on when you are ready to show visitor contact buttons.', 'maneuvrez-maintenance-studio' ); ?></p>
				</div>
				<div class="mmsm-contact-channels-hero-status" role="status" aria-live="polite">
					<span class="mmsm-contact-channels-status-pill" data-contact-channels-status><?php echo esc_html__( 'Off', 'maneuvrez-maintenance-studio' ); ?></span>
				</div>
				<ol class="mmsm-contact-channels-steps" aria-label="<?php echo esc_attr__( 'Contact Channels setup progress', 'maneuvrez-maintenance-studio' ); ?>">
					<li data-contact-step="enabled">
						<span>1</span>
						<strong><?php echo esc_html__( 'Enable', 'maneuvrez-maintenance-studio' ); ?></strong>
						<em><?php echo esc_html__( 'Turn on the feature', 'maneuvrez-maintenance-studio' ); ?></em>
					</li>
					<li data-contact-step="display">
						<span>2</span>
						<strong><?php echo esc_html__( 'Place', 'maneuvrez-maintenance-studio' ); ?></strong>
						<em><?php echo esc_html__( 'Choose where it appears', 'maneuvrez-maintenance-studio' ); ?></em>
					</li>
					<li data-contact-step="channels">
						<span>3</span>
						<strong><?php echo esc_html__( 'Connect', 'maneuvrez-maintenance-studio' ); ?></strong>
						<em><?php echo esc_html__( 'Add at least one destination', 'maneuvrez-maintenance-studio' ); ?></em>
					</li>
				</ol>
			</div>
			<div class="mmsm-contact-channels-workspace">
				<div class="mmsm-contact-channels-main">
					<div class="mmsm-contact-channels-grid">
					<div class="mmsm-contact-channels-panel mmsm-contact-channels-panel-visibility">
					<h3><?php echo esc_html__( 'Visibility', 'maneuvrez-maintenance-studio' ); ?></h3>
					<div class="mmsm-contact-channels-enabled-fields">
						<?php
						$this->render_select_field(
							'contact_channels_maintenance_display',
							'mmsm-contact-channels-maintenance-display',
							__( 'Show during maintenance', 'maneuvrez-maintenance-studio' ),
							array(
								'off'      => __( 'Off', 'maneuvrez-maintenance-studio' ),
								'inside'   => __( 'Inside page', 'maneuvrez-maintenance-studio' ),
								'floating' => __( 'Floating button', 'maneuvrez-maintenance-studio' ),
								'both'     => __( 'Inside page and floating button', 'maneuvrez-maintenance-studio' ),
							),
							(string) $settings['contact_channels_maintenance_display']
						);
						$this->render_select_field(
							'contact_channels_live_display',
							'mmsm-contact-channels-live-display',
							__( 'Show on live site', 'maneuvrez-maintenance-studio' ),
							array(
								'off'      => __( 'Off', 'maneuvrez-maintenance-studio' ),
								'floating' => __( 'Floating button', 'maneuvrez-maintenance-studio' ),
							),
							(string) $settings['contact_channels_live_display']
						);
						?>
						<div class="mmsm-contact-live-dependent">
							<?php
							$this->render_select_field(
								'contact_channels_logged_in_visibility',
								'mmsm-contact-channels-logged-in-visibility',
								__( 'Live-site visibility for logged-in users', 'maneuvrez-maintenance-studio' ),
								array(
									'show_all'       => __( 'Show to visitors and logged-in users', 'maneuvrez-maintenance-studio' ),
									'hide_admins'    => __( 'Hide for administrators', 'maneuvrez-maintenance-studio' ),
									'hide_logged_in' => __( 'Hide for all logged-in users', 'maneuvrez-maintenance-studio' ),
								),
								(string) $settings['contact_channels_logged_in_visibility']
							);
							?>
						</div>
					</div>
				</div>
				<div class="mmsm-contact-channels-panel mmsm-contact-channels-panel-content mmsm-contact-display-dependent">
					<h3><?php echo esc_html__( 'Content', 'maneuvrez-maintenance-studio' ); ?></h3>
					<p class="mmsm-contact-maintenance-inside-dependent">
						<label for="mmsm-contact-channels-heading"><?php echo esc_html__( 'Heading', 'maneuvrez-maintenance-studio' ); ?></label><br />
						<input type="text" class="regular-text" id="mmsm-contact-channels-heading" name="<?php echo esc_attr( MMSM_SETTINGS_OPTION ); ?>[contact_channels_heading]" value="<?php echo esc_attr( (string) $settings['contact_channels_heading'] ); ?>" />
					</p>
					<p class="mmsm-contact-maintenance-inside-dependent">
						<label for="mmsm-contact-channels-description"><?php echo esc_html__( 'Description', 'maneuvrez-maintenance-studio' ); ?></label><br />
						<input type="text" class="regular-text" id="mmsm-contact-channels-description" name="<?php echo esc_attr( MMSM_SETTINGS_OPTION ); ?>[contact_channels_description]" value="<?php echo esc_attr( (string) $settings['contact_channels_description'] ); ?>" />
					</p>
					<p class="mmsm-contact-trigger-label-dependent">
						<label for="mmsm-contact-channels-primary-label"><?php echo esc_html__( 'Floating button / reveal menu label', 'maneuvrez-maintenance-studio' ); ?></label><br />
						<input type="text" class="regular-text" id="mmsm-contact-channels-primary-label" name="<?php echo esc_attr( MMSM_SETTINGS_OPTION ); ?>[contact_channels_primary_label]" value="<?php echo esc_attr( (string) $settings['contact_channels_primary_label'] ); ?>" />
						<span class="description"><?php echo esc_html__( 'Used for the floating button and the reveal menu trigger. Individual channel labels still appear inside button rows and menus.', 'maneuvrez-maintenance-studio' ); ?></span>
					</p>
					<div class="mmsm-contact-floating-dependent">
						<?php
						$this->render_select_field(
							'contact_channels_position',
							'mmsm-contact-channels-position',
							__( 'Floating position', 'maneuvrez-maintenance-studio' ),
							array(
								'bottom_right' => __( 'Bottom right', 'maneuvrez-maintenance-studio' ),
								'bottom_left'  => __( 'Bottom left', 'maneuvrez-maintenance-studio' ),
								'top_right'    => __( 'Top right', 'maneuvrez-maintenance-studio' ),
								'top_left'     => __( 'Top left', 'maneuvrez-maintenance-studio' ),
							),
							(string) $settings['contact_channels_position']
						);
						?>
					</div>
					<div class="mmsm-contact-maintenance-inside-dependent">
						<?php
						$this->render_select_field(
							'contact_channels_display_style',
							'mmsm-contact-channels-display-style',
							__( 'Maintenance display style', 'maneuvrez-maintenance-studio' ),
							array(
								'auto'   => __( 'Auto', 'maneuvrez-maintenance-studio' ),
								'row'    => __( 'Button row', 'maneuvrez-maintenance-studio' ),
								'reveal' => __( 'Reveal menu', 'maneuvrez-maintenance-studio' ),
							),
							(string) $settings['contact_channels_display_style']
						);
						?>
					</div>
				</div>
				<div class="mmsm-contact-channels-panel mmsm-contact-channels-panel-appearance mmsm-contact-display-dependent">
					<h3><?php echo esc_html__( 'Appearance', 'maneuvrez-maintenance-studio' ); ?></h3>
					<?php
					$this->render_select_field(
						'contact_channels_button_shape',
						'mmsm-contact-channels-button-shape',
						__( 'Button shape', 'maneuvrez-maintenance-studio' ),
						array(
							'rounded' => __( 'Rounded', 'maneuvrez-maintenance-studio' ),
							'pill'    => __( 'Pill', 'maneuvrez-maintenance-studio' ),
							'circle'  => __( 'Circle', 'maneuvrez-maintenance-studio' ),
							'square'  => __( 'Square', 'maneuvrez-maintenance-studio' ),
						),
						(string) $settings['contact_channels_button_shape']
					);
					$this->render_select_field(
						'contact_channels_button_display',
						'mmsm-contact-channels-button-display',
						__( 'Button display', 'maneuvrez-maintenance-studio' ),
						array(
							'icon_label' => __( 'Icon + label', 'maneuvrez-maintenance-studio' ),
							'icon_only'  => __( 'Icon only', 'maneuvrez-maintenance-studio' ),
							'label_only' => __( 'Label only', 'maneuvrez-maintenance-studio' ),
						),
						(string) $settings['contact_channels_button_display']
					);
					$this->render_select_field(
						'contact_channels_color_mode',
						'mmsm-contact-channels-color-mode',
						__( 'Button color style', 'maneuvrez-maintenance-studio' ),
						array(
							'theme'  => __( 'Match site / maintenance theme', 'maneuvrez-maintenance-studio' ),
							'brand'  => __( 'Use platform brand colors', 'maneuvrez-maintenance-studio' ),
							'custom' => __( 'Custom colors', 'maneuvrez-maintenance-studio' ),
						),
						(string) $settings['contact_channels_color_mode']
					);
					?>
					<p class="description"><?php echo esc_html__( 'Platform colors help visitors recognize each option. The plugin does not load official platform widgets or scripts.', 'maneuvrez-maintenance-studio' ); ?></p>
					<div class="mmsm-contact-channel-custom-colors" data-contact-color-controls>
						<div class="mmsm-contact-color-group" data-contact-color-group="colors">
							<div class="mmsm-contact-color-group-header">
								<strong><?php echo esc_html__( 'Colors', 'maneuvrez-maintenance-studio' ); ?></strong>
								<div class="mmsm-contact-color-state-tabs" role="tablist" aria-label="<?php echo esc_attr__( 'Contact button color state', 'maneuvrez-maintenance-studio' ); ?>">
									<button type="button" class="mmsm-contact-color-state-toggle is-active" data-contact-color-tab="colors-normal" aria-selected="true"><?php echo esc_html__( 'Normal', 'maneuvrez-maintenance-studio' ); ?></button>
									<button type="button" class="mmsm-contact-color-state-toggle" data-contact-color-tab="colors-hover" aria-selected="false"><?php echo esc_html__( 'Hover', 'maneuvrez-maintenance-studio' ); ?></button>
								</div>
							</div>
							<div class="mmsm-contact-color-panel is-active" data-contact-color-panel="colors-normal">
								<div class="mmsm-contact-channel-color-row mmsm-contact-channel-color-row-trio">
									<div class="mmsm-contact-channel-color-control">
										<span class="mmsm-contact-channel-color-label"><?php echo esc_html__( 'Background', 'maneuvrez-maintenance-studio' ); ?></span>
										<?php $this->render_color_picker_input( 'contact_channels_background_color', 'mmsm-contact-channels-background-color', __( 'Normal background color.', 'maneuvrez-maintenance-studio' ) ); ?>
									</div>
									<div class="mmsm-contact-channel-color-control">
										<span class="mmsm-contact-channel-color-label"><?php echo esc_html__( 'Text', 'maneuvrez-maintenance-studio' ); ?></span>
										<?php $this->render_color_picker_input( 'contact_channels_text_color', 'mmsm-contact-channels-text-color', __( 'Normal text color.', 'maneuvrez-maintenance-studio' ) ); ?>
									</div>
									<div class="mmsm-contact-channel-color-control">
										<span class="mmsm-contact-channel-color-label"><?php echo esc_html__( 'Icon', 'maneuvrez-maintenance-studio' ); ?></span>
										<?php $this->render_color_picker_input( 'contact_channels_icon_color', 'mmsm-contact-channels-icon-color', __( 'Normal icon color.', 'maneuvrez-maintenance-studio' ) ); ?>
									</div>
								</div>
							</div>
							<div class="mmsm-contact-color-panel" data-contact-color-panel="colors-hover">
								<div class="mmsm-contact-channel-color-row mmsm-contact-channel-color-row-dual">
									<div class="mmsm-contact-channel-color-control">
										<span class="mmsm-contact-channel-color-label"><?php echo esc_html__( 'Background', 'maneuvrez-maintenance-studio' ); ?></span>
										<?php $this->render_color_picker_input( 'contact_channels_hover_background_color', 'mmsm-contact-channels-hover-background-color', __( 'Hover background color.', 'maneuvrez-maintenance-studio' ) ); ?>
									</div>
									<div class="mmsm-contact-channel-color-control">
										<span class="mmsm-contact-channel-color-label"><?php echo esc_html__( 'Text & icon', 'maneuvrez-maintenance-studio' ); ?></span>
										<?php $this->render_color_picker_input( 'contact_channels_hover_text_color', 'mmsm-contact-channels-hover-text-color', __( 'Hover text and icon color.', 'maneuvrez-maintenance-studio' ) ); ?>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
					<div class="mmsm-contact-display-dependent">
						<div class="mmsm-contact-channel-list-header">
							<div>
								<h3 class="mmsm-contact-channel-list-title"><?php echo esc_html__( 'Channels', 'maneuvrez-maintenance-studio' ); ?></h3>
								<p class="description"><?php echo esc_html__( 'Start with a channel type and destination. Optional label, icon, and link behavior stay tucked under More options.', 'maneuvrez-maintenance-studio' ); ?></p>
							</div>
							<button type="button" class="button button-primary mmsm-add-contact-channel"><?php echo esc_html__( 'Add channel', 'maneuvrez-maintenance-studio' ); ?></button>
						</div>
						<div class="mmsm-contact-channel-list">
							<?php foreach ( $items as $index => $item ) : ?>
								<?php $this->render_contact_channel_row( $index, is_array( $item ) ? $item : $default_item, $channel_types ); ?>
							<?php endforeach; ?>
						</div>
						<script type="text/template" class="mmsm-contact-channel-template">
							<?php $this->render_contact_channel_row( '__INDEX__', $default_item, $channel_types ); ?>
						</script>
					</div>
				</div>
			</div>
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
	 * Render a standard select field.
	 *
	 * @param string               $key Field key.
	 * @param string               $id Input id.
	 * @param string               $label Field label.
	 * @param array<string,string> $choices Select choices.
	 * @param string               $current Current value.
	 * @return void
	 */
	private function render_select_field( $key, $id, $label, array $choices, $current ) {
		?>
		<p>
			<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label><br />
			<select
				id="<?php echo esc_attr( $id ); ?>"
				name="<?php echo esc_attr( MMSM_SETTINGS_OPTION ); ?>[<?php echo esc_attr( $key ); ?>]"
			>
				<?php foreach ( $choices as $value => $choice_label ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $current, (string) $value ); ?>>
						<?php echo esc_html( $choice_label ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</p>
		<?php
	}

	/**
	 * Render a single Contact Channels repeater row.
	 *
	 * @param int|string           $index Row index.
	 * @param array<string,mixed>  $item Channel item values.
	 * @param array<string,string> $channel_types Supported channel types.
	 * @return void
	 */
	private function render_contact_channel_row( $index, array $item, array $channel_types ) {
		$type          = isset( $item['type'] ) ? (string) $item['type'] : 'whatsapp';
		$country_code  = isset( $item['country_code'] ) ? (string) $item['country_code'] : '';
		$value         = isset( $item['value'] ) ? (string) $item['value'] : '';
		$display_value = $this->get_contact_channel_display_value( $type, $value, $country_code );
		$label         = isset( $item['label'] ) ? (string) $item['label'] : '';
		$message       = isset( $item['prefilled_message'] ) ? (string) $item['prefilled_message'] : '';
		$icon_source   = isset( $item['icon_source'] ) ? (string) $item['icon_source'] : 'default';
		$icon_value    = isset( $item['icon_value'] ) ? (string) $item['icon_value'] : '';
		$open_new_tab  = ! empty( $item['open_new_tab'] );
		$country_codes = ContactChannels::get_country_codes();
		$icon_sources  = ContactChannels::get_icon_source_labels();
		$dashicons     = ContactChannels::get_dashicon_choices();
		?>
		<div class="mmsm-contact-channel-item" data-contact-channel-item>
			<div class="mmsm-contact-channel-toolbar">
				<div class="mmsm-contact-channel-heading">
					<span class="mmsm-contact-channel-icon-preview dashicons dashicons-format-chat" data-contact-channel-icon-preview aria-hidden="true"></span>
					<div>
						<strong data-contact-channel-summary><?php echo esc_html__( 'Contact channel', 'maneuvrez-maintenance-studio' ); ?></strong>
						<span class="mmsm-contact-channel-subsummary" data-contact-channel-subsummary><?php echo esc_html__( 'Choose a destination before this row can render.', 'maneuvrez-maintenance-studio' ); ?></span>
					</div>
				</div>
				<div class="mmsm-contact-channel-actions">
					<span class="mmsm-contact-channel-state" data-contact-channel-state><?php echo esc_html__( 'Needs destination', 'maneuvrez-maintenance-studio' ); ?></span>
					<button type="button" class="button-link-delete mmsm-remove-contact-channel"><?php echo esc_html__( 'Remove', 'maneuvrez-maintenance-studio' ); ?></button>
				</div>
			</div>
			<div class="mmsm-contact-channel-fields">
				<p>
					<label><?php echo esc_html__( 'Type', 'maneuvrez-maintenance-studio' ); ?></label><br />
					<select class="mmsm-contact-channel-type" name="<?php echo esc_attr( MMSM_SETTINGS_OPTION ); ?>[contact_channels_items][<?php echo esc_attr( (string) $index ); ?>][type]">
						<?php foreach ( $channel_types as $type_key => $type_label ) : ?>
							<option value="<?php echo esc_attr( $type_key ); ?>" <?php selected( $type, $type_key ); ?>>
								<?php echo esc_html( $type_label ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</p>
				<p class="mmsm-contact-channel-country-field">
					<label><?php echo esc_html__( 'Country code', 'maneuvrez-maintenance-studio' ); ?></label><br />
					<select class="mmsm-contact-channel-country-code" name="<?php echo esc_attr( MMSM_SETTINGS_OPTION ); ?>[contact_channels_items][<?php echo esc_attr( (string) $index ); ?>][country_code]">
						<?php foreach ( $country_codes as $code => $code_label ) : ?>
							<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $country_code, $code ); ?>>
								<?php echo esc_html( $code_label ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</p>
				<p>
					<label class="mmsm-contact-channel-value-label"><?php echo esc_html__( 'Destination', 'maneuvrez-maintenance-studio' ); ?></label><br />
					<input
						type="text"
						class="regular-text code mmsm-contact-channel-value"
						name="<?php echo esc_attr( MMSM_SETTINGS_OPTION ); ?>[contact_channels_items][<?php echo esc_attr( (string) $index ); ?>][value]"
						value="<?php echo esc_attr( $display_value ); ?>"
						placeholder="+923001234567, page-name, hello@example.com, or https://"
					/>
					<span class="mmsm-contact-channel-help" data-contact-channel-help></span>
				</p>
			</div>
			<details class="mmsm-contact-channel-options">
				<summary><?php echo esc_html__( 'More options', 'maneuvrez-maintenance-studio' ); ?></summary>
				<div class="mmsm-contact-channel-option-fields">
					<p>
						<label><?php echo esc_html__( 'Button label', 'maneuvrez-maintenance-studio' ); ?></label><br />
						<input
							type="text"
							class="regular-text mmsm-contact-channel-label-input"
							name="<?php echo esc_attr( MMSM_SETTINGS_OPTION ); ?>[contact_channels_items][<?php echo esc_attr( (string) $index ); ?>][label]"
							value="<?php echo esc_attr( $label ); ?>"
							placeholder="<?php echo esc_attr( ContactChannels::get_default_labels()[ $type ] ?? __( 'Contact Us', 'maneuvrez-maintenance-studio' ) ); ?>"
						/>
					</p>
					<p class="mmsm-contact-channel-message-field">
						<label><?php echo esc_html__( 'WhatsApp prefilled message', 'maneuvrez-maintenance-studio' ); ?></label><br />
						<input
							type="text"
							class="regular-text"
							name="<?php echo esc_attr( MMSM_SETTINGS_OPTION ); ?>[contact_channels_items][<?php echo esc_attr( (string) $index ); ?>][prefilled_message]"
							value="<?php echo esc_attr( $message ); ?>"
						/>
					</p>
					<p>
						<label><?php echo esc_html__( 'Icon', 'maneuvrez-maintenance-studio' ); ?></label><br />
						<select class="mmsm-contact-channel-icon-source" name="<?php echo esc_attr( MMSM_SETTINGS_OPTION ); ?>[contact_channels_items][<?php echo esc_attr( (string) $index ); ?>][icon_source]">
							<?php foreach ( $icon_sources as $source_key => $source_label ) : ?>
								<option value="<?php echo esc_attr( $source_key ); ?>" <?php selected( $icon_source, $source_key ); ?>>
									<?php echo esc_html( $source_label ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</p>
					<div class="mmsm-contact-channel-icon-value-field">
						<input type="hidden" name="<?php echo esc_attr( MMSM_SETTINGS_OPTION ); ?>[contact_channels_items][<?php echo esc_attr( (string) $index ); ?>][icon_library]" value="dashicons" />
						<p>
							<label><?php echo esc_html__( 'Dashicon', 'maneuvrez-maintenance-studio' ); ?></label><br />
							<select name="<?php echo esc_attr( MMSM_SETTINGS_OPTION ); ?>[contact_channels_items][<?php echo esc_attr( (string) $index ); ?>][icon_value]">
								<?php foreach ( $dashicons as $dashicon_key => $dashicon_label ) : ?>
									<option value="<?php echo esc_attr( $dashicon_key ); ?>" <?php selected( $icon_value, $dashicon_key ); ?>>
										<?php echo esc_html( $dashicon_label ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</p>
					</div>
					<p>
						<label>
							<input
								type="checkbox"
								name="<?php echo esc_attr( MMSM_SETTINGS_OPTION ); ?>[contact_channels_items][<?php echo esc_attr( (string) $index ); ?>][open_new_tab]"
								value="1"
								<?php checked( $open_new_tab ); ?>
							/>
							<?php echo esc_html__( 'Open in a new tab when supported.', 'maneuvrez-maintenance-studio' ); ?>
						</label>
					</p>
				</div>
			</details>
		</div>
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
		$platform_label  = isset( $platforms[ $platform ] ) ? $platforms[ $platform ] : __( 'Social link', 'maneuvrez-maintenance-studio' );
		$display_label   = $is_custom && '' !== $custom_name ? $custom_name : $platform_label;
		?>
		<div class="mmsm-social-item-group" data-social-item>
			<div class="mmsm-social-item-toolbar">
				<div class="mmsm-social-item-heading">
					<span
						class="mmsm-social-item-icon-preview"
						data-social-icon-preview
						<?php if ( '' !== $icon_color ) : ?>
							style="<?php echo esc_attr( 'color: ' . $icon_color . ';' ); ?>"
						<?php endif; ?>
					>
						<?php echo SocialLinksComponent::get_platform_icon_markup( $platform ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</span>
					<div>
						<strong data-social-item-summary><?php echo esc_html( $display_label ); ?></strong>
						<span class="mmsm-social-item-subsummary" data-social-item-subsummary><?php echo esc_html( '' !== $url ? $url : __( 'Add a destination URL or email.', 'maneuvrez-maintenance-studio' ) ); ?></span>
					</div>
				</div>
				<div class="mmsm-social-item-actions">
					<span class="mmsm-social-item-state" data-social-item-state><?php echo esc_html( '' !== $url ? __( 'Ready', 'maneuvrez-maintenance-studio' ) : __( 'Needs URL', 'maneuvrez-maintenance-studio' ) ); ?></span>
					<button type="button" class="button-link-delete mmsm-remove-social-item"><?php echo esc_html__( 'Remove', 'maneuvrez-maintenance-studio' ); ?></button>
				</div>
			</div>
			<div class="mmsm-social-item-primary-fields">
				<p>
					<label><?php echo esc_html__( 'Platform', 'maneuvrez-maintenance-studio' ); ?></label><br />
					<select
						class="mmsm-social-platform-select"
						name="<?php echo esc_attr( MMSM_SETTINGS_OPTION ); ?>[social_links][<?php echo esc_attr( (string) $index ); ?>][platform]"
					>
						<?php foreach ( $platforms as $platform_key => $choice_label ) : ?>
							<option value="<?php echo esc_attr( $platform_key ); ?>" <?php selected( $platform, $platform_key ); ?>>
								<?php echo esc_html( $choice_label ); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</p>
				<p>
					<label><?php echo esc_html__( 'Destination', 'maneuvrez-maintenance-studio' ); ?></label><br />
					<input
						type="text"
						class="regular-text code mmsm-social-url-input"
						name="<?php echo esc_attr( MMSM_SETTINGS_OPTION ); ?>[social_links][<?php echo esc_attr( (string) $index ); ?>][url]"
						value="<?php echo esc_attr( $url ); ?>"
						placeholder="https://example.com"
					/>
				</p>
			</div>
			<div class="mmsm-social-custom-fields<?php echo $is_custom ? '' : ' is-hidden'; ?>" data-custom-fields>
				<p>
					<label><?php echo esc_html__( 'Custom platform name', 'maneuvrez-maintenance-studio' ); ?></label><br />
					<input
						type="text"
						class="regular-text mmsm-social-custom-name-input"
						name="<?php echo esc_attr( MMSM_SETTINGS_OPTION ); ?>[social_links][<?php echo esc_attr( (string) $index ); ?>][custom_name]"
						value="<?php echo esc_attr( $custom_name ); ?>"
						placeholder="<?php echo esc_attr__( 'Community, Store, Support...', 'maneuvrez-maintenance-studio' ); ?>"
					/>
				</p>
			</div>
			<details class="mmsm-social-icon-options" <?php echo ( $is_library || $is_upload || '' !== $icon_color ) ? 'open' : ''; ?>>
				<summary><?php echo esc_html__( 'Icon options', 'maneuvrez-maintenance-studio' ); ?></summary>
				<div class="mmsm-social-icon-picker">
					<p>
						<label><?php echo esc_html__( 'Icon source', 'maneuvrez-maintenance-studio' ); ?></label><br />
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
							<label><?php echo esc_html__( 'Icon library', 'maneuvrez-maintenance-studio' ); ?></label><br />
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
							<label><?php echo esc_html__( 'Dashicon', 'maneuvrez-maintenance-studio' ); ?></label><br />
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
							<button type="button" class="button mmsm-upload-social-icon"><?php echo esc_html__( 'Choose icon', 'maneuvrez-maintenance-studio' ); ?></button>
							<button type="button" class="button-link-delete mmsm-remove-social-icon<?php echo 0 === $custom_icon_id ? ' is-hidden' : ''; ?>"><?php echo esc_html__( 'Remove icon', 'maneuvrez-maintenance-studio' ); ?></button>
						</p>
						<p class="description"><?php echo esc_html__( 'Uploaded icons use the media library. PNG, JPG, and WEBP are accepted.', 'maneuvrez-maintenance-studio' ); ?></p>
					</div>
					<p>
						<label><?php echo esc_html__( 'Icon color', 'maneuvrez-maintenance-studio' ); ?></label><br />
						<input
							type="text"
							class="mmsm-color-picker mmsm-social-icon-color-picker"
							name="<?php echo esc_attr( MMSM_SETTINGS_OPTION ); ?>[social_links][<?php echo esc_attr( (string) $index ); ?>][icon_color]"
							value="<?php echo esc_attr( $icon_color ); ?>"
							data-default-color=""
						/>
					</p>
					<p class="description"><?php echo esc_html__( 'The preview updates as you switch sources. Uploaded image icons keep their original colors.', 'maneuvrez-maintenance-studio' ); ?></p>
				</div>
			</details>
			<p>
				<label>
					<input
						type="checkbox"
						name="<?php echo esc_attr( MMSM_SETTINGS_OPTION ); ?>[social_links][<?php echo esc_attr( (string) $index ); ?>][open_new_tab]"
						value="1"
						<?php checked( $open_new_tab ); ?>
					/>
					<?php echo esc_html__( 'Open in a new tab when supported.', 'maneuvrez-maintenance-studio' ); ?>
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
	 * Return the default admin Contact Channels row.
	 *
	 * @return array<string,mixed>
	 */
	private function get_default_contact_channel_item() {
		return array(
			'type'              => 'whatsapp',
			'country_code'      => '',
			'value'             => '',
			'label'             => '',
			'prefilled_message' => '',
			'icon_source'       => 'default',
			'icon_library'      => 'dashicons',
			'icon_value'        => '',
			'open_new_tab'      => 1,
		);
	}

	/**
	 * Return the value shown in the destination field for paired phone controls.
	 *
	 * @param string $type Channel type.
	 * @param string $value Saved value.
	 * @param string $country_code Saved country code.
	 * @return string
	 */
	private function get_contact_channel_display_value( $type, $value, $country_code ) {
		if ( ! in_array( $type, array( 'whatsapp', 'phone' ), true ) || '' === $country_code || '' === $value ) {
			return $value;
		}

		$country_digits = preg_replace( '/\D+/', '', $country_code );
		$value_digits   = preg_replace( '/\D+/', '', $value );

		if ( ! is_string( $country_digits ) || ! is_string( $value_digits ) || '' === $country_digits ) {
			return $value;
		}

		if ( 0 !== strpos( $value_digits, $country_digits ) ) {
			return $value;
		}

		$local_value = substr( $value_digits, strlen( $country_digits ) );

		return '' === $local_value ? $value : $local_value;
	}

	/**
	 * Build the homepage preview URL for the bypass query UI.
	 *
	 * @param string $key Sanitized query key.
	 * @param string $value Sanitized query value.
	 * @return string
	 */
	private function get_bypass_query_preview_url( $key, $value ) {
		$home_url = home_url( '/' );

		if ( '' === $key ) {
			return $home_url;
		}

		return (string) add_query_arg(
			array(
				$key => $value,
			),
			$home_url
		);
	}

	/**
	 * Return a preview URL for the current custom login slug value.
	 *
	 * @param string $slug Raw or sanitized slug.
	 * @return string
	 */
	private function get_custom_login_preview_url( $slug ) {
		$slug = Sanitizer::sanitize_custom_login_slug( $slug );

		if ( '' === $slug ) {
			return home_url( '/secure-admin/' );
		}

		return home_url( '/' . trailingslashit( $slug ) );
	}

	/**
	 * Return the normalized maintenance countdown instance.
	 *
	 * @return array<string,mixed>
	 */
	private function get_maintenance_countdown() {
		return $this->countdown_service->get_instance( $this->get_settings(), CountdownService::INSTANCE_MAINTENANCE );
	}

	/**
	 * Convert and validate the site-local countdown submission before sanitizing.
	 *
	 * @param array<string,mixed> $input Submitted settings payload.
	 * @param array<string,mixed> $existing Existing normalized settings.
	 * @return array<string,array<string,mixed>>
	 */
	private function prepare_countdowns_for_save( array $input, array $existing ) {
		$existing_countdowns = isset( $existing['countdowns'] ) && is_array( $existing['countdowns'] ) ? $existing['countdowns'] : array();
		$existing_instance   = $this->countdown_service->get_instance( $existing, CountdownService::INSTANCE_MAINTENANCE );
		$submitted_map       = isset( $input['countdowns'] ) && is_array( $input['countdowns'] ) ? $input['countdowns'] : array();
		$submitted           = isset( $submitted_map['maintenance'] ) && is_array( $submitted_map['maintenance'] ) ? $submitted_map['maintenance'] : array();
		$enabled             = ! empty( $submitted['enabled'] );
		$target_local        = isset( $submitted['target_local'] ) ? sanitize_text_field( wp_unslash( $submitted['target_local'] ) ) : '';
		$target_timestamp    = '' === $target_local ? 0 : $this->countdown_service->parse_local_datetime( $target_local );
		$old_target          = (int) $existing_instance['target_timestamp'];
		$old_enabled         = ! empty( $existing_instance['enabled'] );

		if ( $enabled && $target_timestamp <= 0 ) {
			add_settings_error(
				MMSM_SETTINGS_OPTION,
				'mmsm_countdown_target_required',
				esc_html__( 'Choose a valid future target date and time before enabling the countdown.', 'maneuvrez-maintenance-studio' ),
				'error'
			);

			return $existing_countdowns;
		}

		if (
			$enabled &&
			$target_timestamp <= current_datetime()->getTimestamp() &&
			( ! $old_enabled || $target_timestamp !== $old_target )
		) {
			add_settings_error(
				MMSM_SETTINGS_OPTION,
				'mmsm_countdown_target_future',
				esc_html__( 'The countdown target must be in the future. Your previous countdown settings were kept.', 'maneuvrez-maintenance-studio' ),
				'error'
			);

			return $existing_countdowns;
		}

		if ( $enabled && 'show_message' === ( $submitted['expiry_action'] ?? '' ) && '' === trim( (string) ( $submitted['finished_message'] ?? '' ) ) ) {
			add_settings_error(
				MMSM_SETTINGS_OPTION,
				'mmsm_countdown_finished_message_required',
				esc_html__( 'Enter a finished message when “Show a message” is selected. Your previous countdown settings were kept.', 'maneuvrez-maintenance-studio' ),
				'error'
			);

			return $existing_countdowns;
		}

		if (
			$enabled &&
			empty( $submitted['show_days'] ) &&
			empty( $submitted['show_hours'] ) &&
			empty( $submitted['show_minutes'] ) &&
			empty( $submitted['show_seconds'] )
		) {
			add_settings_error(
				MMSM_SETTINGS_OPTION,
				'mmsm_countdown_unit_required',
				esc_html__( 'At least one time unit must remain visible. All four units were restored.', 'maneuvrez-maintenance-studio' ),
				'error'
			);
			$submitted['show_days']    = 1;
			$submitted['show_hours']   = 1;
			$submitted['show_minutes'] = 1;
			$submitted['show_seconds'] = 1;
		}

		$submitted['target_timestamp'] = $target_timestamp;
		unset( $submitted['target_local'] );

		return array(
			CountdownService::INSTANCE_MAINTENANCE => $submitted,
		);
	}

	/**
	 * Prevent a stale auto-disable countdown from undoing a fresh activation.
	 *
	 * An expired countdown can remain enabled after maintenance mode has already
	 * been turned off. If an administrator later enables maintenance again from
	 * Access & Visibility, request reconciliation would otherwise turn it straight
	 * back off. Disable only that expired countdown instance and preserve its
	 * configuration so it can be given a new target from the Countdown section.
	 *
	 * @param array<string,mixed> $input Submitted settings payload.
	 * @param array<string,mixed> $existing Existing normalized settings.
	 * @return array<string,mixed>
	 */
	private function prepare_maintenance_activation( array $input, array $existing ) {
		if ( ! empty( $existing['enabled'] ) || empty( $input['enabled'] ) ) {
			return $existing;
		}

		$countdown = $this->countdown_service->get_instance( $existing, CountdownService::INSTANCE_MAINTENANCE );

		if (
			'disable_mode' !== (string) $countdown['expiry_action'] ||
			! $this->countdown_service->is_expired( $countdown )
		) {
			return $existing;
		}

		$existing['countdowns'][ CountdownService::INSTANCE_MAINTENANCE ]['enabled'] = 0;

		add_settings_error(
			MMSM_SETTINGS_OPTION,
			'mmsm_expired_countdown_disabled',
			esc_html__( 'Maintenance mode was enabled. Its expired auto-disable countdown was turned off; set a new future target in the Countdown section before enabling it again.', 'maneuvrez-maintenance-studio' ),
			'warning'
		);

		return $existing;
	}

	/**
	 * Reject incomplete action pairs without discarding their saved values.
	 *
	 * @param array<string,mixed> $input Submitted settings.
	 * @param array<string,mixed> $existing Existing normalized settings.
	 * @return array<string,mixed>
	 */
	private function prepare_action_pairs_for_save( array $input, array $existing ) {
		foreach ( array( 'primary', 'secondary' ) as $action ) {
			$label_key  = $action . '_action_label';
			$url_key    = $action . '_action_url';
			$label      = isset( $input[ $label_key ] ) ? trim( (string) $input[ $label_key ] ) : '';
			$url        = isset( $input[ $url_key ] ) ? trim( (string) $input[ $url_key ] ) : '';
			$url_scheme = wp_parse_url( $url, PHP_URL_SCHEME );
			$url_host   = wp_parse_url( $url, PHP_URL_HOST );
			$valid_url  = '' === $url || ( in_array( $url_scheme, array( 'http', 'https' ), true ) && ! empty( $url_host ) && '' !== esc_url_raw( $url, array( 'http', 'https' ) ) );

			if ( ( '' === $label ) === ( '' === $url ) && $valid_url ) {
				continue;
			}

			$input[ $label_key ] = isset( $existing[ $label_key ] ) ? $existing[ $label_key ] : '';
			$input[ $url_key ]   = isset( $existing[ $url_key ] ) ? $existing[ $url_key ] : '';

			add_settings_error(
				MMSM_SETTINGS_OPTION,
				'mmsm_' . $action . '_action_pair',
				'primary' === $action
					? esc_html__( 'The primary action needs both a label and a valid full URL. Its previously saved values were kept.', 'maneuvrez-maintenance-studio' )
					: esc_html__( 'The secondary action needs both a label and a valid full URL. Its previously saved values were kept.', 'maneuvrez-maintenance-studio' ),
				'error'
			);
		}

		return $input;
	}

	/**
	 * Return available settings tabs.
	 *
	 * @return array<string,array<string,string>>
	 */
	private function get_tabs() {
		return array(
			'maintenance_page' => array(
				'label' => __( 'Maintenance Page', 'maneuvrez-maintenance-studio' ),
				'icon'  => 'dashicons-welcome-write-blog',
			),
			'design'           => array(
				'label' => __( 'Design', 'maneuvrez-maintenance-studio' ),
				'icon'  => 'dashicons-admin-appearance',
			),
			'access_visibility' => array(
				'label' => __( 'Access & Visibility', 'maneuvrez-maintenance-studio' ),
				'icon'  => 'dashicons-visibility',
			),
			'advanced'          => array(
				'label' => __( 'Advanced', 'maneuvrez-maintenance-studio' ),
				'icon'  => 'dashicons-admin-tools',
			),
		);
	}

	/**
	 * Return the currently requested tab, falling back safely.
	 *
	 * @return string
	 */
	private function get_active_tab() {
		$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'maintenance_page'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only tab selection for admin UI state, sanitized and not persisted.

		return $this->normalize_area_key( $tab );
	}

	/**
	 * Map current and legacy query values to one of the four task areas.
	 *
	 * @param string $tab Requested tab key.
	 * @return string
	 */
	private function normalize_area_key( $tab ) {
		$legacy_destinations = array(
			'general'          => 'maintenance_page',
			'template'         => 'maintenance_page',
			'components'       => 'maintenance_page',
			'countdown'        => 'maintenance_page',
			'contact_channels' => 'maintenance_page',
			'social_links'     => 'maintenance_page',
		);

		if ( isset( $legacy_destinations[ $tab ] ) ) {
			return $legacy_destinations[ $tab ];
		}

		return isset( $this->get_tabs()[ $tab ] ) ? $tab : 'maintenance_page';
	}

	/**
	 * Render the currently active tab section.
	 *
	 * @return void
	 */
	private function render_active_tab() {
		$active_tab = $this->get_active_tab();

		if ( 'maintenance_page' === $active_tab ) {
			$this->render_maintenance_page_editor();
			return;
		}

		foreach ( $this->get_area_sections( $active_tab ) as $area_section ) {
			$this->render_section_fields( $area_section, $active_tab );
		}
	}

	/**
	 * Render the essential page fields followed by compact optional cards.
	 *
	 * Native details elements keep every field submittable when collapsed and
	 * remain usable when JavaScript is unavailable.
	 *
	 * @return void
	 */
	private function render_maintenance_page_editor() {
		$settings               = $this->get_settings();
		$countdown              = $this->get_maintenance_countdown();
		$primary_action_error   = $this->get_settings_error_message( 'mmsm_primary_action_pair' );
		$secondary_action_error = $this->get_settings_error_message( 'mmsm_secondary_action_pair' );
		?>
		<div class="mmsm-settings-panel mmsm-settings-panel-maintenance_page mmsm-editor-essential" id="page-essentials">
			<span class="mmsm-settings-kicker"><?php echo esc_html__( 'Start here', 'maneuvrez-maintenance-studio' ); ?></span>
			<h2 class="title"><?php echo esc_html__( 'Page essentials', 'maneuvrez-maintenance-studio' ); ?></h2>
			<p class="description"><?php echo esc_html__( 'Choose the page type and write the main message visitors will see.', 'maneuvrez-maintenance-studio' ); ?></p>
			<?php $this->render_editor_fields( 'mmsm_template_section', array( 'mmsm_mode_type' ) ); ?>
			<?php $this->render_editor_fields( 'mmsm_general_section', array( 'mmsm_page_title', 'mmsm_message' ) ); ?>
		</div>

		<div class="mmsm-settings-panel mmsm-settings-panel-maintenance_page mmsm-editor-essential" id="primary-action" data-action-group="primary">
			<h2 class="title"><?php echo esc_html__( 'Primary action', 'maneuvrez-maintenance-studio' ); ?></h2>
			<p class="description"><?php echo esc_html__( 'Add both a button label and destination, or leave both blank to hide the button.', 'maneuvrez-maintenance-studio' ); ?></p>
			<?php $this->render_editor_fields( 'mmsm_components_section', array( 'mmsm_primary_action_label', 'mmsm_primary_action_url' ) ); ?>
			<p class="<?php echo esc_attr( 'mmsm-inline-validation' . ( '' !== $primary_action_error ? ' is-error' : '' ) ); ?>" data-action-validation data-server-error="<?php echo esc_attr( $primary_action_error ); ?>" role="status" aria-live="polite"><?php echo esc_html( $primary_action_error ); ?></p>
			<details class="mmsm-local-disclosure">
				<summary aria-expanded="false" aria-controls="mmsm-heading-detail-fields"><?php echo esc_html__( 'Additional heading text', 'maneuvrez-maintenance-studio' ); ?></summary>
				<div id="mmsm-heading-detail-fields" class="mmsm-local-disclosure-body">
					<?php $this->render_editor_fields( 'mmsm_components_section', array( 'mmsm_hero_eyebrow' ) ); ?>
				</div>
			</details>
		</div>

		<div class="mmsm-optional-sections" aria-labelledby="mmsm-optional-sections-title">
			<div class="mmsm-optional-sections-heading">
				<h2 id="mmsm-optional-sections-title"><?php echo esc_html__( 'Optional page sections', 'maneuvrez-maintenance-studio' ); ?></h2>
				<p><?php echo esc_html__( 'Review the summary, then open only the sections you want to change.', 'maneuvrez-maintenance-studio' ); ?></p>
			</div>

			<?php
			$this->render_optional_card_start(
				'secondary-action',
				__( 'Secondary action', 'maneuvrez-maintenance-studio' ),
				$this->get_action_summary( (string) $settings['secondary_action_label'], (string) $settings['secondary_action_url'] ),
				__( 'Edit action', 'maneuvrez-maintenance-studio' ),
				'secondary-action'
			);
			?>
			<div data-action-group="secondary">
				<?php $this->render_editor_fields( 'mmsm_components_section', array( 'mmsm_secondary_action_label', 'mmsm_secondary_action_url' ) ); ?>
				<p class="<?php echo esc_attr( 'mmsm-inline-validation' . ( '' !== $secondary_action_error ? ' is-error' : '' ) ); ?>" data-action-validation data-server-error="<?php echo esc_attr( $secondary_action_error ); ?>" role="status" aria-live="polite"><?php echo esc_html( $secondary_action_error ); ?></p>
			</div>
			<?php $this->render_optional_card_end(); ?>

			<?php
			$progress_summary = ! empty( $settings['show_progress'] )
				? sprintf( /* translators: %d: progress percentage. */ __( 'Progress is on at %d%%.', 'maneuvrez-maintenance-studio' ), (int) $settings['progress_value'] )
				: __( 'Progress is off; status text remains available.', 'maneuvrez-maintenance-studio' );
			$this->render_optional_card_start( 'status-progress', __( 'Status and progress', 'maneuvrez-maintenance-studio' ), $progress_summary, __( 'Customize', 'maneuvrez-maintenance-studio' ), 'status-progress', 'mmsm_components_section', array( 'mmsm_show_progress' ) );
			$this->render_editor_fields( 'mmsm_components_section', array( 'mmsm_status_label', 'mmsm_progress_value' ) );
			?>
			<details class="mmsm-local-disclosure">
				<summary aria-expanded="false" aria-controls="mmsm-simple-email-fields"><?php echo esc_html__( 'Simple email contact', 'maneuvrez-maintenance-studio' ); ?></summary>
				<div id="mmsm-simple-email-fields" class="mmsm-local-disclosure-body">
					<p class="description"><?php echo esc_html__( 'This is the existing email contact block. It is separate from Contact Channels.', 'maneuvrez-maintenance-studio' ); ?></p>
					<?php $this->render_editor_fields( 'mmsm_components_section', array( 'mmsm_contact_label', 'mmsm_contact_message', 'mmsm_contact_email' ) ); ?>
				</div>
			</details>
			<?php $this->render_optional_card_end(); ?>

			<?php
			$countdown_summary = ! empty( $countdown['enabled'] ) ? __( 'On', 'maneuvrez-maintenance-studio' ) : __( 'Off', 'maneuvrez-maintenance-studio' );
			if ( ! empty( $countdown['target_timestamp'] ) ) {
				$countdown_summary .= ' · ' . wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), (int) $countdown['target_timestamp'], wp_timezone() );
			}
			$countdown_actions = array(
				'hold_zero'    => __( 'Keep showing zero', 'maneuvrez-maintenance-studio' ),
				'hide'         => __( 'Hide the countdown', 'maneuvrez-maintenance-studio' ),
				'show_message' => __( 'Show a message', 'maneuvrez-maintenance-studio' ),
				'disable_mode' => __( 'Turn off maintenance mode', 'maneuvrez-maintenance-studio' ),
			);
			if ( isset( $countdown_actions[ $countdown['expiry_action'] ] ) ) {
				$countdown_summary .= ' · ' . $countdown_actions[ $countdown['expiry_action'] ];
			}
			$this->render_optional_card_start( 'countdown', __( 'Countdown', 'maneuvrez-maintenance-studio' ), $countdown_summary, __( 'Edit countdown', 'maneuvrez-maintenance-studio' ), 'countdown', 'mmsm_countdown_section', array( 'mmsm_countdown_enabled' ) );
			$this->render_editor_fields( 'mmsm_countdown_section', array( 'mmsm_countdown_target', 'mmsm_countdown_expiry', 'mmsm_countdown_finished_message' ) );
			?>
			<details class="mmsm-local-disclosure">
				<summary aria-expanded="false" aria-controls="mmsm-countdown-customization"><?php echo esc_html__( 'Customize countdown', 'maneuvrez-maintenance-studio' ); ?></summary>
				<div id="mmsm-countdown-customization" class="mmsm-local-disclosure-body">
					<?php $this->render_editor_fields( 'mmsm_countdown_section', array( 'mmsm_countdown_copy', 'mmsm_countdown_units', 'mmsm_countdown_appearance' ) ); ?>
					<?php $this->render_countdown_section(); ?>
				</div>
			</details>
			<?php $this->render_optional_card_end(); ?>

			<?php
			$contact_count   = count( array_filter( (array) $settings['contact_channels_items'], static fn( $item ) => is_array( $item ) && ! empty( $item['value'] ) ) );
			$contact_summary = sprintf(
				/* translators: 1: enabled state, 2: number of configured contact channels. */
				_n( '%1$s · %2$d configured channel', '%1$s · %2$d configured channels', $contact_count, 'maneuvrez-maintenance-studio' ),
				! empty( $settings['contact_channels_enabled'] ) ? __( 'On', 'maneuvrez-maintenance-studio' ) : __( 'Off', 'maneuvrez-maintenance-studio' ),
				$contact_count
			);
			$this->render_optional_card_start( 'contact-channels', __( 'Contact Channels', 'maneuvrez-maintenance-studio' ), $contact_summary, __( 'Edit channels', 'maneuvrez-maintenance-studio' ), 'contact-channels', 'mmsm_contact_channels_section', array( 'mmsm_contact_channels_enabled' ) );
			$this->render_contact_channels_section();
			$this->render_editor_fields( 'mmsm_contact_channels_section', array( 'mmsm_contact_channels' ) );
			$this->render_optional_card_end();

			$social_count   = count( array_filter( (array) $settings['social_links'], static fn( $item ) => is_array( $item ) && ! empty( $item['url'] ) ) );
			$social_summary = sprintf(
				/* translators: %d: number of configured social links. */
				_n( '%d configured link', '%d configured links', $social_count, 'maneuvrez-maintenance-studio' ),
				$social_count
			);
			if ( empty( $settings['show_footer_section'] ) ) {
				$social_summary .= ' · ' . __( 'Hidden while the footer is off', 'maneuvrez-maintenance-studio' );
			}
			$this->render_optional_card_start( 'social-links', __( 'Social Links', 'maneuvrez-maintenance-studio' ), $social_summary, __( 'Edit links', 'maneuvrez-maintenance-studio' ), 'social-links' );
			$this->render_social_links_section();
			$this->render_editor_fields( 'mmsm_social_links_section', array( 'mmsm_social_links' ) );
			$this->render_optional_card_end();

			$footer_summary = ! empty( $settings['show_footer_section'] )
				? ( ! empty( $settings['show_login_button'] ) ? __( 'Footer and login link are shown.', 'maneuvrez-maintenance-studio' ) : __( 'Footer is shown without a login link.', 'maneuvrez-maintenance-studio' ) )
				: __( 'Footer is hidden; saved footer settings are retained.', 'maneuvrez-maintenance-studio' );
			$this->render_optional_card_start( 'footer-login', __( 'Footer and login link', 'maneuvrez-maintenance-studio' ), $footer_summary, __( 'Customize', 'maneuvrez-maintenance-studio' ), 'footer-login', 'mmsm_advanced_section', array( 'mmsm_show_footer_section' ) );
			$this->render_editor_fields( 'mmsm_advanced_section', array( 'mmsm_show_login_button', 'mmsm_login_label' ) );
			$this->render_optional_card_end();
			?>
		</div>
		<?php
	}

	/**
	 * Render the start of an optional editor card and its disclosure body.
	 *
	 * @param string            $id Card identifier.
	 * @param string            $title Card title.
	 * @param string            $summary Saved configuration summary.
	 * @param string            $action Disclosure action label.
	 * @param string            $summary_type JavaScript summary type.
	 * @param string            $toggle_section Registered section for header toggles.
	 * @param array<int,string> $toggle_fields Registered toggle field IDs.
	 * @return void
	 */
	private function render_optional_card_start( $id, $title, $summary, $action, $summary_type, $toggle_section = '', array $toggle_fields = array() ) {
		$body_id = 'mmsm-' . sanitize_html_class( $id ) . '-editor';
		$is_open = $this->has_card_settings_error( $summary_type );
		?>
		<section class="mmsm-settings-panel mmsm-settings-panel-maintenance_page mmsm-optional-card" id="<?php echo esc_attr( $id ); ?>" data-optional-card="<?php echo esc_attr( $summary_type ); ?>">
			<div class="mmsm-optional-card-header">
				<div>
					<h2 class="title"><?php echo esc_html( $title ); ?></h2>
					<p class="mmsm-optional-card-summary" data-card-summary><?php echo esc_html( $summary ); ?></p>
				</div>
				<?php if ( '' !== $toggle_section && ! empty( $toggle_fields ) ) : ?>
					<div class="mmsm-optional-card-toggle"><?php $this->render_editor_fields( $toggle_section, $toggle_fields, false ); ?></div>
				<?php endif; ?>
			</div>
			<details class="mmsm-card-disclosure"<?php echo $is_open ? ' open' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static boolean attribute. ?>>
				<summary aria-expanded="<?php echo esc_attr( $is_open ? 'true' : 'false' ); ?>" aria-controls="<?php echo esc_attr( $body_id ); ?>"><?php echo esc_html( $action ); ?></summary>
				<div class="mmsm-card-disclosure-body" id="<?php echo esc_attr( $body_id ); ?>">
		<?php
	}

	/**
	 * Close an optional editor card.
	 *
	 * @return void
	 */
	private function render_optional_card_end() {
		?>
				</div>
			</details>
		</section>
		<?php
	}

	/**
	 * Render selected registered fields in an editor table.
	 *
	 * @param string            $section_id Registered settings section.
	 * @param array<int,string> $field_ids Registered field IDs.
	 * @param bool              $table Whether to include a form table wrapper.
	 * @return void
	 */
	private function render_editor_fields( $section_id, array $field_ids, $table = true ) {
		global $wp_settings_fields;

		$registered_fields = ! empty( $wp_settings_fields[ $this->page_slug ][ $section_id ] ) ? $wp_settings_fields[ $this->page_slug ][ $section_id ] : array();

		if ( $table ) {
			echo '<table class="form-table" role="presentation">';
		}

		$this->render_registered_fields( $registered_fields, $field_ids, $table );

		if ( $table ) {
			echo '</table>';
		}
	}

	/**
	 * Return a truthful action-pair summary.
	 *
	 * @param string $label Action label.
	 * @param string $url Action URL.
	 * @return string
	 */
	private function get_action_summary( $label, $url ) {
		if ( '' === $label && '' === $url ) {
			return __( 'Not configured.', 'maneuvrez-maintenance-studio' );
		}

		if ( '' === $label ) {
			return __( 'Needs a label before it can be shown.', 'maneuvrez-maintenance-studio' );
		}

		if ( '' === $url ) {
			return __( 'Needs a destination before it can be shown.', 'maneuvrez-maintenance-studio' );
		}

		return sprintf( /* translators: %s: configured action label. */ __( 'Configured as “%s”.', 'maneuvrez-maintenance-studio' ), $label );
	}

	/**
	 * Return one settings error message by code.
	 *
	 * @param string $code Settings error code.
	 * @return string
	 */
	private function get_settings_error_message( $code ) {
		foreach ( get_settings_errors( MMSM_SETTINGS_OPTION ) as $error ) {
			if ( isset( $error['code'], $error['message'] ) && $code === $error['code'] ) {
				return (string) $error['message'];
			}
		}

		return '';
	}

	/**
	 * Determine whether a card contains a field with a server-side error.
	 *
	 * @param string $summary_type Card summary type.
	 * @return bool
	 */
	private function has_card_settings_error( $summary_type ) {
		$error_prefix = 'countdown' === $summary_type ? 'mmsm_countdown_' : ( 'secondary-action' === $summary_type ? 'mmsm_secondary_action_' : '' );

		if ( '' === $error_prefix ) {
			return false;
		}

		foreach ( get_settings_errors( MMSM_SETTINGS_OPTION ) as $error ) {
			if ( isset( $error['code'] ) && str_starts_with( (string) $error['code'], $error_prefix ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Return the semantic cards rendered within a task area.
	 *
	 * @param string $area_key Area key.
	 * @return array<int,array<string,mixed>>
	 */
	private function get_area_sections( $area_key ) {
		$areas = array(
			'maintenance_page' => array(
				array(
					'id'      => 'page-type',
					'section' => 'mmsm_template_section',
					'title'   => __( 'Page type', 'maneuvrez-maintenance-studio' ),
					'fields'  => array( 'mmsm_mode_type' ),
				),
				array(
					'id'      => 'main-copy',
					'section' => 'mmsm_general_section',
					'title'   => __( 'Main copy', 'maneuvrez-maintenance-studio' ),
					'fields'  => array( 'mmsm_page_title', 'mmsm_message' ),
				),
				array(
					'id'      => 'actions',
					'section' => 'mmsm_components_section',
					'title'   => __( 'Page content and actions', 'maneuvrez-maintenance-studio' ),
					'fields'  => array( 'mmsm_hero_eyebrow', 'mmsm_primary_action_label', 'mmsm_primary_action_url', 'mmsm_secondary_action_label', 'mmsm_secondary_action_url' ),
				),
				array(
					'id'      => 'status-progress',
					'section' => 'mmsm_components_section',
					'title'   => __( 'Status, progress, and email contact', 'maneuvrez-maintenance-studio' ),
					'fields'  => array( 'mmsm_status_label', 'mmsm_show_progress', 'mmsm_progress_value', 'mmsm_contact_label', 'mmsm_contact_message', 'mmsm_contact_email' ),
				),
				array(
					'section'  => 'mmsm_countdown_section',
					'title'    => __( 'Countdown', 'maneuvrez-maintenance-studio' ),
					'callback' => true,
				),
				array(
					'section'  => 'mmsm_contact_channels_section',
					'title'    => __( 'Contact channels', 'maneuvrez-maintenance-studio' ),
					'callback' => true,
				),
				array(
					'section'  => 'mmsm_social_links_section',
					'title'    => __( 'Social links', 'maneuvrez-maintenance-studio' ),
					'callback' => true,
				),
				array(
					'id'      => 'footer-login',
					'section' => 'mmsm_advanced_section',
					'title'   => __( 'Footer and login presentation', 'maneuvrez-maintenance-studio' ),
					'fields'  => array( 'mmsm_show_footer_section', 'mmsm_show_login_button', 'mmsm_login_label' ),
				),
			),
			'design' => array(
				array(
					'section'  => 'mmsm_design_section',
					'title'    => __( 'Design', 'maneuvrez-maintenance-studio' ),
					'callback' => true,
				),
			),
			'access_visibility' => array(
				array(
					'id'      => 'maintenance-status',
					'section' => 'mmsm_general_section',
					'title'   => __( 'Maintenance status', 'maneuvrez-maintenance-studio' ),
					'fields'  => array( 'mmsm_enabled' ),
				),
				array(
					'id'      => 'login-access',
					'section' => 'mmsm_advanced_section',
					'title'   => __( 'Login access', 'maneuvrez-maintenance-studio' ),
					'fields'  => array( 'mmsm_custom_login_enabled', 'mmsm_custom_login_slug', 'mmsm_custom_login_block_mode' ),
				),
				array(
					'id'      => 'testing-bypass',
					'section' => 'mmsm_advanced_section',
					'title'   => __( 'Temporary testing bypass', 'maneuvrez-maintenance-studio' ),
					'fields'  => array( 'mmsm_bypass_query_enabled', 'mmsm_bypass_query_settings' ),
				),
				array(
					'id'      => 'public-urls',
					'section' => 'mmsm_advanced_section',
					'title'   => __( 'Always-public pages', 'maneuvrez-maintenance-studio' ),
					'fields'  => array( 'mmsm_bypass_urls_enabled', 'mmsm_bypass_urls' ),
				),
			),
			'advanced' => array(),
		);

		if ( count( ( new TemplateRegistry() )->all() ) > 1 ) {
			$areas['advanced'][] = array(
				'section' => 'mmsm_template_section',
				'title'   => __( 'Template', 'maneuvrez-maintenance-studio' ),
				'fields'  => array( 'mmsm_template_key' ),
			);
		}

		$areas['advanced'][] = array(
			'section' => 'mmsm_advanced_section',
			'title'   => __( 'Data removal', 'maneuvrez-maintenance-studio' ),
			'fields'  => array( 'mmsm_delete_data_on_uninstall' ),
		);

		return isset( $areas[ $area_key ] ) ? $areas[ $area_key ] : $areas['maintenance_page'];
	}

	/**
	 * Render a registered section title, description, and fields.
	 *
	 * @param array<string,mixed> $area_section Area section definition.
	 * @param string $active_tab Active tab key.
	 * @return void
	 */
	private function render_section_fields( array $area_section, $active_tab ) {
		global $wp_settings_sections, $wp_settings_fields;
		$section_id = $area_section['section'];

		if ( ! isset( $wp_settings_sections[ $this->page_slug ][ $section_id ] ) ) {
			return;
		}

		$section = $wp_settings_sections[ $this->page_slug ][ $section_id ];
		$registered_fields = ! empty( $wp_settings_fields[ $this->page_slug ][ $section_id ] ) ? $wp_settings_fields[ $this->page_slug ][ $section_id ] : array();
		$field_ids         = isset( $area_section['fields'] ) ? $area_section['fields'] : array_keys( $registered_fields );
		$has_fields        = ! empty( $field_ids );
		$section_slug      = isset( $area_section['id'] ) ? sanitize_html_class( $area_section['id'] ) : str_replace( array( 'mmsm_', '_section' ), '', $section_id );
		?>
		<div id="<?php echo esc_attr( $section_slug ); ?>" class="<?php echo esc_attr( 'mmsm-settings-panel mmsm-settings-panel-' . $active_tab . ' mmsm-settings-panel-' . $section_slug ); ?>">
			<?php if ( ! empty( $area_section['title'] ) ) : ?>
				<h2 class="title"><?php echo esc_html( $area_section['title'] ); ?></h2>
			<?php endif; ?>
			<?php
			if ( ! empty( $area_section['callback'] ) && ! empty( $section['callback'] ) ) {
				call_user_func( $section['callback'], $section );
			}
			?>
			<?php if ( $has_fields ) : ?>
				<table class="form-table" role="presentation">
					<?php $this->render_registered_fields( $registered_fields, $field_ids ); ?>
				</table>
			<?php else : ?>
				<p class="description"><?php echo esc_html__( 'No extra settings are available in this tab yet.', 'maneuvrez-maintenance-studio' ); ?></p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render a selected subset of registered WordPress settings fields.
	 *
	 * @param array<string,array<string,mixed>> $registered_fields Registered fields keyed by id.
	 * @param array<int,string>                 $field_ids Field ids to render.
	 * @param bool                              $table_rows Whether to render table-row markup.
	 * @return void
	 */
	private function render_registered_fields( array $registered_fields, array $field_ids, $table_rows = true ) {
		foreach ( $field_ids as $field_id ) {
			if ( ! isset( $registered_fields[ $field_id ] ) ) {
				continue;
			}

			$field = $registered_fields[ $field_id ];

			if ( ! $table_rows ) {
				call_user_func( $field['callback'], $field['args'] );
				continue;
			}

			$class = ! empty( $field['args']['class'] ) ? ' class="' . esc_attr( $field['args']['class'] ) . '"' : '';
			?>
			<tr<?php echo $class; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Constructed from an escaped class attribute above. ?>>
				<th scope="row"><?php echo esc_html( $field['title'] ); ?></th>
				<td><?php call_user_func( $field['callback'], $field['args'] ); ?></td>
			</tr>
			<?php
		}
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
			'no_longer_needed'      => __( 'I no longer need the plugin', 'maneuvrez-maintenance-studio' ),
			'did_not_work'          => __( 'The plugin did not work as expected', 'maneuvrez-maintenance-studio' ),
			'caused_issue'          => __( 'The plugin caused an issue on my site', 'maneuvrez-maintenance-studio' ),
			'missing_features'      => __( 'The plugin is missing features I need', 'maneuvrez-maintenance-studio' ),
			'too_difficult'         => __( 'The plugin is too difficult to use', 'maneuvrez-maintenance-studio' ),
			'found_alternative'     => __( 'I found a better alternative', 'maneuvrez-maintenance-studio' ),
			'troubleshooting'       => __( 'I am troubleshooting temporarily', 'maneuvrez-maintenance-studio' ),
			'other'                 => __( 'Other', 'maneuvrez-maintenance-studio' ),
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
			'maintenance_page' => array(
				'mode_type',
				'page_title',
				'message',
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
				'countdowns',
				'contact_channels_enabled',
				'contact_channels_maintenance_display',
				'contact_channels_live_display',
				'contact_channels_logged_in_visibility',
				'contact_channels_display_style',
				'contact_channels_heading',
				'contact_channels_description',
				'contact_channels_primary_label',
				'contact_channels_position',
				'contact_channels_button_shape',
				'contact_channels_button_display',
				'contact_channels_color_mode',
				'contact_channels_background_color',
				'contact_channels_text_color',
				'contact_channels_icon_color',
				'contact_channels_hover_background_color',
				'contact_channels_hover_text_color',
				'contact_channels_items',
				'social_links_display',
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
				'show_footer_section',
				'show_login_button',
				'login_label',
			),
			'design' => array(
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
			'access_visibility' => array(
				'enabled',
				'custom_login_enabled',
				'custom_login_slug',
				'custom_login_block_mode',
				'bypass_query_enabled',
				'bypass_query_key',
				'bypass_query_value',
				'bypass_urls_enabled',
				'bypass_urls',
			),
			'advanced' => array(
				'template_key',
				'delete_data_on_uninstall',
			),
		);

		return isset( $map[ $tab_key ] ) ? $map[ $tab_key ] : array();
	}

	/**
	 * Return ownership used by an already-open legacy eight-tab form.
	 *
	 * @param string $tab_key Legacy tab key.
	 * @return array<int,string>
	 */
	private function get_legacy_tab_field_keys( $tab_key ) {
		$legacy_map = array(
			'general' => array( 'enabled', 'page_title', 'message' ),
			'template' => array( 'mode_type', 'template_key' ),
			'design' => $this->get_tab_field_keys( 'design' ),
			'components' => array( 'hero_eyebrow', 'primary_action_label', 'primary_action_url', 'secondary_action_label', 'secondary_action_url', 'status_label', 'show_progress', 'progress_value', 'contact_label', 'contact_message', 'contact_email' ),
			'countdown' => array( 'countdowns' ),
			'contact_channels' => array( 'contact_channels_enabled', 'contact_channels_maintenance_display', 'contact_channels_live_display', 'contact_channels_logged_in_visibility', 'contact_channels_display_style', 'contact_channels_heading', 'contact_channels_description', 'contact_channels_primary_label', 'contact_channels_position', 'contact_channels_button_shape', 'contact_channels_button_display', 'contact_channels_color_mode', 'contact_channels_background_color', 'contact_channels_text_color', 'contact_channels_icon_color', 'contact_channels_hover_background_color', 'contact_channels_hover_text_color', 'contact_channels_items' ),
			'social_links' => array( 'social_links_display', 'social_links', 'social_x_url', 'social_instagram_url', 'social_facebook_url', 'social_linkedin_url', 'social_item_1_platform', 'social_item_1_label', 'social_item_1_url', 'social_item_1_new_tab', 'social_item_2_platform', 'social_item_2_label', 'social_item_2_url', 'social_item_2_new_tab', 'social_item_3_platform', 'social_item_3_label', 'social_item_3_url', 'social_item_3_new_tab', 'social_item_4_platform', 'social_item_4_label', 'social_item_4_url', 'social_item_4_new_tab' ),
			'advanced' => array( 'show_login_button', 'show_footer_section', 'custom_login_enabled', 'custom_login_slug', 'custom_login_block_mode', 'bypass_query_enabled', 'bypass_query_key', 'bypass_query_value', 'bypass_urls_enabled', 'bypass_urls', 'delete_data_on_uninstall', 'login_label' ),
		);

		return isset( $legacy_map[ $tab_key ] ) ? $legacy_map[ $tab_key ] : array();
	}
}

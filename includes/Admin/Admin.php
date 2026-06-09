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
	}

	/**
	 * Add the settings page under Settings.
	 *
	 * @return void
	 */
	public function add_settings_page() {
		$this->page_hook = add_options_page(
			__( 'Maintenance Mode Studio', MMSM_TEXT_DOMAIN ),
			__( 'Maintenance Mode Studio', MMSM_TEXT_DOMAIN ),
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
			__( 'General Settings', MMSM_TEXT_DOMAIN ),
			array( $this, 'render_general_section' ),
			$this->page_slug
		);

		add_settings_field(
			'mmsm_enabled',
			__( 'Enable maintenance mode', MMSM_TEXT_DOMAIN ),
			array( $this, 'render_enabled_field' ),
			$this->page_slug,
			'mmsm_general_section'
		);

		add_settings_field(
			'mmsm_mode_type',
			__( 'Mode Type', MMSM_TEXT_DOMAIN ),
			array( $this, 'render_mode_type_field' ),
			$this->page_slug,
			'mmsm_general_section'
		);

		add_settings_field(
			'mmsm_page_title',
			__( 'Page Title', MMSM_TEXT_DOMAIN ),
			array( $this, 'render_page_title_field' ),
			$this->page_slug,
			'mmsm_general_section'
		);

		add_settings_field(
			'mmsm_message',
			__( 'Message', MMSM_TEXT_DOMAIN ),
			array( $this, 'render_message_field' ),
			$this->page_slug,
			'mmsm_general_section'
		);

		add_settings_field(
			'mmsm_show_login_button',
			__( 'Show Login Button', MMSM_TEXT_DOMAIN ),
			array( $this, 'render_show_login_button_field' ),
			$this->page_slug,
			'mmsm_general_section'
		);

		add_settings_section(
			'mmsm_appearance_section',
			__( 'Appearance', MMSM_TEXT_DOMAIN ),
			array( $this, 'render_appearance_section' ),
			$this->page_slug
		);

		add_settings_field(
			'mmsm_theme_mode',
			__( 'Theme Mode', MMSM_TEXT_DOMAIN ),
			array( $this, 'render_theme_mode_field' ),
			$this->page_slug,
			'mmsm_appearance_section'
		);

		add_settings_field(
			'mmsm_template_key',
			__( 'Template', MMSM_TEXT_DOMAIN ),
			array( $this, 'render_template_key_field' ),
			$this->page_slug,
			'mmsm_appearance_section'
		);

		add_settings_field(
			'mmsm_primary_color',
			__( 'Primary Color', MMSM_TEXT_DOMAIN ),
			array( $this, 'render_primary_color_field' ),
			$this->page_slug,
			'mmsm_appearance_section'
		);

		add_settings_field(
			'mmsm_background_color',
			__( 'Background Color', MMSM_TEXT_DOMAIN ),
			array( $this, 'render_background_color_field' ),
			$this->page_slug,
			'mmsm_appearance_section'
		);

		add_settings_field(
			'mmsm_surface_color',
			__( 'Surface Color', MMSM_TEXT_DOMAIN ),
			array( $this, 'render_surface_color_field' ),
			$this->page_slug,
			'mmsm_appearance_section'
		);

		add_settings_field(
			'mmsm_heading_text_color',
			__( 'Heading Text Color', MMSM_TEXT_DOMAIN ),
			array( $this, 'render_heading_text_color_field' ),
			$this->page_slug,
			'mmsm_appearance_section'
		);

		add_settings_field(
			'mmsm_body_text_color',
			__( 'Body Text Color', MMSM_TEXT_DOMAIN ),
			array( $this, 'render_body_text_color_field' ),
			$this->page_slug,
			'mmsm_appearance_section'
		);

		add_settings_field(
			'mmsm_muted_text_color',
			__( 'Muted Text Color', MMSM_TEXT_DOMAIN ),
			array( $this, 'render_muted_text_color_field' ),
			$this->page_slug,
			'mmsm_appearance_section'
		);

		add_settings_field(
			'mmsm_link_text_color',
			__( 'Link Text Color', MMSM_TEXT_DOMAIN ),
			array( $this, 'render_link_text_color_field' ),
			$this->page_slug,
			'mmsm_appearance_section'
		);

		add_settings_field(
			'mmsm_button_text_color',
			__( 'Button Text Color', MMSM_TEXT_DOMAIN ),
			array( $this, 'render_button_text_color_field' ),
			$this->page_slug,
			'mmsm_appearance_section'
		);

		add_settings_field(
			'mmsm_border_color',
			__( 'Border Color', MMSM_TEXT_DOMAIN ),
			array( $this, 'render_border_color_field' ),
			$this->page_slug,
			'mmsm_appearance_section'
		);

		add_settings_section(
			'mmsm_components_section',
			__( 'Components', MMSM_TEXT_DOMAIN ),
			array( $this, 'render_components_section' ),
			$this->page_slug
		);

		add_settings_field(
			'mmsm_hero_eyebrow',
			__( 'Hero Eyebrow', MMSM_TEXT_DOMAIN ),
			array( $this, 'render_hero_eyebrow_field' ),
			$this->page_slug,
			'mmsm_components_section'
		);

		add_settings_field(
			'mmsm_primary_action_label',
			__( 'Primary Action Label', MMSM_TEXT_DOMAIN ),
			array( $this, 'render_primary_action_label_field' ),
			$this->page_slug,
			'mmsm_components_section'
		);

		add_settings_field(
			'mmsm_primary_action_url',
			__( 'Primary Action URL', MMSM_TEXT_DOMAIN ),
			array( $this, 'render_primary_action_url_field' ),
			$this->page_slug,
			'mmsm_components_section'
		);

		add_settings_field(
			'mmsm_secondary_action_label',
			__( 'Secondary Action Label', MMSM_TEXT_DOMAIN ),
			array( $this, 'render_secondary_action_label_field' ),
			$this->page_slug,
			'mmsm_components_section'
		);

		add_settings_field(
			'mmsm_secondary_action_url',
			__( 'Secondary Action URL', MMSM_TEXT_DOMAIN ),
			array( $this, 'render_secondary_action_url_field' ),
			$this->page_slug,
			'mmsm_components_section'
		);

		add_settings_field(
			'mmsm_status_label',
			__( 'Status Label', MMSM_TEXT_DOMAIN ),
			array( $this, 'render_status_label_field' ),
			$this->page_slug,
			'mmsm_components_section'
		);

		add_settings_field(
			'mmsm_show_progress',
			__( 'Show Progress', MMSM_TEXT_DOMAIN ),
			array( $this, 'render_show_progress_field' ),
			$this->page_slug,
			'mmsm_components_section'
		);

		add_settings_field(
			'mmsm_progress_value',
			__( 'Progress Value', MMSM_TEXT_DOMAIN ),
			array( $this, 'render_progress_value_field' ),
			$this->page_slug,
			'mmsm_components_section'
		);

		add_settings_field(
			'mmsm_contact_label',
			__( 'Contact Label', MMSM_TEXT_DOMAIN ),
			array( $this, 'render_contact_label_field' ),
			$this->page_slug,
			'mmsm_components_section'
		);

		add_settings_field(
			'mmsm_contact_message',
			__( 'Contact Message', MMSM_TEXT_DOMAIN ),
			array( $this, 'render_contact_message_field' ),
			$this->page_slug,
			'mmsm_components_section'
		);

		add_settings_field(
			'mmsm_contact_email',
			__( 'Contact Email', MMSM_TEXT_DOMAIN ),
			array( $this, 'render_contact_email_field' ),
			$this->page_slug,
			'mmsm_components_section'
		);

		add_settings_field(
			'mmsm_login_label',
			__( 'Login Label', MMSM_TEXT_DOMAIN ),
			array( $this, 'render_login_label_field' ),
			$this->page_slug,
			'mmsm_components_section'
		);

		for ( $index = 1; $index <= 4; $index++ ) {
			add_settings_field(
				'mmsm_social_item_' . $index,
				sprintf( __( 'Social Item %d', MMSM_TEXT_DOMAIN ), $index ),
				array( $this, 'render_social_item_field' ),
				$this->page_slug,
				'mmsm_components_section',
				array(
					'index' => $index,
				)
			);
		}
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
		?>
		<div class="wrap mmsm-settings-page">
			<h1><?php echo esc_html__( 'Maintenance Mode Studio', MMSM_TEXT_DOMAIN ); ?></h1>
			<p class="mmsm-settings-intro">
				<?php echo esc_html__( 'Configure the maintenance page template, core copy, and a few reusable components without editing code.', MMSM_TEXT_DOMAIN ); ?>
			</p>

			<?php settings_errors( MMSM_SETTINGS_OPTION ); ?>

			<form action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>" method="post">
				<?php
				settings_fields( $this->settings_group );
				do_settings_sections( $this->page_slug );
				submit_button( __( 'Save Settings', MMSM_TEXT_DOMAIN ) );
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
		if ( $hook_suffix !== $this->page_hook ) {
			return;
		}

		wp_enqueue_style(
			'mmsm-admin-settings',
			MMSM_PLUGIN_URL . 'admin/assets/admin.css',
			array(),
			MMSM_VERSION
		);

		wp_enqueue_style( 'wp-color-picker' );

		wp_enqueue_script(
			'mmsm-admin-settings-script',
			MMSM_PLUGIN_URL . 'admin/assets/admin.js',
			array( 'jquery', 'wp-color-picker' ),
			MMSM_VERSION,
			true
		);
	}

	/**
	 * Sanitize saved settings via the shared helper.
	 *
	 * @param mixed $input Raw option payload.
	 * @return array<string,int|string>
	 */
	public function sanitize_settings( $input ) {
		add_settings_error(
			MMSM_SETTINGS_OPTION,
			'mmsm_settings_saved',
			__( 'Settings saved.', MMSM_TEXT_DOMAIN ),
			'updated'
		);

		return Sanitizer::sanitize_settings( $input );
	}

	/**
	 * Render the general section description.
	 *
	 * @return void
	 */
	public function render_general_section() {
		echo '<p>' . esc_html__( 'These settings control the core public experience shown to logged-out visitors.', MMSM_TEXT_DOMAIN ) . '</p>';
	}

	/**
	 * Render the appearance section description.
	 *
	 * @return void
	 */
	public function render_appearance_section() {
		echo '<p>' . esc_html__( 'Choose the template shell, theme mode, and safe color roles used for the public maintenance page.', MMSM_TEXT_DOMAIN ) . '</p>';
	}

	/**
	 * Render the component section description.
	 *
	 * @return void
	 */
	public function render_components_section() {
		echo '<p>' . esc_html__( 'These optional settings feed the reusable frontend components used by the default template.', MMSM_TEXT_DOMAIN ) . '</p>';
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
			<?php echo esc_html__( 'Show the maintenance page to logged-out visitors.', MMSM_TEXT_DOMAIN ); ?>
		</label>
		<p class="description"><?php echo esc_html__( 'Administrators keep normal site access while this is enabled.', MMSM_TEXT_DOMAIN ); ?></p>
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
				<?php echo esc_html__( 'Maintenance', MMSM_TEXT_DOMAIN ); ?>
			</option>
			<option value="coming_soon" <?php selected( $settings['mode_type'], 'coming_soon' ); ?>>
				<?php echo esc_html__( 'Coming Soon', MMSM_TEXT_DOMAIN ); ?>
			</option>
		</select>
		<p class="description"><?php echo esc_html__( 'Choose whether the public page should show as maintenance mode or coming soon mode.', MMSM_TEXT_DOMAIN ); ?></p>
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
		<p class="description"><?php echo esc_html__( 'This title appears as the main heading on the public page.', MMSM_TEXT_DOMAIN ); ?></p>
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
		<p class="description"><?php echo esc_html__( 'Plain text only in this phase.', MMSM_TEXT_DOMAIN ); ?></p>
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
			<?php echo esc_html__( 'Display the default login button on the public page.', MMSM_TEXT_DOMAIN ); ?>
		</label>
		<p class="description"><?php echo esc_html__( 'Turn this off if visitors should not see a login shortcut.', MMSM_TEXT_DOMAIN ); ?></p>
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
				<?php echo esc_html__( 'Light', MMSM_TEXT_DOMAIN ); ?>
			</option>
			<option value="dark" <?php selected( $settings['theme_mode'], 'dark' ); ?>>
				<?php echo esc_html__( 'Dark', MMSM_TEXT_DOMAIN ); ?>
			</option>
			<option value="system" <?php selected( $settings['theme_mode'], 'system' ); ?>>
				<?php echo esc_html__( 'System', MMSM_TEXT_DOMAIN ); ?>
			</option>
		</select>
		<p class="description"><?php echo esc_html__( 'Choose a light, dark, or system-following visual style for the active template.', MMSM_TEXT_DOMAIN ); ?></p>
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
				<?php echo esc_html__( 'Default', MMSM_TEXT_DOMAIN ); ?>
			</option>
		</select>
		<p class="description"><?php echo esc_html__( 'Phase 3 ships with one polished default template.', MMSM_TEXT_DOMAIN ); ?></p>
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
			__( 'Accent color used for buttons, focus accents, and status styling.', MMSM_TEXT_DOMAIN )
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
			__( 'Page background color. Invalid values fall back to the theme default.', MMSM_TEXT_DOMAIN )
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
			__( 'Card and panel surface color.', MMSM_TEXT_DOMAIN )
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
			__( 'Main heading and section title color.', MMSM_TEXT_DOMAIN )
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
			__( 'Primary body copy color for messages and descriptions.', MMSM_TEXT_DOMAIN )
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
			__( 'Secondary copy color for quieter supporting text.', MMSM_TEXT_DOMAIN )
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
			__( 'Link and social label color.', MMSM_TEXT_DOMAIN )
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
			__( 'Text color shown on primary buttons.', MMSM_TEXT_DOMAIN )
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
			__( 'Border color for cards, pills, and link chips.', MMSM_TEXT_DOMAIN )
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
			__( 'Optional short label above the page title.', MMSM_TEXT_DOMAIN )
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
			__( 'Leave blank to hide the primary action, or pair it with a valid URL.', MMSM_TEXT_DOMAIN )
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
			__( 'Use a full public URL such as https://example.com/status.', MMSM_TEXT_DOMAIN )
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
			__( 'Optional secondary action label.', MMSM_TEXT_DOMAIN )
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
			__( 'Use a full public URL or leave blank.', MMSM_TEXT_DOMAIN )
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
			__( 'Shown above the progress bar component.', MMSM_TEXT_DOMAIN )
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
			<?php echo esc_html__( 'Display the status progress bar.', MMSM_TEXT_DOMAIN ); ?>
		</label>
		<p class="description"><?php echo esc_html__( 'Turn this off if you only want the status text.', MMSM_TEXT_DOMAIN ); ?></p>
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
		<p class="description"><?php echo esc_html__( 'A number between 0 and 100.', MMSM_TEXT_DOMAIN ); ?></p>
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
			__( 'Short heading for the contact block.', MMSM_TEXT_DOMAIN )
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
			__( 'Explain when visitors should reach out.', MMSM_TEXT_DOMAIN )
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
		<p class="description"><?php echo esc_html__( 'Only valid email addresses are rendered publicly.', MMSM_TEXT_DOMAIN ); ?></p>
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
			__( 'Used when the login button is enabled.', MMSM_TEXT_DOMAIN )
		);
	}

	/**
	 * Render one grouped social item field.
	 *
	 * @param array<string,mixed> $args Field arguments.
	 * @return void
	 */
	public function render_social_item_field( $args ) {
		$index     = isset( $args['index'] ) ? (int) $args['index'] : 1;
		$settings  = $this->get_settings();
		$platforms = SocialLinksComponent::get_platform_labels();
		$platform  = isset( $settings[ 'social_item_' . $index . '_platform' ] ) ? (string) $settings[ 'social_item_' . $index . '_platform' ] : '';
		$label     = isset( $settings[ 'social_item_' . $index . '_label' ] ) ? (string) $settings[ 'social_item_' . $index . '_label' ] : '';
		$url       = isset( $settings[ 'social_item_' . $index . '_url' ] ) ? (string) $settings[ 'social_item_' . $index . '_url' ] : '';
		$new_tab   = ! empty( $settings[ 'social_item_' . $index . '_new_tab' ] );
		?>
		<div class="mmsm-social-item-group">
			<p>
				<label for="mmsm-social-item-<?php echo esc_attr( (string) $index ); ?>-platform"><?php echo esc_html__( 'Platform', MMSM_TEXT_DOMAIN ); ?></label><br />
				<select
					id="mmsm-social-item-<?php echo esc_attr( (string) $index ); ?>-platform"
					name="<?php echo esc_attr( MMSM_SETTINGS_OPTION ); ?>[social_item_<?php echo esc_attr( (string) $index ); ?>_platform]"
				>
					<option value=""><?php echo esc_html__( 'Select a platform', MMSM_TEXT_DOMAIN ); ?></option>
					<?php foreach ( $platforms as $platform_key => $platform_label ) : ?>
						<option value="<?php echo esc_attr( $platform_key ); ?>" <?php selected( $platform, $platform_key ); ?>>
							<?php echo esc_html( $platform_label ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</p>
			<p>
				<label for="mmsm-social-item-<?php echo esc_attr( (string) $index ); ?>-label"><?php echo esc_html__( 'Custom Label', MMSM_TEXT_DOMAIN ); ?></label><br />
				<input
					type="text"
					class="regular-text"
					id="mmsm-social-item-<?php echo esc_attr( (string) $index ); ?>-label"
					name="<?php echo esc_attr( MMSM_SETTINGS_OPTION ); ?>[social_item_<?php echo esc_attr( (string) $index ); ?>_label]"
					value="<?php echo esc_attr( $label ); ?>"
				/>
			</p>
			<p>
				<label for="mmsm-social-item-<?php echo esc_attr( (string) $index ); ?>-url"><?php echo esc_html__( 'URL or Email', MMSM_TEXT_DOMAIN ); ?></label><br />
				<input
					type="text"
					class="regular-text code"
					id="mmsm-social-item-<?php echo esc_attr( (string) $index ); ?>-url"
					name="<?php echo esc_attr( MMSM_SETTINGS_OPTION ); ?>[social_item_<?php echo esc_attr( (string) $index ); ?>_url]"
					value="<?php echo esc_attr( $url ); ?>"
					placeholder="https://example.com or hello@example.com"
				/>
			</p>
			<p>
				<label for="mmsm-social-item-<?php echo esc_attr( (string) $index ); ?>-new-tab">
					<input
						type="checkbox"
						id="mmsm-social-item-<?php echo esc_attr( (string) $index ); ?>-new-tab"
						name="<?php echo esc_attr( MMSM_SETTINGS_OPTION ); ?>[social_item_<?php echo esc_attr( (string) $index ); ?>_new_tab]"
						value="1"
						<?php checked( $new_tab ); ?>
					/>
					<?php echo esc_html__( 'Open in a new tab when supported.', MMSM_TEXT_DOMAIN ); ?>
				</label>
			</p>
			<p class="description"><?php echo esc_html__( 'Use email addresses or mailto: links for the email platform. Unsupported platforms or invalid values are skipped safely.', MMSM_TEXT_DOMAIN ); ?></p>
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
}

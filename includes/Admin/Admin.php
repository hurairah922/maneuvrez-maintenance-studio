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
 * Handles the Phase 2 admin UI, settings registration, and admin assets.
 */
class Admin {
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
			'mmsm_primary_color',
			__( 'Primary Color', MMSM_TEXT_DOMAIN ),
			array( $this, 'render_primary_color_field' ),
			$this->page_slug,
			'mmsm_appearance_section'
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
		?>
		<div class="wrap mmsm-settings-page">
			<h1><?php echo esc_html__( 'Maintenance Mode Studio', MMSM_TEXT_DOMAIN ); ?></h1>
			<p class="mmsm-settings-intro">
				<?php echo esc_html__( 'Configure the default maintenance page content and appearance without editing code.', MMSM_TEXT_DOMAIN ); ?>
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
		echo '<p>' . esc_html__( 'Keep the visual controls simple for this phase while covering the default template.', MMSM_TEXT_DOMAIN ) . '</p>';
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
		</select>
		<p class="description"><?php echo esc_html__( 'Choose the light or dark visual style for the default template.', MMSM_TEXT_DOMAIN ); ?></p>
		<?php
	}

	/**
	 * Render the primary color field.
	 *
	 * @return void
	 */
	public function render_primary_color_field() {
		$settings = $this->get_settings();
		?>
		<input
			type="text"
			class="regular-text code mmsm-color-field"
			id="mmsm-primary-color"
			name="<?php echo esc_attr( MMSM_SETTINGS_OPTION ); ?>[primary_color]"
			value="<?php echo esc_attr( $settings['primary_color'] ); ?>"
			placeholder="#2563eb"
		/>
		<p class="description"><?php echo esc_html__( 'Use a hex color value such as #2563eb.', MMSM_TEXT_DOMAIN ); ?></p>
		<?php
	}

	/**
	 * Read settings with defaults applied.
	 *
	 * @return array<string,int|string>
	 */
	private function get_settings() {
		return Sanitizer::get_settings( get_option( MMSM_SETTINGS_OPTION, array() ) );
	}
}

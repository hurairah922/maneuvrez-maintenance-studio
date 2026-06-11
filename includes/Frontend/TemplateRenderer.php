<?php
/**
 * Default maintenance template renderer.
 *
 * @package MaintenanceModeStudio
 */

namespace Maneuvrez\MaintenanceModeStudio\Frontend;

use Maneuvrez\MaintenanceModeStudio\Components\ComponentRegistry;
use Maneuvrez\MaintenanceModeStudio\Security\Sanitizer;
use Maneuvrez\MaintenanceModeStudio\Settings\SettingsRepository;
use Maneuvrez\MaintenanceModeStudio\Support\Escaper;

defined( 'ABSPATH' ) || exit;

/**
 * Loads frontend templates through a registry-driven renderer.
 */
class TemplateRenderer {
	/**
	 * Template registry.
	 *
	 * @var TemplateRegistry
	 */
	private $template_registry;

	/**
	 * Component registry.
	 *
	 * @var ComponentRegistry
	 */
	private $component_registry;

	/**
	 * Settings repository.
	 *
	 * @var SettingsRepository
	 */
	private $settings_repository;

	/**
	 * Constructor.
	 *
	 * @param TemplateRegistry|null   $template_registry Template registry.
	 * @param ComponentRegistry|null  $component_registry Component registry.
	 * @param SettingsRepository|null $settings_repository Settings repository.
	 */
	public function __construct( $template_registry = null, $component_registry = null, $settings_repository = null ) {
		$this->template_registry   = $template_registry instanceof TemplateRegistry ? $template_registry : new TemplateRegistry();
		$this->component_registry  = $component_registry instanceof ComponentRegistry ? $component_registry : new ComponentRegistry();
		$this->settings_repository = $settings_repository instanceof SettingsRepository ? $settings_repository : new SettingsRepository();
	}

	/**
	 * Render the selected public template.
	 *
	 * @param array<string,mixed> $settings Sanitized settings.
	 * @return void
	 */
	public function render( array $settings = array() ) {
		if ( empty( $settings ) ) {
			$settings = $this->settings_repository->get_settings();
		}

		$settings = Sanitizer::get_settings( $settings );
		$template = $this->template_registry->resolve( (string) $settings['template_key'] );
		$assets   = $this->enqueue_assets( $template, $settings );
		$context  = $this->build_context( $settings, $template, $assets );
		$renderer = $this;

		if ( ! empty( $template['file'] ) && file_exists( $template['file'] ) ) {
			require $template['file'];
			return;
		}

		$this->render_basic_fallback( $context );
	}

	/**
	 * Render a zone using the current template layout.
	 *
	 * @param string              $zone Zone key.
	 * @param array<string,mixed> $settings Normalized settings.
	 * @param array<string,mixed> $context Shared context.
	 * @return string
	 */
	public function render_zone( $zone, array $settings, array $context ) {
		$template = isset( $context['template'] ) && is_array( $context['template'] ) ? $context['template'] : $this->template_registry->resolve( 'default' );
		$zones    = isset( $template['zones'] ) && is_array( $template['zones'] ) ? $template['zones'] : array();

		if ( ! in_array( $zone, $zones, true ) ) {
			return '';
		}

		$layout  = isset( $template['layout'][ $zone ] ) && is_array( $template['layout'][ $zone ] ) ? $template['layout'][ $zone ] : array();
		$outputs = array();

		foreach ( $layout as $component_key ) {
			$markup = $this->component_registry->render( (string) $component_key, $zone, $settings, $context );

			if ( '' === $markup ) {
				continue;
			}

			$outputs[] = $markup;
		}

		return implode( '', $outputs );
	}

	/**
	 * Register and enqueue only the current template assets.
	 *
	 * @param array<string,mixed> $template Template configuration.
	 * @return array<string,array<int,string>>
	 */
	private function enqueue_assets( array $template, array $settings ) {
		$assets = array(
			'styles'  => array(),
			'scripts' => array(),
		);

		$asset_sources = isset( $template['asset_sources'] ) && is_array( $template['asset_sources'] ) ? $template['asset_sources'] : array();

		if ( ! empty( $asset_sources['styles'] ) && is_array( $asset_sources['styles'] ) ) {
			foreach ( $asset_sources['styles'] as $style_handle => $style_path ) {
				wp_register_style(
					$style_handle,
					MMSM_PLUGIN_URL . ltrim( $style_path, '/' ),
					array(),
					$this->get_asset_version( $style_path )
				);
			}
		}

		if ( ! empty( $asset_sources['scripts'] ) && is_array( $asset_sources['scripts'] ) ) {
			foreach ( $asset_sources['scripts'] as $script_handle => $script_path ) {
				wp_register_script(
					$script_handle,
					MMSM_PLUGIN_URL . ltrim( $script_path, '/' ),
					array(),
					$this->get_asset_version( $script_path ),
					true
				);
				wp_script_add_data( $script_handle, 'defer', true );
			}
		}

		if ( ! empty( $template['assets']['styles'] ) && is_array( $template['assets']['styles'] ) ) {
			foreach ( $template['assets']['styles'] as $style_handle ) {
				wp_enqueue_style( $style_handle );
				$assets['styles'][] = $style_handle;
			}
		}

		if ( $this->should_enqueue_dashicons( $settings ) ) {
			wp_enqueue_style( 'dashicons' );
			$assets['styles'][] = 'dashicons';
		}

		if ( ! empty( $template['assets']['scripts'] ) && is_array( $template['assets']['scripts'] ) ) {
			foreach ( $template['assets']['scripts'] as $script_handle ) {
				wp_enqueue_script( $script_handle );
				$assets['scripts'][] = $script_handle;
			}
		}

		return $assets;
	}

	/**
	 * Determine whether the current page needs WordPress Dashicons.
	 *
	 * @param array<string,mixed> $settings Sanitized settings.
	 * @return bool
	 */
	private function should_enqueue_dashicons( array $settings ) {
		$social_links = isset( $settings['social_links'] ) && is_array( $settings['social_links'] ) ? $settings['social_links'] : array();

		foreach ( $social_links as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$icon_source  = isset( $item['icon_source'] ) ? sanitize_key( $item['icon_source'] ) : '';
			$icon_library = isset( $item['icon_library'] ) ? sanitize_key( $item['icon_library'] ) : '';

			if ( 'library' === $icon_source && 'dashicons' === $icon_library ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Build the shared template context.
	 *
	 * @param array<string,mixed>              $settings Sanitized settings.
	 * @param array<string,mixed>              $template Resolved template config.
	 * @param array<string,array<int,string>>  $assets Asset handles.
	 * @return array<string,mixed>
	 */
	private function build_context( array $settings, array $template, array $assets ) {
		return array(
			'charset'        => get_bloginfo( 'charset' ),
			'language'       => get_bloginfo( 'language' ),
			'site_name'      => get_bloginfo( 'name' ),
			'document_title' => (string) $settings['page_title'],
			'mode_label'     => 'coming_soon' === $settings['mode_type']
				? __( 'Coming Soon', 'maintenance-mode-studio' )
				: __( 'Maintenance Mode Active', 'maintenance-mode-studio' ),
			'login_url'      => wp_login_url(),
			'shell_class'    => Escaper::classes(
				array(
					'mmsm-shell',
					'mmsm-theme-' . (string) $settings['theme_mode'],
					'mmsm-mode-' . (string) $settings['mode_type'],
				)
			),
			'shell_style'    => $this->build_shell_style( $settings ),
			'assets'         => $assets,
			'template'       => $template,
		);
	}

	/**
	 * Return the canonical setting-to-variable map for public colors.
	 *
	 * @return array<string,string>
	 */
	private function get_color_variable_map() {
		return array(
			'background_color'   => '--mm-bg',
			'surface_color'      => '--mm-surface',
			'primary_color'      => '--mm-primary',
			'heading_text_color' => '--mm-heading-text',
			'body_text_color'    => '--mm-body-text',
			'muted_text_color'   => '--mm-muted-text',
			'link_text_color'    => '--mm-link-text',
			'button_text_color'  => '--mm-button-text',
			'border_color'       => '--mm-border',
		);
	}

	/**
	 * Return default colors for the selected theme mode.
	 *
	 * System mode uses the light defaults as its inline fallback base while the
	 * stylesheet applies dark defaults through the media query when no custom
	 * override is printed for a given variable.
	 *
	 * @param string $theme_mode Selected theme mode.
	 * @return array<string,string>
	 */
	private function get_default_colors_for_theme_mode( $theme_mode ) {
		$light_defaults = array(
			'background_color'   => '#f8fafc',
			'surface_color'      => '#ffffff',
			'primary_color'      => '#2563eb',
			'heading_text_color' => '#0f172a',
			'body_text_color'    => '#334155',
			'muted_text_color'   => '#64748b',
			'link_text_color'    => '#2563eb',
			'button_text_color'  => '#ffffff',
			'border_color'       => '#e2e8f0',
		);
		$dark_defaults  = array(
			'background_color'   => '#020617',
			'surface_color'      => '#0f172a',
			'primary_color'      => '#60a5fa',
			'heading_text_color' => '#f8fafc',
			'body_text_color'    => '#cbd5e1',
			'muted_text_color'   => '#94a3b8',
			'link_text_color'    => '#93c5fd',
			'button_text_color'  => '#020617',
			'border_color'       => '#334155',
		);

		if ( 'dark' === $theme_mode ) {
			return $dark_defaults;
		}

		return $light_defaults;
	}

	/**
	 * Normalize color settings against theme defaults.
	 *
	 * @param array<string,mixed> $settings Sanitized settings.
	 * @return array<string,string>
	 */
	private function normalize_colors( array $settings ) {
		$defaults   = $this->get_default_colors_for_theme_mode( isset( $settings['theme_mode'] ) ? (string) $settings['theme_mode'] : 'system' );
		$saved      = array();
		$color_keys = array_keys( $this->get_color_variable_map() );

		foreach ( $color_keys as $key ) {
			if ( isset( $settings[ $key ] ) ) {
				$saved[ $key ] = $settings[ $key ];
			}
		}

		$colors = wp_parse_args( $saved, $defaults );

		foreach ( $defaults as $key => $default ) {
			$color = isset( $colors[ $key ] ) ? sanitize_hex_color( (string) $colors[ $key ] ) : '';

			if ( empty( $color ) ) {
				$colors[ $key ] = $default;
				continue;
			}

			$colors[ $key ] = $color;
		}

		return array_intersect_key( $colors, $this->get_color_variable_map() );
	}

	/**
	 * Build inline shell styles from saved color overrides.
	 *
	 * @param array<string,mixed> $settings Sanitized settings.
	 * @return string
	 */
	private function build_shell_style( array $settings ) {
		$defaults  = $this->get_default_colors_for_theme_mode( isset( $settings['theme_mode'] ) ? (string) $settings['theme_mode'] : 'system' );
		$colors    = $this->normalize_colors( $settings );
		$overrides = array();

		foreach ( $this->get_color_variable_map() as $setting_key => $css_var ) {
			if ( empty( $colors[ $setting_key ] ) ) {
				continue;
			}

			if ( isset( $defaults[ $setting_key ] ) && strtolower( $colors[ $setting_key ] ) === strtolower( $defaults[ $setting_key ] ) ) {
				continue;
			}

			$overrides[ $setting_key ] = $colors[ $setting_key ];
		}

		return $this->build_color_style_attribute( $overrides );
	}

	/**
	 * Build the scoped style attribute for canonical public color variables.
	 *
	 * @param array<string,string> $colors Normalized color values keyed by setting.
	 * @return string
	 */
	private function build_color_style_attribute( array $colors ) {
		$map          = $this->get_color_variable_map();
		$declarations = array();

		foreach ( $map as $setting_key => $css_var ) {
			if ( empty( $colors[ $setting_key ] ) ) {
				continue;
			}

			$color = sanitize_hex_color( $colors[ $setting_key ] );

			if ( empty( $color ) ) {
				continue;
			}

			$declarations[] = sprintf(
				'%s: %s',
				$css_var,
				$color
			);
		}

		return implode( '; ', $declarations );
	}

	/**
	 * Render a minimal fallback page if the template file is unavailable.
	 *
	 * @param array<string,mixed> $context Template context.
	 * @return void
	 */
	private function render_basic_fallback( array $context ) {
		?>
		<!doctype html>
		<html lang="<?php echo esc_attr( $context['language'] ); ?>">
		<head>
			<meta charset="<?php echo esc_attr( $context['charset'] ); ?>" />
			<meta name="viewport" content="width=device-width, initial-scale=1" />
			<title><?php echo esc_html( $context['document_title'] ); ?></title>
		</head>
		<body>
			<main>
				<h1><?php echo esc_html( $context['document_title'] ); ?></h1>
				<p><?php echo esc_html( $context['site_name'] ); ?></p>
			</main>
		</body>
		</html>
		<?php
	}

	/**
	 * Resolve an asset version from file modification time with a safe fallback.
	 *
	 * @param string $relative_path Asset path relative to the plugin root.
	 * @return string
	 */
	private function get_asset_version( $relative_path ) {
		$absolute_path = MMSM_PLUGIN_PATH . ltrim( (string) $relative_path, '/' );

		if ( file_exists( $absolute_path ) ) {
			return (string) filemtime( $absolute_path );
		}

		return MMSM_VERSION;
	}
}

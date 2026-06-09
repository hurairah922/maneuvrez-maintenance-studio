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
		$assets   = $this->enqueue_assets( $template );
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
	private function enqueue_assets( array $template ) {
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

		if ( ! empty( $template['assets']['scripts'] ) && is_array( $template['assets']['scripts'] ) ) {
			foreach ( $template['assets']['scripts'] as $script_handle ) {
				wp_enqueue_script( $script_handle );
				$assets['scripts'][] = $script_handle;
			}
		}

		return $assets;
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
		$theme_variables = $this->get_theme_variables( $settings );

		return array(
			'charset'        => get_bloginfo( 'charset' ),
			'language'       => get_bloginfo( 'language' ),
			'site_name'      => get_bloginfo( 'name' ),
			'document_title' => (string) $settings['page_title'],
			'mode_label'     => 'coming_soon' === $settings['mode_type']
				? __( 'Coming Soon', MMSM_TEXT_DOMAIN )
				: __( 'Maintenance Mode Active', MMSM_TEXT_DOMAIN ),
			'login_url'      => wp_login_url(),
			'shell_class'    => Escaper::classes(
				array(
					'mmsm-shell',
					'mmsm-theme-' . (string) $settings['theme_mode'],
					'mmsm-mode-' . (string) $settings['mode_type'],
				)
			),
			'shell_style'    => Escaper::css_variables( $theme_variables ),
			'assets'         => $assets,
			'template'       => $template,
		);
	}

	/**
	 * Build theme variables for light and dark modes with safe fallbacks.
	 *
	 * @param array<string,mixed> $settings Normalized settings.
	 * @return array<string,string>
	 */
	private function get_theme_variables( array $settings ) {
		$light_defaults = array(
			'mm-bg'           => '#f8fafc',
			'mm-surface'      => '#ffffff',
			'mm-heading-text' => '#0f172a',
			'mm-body-text'    => '#334155',
			'mm-muted-text'   => '#64748b',
			'mm-link-text'    => '#2563eb',
			'mm-primary'      => '#2563eb',
			'mm-button-text'  => '#ffffff',
			'mm-border'       => '#e2e8f0',
			'mm-shadow'       => '0 24px 80px rgba(15,23,42,0.16)',
		);
		$dark_defaults  = array(
			'mm-bg'           => '#020617',
			'mm-surface'      => '#0f172a',
			'mm-heading-text' => '#f8fafc',
			'mm-body-text'    => '#cbd5e1',
			'mm-muted-text'   => '#94a3b8',
			'mm-link-text'    => '#93c5fd',
			'mm-primary'      => '#60a5fa',
			'mm-button-text'  => '#020617',
			'mm-border'       => '#334155',
			'mm-shadow'       => '0 24px 80px rgba(0,0,0,0.34)',
		);

		$light_palette = $this->build_theme_palette( $settings, $light_defaults, false );
		$dark_palette  = $this->build_theme_palette( $settings, $dark_defaults, true );

		return array(
			'mm-bg'                => $light_palette['mm-bg'],
			'mm-surface'           => $light_palette['mm-surface'],
			'mm-heading-text'      => $light_palette['mm-heading-text'],
			'mm-body-text'         => $light_palette['mm-body-text'],
			'mm-muted-text'        => $light_palette['mm-muted-text'],
			'mm-link-text'         => $light_palette['mm-link-text'],
			'mm-primary'           => $light_palette['mm-primary'],
			'mm-button-text'       => $light_palette['mm-button-text'],
			'mm-border'            => $light_palette['mm-border'],
			'mm-shadow'            => $light_palette['mm-shadow'],
			'mm-radius'            => '28px',
			'mm-content-width'     => '1120px',
			'mm-text'              => $light_palette['mm-body-text'],
			'mm-muted'             => $light_palette['mm-muted-text'],
			'mm-primary-text'      => $light_palette['mm-button-text'],
			'mm-dark-bg'           => $dark_palette['mm-bg'],
			'mm-dark-surface'      => $dark_palette['mm-surface'],
			'mm-dark-heading-text' => $dark_palette['mm-heading-text'],
			'mm-dark-body-text'    => $dark_palette['mm-body-text'],
			'mm-dark-muted-text'   => $dark_palette['mm-muted-text'],
			'mm-dark-link-text'    => $dark_palette['mm-link-text'],
			'mm-dark-primary'      => $dark_palette['mm-primary'],
			'mm-dark-button-text'  => $dark_palette['mm-button-text'],
			'mm-dark-border'       => $dark_palette['mm-border'],
			'mm-dark-shadow'       => $dark_palette['mm-shadow'],
		);
	}

	/**
	 * Build a readable palette for one theme mode.
	 *
	 * @param array<string,mixed>  $settings Normalized settings.
	 * @param array<string,string> $defaults Safe defaults for the theme.
	 * @param bool                 $is_dark Whether this is the dark palette.
	 * @return array<string,string>
	 */
	private function build_theme_palette( array $settings, array $defaults, $is_dark ) {
		$background = $this->sanitize_theme_color( $settings['background_color'], $defaults['mm-bg'] );
		$surface    = $this->sanitize_theme_color( $settings['surface_color'], $defaults['mm-surface'] );
		$primary    = $this->sanitize_theme_color( $settings['primary_color'], $defaults['mm-primary'] );
		$heading    = $this->sanitize_theme_color( $settings['heading_text_color'], $defaults['mm-heading-text'] );
		$body       = $this->sanitize_theme_color( $settings['body_text_color'], $defaults['mm-body-text'] );
		$muted      = $this->sanitize_theme_color( $settings['muted_text_color'], $defaults['mm-muted-text'] );
		$link       = $this->sanitize_theme_color( $settings['link_text_color'], $defaults['mm-link-text'] );
		$button     = $this->sanitize_theme_color( $settings['button_text_color'], $defaults['mm-button-text'] );
		$border     = $this->sanitize_theme_color( $settings['border_color'], $defaults['mm-border'] );

		if ( $is_dark && ! $this->is_dark_enough( $background ) ) {
			$background = $defaults['mm-bg'];
		}

		if ( $is_dark && ! $this->is_dark_enough( $surface ) ) {
			$surface = $defaults['mm-surface'];
		}

		$heading = $this->ensure_readable_text_color( $heading, $surface, $defaults['mm-heading-text'], 4.5 );
		$body    = $this->ensure_readable_text_color( $body, $surface, $defaults['mm-body-text'], 4.5 );
		$muted   = $this->ensure_readable_text_color( $muted, $surface, $defaults['mm-muted-text'], 3.2 );
		$link    = $this->ensure_distinct_link_color( $link, $surface, $body, $defaults['mm-link-text'] );
		$button  = $this->ensure_button_text_color( $button, $primary, $defaults['mm-button-text'] );
		$border  = $this->ensure_border_color( $border, $surface, $defaults['mm-border'] );

		return array(
			'mm-bg'           => $background,
			'mm-surface'      => $surface,
			'mm-heading-text' => $heading,
			'mm-body-text'    => $body,
			'mm-muted-text'   => $muted,
			'mm-link-text'    => $link,
			'mm-primary'      => $primary,
			'mm-button-text'  => $button,
			'mm-border'       => $border,
			'mm-shadow'       => $defaults['mm-shadow'],
		);
	}

	/**
	 * Return a sanitized theme color.
	 *
	 * @param mixed  $color Raw color.
	 * @param string $default Default color.
	 * @return string
	 */
	private function sanitize_theme_color( $color, $default ) {
		$color = sanitize_hex_color( (string) $color );

		if ( empty( $color ) ) {
			return $default;
		}

		return $color;
	}

	/**
	 * Ensure a text color remains readable against its surface.
	 *
	 * @param string $color Requested color.
	 * @param string $background Surface color.
	 * @param string $default Default fallback.
	 * @param float  $threshold Minimum contrast threshold.
	 * @return string
	 */
	private function ensure_readable_text_color( $color, $background, $default, $threshold ) {
		if ( $this->get_contrast_ratio( $color, $background ) < $threshold ) {
			return $default;
		}

		return $color;
	}

	/**
	 * Ensure link text is readable and distinct from body text.
	 *
	 * @param string $link_color Requested link color.
	 * @param string $background Surface color.
	 * @param string $body_color Body text color.
	 * @param string $default Default fallback.
	 * @return string
	 */
	private function ensure_distinct_link_color( $link_color, $background, $body_color, $default ) {
		if ( $this->get_contrast_ratio( $link_color, $background ) < 3.5 ) {
			return $default;
		}

		if ( $this->get_contrast_ratio( $link_color, $body_color ) < 1.15 ) {
			return $default;
		}

		return $link_color;
	}

	/**
	 * Ensure button text is readable against the primary color.
	 *
	 * @param string $button_color Requested button text color.
	 * @param string $primary_color Primary button background color.
	 * @param string $default Default fallback.
	 * @return string
	 */
	private function ensure_button_text_color( $button_color, $primary_color, $default ) {
		if ( $this->get_contrast_ratio( $button_color, $primary_color ) >= 4.5 ) {
			return $button_color;
		}

		if ( $this->get_contrast_ratio( $default, $primary_color ) >= 4.5 ) {
			return $default;
		}

		return $this->get_contrast_ratio( '#ffffff', $primary_color ) >= $this->get_contrast_ratio( '#020617', $primary_color ) ? '#ffffff' : '#020617';
	}

	/**
	 * Ensure borders remain visible against the surface.
	 *
	 * @param string $border_color Requested border color.
	 * @param string $surface_color Surface color.
	 * @param string $default Default fallback.
	 * @return string
	 */
	private function ensure_border_color( $border_color, $surface_color, $default ) {
		if ( $this->get_contrast_ratio( $border_color, $surface_color ) < 1.25 ) {
			return $default;
		}

		return $border_color;
	}

	/**
	 * Determine whether a color is dark enough for dark mode surfaces.
	 *
	 * @param string $hex_color Color value.
	 * @return bool
	 */
	private function is_dark_enough( $hex_color ) {
		list( $red, $green, $blue ) = $this->hex_to_rgb( $hex_color );
		$luminance = ( 0.2126 * $red ) + ( 0.7152 * $green ) + ( 0.0722 * $blue );

		return $luminance < 0.35;
	}

	/**
	 * Calculate contrast ratio between two colors.
	 *
	 * @param string $foreground Foreground color.
	 * @param string $background Background color.
	 * @return float
	 */
	private function get_contrast_ratio( $foreground, $background ) {
		$foreground_luminance = $this->get_relative_luminance( $foreground );
		$background_luminance = $this->get_relative_luminance( $background );
		$light                = max( $foreground_luminance, $background_luminance );
		$dark                 = min( $foreground_luminance, $background_luminance );

		return ( $light + 0.05 ) / ( $dark + 0.05 );
	}

	/**
	 * Convert a hex color into a relative luminance value.
	 *
	 * @param string $hex_color Hex color.
	 * @return float
	 */
	private function get_relative_luminance( $hex_color ) {
		list( $red, $green, $blue ) = $this->hex_to_rgb( $hex_color );

		$red   = $red <= 0.03928 ? $red / 12.92 : pow( ( $red + 0.055 ) / 1.055, 2.4 );
		$green = $green <= 0.03928 ? $green / 12.92 : pow( ( $green + 0.055 ) / 1.055, 2.4 );
		$blue  = $blue <= 0.03928 ? $blue / 12.92 : pow( ( $blue + 0.055 ) / 1.055, 2.4 );

		return ( 0.2126 * $red ) + ( 0.7152 * $green ) + ( 0.0722 * $blue );
	}

	/**
	 * Convert a hex color string to normalized RGB channel values.
	 *
	 * @param string $hex_color Hex color.
	 * @return array<int,float>
	 */
	private function hex_to_rgb( $hex_color ) {
		$hex_color = ltrim( (string) $hex_color, '#' );

		if ( 3 === strlen( $hex_color ) ) {
			$hex_color = $hex_color[0] . $hex_color[0] . $hex_color[1] . $hex_color[1] . $hex_color[2] . $hex_color[2];
		}

		return array(
			hexdec( substr( $hex_color, 0, 2 ) ) / 255,
			hexdec( substr( $hex_color, 2, 2 ) ) / 255,
			hexdec( substr( $hex_color, 4, 2 ) ) / 255,
		);
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

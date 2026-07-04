<?php
/**
 * Contact Channels component.
 *
 * @package MaintenanceModeStudio
 */

namespace Maneuvrez\MaintenanceModeStudio\Components;

use Maneuvrez\MaintenanceModeStudio\Support\ContactChannels;
use Maneuvrez\MaintenanceModeStudio\Support\Escaper;

defined( 'ABSPATH' ) || exit;

/**
 * Renders Contact Channels on maintenance and live frontend pages.
 */
class ContactChannelsComponent implements ComponentInterface {
	/**
	 * {@inheritDoc}
	 */
	public function get_key() {
		return 'contact_channels';
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_label() {
		return __( 'Contact Channels', 'maneuvrez-maintenance-studio' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_supported_zones() {
		return array( 'main', 'footer' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_settings_schema() {
		return array(
			array(
				'key'     => 'contact_channels_items',
				'label'   => __( 'Contact Channels', 'maneuvrez-maintenance-studio' ),
				'type'    => 'repeater',
				'default' => array(),
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function render( array $settings, array $context = array() ) {
		if ( empty( $settings['contact_channels_enabled'] ) ) {
			return '';
		}

		$display = isset( $settings['contact_channels_maintenance_display'] ) ? (string) $settings['contact_channels_maintenance_display'] : 'inside';

		if ( ! in_array( $display, array( 'inside', 'both' ), true ) ) {
			return '';
		}

		return self::render_inside( $settings );
	}

	/**
	 * Render an inside-page contact block.
	 *
	 * @param array<string,mixed> $settings Sanitized settings.
	 * @return string
	 */
	public static function render_inside( array $settings ) {
		$items = ContactChannels::build_items( $settings );

		if ( empty( $items ) ) {
			return '';
		}

		$style = isset( $settings['contact_channels_display_style'] ) ? (string) $settings['contact_channels_display_style'] : 'auto';
		$mode  = 'reveal' === $style || ( 'auto' === $style && count( $items ) > 3 ) ? 'reveal' : 'row';

		ob_start();
		?>
		<section
			class="<?php echo esc_attr( self::get_container_classes( $settings, array( 'mmsm-component', 'mmsm-component-contact-channels', 'mmsm-contact-channels-inside', 'mmsm-contact-channels-mode-' . $mode ) ) ); ?>"
			style="<?php echo esc_attr( self::get_custom_style( $settings ) ); ?>"
			aria-label="<?php echo esc_attr__( 'Contact Channels', 'maneuvrez-maintenance-studio' ); ?>"
			data-mmsm-contact-channels
		>
			<h2 class="mmsm-section-title"><?php echo esc_html( self::get_setting_text( $settings, 'contact_channels_heading', 'Need help?' ) ); ?></h2>
			<p class="mmsm-section-text"><?php echo esc_html( self::get_setting_text( $settings, 'contact_channels_description', 'Contact us while the site is being updated.' ) ); ?></p>
			<?php echo 'row' === $mode ? self::render_button_list( $items, $settings ) : self::render_reveal( $items, $settings, 'inside' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</section>
		<?php

		return (string) ob_get_clean();
	}

	/**
	 * Render a floating Contact Channels button or menu.
	 *
	 * @param array<string,mixed> $settings Sanitized settings.
	 * @param string              $context Render context.
	 * @return string
	 */
	public static function render_floating( array $settings, $context = 'maintenance' ) {
		$items = ContactChannels::build_items( $settings );

		if ( empty( $items ) ) {
			return '';
		}

		$classes = self::get_container_classes(
			$settings,
			array(
				'mmsm-contact-channels',
				'mmsm-contact-channels-floating',
				'mmsm-contact-channels-floating-' . sanitize_key( $context ),
			)
		);

		ob_start();
		?>
		<div
			class="<?php echo esc_attr( $classes ); ?>"
			style="<?php echo esc_attr( self::get_custom_style( $settings ) ); ?>"
			aria-label="<?php echo esc_attr__( 'Contact Channels', 'maneuvrez-maintenance-studio' ); ?>"
			data-mmsm-contact-channels
		>
			<?php
			if ( 1 === count( $items ) ) {
				$floating_item          = $items[0];
				$floating_item['label'] = self::get_setting_text(
					$settings,
					'contact_channels_primary_label',
					isset( $floating_item['label'] ) ? (string) $floating_item['label'] : 'Contact Us'
				);

				echo self::render_channel_button( $floating_item, $settings, array( 'mmsm-contact-channel-primary' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			} else {
				echo self::render_reveal( $items, $settings, 'floating' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
			?>
		</div>
		<?php

		return (string) ob_get_clean();
	}

	/**
	 * Render a list of direct channel buttons.
	 *
	 * @param array<int,array<string,mixed>> $items Render-ready items.
	 * @param array<string,mixed>            $settings Sanitized settings.
	 * @return string
	 */
	private static function render_button_list( array $items, array $settings ) {
		ob_start();
		?>
		<ul class="mmsm-contact-channel-list">
			<?php foreach ( $items as $item ) : ?>
				<li><?php echo self::render_channel_button( $item, $settings ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></li>
			<?php endforeach; ?>
		</ul>
		<?php

		return (string) ob_get_clean();
	}

	/**
	 * Render an accessible reveal control.
	 *
	 * @param array<int,array<string,mixed>> $items Render-ready items.
	 * @param array<string,mixed>            $settings Sanitized settings.
	 * @param string                         $context Reveal context.
	 * @return string
	 */
	private static function render_reveal( array $items, array $settings, $context ) {
		$menu_id = wp_unique_id( 'mmsm-contact-channels-menu-' );
		$display = isset( $settings['contact_channels_button_display'] ) ? (string) $settings['contact_channels_button_display'] : 'icon_label';
		$label   = self::get_setting_text( $settings, 'contact_channels_primary_label', 'Contact Us' );

		ob_start();
		?>
		<div class="mmsm-contact-channel-reveal">
			<button
				type="button"
				class="mmsm-contact-channel-trigger"
				aria-label="<?php echo esc_attr( $label ); ?>"
				aria-expanded="false"
				aria-controls="<?php echo esc_attr( $menu_id ); ?>"
			>
				<?php echo self::render_default_icon( $settings ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				<?php if ( 'icon_only' !== $display ) : ?>
					<span class="mmsm-contact-channel-label"><?php echo esc_html( $label ); ?></span>
				<?php endif; ?>
			</button>
			<ul id="<?php echo esc_attr( $menu_id ); ?>" class="mmsm-contact-channel-menu mmsm-contact-channel-menu-<?php echo esc_attr( sanitize_key( $context ) ); ?>" hidden>
				<?php foreach ( $items as $item ) : ?>
					<li><?php echo self::render_channel_button( $item, $settings, array( 'mmsm-contact-channel-menu-item' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php

		return (string) ob_get_clean();
	}

	/**
	 * Render one channel link.
	 *
	 * @param array<string,mixed> $item Render-ready item.
	 * @param array<string,mixed> $settings Sanitized settings.
	 * @param array<int,string>   $extra_classes Extra classes.
	 * @return string
	 */
	private static function render_channel_button( array $item, array $settings, array $extra_classes = array() ) {
		$classes = Escaper::classes(
			array_merge(
				array(
					'mmsm-contact-channel-button',
					isset( $item['brand_class'] ) ? (string) $item['brand_class'] : '',
				),
				$extra_classes
			)
		);
		$display = isset( $settings['contact_channels_button_display'] ) ? (string) $settings['contact_channels_button_display'] : 'icon_label';
		$label   = isset( $item['label'] ) ? (string) $item['label'] : '';
		$url     = isset( $item['url'] ) ? (string) $item['url'] : '';
		$target  = ! empty( $item['new_tab'] ) ? ' target="_blank" rel="noreferrer noopener"' : '';

		ob_start();
		?>
		<a
			class="<?php echo esc_attr( $classes ); ?>"
			href="<?php echo esc_url( $url, array( 'http', 'https', 'mailto', 'tel' ) ); ?>"
			aria-label="<?php echo esc_attr( $label ); ?>"
			<?php echo $target; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		>
			<?php if ( 'label_only' !== $display && ! empty( $item['icon_class'] ) ) : ?>
				<span class="<?php echo esc_attr( (string) $item['icon_class'] ); ?>" aria-hidden="true"></span>
			<?php endif; ?>
			<?php if ( 'icon_only' !== $display ) : ?>
				<span class="mmsm-contact-channel-label"><?php echo esc_html( $label ); ?></span>
			<?php endif; ?>
		</a>
		<?php

		return (string) ob_get_clean();
	}

	/**
	 * Render a safe default trigger icon.
	 *
	 * @param array<string,mixed> $settings Sanitized settings.
	 * @return string
	 */
	private static function render_default_icon( array $settings ) {
		if ( 'label_only' === (string) ( $settings['contact_channels_button_display'] ?? '' ) ) {
			return '';
		}

		return '<span class="dashicons dashicons-format-chat" aria-hidden="true"></span>';
	}

	/**
	 * Build container classes.
	 *
	 * @param array<string,mixed> $settings Sanitized settings.
	 * @param array<int,string>   $base Base classes.
	 * @return string
	 */
	private static function get_container_classes( array $settings, array $base ) {
		return Escaper::classes(
			array_merge(
				$base,
				array(
					'mmsm-contact-channels',
					'mmsm-contact-channels-position-' . (string) ( $settings['contact_channels_position'] ?? 'bottom_right' ),
					'mmsm-contact-channels-shape-' . (string) ( $settings['contact_channels_button_shape'] ?? 'rounded' ),
					'mmsm-contact-channels-display-' . (string) ( $settings['contact_channels_button_display'] ?? 'icon_label' ),
					'mmsm-contact-channels-color-' . (string) ( $settings['contact_channels_color_mode'] ?? 'theme' ),
				)
			)
		);
	}

	/**
	 * Build custom color CSS variables.
	 *
	 * @param array<string,mixed> $settings Sanitized settings.
	 * @return string
	 */
	private static function get_custom_style( array $settings ) {
		if ( 'custom' !== (string) ( $settings['contact_channels_color_mode'] ?? '' ) ) {
			return '';
		}

		return Escaper::css_variables(
			array(
				'mmsm-contact-bg'       => (string) ( $settings['contact_channels_background_color'] ?? '' ),
				'mmsm-contact-text'     => (string) ( $settings['contact_channels_text_color'] ?? '' ),
				'mmsm-contact-icon'     => (string) ( $settings['contact_channels_icon_color'] ?? '' ),
				'mmsm-contact-hover-bg' => (string) ( $settings['contact_channels_hover_background_color'] ?? '' ),
				'mmsm-contact-hover-text' => (string) ( $settings['contact_channels_hover_text_color'] ?? '' ),
			)
		);
	}

	/**
	 * Return a text setting with fallback.
	 *
	 * @param array<string,mixed> $settings Sanitized settings.
	 * @param string              $key Setting key.
	 * @param string              $fallback Fallback text.
	 * @return string
	 */
	private static function get_setting_text( array $settings, $key, $fallback ) {
		$value = isset( $settings[ $key ] ) ? trim( (string) $settings[ $key ] ) : '';

		return '' === $value ? $fallback : $value;
	}
}

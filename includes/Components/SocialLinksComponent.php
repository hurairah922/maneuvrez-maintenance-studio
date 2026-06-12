<?php
/**
 * Social links component.
 *
 * @package MaintenanceModeStudio
 */

namespace Maneuvrez\MaintenanceModeStudio\Components;

use Maneuvrez\MaintenanceModeStudio\Support\Escaper;

defined( 'ABSPATH' ) || exit;

/**
 * Renders a list of valid social links.
 */
class SocialLinksComponent implements ComponentInterface {
	/**
	 * Return supported platform labels.
	 *
	 * @return array<string,string>
	 */
	public static function get_platform_labels() {
		return array(
			'facebook'  => 'Facebook',
			'instagram' => 'Instagram',
			'linkedin'  => 'LinkedIn',
			'x'         => 'X',
			'youtube'   => 'YouTube',
			'github'    => 'GitHub',
			'tiktok'    => 'TikTok',
			'threads'   => 'Threads',
			'website'   => 'Website',
			'email'     => 'Email',
			'custom'    => 'Link',
		);
	}

	/**
	 * Return supported icon source labels.
	 *
	 * @return array<string,string>
	 */
	public static function get_icon_source_labels() {
		return array(
			'platform' => __( 'Platform default', 'maintenance-mode-studio' ),
			'library'  => __( 'WordPress icon library', 'maintenance-mode-studio' ),
			'upload'   => __( 'Uploaded image', 'maintenance-mode-studio' ),
		);
	}

	/**
	 * Return installed icon libraries.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public static function get_icon_libraries() {
		$libraries = array(
			'dashicons' => array(
				'label' => __( 'WordPress Dashicons', 'maintenance-mode-studio' ),
				'icons' => self::get_dashicon_choices(),
			),
		);

		/**
		 * Filter available social icon libraries.
		 *
		 * @param array<string,array<string,mixed>> $libraries Available libraries.
		 */
		return apply_filters( 'mmsm_social_icon_libraries', $libraries );
	}

	/**
	 * Return supported Dashicon choices.
	 *
	 * @return array<string,string>
	 */
	public static function get_dashicon_choices() {
		return array(
			'admin-site'   => __( 'Site', 'maintenance-mode-studio' ),
			'admin-links'  => __( 'Link', 'maintenance-mode-studio' ),
			'email-alt'    => __( 'Email', 'maintenance-mode-studio' ),
			'external'     => __( 'External', 'maintenance-mode-studio' ),
			'facebook-alt' => __( 'Facebook', 'maintenance-mode-studio' ),
			'share'        => __( 'Share', 'maintenance-mode-studio' ),
			'share-alt'    => __( 'Share Alt', 'maintenance-mode-studio' ),
			'twitter'      => __( 'Twitter / X', 'maintenance-mode-studio' ),
			'video-alt3'   => __( 'Video', 'maintenance-mode-studio' ),
			'wordpress'    => __( 'WordPress', 'maintenance-mode-studio' ),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_key() {
		return 'social_links';
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_label() {
		return __( 'Social links', 'maintenance-mode-studio' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_supported_zones() {
		return array( 'footer' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_settings_schema() {
		return array(
			array(
				'key'      => 'social_links',
				'label'    => __( 'Social links', 'maintenance-mode-studio' ),
				'type'     => 'repeater',
				'default'  => array(),
				'required' => false,
				'allowed'  => array_keys( self::get_platform_labels() ),
				'fields'   => array(
					array(
						'key' => 'platform',
						'type' => 'select',
					),
					array(
						'key' => 'url',
						'type' => 'text',
					),
					array(
						'key' => 'custom_name',
						'type' => 'text',
					),
					array(
						'key' => 'custom_icon_id',
						'type' => 'number',
					),
					array(
						'key' => 'icon_source',
						'type' => 'select',
					),
					array(
						'key' => 'icon_library',
						'type' => 'select',
					),
					array(
						'key' => 'icon_value',
						'type' => 'text',
					),
					array(
						'key' => 'icon_color',
						'type' => 'color',
					),
					array(
						'key' => 'open_new_tab',
						'type' => 'boolean',
					),
				),
			),
		);
	}

	/**
	 * {@inheritDoc}
	 */
	public function render( array $settings, array $context = array() ) {
		$links = $this->build_links( $settings );

		if ( empty( $links ) ) {
			return '';
		}

		ob_start();
		?>
		<section class="mmsm-component mmsm-component-social" aria-label="<?php echo esc_attr__( 'Social links', 'maintenance-mode-studio' ); ?>">
			<ul class="mmsm-social-list">
				<?php foreach ( $links as $link ) : ?>
					<li>
						<a
							class="mmsm-social-link"
							href="<?php echo esc_url( $link['url'] ); ?>"
							<?php if ( ! empty( $link['new_tab'] ) ) : ?>
								target="_blank" rel="noreferrer noopener"
							<?php endif; ?>
						>
							<span
								class="mmsm-social-icon"
								aria-hidden="true"
								<?php if ( ! empty( $link['icon_color'] ) ) : ?>
									style="<?php echo esc_attr( 'color: ' . $link['icon_color'] . ';' ); ?>"
								<?php endif; ?>
							>
								<?php echo $link['icon']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							</span>
							<span class="mmsm-social-label"><?php echo esc_html( $link['label'] ); ?></span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</section>
		<?php

		return (string) ob_get_clean();
	}

	/**
	 * Build sanitized social link items.
	 *
	 * @param array<string,mixed> $settings Normalized settings.
	 * @return array<int,array<string,mixed>>
	 */
	private function build_links( array $settings ) {
		$links    = array();
		$defaults = self::get_platform_labels();
		$social_links = isset( $settings['social_links'] ) && is_array( $settings['social_links'] ) ? $settings['social_links'] : array();

		foreach ( $social_links as $item ) {
			if ( ! is_array( $item ) ) {
				continue;
			}

			$platform       = isset( $item['platform'] ) ? sanitize_key( $item['platform'] ) : '';
			$url            = isset( $item['url'] ) ? (string) $item['url'] : '';
			$custom_name    = isset( $item['custom_name'] ) ? trim( (string) $item['custom_name'] ) : '';
			$custom_icon_id = isset( $item['custom_icon_id'] ) ? absint( $item['custom_icon_id'] ) : 0;
			$icon_source    = isset( $item['icon_source'] ) ? sanitize_key( $item['icon_source'] ) : '';
			$icon_library   = isset( $item['icon_library'] ) ? sanitize_key( $item['icon_library'] ) : '';
			$icon_value     = isset( $item['icon_value'] ) ? sanitize_key( $item['icon_value'] ) : '';
			$icon_color     = isset( $item['icon_color'] ) ? sanitize_hex_color( (string) $item['icon_color'] ) : '';
			$new_tab        = ! empty( $item['open_new_tab'] );

			if ( '' === $platform || ! isset( $defaults[ $platform ] ) ) {
				continue;
			}

			$url = $this->normalize_item_url( $platform, $url );

			if ( '' === $url ) {
				continue;
			}

			$label = 'custom' === $platform && '' !== $custom_name ? $custom_name : $defaults[ $platform ];

			$links[] = array(
				'icon'    => $this->get_icon_markup( $platform, $label, $custom_icon_id, $icon_source, $icon_library, $icon_value ),
				'icon_color' => $icon_color,
				'label'   => $label,
				'new_tab' => $new_tab && 'email' !== $platform,
				'url'     => $url,
			);
		}

		return $links;
	}

	/**
	 * Normalize a social URL by platform.
	 *
	 * @param string $platform Platform key.
	 * @param string $url Raw URL or email value.
	 * @return string
	 */
	private function normalize_item_url( $platform, $url ) {
		if ( 'email' === $platform ) {
			return Escaper::email_url( $url );
		}

		return Escaper::public_url( $url );
	}

	/**
	 * Return icon markup for the selected source.
	 *
	 * @param string $platform Platform key.
	 * @return string
	 */
	private function get_icon_markup( $platform, $label, $custom_icon_id, $icon_source, $icon_library, $icon_value ) {
		if ( '' === $icon_source && $custom_icon_id > 0 ) {
			$icon_source = 'upload';
		}

		if ( 'upload' === $icon_source ) {
			$custom_icon = $this->get_custom_icon_markup( $custom_icon_id, $label );

			if ( '' !== $custom_icon ) {
				return $custom_icon;
			}
		}

		if ( 'library' === $icon_source ) {
			$library_icon = $this->get_library_icon_markup( $icon_library, $icon_value, $label );

			if ( '' !== $library_icon ) {
				return $library_icon;
			}
		}

		return $this->get_platform_icon( $platform );
	}

	/**
	 * Return a safe uploaded icon image tag for custom links.
	 *
	 * @param int    $attachment_id Attachment id.
	 * @param string $label Accessible label.
	 * @return string
	 */
	private function get_custom_icon_markup( $attachment_id, $label ) {
		if ( $attachment_id <= 0 ) {
			return '';
		}

		$mime_type = get_post_mime_type( $attachment_id );
		$allowed   = array(
			'image/png',
			'image/jpeg',
			'image/webp',
		);

		if ( ! in_array( $mime_type, $allowed, true ) ) {
			return '';
		}

		$icon_url = wp_get_attachment_url( $attachment_id );

		if ( empty( $icon_url ) ) {
			return '';
		}

		return sprintf(
			'<img src="%1$s" alt="%2$s" />',
			esc_url( $icon_url ),
			esc_attr( $label )
		);
	}

	/**
	 * Return an icon from an installed icon library.
	 *
	 * @param string $library Library key.
	 * @param string $icon_value Icon value within the library.
	 * @param string $label Accessible label.
	 * @return string
	 */
	private function get_library_icon_markup( $library, $icon_value, $label ) {
		$libraries = self::get_icon_libraries();

		if ( ! isset( $libraries[ $library ] ) ) {
			return '';
		}

		$icons = isset( $libraries[ $library ]['icons'] ) && is_array( $libraries[ $library ]['icons'] )
			? $libraries[ $library ]['icons']
			: array();

		if ( ! isset( $icons[ $icon_value ] ) ) {
			return '';
		}

		if ( 'dashicons' === $library ) {
			return sprintf(
				'<span class="dashicons dashicons-%1$s" aria-label="%2$s"></span>',
				esc_attr( $icon_value ),
				esc_attr( $label )
			);
		}

		return '';
	}

	/**
	 * Return built-in platform icon markup.
	 *
	 * @param string $platform Platform key.
	 * @return string
	 */
	private function get_platform_icon( $platform ) {
		$icons = array(
			'facebook'  => '<svg viewBox="0 0 24 24" role="img" focusable="false"><circle cx="12" cy="12" r="10"></circle><text x="12" y="16" text-anchor="middle">f</text></svg>',
			'instagram' => '<svg viewBox="0 0 24 24" role="img" focusable="false"><rect x="4" y="4" width="16" height="16" rx="4"></rect><circle cx="12" cy="12" r="3.5"></circle><circle cx="17" cy="7" r="1"></circle></svg>',
			'linkedin'  => '<svg viewBox="0 0 24 24" role="img" focusable="false"><rect x="4" y="4" width="16" height="16" rx="3"></rect><text x="12" y="16" text-anchor="middle">in</text></svg>',
			'x'         => '<svg viewBox="0 0 24 24" role="img" focusable="false"><path d="M6 5L18 19M18 5L6 19"></path></svg>',
			'youtube'   => '<svg viewBox="0 0 24 24" role="img" focusable="false"><rect x="3" y="6" width="18" height="12" rx="4"></rect><path d="M10 9L16 12L10 15Z"></path></svg>',
			'github'    => '<svg viewBox="0 0 24 24" role="img" focusable="false"><circle cx="12" cy="12" r="10"></circle><text x="12" y="16" text-anchor="middle">gh</text></svg>',
			'tiktok'    => '<svg viewBox="0 0 24 24" role="img" focusable="false"><path d="M13 5V15A3 3 0 1 1 10 12"></path><path d="M13 5C14 7 16 8 18 8"></path></svg>',
			'threads'   => '<svg viewBox="0 0 24 24" role="img" focusable="false"><circle cx="12" cy="12" r="9"></circle><text x="12" y="16" text-anchor="middle">@</text></svg>',
			'website'   => '<svg viewBox="0 0 24 24" role="img" focusable="false"><circle cx="12" cy="12" r="9"></circle><path d="M3 12H21M12 3C14.8 5.4 16.4 8.6 16.4 12C16.4 15.4 14.8 18.6 12 21M12 3C9.2 5.4 7.6 8.6 7.6 12C7.6 15.4 9.2 18.6 12 21"></path></svg>',
			'email'     => '<svg viewBox="0 0 24 24" role="img" focusable="false"><rect x="3" y="6" width="18" height="12" rx="2"></rect><path d="M4 8L12 13L20 8"></path></svg>',
			'custom'    => '<svg viewBox="0 0 24 24" role="img" focusable="false"><path d="M10 14L14 10"></path><path d="M8 16L6 18A3 3 0 1 1 2 14L4 12"></path><path d="M16 8L18 6A3 3 0 1 1 22 10L20 12"></path></svg>',
		);

		if ( ! isset( $icons[ $platform ] ) ) {
			$platform = 'custom';
		}

		return wp_kses(
			$icons[ $platform ],
			array(
				'img'    => array(
					'src' => true,
					'alt' => true,
				),
				'span'   => array(
					'class' => true,
					'aria-label' => true,
				),
				'svg'    => array(
					'viewBox'   => true,
					'role'      => true,
					'focusable' => true,
				),
				'path'   => array(
					'd' => true,
				),
				'circle' => array(
					'cx' => true,
					'cy' => true,
					'r'  => true,
				),
				'rect'   => array(
					'x'      => true,
					'y'      => true,
					'width'  => true,
					'height' => true,
					'rx'     => true,
				),
				'text'   => array(
					'x'           => true,
					'y'           => true,
					'text-anchor' => true,
				),
			)
		);
	}
}

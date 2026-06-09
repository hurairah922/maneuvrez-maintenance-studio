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
	 * {@inheritDoc}
	 */
	public function get_key() {
		return 'social_links';
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_label() {
		return __( 'Social links', MMSM_TEXT_DOMAIN );
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
		$schema    = array();
		$platforms = self::get_platform_labels();

		for ( $index = 1; $index <= 4; $index++ ) {
			$schema[] = array(
				'key'      => 'social_item_' . $index . '_platform',
				'label'    => sprintf( __( 'Social item %d platform', MMSM_TEXT_DOMAIN ), $index ),
				'type'     => 'select',
				'default'  => '',
				'required' => false,
				'allowed'  => array_keys( $platforms ),
			);
			$schema[] = array(
				'key'      => 'social_item_' . $index . '_label',
				'label'    => sprintf( __( 'Social item %d label', MMSM_TEXT_DOMAIN ), $index ),
				'type'     => 'text',
				'default'  => '',
				'required' => false,
			);
			$schema[] = array(
				'key'      => 'social_item_' . $index . '_url',
				'label'    => sprintf( __( 'Social item %d URL', MMSM_TEXT_DOMAIN ), $index ),
				'type'     => 'text',
				'default'  => '',
				'required' => false,
			);
			$schema[] = array(
				'key'      => 'social_item_' . $index . '_new_tab',
				'label'    => sprintf( __( 'Social item %d open in new tab', MMSM_TEXT_DOMAIN ), $index ),
				'type'     => 'checkbox',
				'default'  => 0,
				'required' => false,
			);
		}

		return $schema;
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
		<section class="mmsm-component mmsm-component-social" aria-label="<?php echo esc_attr__( 'Social links', MMSM_TEXT_DOMAIN ); ?>">
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
							<span class="mmsm-social-icon" aria-hidden="true">
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

		for ( $index = 1; $index <= 4; $index++ ) {
			$platform = isset( $settings[ 'social_item_' . $index . '_platform' ] ) ? sanitize_key( $settings[ 'social_item_' . $index . '_platform' ] ) : '';
			$label    = isset( $settings[ 'social_item_' . $index . '_label' ] ) ? trim( (string) $settings[ 'social_item_' . $index . '_label' ] ) : '';
			$url      = isset( $settings[ 'social_item_' . $index . '_url' ] ) ? (string) $settings[ 'social_item_' . $index . '_url' ] : '';
			$new_tab  = ! empty( $settings[ 'social_item_' . $index . '_new_tab' ] );

			if ( '' === $platform || ! isset( $defaults[ $platform ] ) ) {
				continue;
			}

			$url = $this->normalize_item_url( $platform, $url );

			if ( '' === $url ) {
				continue;
			}

			if ( '' === $label ) {
				$label = $defaults[ $platform ];
			}

			$links[] = array(
				'icon'    => $this->get_platform_icon( $platform ),
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
	 * Return controlled inline SVG icon markup.
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

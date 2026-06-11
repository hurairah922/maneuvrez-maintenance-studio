<?php
/**
 * Default public template.
 *
 * @var array<string,mixed> $context Template context.
 * @var array<string,mixed> $settings Normalized settings.
 * @var Maneuvrez\MaintenanceModeStudio\Frontend\TemplateRenderer $renderer Template renderer.
 *
 * @package MaintenanceModeStudio
 */

defined( 'ABSPATH' ) || exit;
?>
<!doctype html>
<html lang="<?php echo esc_attr( $context['language'] ); ?>">
<head>
	<meta charset="<?php echo esc_attr( $context['charset'] ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<title><?php echo esc_html( $context['document_title'] ); ?></title>
	<meta name="robots" content="noindex,nofollow" />
	<?php wp_print_styles( $context['assets']['styles'] ); ?>
</head>
<body class="mmsm-maintenance-page">
	<div
		class="<?php echo esc_attr( $context['shell_class'] ); ?>"
		style="<?php echo esc_attr( $context['shell_style'] ); ?>"
		data-theme-mode="<?php echo esc_attr( $settings['theme_mode'] ); ?>"
	>
		<div class="mmsm-shell-backdrop" aria-hidden="true"></div>
		<main class="mmsm-layout" aria-labelledby="mmsm-title">
			<div class="mmsm-panel mmsm-panel-main">
				<div class="mmsm-card">
					<p class="mmsm-mode-badge"><?php echo esc_html( $context['mode_label'] ); ?></p>
					<?php echo $renderer->render_zone( 'main', $settings, $context ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
			</div>
			<?php if ( ! empty( $settings['show_footer_section'] ) ) : ?>
				<footer class="mmsm-panel mmsm-panel-footer">
					<div class="mmsm-footer-meta">
						<p class="mmsm-site-name"><?php echo esc_html( $context['site_name'] ); ?></p>
						<p class="mmsm-site-copy"><?php echo esc_html__( 'Thanks for your patience while we fine-tune a few things.', 'maintenance-mode-studio' ); ?></p>
					</div>
					<?php echo $renderer->render_zone( 'footer', $settings, $context ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</footer>
			<?php endif; ?>
		</main>
	</div>
	<?php wp_print_scripts( $context['assets']['scripts'] ); ?>
</body>
</html>

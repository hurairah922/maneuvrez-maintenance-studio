<?php
/**
 * Default public maintenance template.
 *
 * @var array<string,string> $context Template context.
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
	<title><?php echo esc_html( $context['title'] ); ?></title>
	<meta name="robots" content="noindex,nofollow" />
	<link rel="stylesheet" href="<?php echo esc_url( $context['styles_url'] ); ?>" />
</head>
<body class="mmsm-maintenance-page">
	<main class="mmsm-shell" aria-labelledby="mmsm-title">
		<section class="mmsm-card">
			<p class="mmsm-badge"><?php echo esc_html( $context['status'] ); ?></p>
			<h1 id="mmsm-title"><?php echo esc_html( $context['title'] ); ?></h1>
			<p class="mmsm-message"><?php echo esc_html( $context['message'] ); ?></p>

			<div class="mmsm-orb-shell" aria-hidden="true">
				<div class="mmsm-orb"></div>
				<div class="mmsm-orb-ring mmsm-orb-ring-one"></div>
				<div class="mmsm-orb-ring mmsm-orb-ring-two"></div>
			</div>

			<div class="mmsm-actions">
				<a class="mmsm-button" href="<?php echo esc_url( $context['login_url'] ); ?>">
					<?php echo esc_html__( 'Log in', MMSM_TEXT_DOMAIN ); ?>
				</a>
			</div>
		</section>

		<footer class="mmsm-footer">
			<span><?php echo esc_html( $context['site_name'] ); ?></span>
		</footer>
	</main>
	<script src="<?php echo esc_url( $context['script_url'] ); ?>" defer></script>
</body>
</html>

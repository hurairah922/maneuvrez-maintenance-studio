=== Maintenance Mode Studio ===
Contributors: abuhurarrah
Tags: maintenance mode, coming soon, maintenance page
Requires at least: 6.4
Tested up to: 6.5
Requires PHP: 8.0
Stable tag: 0.1.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Create interactive maintenance, coming soon, launch, and private site pages with a modern default experience.

== Description ==

Maintenance Mode Studio helps WordPress site owners replace boring maintenance screens with a polished, responsive landing page.

This Phase 1 foundation includes:

* a safe plugin shell
* a settings page with a maintenance mode toggle
* administrator bypass behavior
* a default responsive maintenance page for logged-out visitors

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/maintenance-mode-studio` directory, or install the plugin through the WordPress plugins screen.
2. Activate the plugin through the `Plugins` screen in WordPress.
3. Open `Settings > Maintenance Mode Studio` to enable maintenance mode.

== Frequently Asked Questions ==

= Does this block administrators? =

No. Logged-in administrators keep normal access to the site.

= Are login and API requests still available? =

Yes. The Phase 1 shell keeps `wp-login.php`, REST, AJAX, cron, and WP-CLI requests accessible.

== Changelog ==

= 0.1.0 =

* Initial Phase 1 plugin shell.

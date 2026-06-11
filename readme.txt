=== Maintenance Mode Studio ===
Contributors: hurairah922
Tags: maintenance mode, coming soon, maintenance page
Requires at least: 6.4
Tested up to: 7.0
Requires PHP: 8.0
Stable tag: 0.1.2
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

= 0.1.2 =

* fix color variable mapping so saved design colors apply consistently on the frontend
* remove the duplicate settings saved notice from the admin flow
* preserve social links when saving and add support for uploaded or Dashicon-based social icons
* add a footer section visibility toggle for the public template
* complete the Phase 3 Plugin Check pass for i18n literals, nonce verification, REST compatibility, request sanitization, and translation folder alignment

= 0.1.1 =

* add the Phase 3 registry-driven template and component system
* add design color controls, social link management, and responsive default template rendering
* add hero, status/progress, contact reveal, social links, and login frontend components

= 0.1.0 =

* Initial Phase 1 plugin shell.

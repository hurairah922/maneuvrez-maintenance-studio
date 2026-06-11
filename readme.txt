=== Maintenance Mode Studio ===
Contributors: hurairah922
Tags: maintenance mode, coming soon, maintenance page, admin bypass, social links
Requires at least: 6.4
Tested up to: 7.0
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Create a responsive maintenance or coming soon page with custom copy, colors, contact details, social links, login access, and administrator bypass.

== Description ==

Maintenance Mode Studio helps WordPress site owners replace the default downtime screen with a polished maintenance or coming soon page.

This release includes:

* custom page title and message settings
* maintenance mode and coming soon mode
* customizable colors for the default template
* contact details, social links, and login access controls
* administrator bypass behavior
* a responsive default public template

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/maintenance-mode-studio` directory, or install the plugin through the WordPress plugins screen.
2. Activate the plugin through the `Plugins` screen in WordPress.
3. Open `Settings > Maintenance Mode Studio` to enable maintenance mode.

== Frequently Asked Questions ==

= Does this block administrators? =

No. Logged-in administrators keep normal access to the site.

= Are login and API requests still available? =

Yes. The plugin keeps `wp-login.php`, REST, AJAX, cron, and WP-CLI requests accessible.

== Changelog ==

= 1.0.0 =

* first WordPress.org submission-ready release
* add a responsive default maintenance and coming soon page template
* add configurable page title, message, hero eyebrow, and action button fields
* add customizable colors for background, surface, text, links, borders, and buttons
* add contact details, status/progress, login button, and footer visibility controls
* add social links with platform defaults, WordPress Dashicon choices, and uploaded image icons
* preserve administrator access and keep login, REST, AJAX, cron, and WP-CLI requests available
* improve settings sanitization, request validation, and Plugin Check compatibility
* add an uninstall preference so site owners can choose whether settings are removed on plugin deletion
* clean the release package for WordPress.org submission by excluding development-only files and unused assets

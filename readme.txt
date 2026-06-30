=== Maneuvrez Maintenance Studio ===
Contributors: hurairah922
Tags: maintenance mode, coming soon, maintenance page, admin bypass, social links
Requires at least: 6.4
Tested up to: 7.0
Stable tag: 1.0.2
Requires PHP: 8.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Create a responsive maintenance or coming soon page with custom copy, colors, contact details, social links, login access, testing bypasses, optional hidden login routing, and administrator bypass.

== Description ==

Maneuvrez Maintenance Studio helps WordPress site owners replace the default downtime screen with a polished maintenance or coming soon page.

This release includes:

* custom page title and message settings
* maintenance mode and coming soon mode
* customizable colors for the default template
* contact details, social links, login access controls, optional testing bypasses, and an optional custom login URL
* administrator bypass behavior
* a responsive default public template

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/maneuvrez-maintenance-studio` directory, or install the plugin through the WordPress plugins screen.
2. Activate the plugin through the `Plugins` screen in WordPress.
3. Open `Settings > Maneuvrez Maintenance Studio` to enable maintenance mode.

== Frequently Asked Questions ==

= Does this block administrators? =

No. Logged-in administrators keep normal access to the site.

= Are login and API requests still available? =

Yes. REST, AJAX, cron, and WP-CLI requests remain accessible. You can optionally move the public login screen to a custom URL, and the plugin keeps required logout and password-recovery flows working.

= What should I do before enabling a custom login URL? =

Save the generated login URL somewhere safe first. If you lose it, disable the plugin through FTP or WP-CLI to restore the default WordPress login entry points.

== Changelog ==

= 1.0.2 =

* add an optional custom public login URL that loads the real WordPress login screen from a saved slug
* hide direct `wp-login.php` and `/wp-admin/` login entry points from logged-out visitors while preserving required logout and password-recovery flows
* add Advanced-tab controls, preview messaging, and slug sanitization rules for the custom login feature
* render blocked `/wp-admin/` requests through the active theme's frontend 404 template with the required block styles
* fix custom login routing warnings by loading the real WordPress login flow with the expected core login globals

= 1.0.1 =

* add an optional query-parameter maintenance bypass for temporary frontend testing
* add a public URL allowlist so exact frontend paths can stay visible during maintenance mode
* add a compact Advanced-tab bypass UI with a random generator and live homepage preview
* sanitize bypass keys, values, and allowlist paths while rejecting external or protected routes

= 1.0.0 =

* first WordPress.org submission-ready release
* add a responsive default maintenance and coming soon page template
* add configurable page title, message, hero eyebrow, and action button fields
* add customizable colors for background, surface, text, links, borders, and buttons
* add contact details, status/progress, login button, and footer visibility controls
* add social links with platform defaults, WordPress Dashicon choices, uploaded image icons, and per-icon color controls
* preserve administrator access and keep login, REST, AJAX, cron, and WP-CLI requests available
* add an optional plugins-screen feedback prompt for deactivate and delete actions
* add a direct Settings link in the Installed Plugins list for faster access to plugin settings
* improve settings sanitization, request validation, and Plugin Check compatibility
* add an uninstall preference so site owners can choose whether settings are removed on plugin deletion
* store uninstall feedback locally in WordPress and keep deactivation and deletion flows non-blocking
* fix the uninstall preference sync so saving settings does not trigger recursive option updates
* clean the release package for WordPress.org submission by excluding development-only files and unused assets
* rename the plugin to Maneuvrez Maintenance Studio across the public branding and project docs
* refresh the completed spec archive to reflect the current project shape

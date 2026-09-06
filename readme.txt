=== Maneuvrez Maintenance Studio ===
Contributors: hurairah922
Tags: maintenance mode, coming soon, maintenance page, contact buttons, social links
Requires at least: 6.4
Tested up to: 7.0
Stable tag: 1.2.1
Requires PHP: 8.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Create a responsive maintenance or coming soon page with custom design, contact buttons, social links, login access, and bypass controls.

== Description ==

Maneuvrez Maintenance Studio helps WordPress site owners replace the default downtime screen with a polished maintenance or coming soon page.

Core features include:

* custom page title and message settings
* maintenance mode and coming soon mode
* customizable colors for the default template with live design preview
* Contact Channels for WhatsApp, Messenger, phone, email, directions, and custom links
* optional live-site floating contact button after maintenance mode is turned off
* social links with platform defaults, WordPress Dashicons, uploads, and icon-only display
* login access controls, optional testing bypasses, and an optional custom login URL
* administrator bypass behavior for logged-in site managers
* a configurable launch countdown with theme-aware or custom colors, motion choices, and completion actions
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

= Do Contact Channels load third-party chat widgets? =

No. Contact Channels render normal links for services such as WhatsApp, Messenger, phone, email, maps, and custom URLs. The plugin does not load third-party chat SDKs, tracking pixels, or embedded widgets for those buttons.

= Can Contact Channels appear after maintenance mode is off? =

Yes. You can show an optional floating contact button on the live site, and choose whether logged-in users or administrators should see it.

= What happens when the countdown finishes? =

Choose whether the countdown stays at zero, hides, shows a completion message, or turns off maintenance mode. WordPress schedules a one-time expiry event, and the first normal site request after the target also applies the selected action if WP-Cron has not run yet.

= What should I do before enabling a custom login URL? =

Save the generated login URL somewhere safe first. If you lose it, disable the plugin through FTP or WP-CLI to restore the default WordPress login entry points.

== Changelog ==

= 1.2.1 =

* update public plugin author branding to Maneuvrez

= 1.2.0 =

* add a configurable maintenance-page countdown using the WordPress site timezone
* add selectable days, hours, minutes, and seconds with responsive, accessible output
* add theme-aligned colors, optional custom countdown colors, and reduced-motion-aware digit or timer-card animations
* add completion actions to hold at zero, hide the countdown, show a message, or turn off maintenance mode
* add one-time expiry scheduling, stale-event cleanup, request-time fallback, and a simple admin scheduling health check
* add a live countdown settings preview that follows the entered target time and remains visible beside the settings on wide screens

= 1.1.0 =

* add a dedicated Contact Channels settings tab with WhatsApp, Messenger, phone, email, directions, and custom link rows
* add maintenance-page and live-site display controls for Contact Channels, including inside-page buttons, reveal menus, and floating buttons
* add live Contact Channels previews that update while changing placement, labels, colors, icons, destinations, and button style
* add floating button positions for top left, top right, bottom left, and bottom right
* add grouped normal and hover color controls for Contact Channels buttons, including background, text, and icon colors
* add country code pairing for phone and WhatsApp rows to avoid duplicated country codes when settings are saved again
* add WordPress Dashicon choices for Contact Channels and social links so icons render from assets available in WordPress
* add social link display options for icon-only or icon-plus-label output while keeping labels available to screen readers
* improve the Social Links admin builder with clearer rows, icon previews, destination states, and grouped icon options
* redesign the settings screen with a sidebar-style navigation layout, clearer panels, and a live Design-tab color preview
* fix single-channel floating Contact Channels buttons so the configured floating button label is used on the live site

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

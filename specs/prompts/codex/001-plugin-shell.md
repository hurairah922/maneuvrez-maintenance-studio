# Codex Task: Plugin Shell

## Branch

Create or work on this branch:

    feature/001-plugin-shell

## Goal

Create the first working WordPress plugin shell for Maneuvrez Maintenance Studio.

## Project Context

Plugin:

    Maneuvrez Maintenance Studio

Extended title:

    Maneuvrez Maintenance Studio - Coming Soon, Games, Forms and Interactive Pages

Publisher:

    Abu Hurarrah

Publisher domain:

    https://abuhurarrah.com

Creator credit:

    Abu Hurarrah

Creator domain:

    https://abuhurarrah.com

Contact email:

    hello@abuhurarrah.com

Plugin slug:

    maneuvrez-maintenance-studio

Prefix:

    mmsm_

PHP namespace:

    Maneuvrez\MaintenanceModeStudio

Text domain:

    maneuvrez-maintenance-studio

REST namespace:

    mmsm/v1

License:

    GPL-2.0-or-later

## Required Docs To Follow

Read these first:

    docs/constitution/product.md
    docs/constitution/technical.md
    docs/constitution/design.md
    docs/constitution/security.md
    docs/roadmap/phases.md
    specs/features/active.md

## Task

Create the first working plugin skeleton from scratch.

## Files To Create

Create these files:

    maneuvrez-maintenance-studio.php
    readme.txt
    uninstall.php
    includes/Plugin.php
    includes/Activator.php
    includes/Deactivator.php
    includes/Admin/Admin.php
    includes/Frontend/MaintenanceRouter.php
    includes/Frontend/TemplateRenderer.php
    includes/Security/Sanitizer.php
    public/templates/default.php
    public/assets/public.css
    public/assets/public.js
    composer.json
    package.json
    phpcs.xml.dist

## Requirements

The plugin must:

- Activate without fatal errors.
- Deactivate without fatal errors.
- Use namespace `Maneuvrez\MaintenanceModeStudio`.
- Use prefix `mmsm_`.
- Use text domain `maneuvrez-maintenance-studio`.
- Define constants for version, file, basename, path, URL, and text domain.
- Register activation and deactivation hooks.
- Load the text domain.
- Add an admin page.
- Add a basic maintenance mode setting.
- Save settings securely.
- Render a basic maintenance page for logged-out visitors when enabled.
- Allow logged-in admins to bypass maintenance mode.
- Keep `wp-login.php` accessible.
- Avoid blocking REST, AJAX, cron, and WP-CLI requests.
- Escape all output.
- Sanitize all input.
- Use nonce checks for settings actions.
- Use capability checks for admin actions.
- Use translation functions for visible strings.
- Add important comments for future Pro extension points.

## Plugin Header

Use this header in `maneuvrez-maintenance-studio.php`:

    Plugin Name: Maneuvrez Maintenance Studio
    Plugin URI: https://abuhurarrah.com/plugins/maneuvrez-maintenance-studio
    Description: Create interactive maintenance, coming soon, launch, and private site pages with games, forms, contact options, social links, login access, and modern responsive animations.
    Version: 0.1.0
    Author: Abu Hurarrah
    Author URI: https://abuhurarrah.com
    Text Domain: maneuvrez-maintenance-studio
    Domain Path: /languages
    License: GPL-2.0-or-later
    License URI: https://www.gnu.org/licenses/gpl-2.0.html

Also include this comment:

    Created by Abu Hurarrah.
    Creator URI: https://abuhurarrah.com

## Out Of Scope

Do not implement:

- Games
- Forms
- Surveys
- Drag-and-drop builder
- Visual preview builder
- Leaderboard
- Secret bypass links
- Custom CSS editor
- Custom JS editor
- reCAPTCHA
- Email integrations
- Pro licensing
- Payments

## Acceptance Criteria

- Plugin appears in WordPress admin.
- Plugin activates cleanly.
- Plugin deactivates cleanly.
- Admin page loads.
- Basic maintenance mode setting can be saved.
- Logged-in admins are not blocked.
- Logged-out visitors see a default maintenance page when enabled.
- Login page remains accessible.
- REST requests are not blocked.
- AJAX requests are not blocked.
- Cron requests are not blocked.
- WP-CLI requests are not blocked.
- All output is escaped.
- All input is sanitized.
- Settings actions use nonce checks.
- Admin actions use capability checks.
- Text strings are translation-ready.

# Review Task: Plugin Shell

## Goal

Review the Phase 1 plugin shell implementation for Maneuvrez Maintenance Studio.

## Required Files To Review

Review these files:

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
    specs/features/active.md

## Check Against Docs

Use these docs as the source of truth:

    docs/constitution/product.md
    docs/constitution/technical.md
    docs/constitution/design.md
    docs/constitution/security.md
    docs/roadmap/phases.md
    specs/features/active.md

## Review Checklist

Check that:

- The plugin activates without fatal errors.
- The plugin deactivates without fatal errors.
- The plugin uses namespace `Maneuvrez\MaintenanceModeStudio`.
- The plugin uses prefix `mmsm_`.
- The plugin uses text domain `maneuvrez-maintenance-studio`.
- Plugin constants are defined safely.
- Activation and deactivation hooks are registered correctly.
- The admin page loads only for users with the right capability.
- Settings are saved with nonce checks.
- Settings are sanitized before saving.
- Public output is escaped.
- Logged-in admins bypass maintenance mode.
- `wp-login.php` remains accessible.
- REST requests are not blocked.
- AJAX requests are not blocked.
- Cron requests are not blocked.
- WP-CLI requests are not blocked.
- The default public template is responsive.
- No games, forms, drag builder, Pro features, or external integrations were added early.

## Output Format

Return:

- Pass or fail
- Critical issues
- Security issues
- WordPress.org compliance issues
- Suggested fixes
- Final recommendation

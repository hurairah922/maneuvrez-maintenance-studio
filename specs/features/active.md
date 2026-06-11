## WordPress.org Submission Readiness Pass

This active spec tracks the final cleanup pass required before submitting Maintenance Mode Studio to WordPress.org.

The goal is to make the shipped plugin package production-ready without changing the current feature set.

## Scope

This pass is limited to:

* truthful public plugin metadata and readme copy
* uninstall cleanup for the real settings option
* JavaScript i18n for admin-facing UI strings
* removal of SVG social icon upload support until dedicated sanitization exists
* removal of internal phase wording from public-facing plugin files
* release ZIP cleanup so only production plugin files are shipped
* final validation for syntax, packaging, and known Plugin Check blockers

This pass must not add new product features.

## Product Copy Rules

Public plugin copy must describe only features that exist in the submitted ZIP.

Do not claim unshipped or future features in:

* `maintenance-mode-studio.php`
* `readme.txt`
* public admin UI copy
* any file that will ship in the release ZIP

The following feature claims are deferred unless they are fully implemented:

* games
* feedback forms
* surveys
* advanced launch pages
* private site mode
* other interactive experiences not present in the runtime

## Packaging Rules

The release ZIP must contain only runtime plugin files and assets required for installation.

The package must exclude internal and development-only files such as:

* `README.md`
* `specs/`
* `docs/`
* `tests/`
* `.git/`
* `.github/`
* `node_modules/`
* `vendor/`
* build scripts
* reports, logs, and generated archives
* unused public assets

The final ZIP must be created from a reliable exclusion list and verified after creation.

## Security And Review Expectations

This pass must preserve the existing escaping, sanitization, capability checks, and nonce verification work.

SVG social icon upload and render support is deferred until a future release with proper SVG sanitization. The current release must allow only raster image uploads for custom social icons.

Admin JavaScript strings must be localized using WordPress JS i18n.

## Files To Inspect

Primary files for this pass:

* `maintenance-mode-studio.php`
* `readme.txt`
* `README.md`
* `uninstall.php`
* `includes/Admin/Admin.php`
* `admin/assets/admin.js`
* `includes/Components/SocialLinksComponent.php`
* `includes/Security/Sanitizer.php`
* `build-zip.sh`
* `.distignore`

## Exit Criteria

This pass is complete when all of the following are true:

* `uninstall.php` removes the real `maintenance_mode_settings` option and keeps intended legacy cleanup.
* The main plugin header description matches shipped features only.
* `readme.txt` matches shipped features only and uses five or fewer tags.
* Admin JavaScript strings are localized through WordPress JS i18n with `wp-i18n` and `wp_set_script_translations()`.
* SVG is removed from allowed social icon MIME types, validation, and UI copy.
* Internal phase wording is removed from public-facing plugin files.
* The release ZIP excludes `README.md`, specs, prompts, tests, `.git`, node/vendor folders, logs, generated reports, unused public assets, and other development-only files.
* Plugin Check shows no blocking i18n, security, escaping, sanitization, or packaging issues in the updated scope.
* PHP syntax checks pass on all PHP files.
* The final ZIP is structurally ready for WordPress admin upload and should be tested in a WordPress install before submission.

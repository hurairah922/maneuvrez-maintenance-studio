# Codex Prompt: 003 — WordPress.org Submission Readiness Fixes

## Background

Maintenance Mode Studio is close to WordPress.org submission, but it still needs a final cleanup pass to remove review risks and tighten the release package.

This prompt exists to guide a safe submission-readiness pass without adding new features.

## Scope

This pass is limited to:

* truthful public plugin metadata and readme copy
* uninstall cleanup for the real settings option
* admin JavaScript localization through WordPress JS i18n
* removal of SVG social icon upload support for this release
* removal of internal phase wording from public-facing shipped files
* release ZIP cleanup so it contains only production plugin files
* validation for syntax, packaging, and known submission blockers

Do not introduce new product features or roadmap items.

## Files To Inspect

```text
maintenance-mode-studio.php
readme.txt
README.md
uninstall.php
includes/Admin/Admin.php
admin/assets/admin.js
includes/Components/SocialLinksComponent.php
includes/Security/Sanitizer.php
includes/Plugin.php
build-zip.sh
.distignore
specs/features/active.md
specs/prompts/codex/003-wordpress-org-submission-readiness.md
```

## Files Likely To Change

```text
maintenance-mode-studio.php
readme.txt
uninstall.php
includes/Admin/Admin.php
admin/assets/admin.js
includes/Components/SocialLinksComponent.php
includes/Security/Sanitizer.php
build-zip.sh
.distignore
README.md
package.json
composer.json
specs/features/active.md
specs/prompts/codex/003-wordpress-org-submission-readiness.md
```

## Exact Implementation Requirements

### 1. Fix uninstall cleanup

Delete the active settings option during uninstall:

```php
delete_option( 'maintenance_mode_settings' );
```

Keep the existing legacy cleanup for:

```php
delete_option( 'mmsm_settings' );
delete_option( 'mmsm_version' );
```

Do not remove unrelated options.

### 2. Make public plugin claims truthful

The plugin header and `readme.txt` must describe only features that ship today.

Use this plugin description unless the implemented feature set changes:

```php
Description: Create a responsive maintenance or coming soon page with custom copy, colors, contact details, social links, login access, and administrator bypass.
```

Remove or rewrite claims about:

* games
* forms
* surveys
* advanced launch pages
* private site mode
* other interactive or future experiences not present in the runtime

Keep total `readme.txt` tags at five or fewer.

### 3. Remove internal phase wording from public-facing files

Public-facing shipped files must not mention internal development phases or roadmap language.

Search for and rewrite terms such as:

```text
Phase 1
Phase 2
Phase 3
foundation
roadmap
planned
coming later
future feature
```

This applies to shipped files such as:

* `maintenance-mode-studio.php`
* `readme.txt`
* admin UI copy
* frontend UI copy

Internal docs in `specs/` may still refer to phases.

### 4. Localize admin JavaScript strings

User-facing strings in admin JavaScript must use WordPress JS i18n.

When enqueueing the settings script, include:

```php
array( 'jquery', 'wp-color-picker', 'wp-i18n' )
```

Register script translations:

```php
wp_set_script_translations(
	'mmsm-admin-settings-script',
	'maintenance-mode-studio'
);
```

In JS, use:

```js
const { __ } = wp.i18n;
```

Localize strings such as:

```js
__( 'Use icon', 'maintenance-mode-studio' )
__( 'Choose social icon', 'maintenance-mode-studio' )
__( 'Choose a PNG, JPG, or WEBP image.', 'maintenance-mode-studio' )
```

### 5. Remove SVG upload support for this release

SVG social icon uploads and rendering are deferred until there is dedicated SVG sanitization.

Do not allow:

```text
image/svg+xml
```

Allow only:

```text
image/png
image/jpeg
image/webp
```

Update UI copy and validation text so they no longer mention SVG.

### 6. Exclude development and unused files from the release ZIP

The submission ZIP must not include:

```text
README.md
specs/
docs/
tests/
.git/
.github/
node_modules/
vendor/
build-zip.sh
package.json
composer.json
phpcs.xml.dist
phpunit.xml.dist
logs
generated archives
unused public assets
```

If `public/assets/public.css` and `public/assets/public.js` are unused, do not ship them in the release ZIP.

Use a reliable exclusion list and verify the ZIP contents after creation.

### 7. Metadata hygiene

`Plugin URI` must be omitted unless there is a real, plugin-specific landing page.

`Author URI` may point to the author website if it is real.

Keep `Domain Path: /languages` only if the `languages/` directory exists.

`README.md`, `package.json`, and `composer.json` should not contradict the shipped feature set, even if they are excluded from the release ZIP.

## Review Checklist

* The active settings option is deleted on uninstall.
* The plugin header and `readme.txt` claim only implemented features.
* No shipped public-facing file mentions internal phases.
* Admin JS strings are localized with `wp.i18n.__`.
* `wp-i18n` is listed as a script dependency.
* `wp_set_script_translations()` is called for the admin script.
* SVG MIME support is removed from custom social icons.
* Validation messages mention only PNG, JPG, and WEBP.
* The release ZIP excludes repo, tooling, planning, and unused runtime files.
* `README.md` is not shipped in the release ZIP.

## Validation Commands

Run these from the plugin root:

```bash
find . -name '*.php' -print0 | xargs -0 -n1 php -l
```

```bash
rg -n "image/svg\\+xml|Choose an SVG|Phase 1|Phase 2|Phase 3|games|surveys|feedback forms|private site|Plugin URI" \
  maintenance-mode-studio.php readme.txt README.md includes admin build-zip.sh composer.json package.json
```

```bash
bash build-zip.sh
```

```bash
unzip -l ../maintenance-mode-studio.zip
```

If a local WordPress install is available, also test the generated ZIP through the WordPress admin plugin upload screen before submission.

## Acceptance Criteria

The pass is complete when:

* `uninstall.php` removes `maintenance_mode_settings` and preserves intended legacy cleanup.
* Plugin metadata uses truthful shipped-feature copy and omits `Plugin URI` unless a real plugin page exists.
* `readme.txt` is aligned with the shipped feature set and uses no more than five tags.
* Admin JS user-facing strings are localized through WordPress JS i18n.
* SVG social icon upload support is fully removed from this release.
* Public-facing shipped files no longer contain internal phase language.
* The release ZIP excludes development, planning, and unused files and remains installable as a normal WordPress plugin ZIP.
* PHP syntax validation passes.
* No known blocking submission issues remain in the updated scope.

## Phase 3 Fix: Plugin Check Compliance

Phase 3 must include a Plugin Check stabilization pass.

The current Plugin Check report shows many repeated errors and a few packaging/security warnings.

The goal is to reduce Plugin Check issues without breaking the plugin behavior already built in Phase 1, Phase 2, and Phase 3.

## Main Plugin Check Problems

Current reported issues include:

* Translation text domain passed as a constant instead of a string literal
* Production package includes development files
* Plugin header `Domain Path` points to a missing folder
* Manual `load_plugin_textdomain()` usage warning
* Missing nonce verification for admin form handling
* Unsanitized `$_SERVER['REQUEST_URI']`
* `readme.txt` tested up to value is outdated
* WordPress compatibility mismatch for `wp_is_serving_rest_request()`

## Text Domain Literal Requirement

Plugin Check reports many errors like this:

```text id="b7h9ct"
WordPress.WP.I18n.NonSingularStringLiteralDomain
The $domain parameter must be a single text string literal. Found: MMSM_TEXT_DOMAIN
```

This must be fixed across the plugin.

Do not pass this constant into translation functions:

```php id="xdn90s"
MMSM_TEXT_DOMAIN
```

Use the literal text domain string directly:

```php id="i3lkyf"
'maintenance-mode-studio'
```

Correct examples:

```php id="hlclwl"
__( 'Settings saved.', 'maintenance-mode-studio' );

esc_html__( 'Maintenance in progress', 'maintenance-mode-studio' );

esc_attr__( 'Admin login', 'maintenance-mode-studio' );
```

Incorrect examples:

```php id="dm6x3n"
__( 'Settings saved.', MMSM_TEXT_DOMAIN );

esc_html__( 'Maintenance in progress', MMSM_TEXT_DOMAIN );

esc_attr__( 'Admin login', MMSM_TEXT_DOMAIN );
```

## Files To Fix For Text Domain Errors

Inspect and fix translation function calls in these files:

```text id="xyh0vw"
includes/Components/StatusProgressComponent.php
includes/Components/HeroComponent.php
includes/Components/ContactRevealComponent.php
includes/Components/LoginComponent.php
includes/Components/SocialLinksComponent.php
includes/Frontend/TemplateRenderer.php
includes/Frontend/TemplateRegistry.php
includes/Admin/Admin.php
templates/public/default.php
public/templates/default.php
```

Replace the text domain constant only in translation function calls.

Do not remove the constant if other internal code still uses it safely.

The Plugin Check requirement is specifically about the `$domain` argument in internationalization functions.

Common functions to check:

```text id="f4yqot"
__()
_e()
_x()
_ex()
esc_html__()
esc_html_e()
esc_html_x()
esc_attr__()
esc_attr_e()
esc_attr_x()
_n()
_nx()
_n_noop()
_nx_noop()
```

## Development Files In Production Package

Plugin Check reports production package issues for:

```text id="zsdgm5"
phpcs.xml.dist
.distignore
.gitignore
.github
```

These files should not be included in the final production plugin ZIP.

Required behavior:

* Keep development files in the Git repository if useful
* Exclude development files from the release ZIP
* Do not ship GitHub workflow files in the WordPress.org plugin package
* Do not ship PHPCS config files in the production plugin package unless specifically allowed

Recommended `.distignore` entries:

```text id="hw9bvg"
.git
.github
.gitignore
.distignore
phpcs.xml
phpcs.xml.dist
composer.json
composer.lock
package.json
package-lock.json
node_modules
vendor/bin
tests
specs
```

The exact list can be adjusted, but production ZIP should include only runtime plugin files and required assets.

## Domain Path Warning

Plugin Check reports:

```text id="eybllg"
The "Domain Path" header in the plugin file must point to an existing folder. Found: "languages"
```

Fix this in one of two ways.

### Option A: Create The Folder

Create this folder:

```text id="qg6shc"
languages/
```

Keep the plugin header:

```php id="sogzj6"
Domain Path: /languages
```

### Option B: Remove The Header

If no translation files are shipped yet, remove the `Domain Path` header.

Preferred Phase 3 fix:

* Create the `languages/` folder
* Keep `Domain Path: /languages`
* Add an empty placeholder file only if needed by the repository workflow

Do not add fake translation files.

## Discouraged `load_plugin_textdomain()` Warning

Plugin Check reports:

```text id="c39hc4"
load_plugin_textdomain() has been discouraged since WordPress version 4.6.
```

Expected fix:

* Remove manual `load_plugin_textdomain()` unless there is a strong reason to keep it
* Let WordPress.org handle translations automatically for the plugin slug
* Make sure the plugin text domain matches the plugin slug

Text domain should be:

```text id="f1yot9"
maintenance-mode-studio
```

## Nonce Verification Fixes

Plugin Check reports nonce warnings in admin form handling.

Files to inspect:

```text id="xf386y"
includes/Admin/Admin.php
includes/Frontend/MaintenanceRouter.php
```

Admin save handlers must verify nonce before processing submitted settings.

Expected admin save flow:

```text id="js6a0l"
1. Confirm the request is a settings save request.
2. Verify the nonce.
3. Verify user capability.
4. Sanitize submitted data.
5. Merge submitted settings with existing settings.
6. Save settings.
7. Redirect back with one success notice.
```

Admin requests should use:

```php id="gcyqsm"
check_admin_referer()
```

or:

```php id="sr2pa0"
wp_verify_nonce()
```

Use the project’s current form structure and avoid breaking saves.

Do not add nonce checks to simple page loads that do not process submitted data.

## Request URI Sanitization

Plugin Check reports unsanitized use of:

```php id="h0hg9a"
$_SERVER['REQUEST_URI']
```

Fix by unslashing and sanitizing before use.

Expected pattern:

```php id="lxi8y5"
$request_uri = isset( $_SERVER['REQUEST_URI'] )
    ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) )
    : '';
```

If the value is used as a URL or path, use the safest sanitizer for that use case.

Do not echo raw server values.

Do not trust `$_SERVER` values.

## WordPress Version Compatibility

Plugin Check reports:

```text id="qlxw1e"
Function "wp_is_serving_rest_request()" requires WordPress 6.5.0, but your plugin minimum supported version is WordPress 6.4.0.
```

Fix this in one of two ways.

### Option A: Raise Minimum WordPress Version

If the plugin intends to require WordPress 6.5 or higher, update plugin headers and readme requirements to match.

### Option B: Add Compatibility Fallback

If the plugin should support WordPress 6.4, avoid calling `wp_is_serving_rest_request()` directly without a function check.

Example:

```php id="bejo7c"
$is_rest_request = function_exists( 'wp_is_serving_rest_request' )
    ? wp_is_serving_rest_request()
    : defined( 'REST_REQUEST' ) && REST_REQUEST;
```

Preferred Phase 3 fix:

* Use a compatibility fallback
* Keep the minimum supported WordPress version stable unless intentionally changed

## Readme Tested Up To

Plugin Check reports:

```text id="zw2rkm"
Tested up to: 6.5 < 7.0
```

Update `readme.txt` so the tested version matches the WordPress version being checked.

For the current check environment, use:

```text id="w92nhq"
Tested up to: 7.0
```

Also confirm the main plugin header and readme are consistent.

## Duplicate Template Path Check

The report includes both:

```text id="l3ps9s"
templates/public/default.php
public/templates/default.php
```

Confirm whether both files are needed.

Expected behavior:

* Keep one canonical public template path
* Remove duplicate legacy template files if they are unused
* If backward compatibility needs both paths, make sure both are compliant
* Avoid maintaining two different default templates that drift apart

Preferred Phase 3 target:

```text id="vdlc1x"
templates/public/default.php
```

## Data Safety Requirements

This Plugin Check pass must not break:

* Maintenance mode routing
* Admin settings tabs
* Color picker saving
* Color frontend application
* Social links repeater
* Custom social icons
* Cross-tab settings preservation
* Single save notice behavior
* Public template rendering

Do not rewrite working architecture unless needed.

Apply focused fixes.

## Updated Phase 3 Exit Criteria

Phase 3 is complete when:

* Translation functions use the literal text domain string
* Plugin Check text domain literal errors are cleared
* Development files are excluded from the production plugin ZIP
* `Domain Path` points to an existing folder or is removed
* Manual `load_plugin_textdomain()` warning is resolved or intentionally documented
* Admin form processing uses nonce verification
* Server input is sanitized before use
* WordPress compatibility issue is resolved
* `readme.txt` tested up to value is current for the target release
* Duplicate template paths are resolved or made compliant
* No existing Phase 3 behavior regresses

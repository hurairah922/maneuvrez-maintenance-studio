## Phase 3 Plugin Check Fix Pass

Run a focused Plugin Check compliance pass.

The current Plugin Check report shows 156 errors and 12 warnings.

Most errors are caused by one repeated issue:

```text id="nyia1j"
The $domain parameter must be a single text string literal. Found: MMSM_TEXT_DOMAIN
```

Fix the reported issues without breaking existing Phase 3 behavior.

## 1. Replace Text Domain Constant In Translation Calls

Inspect all translation function calls.

In translation functions, replace:

```php id="pj1i1w"
MMSM_TEXT_DOMAIN
```

with:

```php id="bjeeqh"
'maintenance-mode-studio'
```

Correct example:

```php id="ikh6vn"
esc_html__( 'Settings saved.', 'maintenance-mode-studio' )
```

Incorrect example:

```php id="djpk2x"
esc_html__( 'Settings saved.', MMSM_TEXT_DOMAIN )
```

Files reported by Plugin Check:

```text id="a8lx0c"
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

Search the whole plugin for translation functions using `MMSM_TEXT_DOMAIN`.

Functions to check include:

```text id="wnqjzv"
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

Important:

* Only change the text domain argument in i18n functions.
* Do not replace unrelated safe uses of `MMSM_TEXT_DOMAIN` unless needed.
* Do not change the actual plugin text domain.
* Use exactly `'maintenance-mode-studio'`.

## 2. Fix Domain Path Warning

Plugin Check reports that `Domain Path: /languages` points to a missing folder.

Fix by creating:

```text id="xdyiyi"
languages/
```

Keep the plugin header only if this folder exists:

```php id="xp5d97"
Domain Path: /languages
```

If the project does not plan to ship translation files yet, removing the `Domain Path` header is acceptable.

Preferred fix:

* Create `languages/`
* Keep `Domain Path: /languages`

## 3. Remove Or Justify Manual Textdomain Loading

Plugin Check reports `load_plugin_textdomain()` as discouraged.

Inspect:

```text id="pb5b5a"
includes/Plugin.php
```

Preferred fix:

* Remove manual `load_plugin_textdomain()` usage
* Let WordPress.org load translations automatically
* Keep the plugin text domain consistent with the slug

If keeping it for a specific non-WordPress.org workflow, document why in code.

Do not add new textdomain loading logic.

## 4. Fix Admin Nonce Warnings

Plugin Check reports nonce issues in:

```text id="j03qig"
includes/Admin/Admin.php
```

Inspect the reported lines around:

```text id="xttrmu"
465
473
1305
```

Expected fix:

* Verify nonce before processing submitted form data
* Verify capability before saving settings
* Sanitize submitted data after nonce/capability checks
* Preserve the existing tab save merge behavior
* Preserve the single save notice behavior

Use the existing form nonce if present.

If missing, add a nonce field to the settings form:

```php id="ag5bxk"
wp_nonce_field( 'mmsm_save_settings', 'mmsm_settings_nonce' );
```

Then verify it before processing:

```php id="liy6b1"
if (
    ! isset( $_POST['mmsm_settings_nonce'] )
    || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mmsm_settings_nonce'] ) ), 'mmsm_save_settings' )
) {
    return;
}
```

Do not process submitted settings before nonce verification.

## 5. Fix Request URI Sanitization

Plugin Check reports this file:

```text id="snziip"
includes/Frontend/MaintenanceRouter.php
```

Reported issues:

* Processing form data without nonce verification
* Unsanitized `$_SERVER['REQUEST_URI']`

Inspect the relevant routing logic.

For `$_SERVER['REQUEST_URI']`, use a sanitized value before comparison or output.

Recommended pattern:

```php id="bmio08"
$request_uri = isset( $_SERVER['REQUEST_URI'] )
    ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) )
    : '';
```

If a URL is built from it, escape later at output time.

If this code only reads routing data and does not process a submitted form, add a targeted phpcs ignore only if appropriate and documented.

Do not silence warnings if proper sanitization is possible.

## 6. Fix WordPress Version Compatibility

Plugin Check reports:

```text id="fih9al"
wp_is_serving_rest_request() requires WordPress 6.5.0, but plugin minimum is WordPress 6.4.0.
```

File:

```text id="kdz4bh"
includes/Frontend/MaintenanceRouter.php
```

Preferred fix:

```php id="c8nzcj"
$is_rest_request = function_exists( 'wp_is_serving_rest_request' )
    ? wp_is_serving_rest_request()
    : defined( 'REST_REQUEST' ) && REST_REQUEST;
```

Use the fallback result instead of calling `wp_is_serving_rest_request()` directly.

Do not raise the minimum WordPress version unless the project intentionally drops WordPress 6.4 support.

## 7. Update Readme Tested Up To

Plugin Check reports:

```text id="jjww6b"
Tested up to: 6.5 < 7.0
```

Update `readme.txt`:

```text id="lbe6hy"
Tested up to: 7.0
```

Confirm the readme remains valid WordPress.org format.

Do not change unrelated readme content unless required.

## 8. Fix Production Package Exclusions

Plugin Check reports production package warnings/errors for:

```text id="vjbpug"
phpcs.xml.dist
.distignore
.gitignore
.github
```

These files may remain in the development repository, but they should not be included in the production plugin ZIP.

Update packaging rules so production builds exclude:

```text id="d7nxew"
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

Important:

* Do not delete useful development files from the repo unless the project wants that.
* Make sure the production ZIP excludes them.
* If Plugin Check is being run against the repo folder instead of the production ZIP, document that these packaging warnings should be checked against the final ZIP.

## 9. Resolve Duplicate Default Template Path

Plugin Check reports both:

```text id="rd2bzy"
templates/public/default.php
public/templates/default.php
```

Inspect whether both are used.

Preferred Phase 3 canonical path:

```text id="bfqr6f"
templates/public/default.php
```

Fix options:

### Option A: Remove Legacy Duplicate

If `public/templates/default.php` is unused, remove it.

### Option B: Keep Compatibility File

If it is still referenced, make it compliant and keep it as a thin compatibility layer.

Do not maintain two separate default templates with duplicate logic.

## 10. Regression Safety

After fixes, verify that these still work:

```text id="t7u0dv"
Maintenance mode renders
Admin settings page loads
Tabs still save correctly
Saving one tab preserves other tab values
Color picker values save
Colors apply on frontend
Social links add/remove works
Empty social rows do not render publicly
Custom social icons still render safely
Only one save notice appears
```

## 11. Run Plugin Check Again

After applying fixes:

* Run Plugin Check again
* Export or review the new results
* Confirm the repeated i18n literal-domain errors are gone
* Confirm no new fatal or security issues were introduced

## Deliverables

Report:

* Files changed
* Number of Plugin Check errors before and after
* Whether all `MMSM_TEXT_DOMAIN` i18n errors were removed
* How nonce warnings were fixed
* How request URI sanitization was fixed
* Whether `load_plugin_textdomain()` was removed
* How production package exclusions were handled
* Whether duplicate template path was removed or kept
* Any remaining Plugin Check warnings and why they remain

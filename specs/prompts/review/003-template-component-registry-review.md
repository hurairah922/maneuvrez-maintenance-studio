## Phase 3 Plugin Check Review

Review the Plugin Check compliance fix pass.

The original Plugin Check report showed:

```text id="sy4x3w"
156 errors and 12 warnings
```

Most errors came from translation functions using a text domain constant.

## Text Domain Literal Review

Check all translation function calls.

The text domain argument must be a literal string:

```php id="iibp1v"
'maintenance-mode-studio'
```

This is correct:

```php id="b7ywas"
esc_html__( 'Settings saved.', 'maintenance-mode-studio' );
```

This is incorrect:

```php id="osgsxf"
esc_html__( 'Settings saved.', MMSM_TEXT_DOMAIN );
```

Review these files:

```text id="umem86"
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

Also search the whole plugin for:

```text id="udpec0"
MMSM_TEXT_DOMAIN
```

Flag as high priority if it is still used as the text domain argument in any i18n function.

## Domain Path Review

Check the main plugin header.

If it contains:

```php id="r9xf37"
Domain Path: /languages
```

then this folder must exist:

```text id="s02ldn"
languages/
```

Review that:

* The folder exists if the header remains
* The header is removed if the folder does not exist
* No fake translation files are added only to satisfy the check

## Textdomain Loading Review

Check:

```text id="xle3mr"
includes/Plugin.php
```

Review whether `load_plugin_textdomain()` was removed.

Preferred outcome:

* No manual `load_plugin_textdomain()` call
* WordPress.org loads translations automatically
* Text domain matches the plugin slug

Flag as low priority if it remains with a valid documented reason.

## Nonce Review

Review admin form processing in:

```text id="y73mha"
includes/Admin/Admin.php
```

Confirm:

* Settings save requests include a nonce field
* Save handlers verify the nonce before processing data
* Save handlers verify user capability
* Submitted values are sanitized after nonce and capability checks
* Existing cross-tab save preservation still works

Flag as high priority if settings can be saved without nonce verification.

## Request URI Sanitization Review

Review:

```text id="arkkr9"
includes/Frontend/MaintenanceRouter.php
```

Confirm that `$_SERVER['REQUEST_URI']` is not used raw.

Expected pattern:

```php id="zkrgsn"
$request_uri = isset( $_SERVER['REQUEST_URI'] )
    ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) )
    : '';
```

Flag as medium priority if request URI is still unsanitized.

Flag as high priority if it is echoed or used in output without escaping.

## WordPress Compatibility Review

Check this reported issue:

```text id="qrrqu8"
wp_is_serving_rest_request() requires WordPress 6.5.0, but plugin minimum is WordPress 6.4.0.
```

Preferred fix:

```php id="numv3k"
$is_rest_request = function_exists( 'wp_is_serving_rest_request' )
    ? wp_is_serving_rest_request()
    : defined( 'REST_REQUEST' ) && REST_REQUEST;
```

Review that the plugin either:

* Uses a compatibility fallback
* Or intentionally raises the minimum supported WordPress version

Flag as high priority if the plugin still calls `wp_is_serving_rest_request()` directly while claiming WordPress 6.4 support.

## Readme Review

Check `readme.txt`.

Required for the current Plugin Check result:

```text id="ggd0e6"
Tested up to: 7.0
```

Also confirm:

* Readme format remains valid
* Stable tag still makes sense
* Requires at least still matches the plugin header

## Production Package Review

Check whether the Plugin Check was run on the repo or the final production ZIP.

Development files reported:

```text id="qfxwhj"
phpcs.xml.dist
.distignore
.gitignore
.github
```

Expected production ZIP behavior:

* `.github` is excluded
* `.gitignore` is excluded
* `.distignore` is excluded
* `phpcs.xml.dist` is excluded
* Development/test/spec files are excluded
* Runtime plugin files remain included

Flag as medium priority if the production ZIP still includes development files.

Do not require deleting development files from the repository.

## Duplicate Template Path Review

Review:

```text id="vkj7ku"
templates/public/default.php
public/templates/default.php
```

Confirm one of these outcomes:

* `templates/public/default.php` is the canonical template and the duplicate is removed
* The duplicate remains only as a thin compatibility file
* Both files are compliant if both are shipped

Flag as medium priority if two separate default templates contain duplicated rendering logic.

## Regression Review

After Plugin Check fixes, verify:

```text id="ry3qx3"
Maintenance mode renders
Admin settings page loads
Tabs save correctly
Saving one tab preserves other tab values
Color picker values save
Colors apply on frontend
Social links add/remove works
Empty social rows do not render publicly
Custom social icons render safely
Only one save notice appears
```

## Updated Scope Check Rows

Add these rows to the scope check table:

```markdown id="j6rzls"
| I18n literal text domain | Pass/Fail/Partial | Notes |
| Domain Path validity | Pass/Fail/Partial | Notes |
| Textdomain loading warning | Pass/Fail/Partial | Notes |
| Admin nonce verification | Pass/Fail/Partial | Notes |
| Request URI sanitization | Pass/Fail/Partial | Notes |
| WordPress version compatibility | Pass/Fail/Partial | Notes |
| Readme tested up to | Pass/Fail/Partial | Notes |
| Production package exclusions | Pass/Fail/Partial | Notes |
| Duplicate template path | Pass/Fail/Partial | Notes |
```

## Additional High-Priority Issues To Flag

Flag as high priority if:

* Any i18n function still uses `MMSM_TEXT_DOMAIN` as the domain argument
* Settings save requests lack nonce verification
* Raw request input is printed publicly
* WordPress 6.5-only functions are used while minimum support says 6.4
* Plugin Check errors increase after the fix
* The Plugin Check pass breaks existing Phase 3 behavior

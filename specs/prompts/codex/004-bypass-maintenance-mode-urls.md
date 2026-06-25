You are working on an existing WordPress plugin:

Plugin:

* Name: Maneuvrez Maintenance Studio
* Slug: maneuvrez-maintenance-studio
* Namespace: Maneuvrez\MaintenanceModeStudio
* Prefix: mmsm_
* Main option: mmsm_settings
* Target: WordPress 6.4+, PHP 8.0+

Feature to implement:
Add optional maintenance-mode bypass controls for regular testing.

The plugin already bypasses maintenance mode for admins, wp-admin, AJAX, cron, REST, WP-CLI, wp-login.php, and logged-in users with manage_options. Do not remove or break those existing bypasses.

Add two new optional bypass mechanisms:

1. Query-parameter bypass
2. Public URL allowlist bypass

Important behavior:

Query-parameter bypass:

* Admin can enable/disable this feature.
* Admin can define a query parameter key and value.
* When maintenance mode is active, any frontend URL containing the exact configured key/value pair must bypass the maintenance page and show the real site page.
* The key and value match must be exact.
* Example:

  * key: preview
  * value: abc123
  * https://example.com/?preview=abc123 bypasses maintenance mode.
  * https://example.com/about/?preview=abc123 also bypasses maintenance mode.
  * https://example.com/?preview=wrong does not bypass.
  * https://example.com/?other=abc123 does not bypass.
* The query parameter must work on all site pages, not only the homepage.
* Keep this simple. This is not a full token/session system yet.
* Do not set cookies unless the existing architecture already has a clear pattern for that. One valid URL request should bypass for that request only.
* This should be treated as a roadmap stepping stone toward future access rules / bypass links, not a full visitor access system.

Random key/value generator:

* Add a small admin UI control to generate a random query key/value pair.
* The generated key/value should populate the settings fields.
* Use JavaScript for the UI generation if that best fits the existing jQuery admin scripts.
* Also validate/sanitize server-side on save.
* Suggested random defaults:

  * key: `mmsm_preview`
  * value: random URL-safe token
* If generating both key and value, make the key readable and safe, and make the value stronger/random.
* Do not call external services.
* Do not store anything outside `mmsm_settings`.

URL preview:

* Show a read-only preview of the homepage bypass URL after adding the query parameter.
* The UI should make clear that the same query parameter can be appended to any frontend URL.
* Preview example:

  * `https://example.com/?mmsm_preview=abc123`
* Update the preview live when key or value changes.
* Build the preview safely using the site URL and URL encoding.
* The preview is for display only; it should not be a separate saved option.

Compact UI requirement:

* Add these settings in the existing classic PHP admin settings page, preferably under the Advanced tab unless the current code has a better bypass/access area.
* Keep the query parameter UI compact.
* Display the readonly site URL at the beginning of the row.
* Display the key and value input fields side by side in the same row.
* Do not stack key and value vertically unless mobile CSS requires it.
* Beneath that compact row, show the generated homepage preview URL.
* Include a small “Generate” button next to the key/value fields.
* Include helpful but short descriptions.

Specific URL allowlist bypass:

* Admin can enable/disable this feature.
* Admin can enter a list of specific frontend URLs or paths that should always bypass maintenance mode for everyone.
* These URLs are public allowlist entries, not secret preview links.
* When maintenance mode is active and the current request matches one of these entries, show the real site page instead of the maintenance page.
* Match behavior should be exact after normalization.
* Accept either absolute URLs from the same site or site-relative paths.
* Examples:

  * `/about/`
  * `/contact/`
  * `https://example.com/about/`
* Normalize same-site absolute URLs to paths before saving or before matching.
* Do not allow external domains to become bypass entries. If an external absolute URL is entered, ignore it during sanitization or strip it safely.
* Matching should not be wildcard by default.
* Avoid broad unsafe patterns like `*`, regex, or partial contains matching.
* Keep trailing slash handling consistent with WordPress permalinks. A practical approach is to compare normalized paths with and without a trailing slash.
* Do not allow this to bypass admin/security routes unnecessarily. This is intended for frontend public pages only.

Settings to add to `mmsm_settings`:
Use names that match the existing naming style. Suggested keys:

* `bypass_query_enabled`
* `bypass_query_key`
* `bypass_query_value`
* `bypass_urls_enabled`
* `bypass_urls`

If the existing SettingsRepository has defaults/sanitization helpers, extend those rather than creating a parallel settings system.

Expected file areas to inspect and modify:

* Bootstrap / Plugin wiring only if necessary.
* `SettingsRepository` or equivalent settings/defaults/sanitization class.
* `MaintenanceRouter` or equivalent request interception class.
* Admin settings page PHP renderer.
* Admin JS file that already handles jQuery UI behavior.
* Admin CSS file if needed for compact row layout.
* Any README/docs/spec file only if the repo already keeps feature documentation updated.

Implementation details:

MaintenanceRouter:

* Add bypass checks before rendering the maintenance template.
* Keep all existing bypass checks.
* Suggested order:

  1. Existing system/admin bypasses.
  2. Query parameter bypass.
  3. Public URL allowlist bypass.
  4. Otherwise render maintenance page.
* The query bypass should only run when:

  * maintenance mode is enabled,
  * `bypass_query_enabled` is truthy,
  * saved key and value are non-empty.
* Use `wp_unslash()` and safe access to `$_GET`.
* Compare strings exactly after sanitizing the saved key/value.
* Do not echo unsanitized request data.

URL allowlist matching:

* Get current request path safely from `$_SERVER['REQUEST_URI']`.
* Strip query string.
* Normalize to a path beginning with `/`.
* Decode carefully only if needed; avoid unsafe transformations.
* Compare against sanitized allowlist paths.
* Treat `/about` and `/about/` as equivalent if practical.
* Only same-site paths should be saved/matched.
* Do not match the whole site accidentally.

Sanitization:

* Query key:

  * allow only URL-query-safe characters.
  * recommended: lowercase letters, uppercase letters, numbers, underscore, hyphen.
  * no spaces.
* Query value:

  * allow URL-safe token characters.
  * recommended: letters, numbers, underscore, hyphen.
  * no spaces.
* Bypass URLs:

  * textarea, one URL/path per line.
  * trim whitespace.
  * remove empty lines.
  * accept same-site absolute URLs and convert to paths.
  * accept paths beginning with `/`.
  * reject external URLs.
  * reject wildcard-only or unsafe entries.
  * deduplicate.
* Escape all admin output with `esc_html`, `esc_attr`, `esc_url`, or textarea-safe escaping as appropriate.

Admin UI:

* Add section title like “Testing Bypass”.
* Add checkbox for enabling query parameter bypass.
* Add compact row:

  * readonly site URL text/input at start,
  * key input,
  * value input,
  * Generate button.
* Show homepage preview below the row.
* Add helper text: “Append this query string to any frontend URL to preview the real page while maintenance mode is active.”
* Add checkbox for enabling public URL allowlist.
* Add textarea for allowlist, one URL/path per line.
* Add helper text: “These URLs are public and will bypass maintenance mode for everyone. Use exact frontend paths only.”

Admin JS:

* Add live preview update.
* Add Generate button behavior.
* Use existing jQuery admin script if available.
* Generate URL-safe random token using `window.crypto.getRandomValues` when available, with a fallback to `Math.random`.
* Do not require a build-system rewrite.
* Ensure the JS only runs on the plugin settings page.

Security:

* Use existing settings form nonce/capability handling.
* Do not add unauthenticated AJAX for generation.
* Do not create REST endpoints.
* Do not introduce telemetry.
* Do not make external calls.
* Keep WordPress.org compatibility.

Quality:

* Keep the change small and aligned with the existing classic PHP admin architecture.
* Do not convert the admin UI to React.
* Do not implement drag-and-drop, forms, submissions, password access, roles, sessions, cookies, or multiple experience assignment.
* Do not rewrite the router/renderer architecture.
* Preserve existing features and settings.
* Add inline comments only where they clarify non-obvious normalization/matching logic.

After implementing:

* Run PHP syntax checks on modified PHP files.
* If package scripts exist, run the relevant build/check command only if needed for changed assets.
* Manually inspect the settings UI behavior.
* Test these cases:

  1. Maintenance disabled: site behaves normally.
  2. Maintenance enabled, no bypass settings: existing maintenance page appears.
  3. Maintenance enabled, correct query key/value on homepage: real homepage appears.
  4. Maintenance enabled, correct query key/value on inner page: real inner page appears.
  5. Maintenance enabled, wrong query value: maintenance page appears.
  6. Maintenance enabled, allowed path `/about/`: real about page appears.
  7. Maintenance enabled, non-allowed path: maintenance page appears.
  8. External URL in allowlist is not accepted as a bypass.
  9. Existing admin/AJAX/REST/wp-login bypasses still work.
  10. Saved settings persist and are sanitized.

Return:

* Summary of files changed.
* Explanation of the bypass logic.
* Any assumptions made because of existing code structure.
* Any roadmap mismatch found in docs/specs.

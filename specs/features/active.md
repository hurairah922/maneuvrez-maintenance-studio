# Active Feature Spec

## Feature

Maintenance Mode — Phase 2: Admin Settings Foundation

## Status

Active

## Owner

Abu Hurarrah

## Publisher

Abu Hurarrah

## Company

Maneuvrez

## Phase Goal

Expand the basic Phase 1 maintenance mode shell into a settings-driven mode manager.

The public maintenance page already appears in Phase 1. Phase 2 should allow the site owner to control the main public template content and appearance from the WordPress admin.

## Primary Outcome

A WordPress admin user can configure the maintenance mode experience without editing code.

The settings should cover the current Phase 1 public template, persist safely, and keep the admin experience simple.

## Scope

Build the admin settings foundation for:

- mode type selection
- page title and message settings
- theme mode controls
- color controls
- login button visibility setting
- asset loading foundation
- settings UX cleanup

## Non-Goals

Do not build advanced templates in this phase.

Do not add countdown timers in this phase.

Do not add email capture in this phase.

Do not add third-party integrations in this phase.

Do not build a full design system in this phase.

Do not add analytics in this phase.

Do not add role-based access controls beyond the existing admin capability checks.

## User Story

As a WordPress site owner, I want to configure the maintenance mode page from the admin panel so I can update the page title, message, visual style, and login button without changing plugin code.

## Functional Requirements

### 1. Mode Type Selection

Add a setting for the public mode type.

Supported mode types:

- `maintenance`
- `coming_soon`

Default value:

```text
maintenance
```

Admin label:

```text
Mode Type
```

Admin help text:

```text
Choose whether the public page should show as maintenance mode or coming soon mode.
```

Expected behavior:

- `maintenance` should represent a temporary site maintenance state.
- `coming_soon` should represent a pre-launch or upcoming website state.
- The selected mode should be saved in WordPress options.
- Invalid values should fall back to `maintenance`.

### 2. Page Title Setting

Add a setting for the public page title.

Default value:

```text
Maintenance Mode
```

Admin label:

```text
Page Title
```

Expected behavior:

- The saved title should appear on the public maintenance page.
- The title should be sanitized before saving.
- The title should be escaped before rendering.
- Empty values should fall back to the default value.

### 3. Message Setting

Add a setting for the public page message.

Default value:

```text
Our website is currently undergoing scheduled maintenance. Please check back soon.
```

Admin label:

```text
Message
```

Expected behavior:

- The saved message should appear on the public maintenance page.
- The message should support plain text only in this phase.
- The message should be sanitized before saving.
- The message should be escaped before rendering.
- Empty values should fall back to the default value.

### 4. Theme Mode Setting

Add a setting for the public page theme mode.

Supported theme modes:

- `light`
- `dark`

Default value:

```text
light
```

Admin label:

```text
Theme Mode
```

Expected behavior:

- The selected theme should affect the public maintenance page styling.
- Invalid values should fall back to `light`.
- Theme classes should be applied safely to the public template wrapper.

Example wrapper classes:

```text
mm-public-template mm-theme-light
mm-public-template mm-theme-dark
```

### 5. Color Controls

Add a primary color setting for the public page.

Default value:

```text
#2563eb
```

Admin label:

```text
Primary Color
```

Expected behavior:

- The primary color should control key visual accents.
- The value should be validated as a hex color.
- Invalid values should fall back to the default color.
- The color should be escaped before output.

Recommended use cases:

- button background
- accent border
- highlight color
- focus state where relevant

### 6. Login Button Setting

Add a setting to show or hide the login button on the public maintenance page.

Supported values:

- `1`
- `0`

Default value:

```text
1
```

Admin label:

```text
Show Login Button
```

Expected behavior:

- When enabled, the public page should show a login button.
- When disabled, the login button should not render.
- The login button should point to the WordPress login URL.
- The login URL should be generated with `wp_login_url()`.
- The login URL should be escaped with `esc_url()`.

Default button text:

```text
Log in
```

### 7. Asset Loading Foundation

Add a clean asset loading foundation for admin and public styles.

Expected behavior:

- Public styles should load only when the maintenance template is being rendered.
- Admin styles should load only on the plugin settings page.
- Asset handles should use a consistent plugin prefix.
- Asset URLs should be generated from plugin constants or a shared plugin helper.
- Asset versions should use the plugin version constant.

Recommended handles:

```text
maintenance-mode-public
maintenance-mode-admin
```

Recommended files:

```text
assets/css/public.css
assets/css/admin.css
```

### 8. Settings UX Cleanup

Improve the settings page so it stays simple and readable.

Expected behavior:

- Use clear field labels.
- Use short help text.
- Group related settings together.
- Keep the page focused on Phase 2 controls only.
- Avoid cluttered layout or unnecessary advanced options.
- Show a clear save button.
- Use WordPress admin UI patterns where possible.

Recommended settings sections:

```text
Mode
Content
Appearance
Access
```

## Data Model

Use a single WordPress option array for Phase 2 settings.

Recommended option name:

```text
maintenance_mode_settings
```

Recommended option shape:

```php
[
    'mode_type' => 'maintenance',
    'page_title' => 'Maintenance Mode',
    'message' => 'Our website is currently undergoing scheduled maintenance. Please check back soon.',
    'theme_mode' => 'light',
    'primary_color' => '#2563eb',
    'show_login_button' => '1',
]
```

## Default Settings

Create one shared defaults method or function.

Required defaults:

```php
[
    'mode_type' => 'maintenance',
    'page_title' => 'Maintenance Mode',
    'message' => 'Our website is currently undergoing scheduled maintenance. Please check back soon.',
    'theme_mode' => 'light',
    'primary_color' => '#2563eb',
    'show_login_button' => '1',
]
```

Expected behavior:

- Defaults should be merged with saved settings before use.
- Missing keys should not break the public template.
- Invalid values should be normalized.
- The public template should always have safe fallback values.

## Sanitization Rules

### `mode_type`

Allowed values:

```text
maintenance
coming_soon
```

Fallback:

```text
maintenance
```

### `page_title`

Sanitization:

```php
sanitize_text_field()
```

Fallback:

```text
Maintenance Mode
```

### `message`

Sanitization:

```php
sanitize_textarea_field()
```

Fallback:

```text
Our website is currently undergoing scheduled maintenance. Please check back soon.
```

### `theme_mode`

Allowed values:

```text
light
dark
```

Fallback:

```text
light
```

### `primary_color`

Sanitization:

```php
sanitize_hex_color()
```

Fallback:

```text
#2563eb
```

### `show_login_button`

Allowed values:

```text
1
0
```

Fallback:

```text
1
```

## Escaping Rules

Escape all public output.

Required escaping:

```php
esc_html()
esc_attr()
esc_url()
```

Expected usage:

- Use `esc_html()` for visible text.
- Use `esc_attr()` for attributes and inline CSS variable values where applicable.
- Use `esc_url()` for login URLs and asset URLs.

## Public Template Requirements

The public template should use saved settings for:

- mode type label or template state
- page title
- message
- theme mode class
- primary color
- login button visibility

Expected wrapper structure:

```php
<div class="mm-public-template mm-theme-<?php echo esc_attr( $settings['theme_mode'] ); ?>">
    <!-- Public maintenance mode content -->
</div>
```

Expected title rendering:

```php
<h1><?php echo esc_html( $settings['page_title'] ); ?></h1>
```

Expected message rendering:

```php
<p><?php echo esc_html( $settings['message'] ); ?></p>
```

Expected login button behavior:

```php
<?php if ( '1' === $settings['show_login_button'] ) : ?>
    <a href="<?php echo esc_url( wp_login_url() ); ?>">
        <?php echo esc_html__( 'Log in', 'maintenance-mode' ); ?>
    </a>
<?php endif; ?>
```

## Admin Settings Page Requirements

The admin settings page should include:

- mode type select field
- page title text input
- message textarea
- theme mode select field
- primary color input
- show login button checkbox
- save button

Recommended page title:

```text
Maintenance Mode Settings
```

Recommended menu title:

```text
Maintenance Mode
```

Required capability:

```text
manage_options
```

Expected behavior:

- Only authorized admin users can access settings.
- Settings should save through the WordPress Settings API or a secure equivalent.
- Saving should use nonce protection if using a custom save handler.
- Settings should persist across page reloads.
- Invalid submitted values should not break the public page.

## File Structure

Use the existing Phase 1 structure where possible.

Recommended additions:

```text
assets/
  css/
    admin.css
    public.css
```

Recommended existing files to update:

```text
maintenance-mode.php
includes/Admin.php
includes/MaintenanceRouter.php
templates/public/maintenance.php
```

If the current project uses nested class folders, keep the current project structure and adapt the same responsibilities to the existing paths.

Do not create duplicate competing class structures.

## Implementation Notes

### Admin Class

The admin class should own:

- menu registration
- settings registration
- settings field rendering
- admin asset loading
- settings sanitization

### Router or Public Controller

The public router/controller should own:

- deciding when maintenance mode should render
- loading saved settings
- passing safe settings to the public template
- loading public assets only when needed

### Template

The template should own:

- public HTML structure
- escaped output
- minimal conditional rendering

Do not place settings sanitization inside the template.

## CSS Requirements

### Public CSS

Create public styling for:

- light theme
- dark theme
- centered layout
- readable title and message
- login button
- primary color usage
- responsive spacing

Use stable class names with the `mm-` prefix.

Recommended classes:

```text
mm-public-template
mm-theme-light
mm-theme-dark
mm-public-card
mm-public-title
mm-public-message
mm-public-login
```

### Admin CSS

Create light admin cleanup styles only if needed.

Admin CSS should improve readability without fighting WordPress admin styles.

Recommended classes:

```text
mm-settings-page
mm-settings-section
mm-settings-field
mm-settings-help
```

## Accessibility Requirements

- Use readable color contrast for light and dark themes.
- Use visible focus states for links and buttons.
- Use real labels for form fields.
- Do not rely only on color to communicate state.
- Keep the login button keyboard accessible.

## Security Requirements

- Check `manage_options` for admin settings access.
- Sanitize all saved settings.
- Escape all rendered output.
- Avoid raw HTML in title and message fields.
- Do not trust option values directly.
- Do not render unsanitized inline styles.

## Backward Compatibility

Phase 2 must not break Phase 1 behavior.

If no settings have been saved yet, the public maintenance page should still render using defaults.

If settings are partially missing, defaults should fill missing values.

## Exit Criteria

Phase 2 is complete when:

- The admin can select mode type.
- The admin can edit page title.
- The admin can edit message text.
- The admin can select light or dark theme mode.
- The admin can set a primary color.
- The admin can show or hide the login button.
- Settings persist after saving and page reload.
- Invalid saved values fall back safely.
- Public output is escaped.
- Admin settings are sanitized.
- Admin styles load only on the settings page.
- Public styles load only on the maintenance page.
- The Phase 1 public template is fully covered by settings.
- The admin experience remains simple.

## Manual QA Checklist

### Admin Settings

- [ ] Settings page loads for admin users.
- [ ] Settings page does not load for unauthorized users.
- [ ] Mode type saves correctly.
- [ ] Page title saves correctly.
- [ ] Message saves correctly.
- [ ] Theme mode saves correctly.
- [ ] Primary color saves correctly.
- [ ] Login button setting saves correctly.
- [ ] Saved values remain after page reload.
- [ ] Empty title falls back to default.
- [ ] Empty message falls back to default.
- [ ] Invalid mode type falls back to `maintenance`.
- [ ] Invalid theme mode falls back to `light`.
- [ ] Invalid color falls back to `#2563eb`.

### Public Page

- [ ] Public page renders with saved page title.
- [ ] Public page renders with saved message.
- [ ] Light theme applies correctly.
- [ ] Dark theme applies correctly.
- [ ] Primary color applies correctly.
- [ ] Login button appears when enabled.
- [ ] Login button disappears when disabled.
- [ ] Login button points to the WordPress login URL.
- [ ] Public page still works when no settings are saved.
- [ ] Public page still works when partial settings are saved.

### Assets

- [ ] Admin CSS loads only on the plugin settings page.
- [ ] Public CSS loads only when the maintenance template renders.
- [ ] No unnecessary assets load across the whole admin.
- [ ] No unnecessary assets load across normal public pages.

### Security

- [ ] All saved settings are sanitized.
- [ ] All public output is escaped.
- [ ] Settings access requires `manage_options`.
- [ ] No raw user-controlled HTML renders on the public page.
- [ ] No PHP warnings appear with missing or invalid settings.

## Suggested Codex Prompt

```text
Phase 1 is complete. The basic maintenance mode page appears.

Now implement Phase 2: Admin Settings Foundation.

Use the active feature spec at specs/features/active.md as the source of truth.

Build the following:

- mode type selection: maintenance or coming soon
- page title setting
- message setting
- theme mode setting: light or dark
- primary color setting
- login button visibility setting
- public asset loading foundation
- admin asset loading foundation
- settings UX cleanup

Requirements:

- Use one option array named maintenance_mode_settings.
- Provide safe defaults for every setting.
- Merge saved settings with defaults before use.
- Sanitize all settings before saving.
- Escape all public output before rendering.
- Keep raw HTML out of title and message fields.
- Load public CSS only when the maintenance page renders.
- Load admin CSS only on the plugin settings page.
- Keep the admin UI simple and WordPress-native.
- Do not add Phase 3 features.
- Do not create duplicate competing folder structures.
- Preserve the current project structure unless a change is necessary.

Exit criteria:

- Settings cover the Phase 1 public template.
- Settings persist safely.
- Invalid values fall back safely.
- The admin experience remains simple.
```

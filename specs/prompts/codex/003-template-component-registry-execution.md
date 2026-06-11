## Phase 3 Fix: Reliable Color Mapping and Duplicate Save Notice

Implement the remaining Phase 3 fixes.

Current known issues:

* Color picker values are saving correctly
* Saved colors are not always applied correctly on the public template
* Color mapping appears inconsistent
* Settings saved notice appears twice after saving

Fix these without breaking existing settings, tabs, social links, or data preservation behavior.

## 1. Audit Existing Color Flow

First inspect the full color flow.

Trace:

```text id="xbwo35"
Admin color picker field
Saved option key
Sanitization logic
Settings repository output
Template renderer input
Generated CSS variable
Template/component CSS usage
Final public frontend style
```

Identify mismatches between saved keys and CSS variables.

Common problems to check:

* Saved color key differs from renderer key
* Renderer outputs a variable that CSS does not use
* CSS uses old variable names
* Component uses hardcoded colors instead of variables
* Default CSS loads after custom inline CSS and overrides it
* Dark mode CSS overrides saved custom variables unintentionally
* System mode media query overrides saved custom variables unintentionally
* Admin preview uses a different map than the frontend

## 2. Create Canonical Color Map

Create one canonical map between saved setting keys and public CSS variables.

Use this target map unless the project already has equivalent keys:

```php id="s2qbyq"
private function get_color_variable_map(): array {
    return [
        'background_color' => '--mm-bg',
        'surface_color' => '--mm-surface',
        'primary_color' => '--mm-primary',
        'heading_text_color' => '--mm-heading-text',
        'body_text_color' => '--mm-body-text',
        'muted_text_color' => '--mm-muted-text',
        'link_text_color' => '--mm-link-text',
        'button_text_color' => '--mm-button-text',
        'border_color' => '--mm-border',
    ];
}
```

If current saved keys are different, either:

* Adapt this map to the existing saved keys
* Or add backward-compatible migration/fallback logic

Do not break existing saved colors.

Do not rename saved option keys without migration.

## 3. Normalize Color Settings

Before rendering colors, normalize them.

Expected behavior:

```php id="so68zw"
private function normalize_colors( array $settings ): array {
    $defaults = $this->get_default_colors_for_theme_mode( $settings['theme_mode'] ?? 'system' );
    $saved = isset( $settings['colors'] ) && is_array( $settings['colors'] ) ? $settings['colors'] : [];

    $colors = wp_parse_args( $saved, $defaults );

    foreach ( $colors as $key => $value ) {
        $color = sanitize_hex_color( $value );

        if ( empty( $color ) ) {
            $colors[ $key ] = $defaults[ $key ] ?? '';
            continue;
        }

        $colors[ $key ] = $color;
    }

    return $colors;
}
```

Rules:

* Always merge saved colors with defaults
* Sanitize every color before rendering
* Use fallback defaults for invalid values
* Do not render unknown color keys as CSS variables
* Do not render empty values
* Do not trust saved settings directly

## 4. Generate Scoped CSS Variables

Generate CSS variables from the normalized colors and canonical map.

The CSS variables must be scoped to the maintenance page wrapper.

Acceptable output:

```php id="gsx5nx"
private function build_color_style_attribute( array $colors ): string {
    $map = $this->get_color_variable_map();
    $declarations = [];

    foreach ( $map as $setting_key => $css_var ) {
        if ( empty( $colors[ $setting_key ] ) ) {
            continue;
        }

        $color = sanitize_hex_color( $colors[ $setting_key ] );

        if ( empty( $color ) ) {
            continue;
        }

        $declarations[] = sprintf(
            '%s: %s',
            $css_var,
            $color
        );
    }

    return implode( '; ', $declarations );
}
```

When printing the style attribute, escape it safely:

```php id="x6gt93"
style="<?php echo esc_attr( $color_style ); ?>"
```

Alternative acceptable approach:

* Use `wp_add_inline_style()` after registering/enqueuing the template stylesheet
* Scope the variables to `.mm-public-shell`
* Ensure inline custom values load after the default stylesheet

Do not print global unscoped color CSS.

## 5. Fix Light, Dark, and System Mode Overrides

Review the CSS for light, dark, and system mode.

Saved colors must not disappear because default theme mode rules override them later.

Rules:

* Defaults may define fallback variables
* Saved custom variables must override defaults
* Dark mode rules must not override saved inline variables unintentionally
* System mode media queries must not override saved inline variables unintentionally
* The wrapper class should clearly identify the selected mode

Recommended wrapper classes:

```text id="cdwrd1"
mm-theme-light
mm-theme-dark
mm-theme-system
```

If using inline style variables on the wrapper, do not redefine the same variables later on a more specific descendant selector.

## 6. Update Component CSS To Use Variables

Replace hardcoded frontend colors with canonical variables.

Required mappings:

```css id="j3qtar"
.mm-public-shell {
    background: var(--mm-bg);
    color: var(--mm-body-text);
}

.mm-public-card {
    background: var(--mm-surface);
    border-color: var(--mm-border);
}

.mm-hero-title {
    color: var(--mm-heading-text);
}

.mm-hero-message,
.mm-contact-message {
    color: var(--mm-body-text);
}

.mm-muted,
.mm-status-label {
    color: var(--mm-muted-text);
}

.mm-social-link,
.mm-login-link {
    color: var(--mm-link-text);
}

.mm-button,
.mm-primary-button {
    background: var(--mm-primary);
    color: var(--mm-button-text);
}

.mm-progress-fill {
    background: var(--mm-primary);
}
```

Adapt selectors to the actual project.

The key requirement is that all public-facing text and UI colors use the canonical variables.

## 7. Preserve Existing Settings Behavior

Do not regress previous fixes.

Confirm that:

* Saving one tab does not erase other tab values
* Missing fields are not saved as null
* Social links remain saved when saving Design
* Colors remain saved when saving General
* Components remain saved when saving Social Links
* Empty social link rows do not render publicly
* Custom social icons still render safely

## 8. Fix Duplicate Settings Saved Notice

Find why the saved notice appears twice.

Common causes:

* WordPress Settings API renders one notice
* Custom admin template renders another notice
* `settings_errors()` is called more than once
* A redirect query param triggers a custom notice while Settings API also triggers one
* Both `add_settings_error()` and manual HTML notice output are used for the same success
* The admin page callback renders notices and WordPress admin renders them again

Choose one notice owner.

### Option A: WordPress Settings API Notice

Use the Settings API notice only.

Rules:

* Keep one `add_settings_error()` success notice if needed
* Call `settings_errors()` only once
* Remove custom duplicate success notice HTML
* Do not render a second notice based on query params

### Option B: Custom Notice Only

Use one custom notice only.

Rules:

* Do not call `settings_errors()` for the same success notice
* Do not add a duplicate Settings API success notice
* Render the custom notice only after a confirmed successful save
* Ensure the notice does not persist incorrectly across tab switches

Choose the cleaner option for the current implementation.

## 9. Save Notice Expected Behavior

After the fix:

* Initial settings page load shows no saved notice
* Successful save shows one notice
* Failed save does not show a success notice
* Switching tabs does not duplicate the notice
* Refreshing after redirect does not duplicate the notice
* The notice uses WordPress admin styling
* Active tab state remains preserved

Recommended text:

```text id="mugb6i"
Settings saved.
```

## 10. Regression Test Flow

Run this manual test flow:

```text id="ofn53x"
1. Open the settings page.
2. Confirm no saved notice appears on first load.
3. Go to Design.
4. Change background color.
5. Change heading text color.
6. Change body text color.
7. Save Design.
8. Confirm exactly one saved notice appears.
9. Open the public maintenance page.
10. Confirm the background color applies.
11. Confirm heading text color applies.
12. Confirm body text color applies.
13. Refresh the public page.
14. Confirm colors still apply.
15. Switch to dark mode.
16. Save Design.
17. Confirm exactly one saved notice appears.
18. Confirm dark mode colors apply correctly.
19. Switch to system mode.
20. Save Design.
21. Confirm exactly one saved notice appears.
22. Confirm colors still apply consistently.
23. Save General.
24. Confirm Design colors were not erased.
25. Save Social Links.
26. Confirm Design colors were not erased.
```

Also inspect frontend CSS in the browser:

* Confirm variables exist on the public wrapper
* Confirm variables contain saved hex values
* Confirm components reference those variables
* Confirm no later CSS rule overrides saved values incorrectly

## Deliverables

When complete, report:

* Files changed
* Root cause of inconsistent color application
* Final color key to CSS variable mapping
* How saved colors are scoped on the frontend
* How light, dark, and system modes now apply colors
* Which duplicate notice source was removed
* Manual regression test results
* Any remaining risks

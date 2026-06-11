## Phase 3 Fix: Reliable Color Application and Single Save Notice

Phase 3 must fix inconsistent frontend color application and duplicate save notices.

The color picker fields are already present and saving correctly.

The remaining issue is that saved color values are not always applied correctly on the public template.

In some cases, colors appear correctly.

In other cases, default colors or incorrect theme variables are used.

This must be fixed with a clear color mapping layer and one source of truth for public theme variables.

## Color Application Problem

Current issue:

```text id="uj75hx"
Color picker values save correctly, but the selected colors are not applied consistently on the frontend.
```

Likely causes to check:

* Saved color keys do not match frontend CSS variable names
* Renderer maps saved settings to the wrong CSS variables
* Template uses old variables while settings save new variables
* CSS fallback values override saved inline variables
* Light and dark mode variables are mixed incorrectly
* System mode does not apply saved values consistently
* Component styles use hardcoded colors instead of theme variables
* Template wrapper does not receive the generated CSS variables consistently
* Assets load after inline styles and override saved values
* Multiple style sources compete with each other

Phase 3 must remove this ambiguity.

## Required Color Mapping Source of Truth

Create one canonical color map that connects saved settings to frontend CSS variables.

The implementation should define a clear map like this:

```php id="p0vqv9"
[
    'background_color' => '--mm-bg',
    'surface_color' => '--mm-surface',
    'primary_color' => '--mm-primary',
    'heading_text_color' => '--mm-heading-text',
    'body_text_color' => '--mm-body-text',
    'muted_text_color' => '--mm-muted-text',
    'link_text_color' => '--mm-link-text',
    'button_text_color' => '--mm-button-text',
    'border_color' => '--mm-border',
]
```

The exact saved setting keys may differ if the plugin already uses different names.

However, the final implementation must have one explicit mapping between:

* Saved option keys
* Sanitized color values
* Public CSS variable names
* Template/component usage

Do not rely on scattered manual mappings.

Do not duplicate different mappings across multiple files.

## Required Public CSS Variables

The public template must consistently use these variables:

```css id="jt7b1b"
--mm-bg;
--mm-surface;
--mm-primary;
--mm-heading-text;
--mm-body-text;
--mm-muted-text;
--mm-link-text;
--mm-button-text;
--mm-border;
```

Every public component should use these variables instead of hardcoded color values.

Required component mapping:

| UI Element              | CSS Variable        |
| ----------------------- | ------------------- |
| Page background         | `--mm-bg`           |
| Card/surface background | `--mm-surface`      |
| Main heading            | `--mm-heading-text` |
| Body/message text       | `--mm-body-text`    |
| Secondary/muted text    | `--mm-muted-text`   |
| Links/social labels     | `--mm-link-text`    |
| Primary buttons         | `--mm-primary`      |
| Button text             | `--mm-button-text`  |
| Borders/dividers        | `--mm-border`       |
| Progress fill/accent    | `--mm-primary`      |

## Color Rendering Strategy

The renderer should generate CSS variables from sanitized saved color settings.

Preferred strategy:

* Load saved settings
* Merge with defaults
* Sanitize color values
* Build the CSS variable map
* Print or enqueue inline CSS only on the maintenance template
* Scope variables to the public maintenance wrapper

Example output:

```html id="d79a48"
<div class="mm-public-shell" style="--mm-bg: #020617; --mm-surface: #0f172a; --mm-heading-text: #f8fafc;">
```

Or:

```html id="cmyf5h"
<style>
.mm-public-shell {
    --mm-bg: #020617;
    --mm-surface: #0f172a;
    --mm-heading-text: #f8fafc;
}
</style>
```

Either approach is acceptable.

Rules:

* Do not output invalid color values
* Do not output empty CSS variable values
* Do not output raw unsanitized settings
* Do not output color CSS globally across the whole site
* Do not let frontend CSS override saved custom variables with later defaults
* Do not define conflicting duplicate values for the same variable

## Light, Dark, and System Mode Color Rules

Color mapping must work in all theme modes:

```text id="p01nbd"
light
dark
system
```

Expected behavior:

* In `light` mode, apply light/default color values plus any saved overrides
* In `dark` mode, apply dark/default color values plus any saved overrides
* In `system` mode, use system color defaults and still respect saved overrides
* Saved custom color values should not randomly disappear after refresh
* The same saved values should apply consistently after saving, reloading, and reopening the frontend

If the plugin stores separate light and dark color groups, map each group clearly.

If the plugin stores one shared color group, apply it consistently across the selected theme mode.

Avoid mixing saved light colors into dark mode unless that is explicitly how the settings are designed.

## Component Color Compliance

All Phase 3 public components must use the canonical variables.

Required components to check:

* Hero component
* Status/progress component
* Contact reveal component
* Social links component
* Login component
* Public template wrapper

No component should use hardcoded text colors that bypass user-selected colors.

Acceptable hardcoded values:

* Transparent
* Current color
* Safe layout-only values
* Non-color layout values

Avoid hardcoded theme colors like:

```css id="y0qqsh"
#ffffff
#000000
#64748b
#94a3b8
```

unless they are only used as fallback values inside the canonical defaults.

## Admin Preview Color Consistency

If the admin settings page includes a preview, the preview should use the same color map as the frontend.

The admin preview should not use a separate color mapping that disagrees with the public template.

If maintaining a live preview is too risky, prioritize correct public frontend rendering.

## Duplicate Save Notice Issue

Current issue:

```text id="ntyfat"
The settings saved notice appears twice after saving.
```

Phase 3 must show only one save success notice.

Expected behavior:

* Saving settings shows one success notice
* The notice text should not duplicate
* The notice should not appear twice from both WordPress Settings API and a custom notice
* The notice should not duplicate when switching tabs after saving
* Failed saves should not show a success notice
* Validation errors should show separately when needed

## Save Notice Ownership

There must be one owner for the saved notice.

Acceptable approaches:

### Option A: Use WordPress Settings API Notice

Use the default WordPress Settings API updated notice.

Do not also render a custom success notice.

### Option B: Use Custom Notice Only

Suppress or avoid the default WordPress Settings API notice.

Render one custom notice after a successful save.

The implementation should choose one approach and remove duplicate notice output.

Do not use both.

## Save Notice Rules

The save notice should:

* Render once after a successful save
* Use WordPress admin notice styling
* Be dismissible if simple to support
* Preserve active tab state
* Not show on initial page load
* Not show twice after redirect
* Not show if the save failed

Recommended notice text:

```text id="d41u5m"
Settings saved.
```

## Data Safety Requirements

This fix must not break previously fixed save behavior.

The implementation must still:

* Preserve settings from other tabs
* Avoid saving missing fields as null
* Merge active tab settings into existing settings
* Preserve nested settings that were not submitted
* Preserve social links when saving other tabs
* Preserve colors when saving other tabs
* Preserve tab state after save

## Updated Phase 3 Exit Criteria

Phase 3 is complete when:

* Saved color picker values apply reliably on the public template
* Color setting keys map clearly to CSS variables
* Public components use the canonical CSS variables
* Light mode colors apply correctly
* Dark mode colors apply correctly
* System mode colors apply correctly
* Saved colors remain applied after refresh
* No conflicting frontend color mappings exist
* No invalid color values reach public CSS
* Admin preview, if present, matches the public color mapping
* Saving settings shows only one saved notice
* The saved notice does not duplicate after tab changes or refreshes
* Existing data preservation fixes remain intact

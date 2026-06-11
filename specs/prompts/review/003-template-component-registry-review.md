## Phase 3 Color Mapping and Save Notice Review

Review the fixes for inconsistent color application and duplicate save notices.

Focus on:

* Saved color values applying reliably
* Correct mapping between saved settings and CSS variables
* Light, dark, and system mode behavior
* Component usage of theme variables
* Scoped frontend color output
* Duplicate admin save notice removal
* No regression to tab save data preservation

## Color Flow Review

Trace the complete color flow:

```text id="k7uoei"
Admin color picker field
Saved option key
Sanitized saved value
Settings repository output
Renderer color normalization
CSS variable output
Template/component CSS usage
Final public frontend result
```

Check for mismatches at every step.

Flag any point where:

* A saved key does not map to the expected CSS variable
* A CSS variable is generated but not used
* A component uses an old or wrong variable
* A saved value is overwritten by default CSS
* A dark mode or system mode rule overrides saved custom values
* Invalid color values can reach public CSS

## Canonical Color Map Review

Confirm there is one clear map between saved color keys and frontend CSS variables.

Expected target map:

```php id="isc62y"
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

If the implementation uses different saved keys, confirm that:

* The mapping is explicit
* Backward compatibility is preserved
* Existing saved colors still work
* The public CSS variables are still the canonical output

Flag as high priority if color mapping is scattered across multiple inconsistent files.

## Color Normalization Review

Check that colors are normalized before rendering.

Review that:

* Saved colors are merged with defaults
* Every rendered color is sanitized with `sanitize_hex_color()`
* Invalid colors fall back safely
* Empty colors are not rendered as empty CSS variables
* Unknown color keys are ignored
* Raw saved values are not printed into public CSS
* Defaults are theme-aware where needed

Flag as high priority if raw option values are printed into a `style` attribute or inline CSS.

## Frontend CSS Variable Scope Review

Check where CSS variables are printed.

Acceptable locations:

* Scoped style attribute on the public maintenance wrapper
* Scoped inline CSS using `wp_add_inline_style()`

Review that:

* Variables are scoped to the maintenance template
* Variables are not printed globally across the whole site
* Variables load after default stylesheet if using inline CSS
* Variables are not redefined later by a more specific selector
* Saved custom values override defaults reliably

Flag as high priority if saved colors can be overridden by later default CSS.

## Light, Dark, and System Mode Review

Check all theme modes:

```text id="ytf0xc"
light
dark
system
```

Review that:

* Light mode applies expected colors
* Dark mode applies expected colors
* System mode applies expected colors
* Saved custom values remain applied after refresh
* Switching theme modes does not erase saved colors
* Dark mode does not accidentally reuse unreadable light text colors
* System mode media queries do not override saved custom variables incorrectly

Flag as high priority if colors appear only sometimes or disappear after refresh.

## Component Color Usage Review

Check all public template/component CSS.

Required components:

* Hero component
* Status/progress component
* Contact reveal component
* Social links component
* Login component
* Public shell/template wrapper

Review that each component uses canonical variables:

```css id="a3upf5"
--mm-bg
--mm-surface
--mm-primary
--mm-heading-text
--mm-body-text
--mm-muted-text
--mm-link-text
--mm-button-text
--mm-border
```

Flag hardcoded color values that override user settings.

Flag old variables that no longer map to saved settings.

Flag component-specific colors that bypass the theme system.

## Duplicate Save Notice Review

Check the duplicate save notice issue.

Current bug:

```text id="jxz1dt"
The settings saved notice appears twice after saving.
```

Expected behavior:

* Initial page load shows no saved notice
* Successful save shows one saved notice
* Failed save does not show success
* Tab switching does not duplicate notice
* Page refresh does not duplicate notice unexpectedly
* Notice uses WordPress admin styling
* Active tab state remains preserved

Check likely duplicate sources:

* `settings_errors()` called more than once
* Custom HTML notice plus Settings API notice
* `add_settings_error()` plus manual success query param notice
* Redirect notice plus admin page callback notice
* Notice rendered in both parent layout and tab partial

Flag as high priority if more than one success notice appears.

## Regression Review

Confirm this fix did not break previous Phase 3 fixes.

Review that:

* Saving one tab preserves other tab settings
* Missing submitted fields are not saved as null
* Social links still use add/remove repeater rows
* One default social item still appears in admin when needed
* Empty default social rows do not render publicly
* Custom social icons still render safely
* Settings tabs still work
* Color picker values still save correctly

## Required Manual Test

Verify this flow:

```text id="dv16mx"
1. Open settings page.
2. Confirm no saved notice appears on initial load.
3. Open Design tab.
4. Set custom background color.
5. Set custom heading text color.
6. Set custom body text color.
7. Save Design.
8. Confirm exactly one saved notice appears.
9. Open public maintenance page.
10. Confirm saved colors appear.
11. Refresh public page.
12. Confirm saved colors remain.
13. Switch to dark mode.
14. Save Design.
15. Confirm exactly one saved notice appears.
16. Confirm dark mode colors apply correctly.
17. Save General tab.
18. Confirm Design colors remain saved and applied.
19. Save Social Links tab.
20. Confirm Design colors remain saved and applied.
```

## Updated Scope Check Rows

Add these rows to the scope check table:

```markdown id="nmzy93"
| Canonical color mapping | Pass/Fail/Partial | Notes |
| Saved color frontend application | Pass/Fail/Partial | Notes |
| Scoped CSS variable output | Pass/Fail/Partial | Notes |
| Light mode color reliability | Pass/Fail/Partial | Notes |
| Dark mode color reliability | Pass/Fail/Partial | Notes |
| System mode color reliability | Pass/Fail/Partial | Notes |
| Component color variable usage | Pass/Fail/Partial | Notes |
| Single save notice | Pass/Fail/Partial | Notes |
```

## Additional High-Priority Issues To Flag

Flag as high priority if:

* Saved colors do not reliably apply on the frontend
* Saved color keys do not map clearly to CSS variables
* Components bypass saved colors with hardcoded values
* Dark mode overrides saved custom colors incorrectly
* System mode overrides saved custom colors incorrectly
* Raw unsanitized color values reach public CSS
* Color CSS is printed globally outside the maintenance template
* The saved notice appears more than once
* Fixing colors regresses tab save preservation

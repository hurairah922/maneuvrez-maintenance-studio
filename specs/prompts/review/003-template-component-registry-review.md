# Codex Review Prompt

## Task

Review the Phase 3 implementation for the Template and Component Registry.

## Context

This is a WordPress maintenance mode / coming soon plugin.

Phase 3 should add a frontend template and component architecture.

Use `specs/features/active.md` as the source of truth.

## Review Goal

Check whether the implementation is safe, complete, simple, and aligned with Phase 3 scope.

Focus on:

* Correct architecture
* Public rendering safety
* Template registry behavior
* Component registry behavior
* Zone compatibility
* Settings fallback behavior
* Responsive frontend quality
* Asset loading correctness
* Empty-state handling
* WordPress standards

## Expected Phase 3 Features

The implementation should include:

* PHP template renderer
* Template registry
* Component registry
* Zone compatibility rules
* Theme variables
* Light/dark/system mode
* Responsive shell
* Default copy
* Asset loading per template
* Component settings schema
* Hero component
* Social links component
* Contact reveal component
* Login component
* Status/progress component

## Review Checklist

### 1. Architecture

Check that:

* Template rendering is separated from routing
* Template registry is separated from component registry
* Components are reusable
* Components follow a shared interface or consistent contract
* Settings are normalized before rendering
* The implementation does not create unnecessary complexity
* Existing Phase 1 and Phase 2 behavior is preserved

Flag any duplicated architecture or unclear ownership.

### 2. Template Renderer

Check that the renderer:

* Resolves selected template safely
* Falls back to the default template
* Passes normalized settings into the template
* Renders components through the registry
* Skips missing components safely
* Skips incompatible zone components safely
* Does not produce PHP warnings for missing data

### 3. Template Registry

Check that the registry:

* Defines at least the `default` template
* Provides metadata for the template
* Defines supported zones
* Defines required assets
* Defines a default component layout
* Handles invalid template keys safely

### 4. Component Registry

Check that the registry:

* Registers all required components
* Can return available components
* Can check whether a component exists
* Can render components safely
* Enforces zone compatibility
* Does not fatal on unknown components

Required components:

* Hero
* Social links
* Contact reveal
* Login
* Status/progress

### 5. Component Settings Schema

Check that each component declares a settings schema or equivalent structure.

Each schema should define:

* Field key
* Field label
* Field type
* Default value
* Required status where useful
* Allowed values where useful

Flag missing or inconsistent schemas.

### 6. Hero Component

Check that the hero component:

* Renders title
* Renders message
* Supports optional eyebrow text
* Supports optional primary action
* Supports optional secondary action
* Uses defaults when title or message is missing
* Escapes text and URLs
* Skips actions with empty or invalid URLs

### 7. Social Links Component

Check that the social links component:

* Renders only valid social links
* Skips empty URLs
* Skips invalid URLs
* Escapes labels
* Renders nothing when no valid links exist
* Does not invent fake social links

### 8. Contact Reveal Component

Check that the contact reveal component:

* Renders contact label
* Renders contact message
* Supports optional email
* Validates email before rendering a `mailto:` link
* Escapes all output
* Avoids broken contact links

### 9. Login Component

Check that the login component:

* Renders only when enabled
* Uses WordPress login URL
* Uses default label when label is empty
* Escapes URL and label
* Renders nothing when disabled

### 10. Status/Progress Component

Check that the status/progress component:

* Renders status label
* Supports optional progress display
* Uses default progress when missing
* Clamps progress between `0` and `100`
* Uses valid accessible progress markup
* Escapes label text

### 11. Theme Support

Check that the template supports:

* Light mode
* Dark mode
* System mode

Required CSS variables:

```css
--mm-bg;
--mm-surface;
--mm-text;
--mm-muted;
--mm-border;
--mm-primary;
--mm-primary-text;
--mm-shadow;
--mm-radius;
--mm-content-width;
```

Flag hardcoded theme values that prevent settings from working.

### 12. Responsive Behavior

Check that the public page works on:

* Desktop
* Tablet
* Mobile
* Small mobile

Look for:

* Horizontal overflow
* Tiny tap targets
* Text too small to read
* Broken spacing
* Components overlapping
* Content cut off on small screens

### 13. Asset Loading

Check that public template assets:

* Load only when maintenance mode is active
* Load only for the selected template
* Do not load in the WordPress admin unnecessarily
* Do not load across normal public site pages when maintenance mode is off
* Avoid duplicate enqueue calls

### 14. Security

Check that:

* Public output is escaped
* URLs are escaped with `esc_url()`
* Text is escaped with `esc_html()` or `esc_attr()`
* Rich text, if any, uses a safe allowlist
* Saved options are not trusted blindly
* Invalid saved data cannot break rendering

### 15. Accessibility

Check that:

* The template uses semantic HTML
* There is a clear heading hierarchy
* Links and buttons are keyboard accessible
* Focus states are visible
* Progress markup is accessible
* Color contrast is acceptable

### 16. Empty States

Check that the page does not break when:

* Title is missing
* Message is missing
* Component settings are missing
* Template key is invalid
* Component key is invalid
* Zone has no components
* Social links are empty
* Email is invalid
* Progress is invalid

### Color Picker and Theme Readability Review

Check that editable color settings use proper color picker fields.

The implementation should not require users to manually type color values into plain text inputs.

Review that:

* Background color uses a color picker
* Surface/card color uses a color picker
* Primary/accent color uses a color picker
* Heading text color uses a color picker
* Body text color uses a color picker
* Muted text color uses a color picker
* Link text color uses a color picker
* Button text color uses a color picker
* Border color uses a color picker

Check sanitization:

* Only valid hex colors are saved
* Invalid color values fall back safely
* Arbitrary CSS values are rejected
* Gradients are rejected
* Raw CSS variables are rejected
* Malformed values do not reach public output

Expected sanitizer:

```php
sanitize_hex_color()
```

Check dark mode readability carefully.

The following text must be clearly readable in dark mode:

* Hero heading
* Hero message
* Eyebrow text
* Status label
* Progress text
* Contact label
* Contact message
* Social labels
* Login link
* Button text
* Footer text

Required text color variables:

```css
--mm-heading-text;
--mm-body-text;
--mm-muted-text;
--mm-link-text;
--mm-button-text;
```

Flag any hardcoded color that causes unreadable text in dark mode.

Flag any component that bypasses theme variables.

### Social Icon and Label Review

Check that the social links component allows both icon/platform choice and label choice.

Each social link item should support:

* Platform/icon choice
* Custom label
* URL
* Optional open in new tab setting

Supported platforms should include:

```text
facebook
instagram
linkedin
x
youtube
github
tiktok
threads
website
email
custom
```

Review that:

* Known platforms render matching icons
* `custom` renders a generic link icon
* `website` renders a generic website/link icon
* `email` renders a mail icon
* Custom labels display when provided
* Empty custom labels fall back to default platform labels
* Empty or invalid URLs are skipped
* Unsupported platform keys are skipped
* Labels are escaped
* URLs are escaped
* User-provided raw SVG or HTML is not rendered
* No large third-party icon library is added unnecessarily

For email links, check that:

* Valid email addresses become safe `mailto:` links
* Safe `mailto:` URLs work
* Invalid email values are rejected
* Malformed `mailto:` links are skipped

### Updated Scope Check Rows

Add these rows to the scope check table:

```markdown
| Color picker fields | Pass/Fail/Partial | Notes |
| Hex color sanitization | Pass/Fail/Partial | Notes |
| Dark mode text readability | Pass/Fail/Partial | Notes |
| Theme text color variables | Pass/Fail/Partial | Notes |
| Social icon choices | Pass/Fail/Partial | Notes |
| Social custom labels | Pass/Fail/Partial | Notes |
```

### Additional High-Priority Issues To Flag

Flag as high priority if:

* Dark mode text is hard to read
* Public text color uses unsafe raw setting values
* Color settings accept arbitrary CSS
* Invalid color values break public styles
* Social icons render raw user-provided SVG or HTML
* Social links render invalid URLs
* Email social links expose broken `mailto:` links


## Output Format

Return the review using this structure:

```markdown
# Phase 3 Review

## Verdict

Choose one:

- Pass
- Pass with minor fixes
- Needs fixes
- Blocked

## Summary

Write a short summary of the implementation quality.

## Findings

### High Priority

List blocking or serious issues.

### Medium Priority

List issues that should be fixed soon.

### Low Priority

List polish or maintainability issues.

## Scope Check

| Requirement | Status | Notes |
|---|---|---|
| PHP template renderer | Pass/Fail/Partial | Notes |
| Template registry | Pass/Fail/Partial | Notes |
| Component registry | Pass/Fail/Partial | Notes |
| Zone compatibility rules | Pass/Fail/Partial | Notes |
| Theme variables | Pass/Fail/Partial | Notes |
| Light/dark/system mode | Pass/Fail/Partial | Notes |
| Responsive shell | Pass/Fail/Partial | Notes |
| Default copy | Pass/Fail/Partial | Notes |
| Asset loading per template | Pass/Fail/Partial | Notes |
| Component settings schema | Pass/Fail/Partial | Notes |
| Hero component | Pass/Fail/Partial | Notes |
| Social links component | Pass/Fail/Partial | Notes |
| Contact reveal component | Pass/Fail/Partial | Notes |
| Login component | Pass/Fail/Partial | Notes |
| Status/progress component | Pass/Fail/Partial | Notes |
| Empty states | Pass/Fail/Partial | Notes |

## Recommended Fixes

List exact fixes in priority order.

## Files To Inspect Closely

List file paths that need attention.

## Final Notes

Mention any risks, assumptions, or follow-up work.
```

## Review Rules

Be strict.

Do not approve incomplete architecture just because the page visually renders.

Do not ask for unrelated features.

Do not expand scope beyond Phase 3.

Prefer simple fixes over large rewrites.

Call out anything that could break WordPress plugin behavior, public rendering, or future extensibility.




### Settings Tabs Review

Check that the Phase 3 admin settings are divided into clear tabs.

Required tabs:

```text id="d6d93c"
General
Template
Design
Components
Social Links
Advanced
```

Review that:

* The tab labels are clear
* Settings are grouped logically
* The active tab remains usable after saving
* The save flow still works
* Required settings are not hidden in confusing places
* The tab UI follows WordPress admin conventions
* The tab UI is keyboard accessible
* The settings page does not become overloaded or hard to scan

Flag as medium priority if the settings page is still one long page.

Flag as high priority if tabs break saving or hide required fields.

### Social Links Repeater Review

Check that social links use a removable repeater field.

The admin should support:

* Add new social item
* Remove social item
* Platform dropdown
* URL or email value field
* Custom platform name only for `custom`
* Custom icon upload only for `custom`
* Optional open in new tab setting

Known platform items should only ask for:

* Platform
* URL or email value
* Optional open in new tab setting

Known platform items should not ask for manual labels.

The visible label should come from the selected platform.

Supported platform values:

```text id="hjyuzw"
facebook
instagram
linkedin
x
youtube
github
tiktok
threads
website
email
custom
```

Review saved data sanitization:

* Platform is allowlisted
* URL is validated
* Email values are validated
* Custom platform name is sanitized
* Custom icon ID is sanitized as an integer
* Open in new tab is normalized to boolean
* Invalid rows are skipped or removed safely

### Custom Social Icon Review

Check that custom social platforms can optionally use uploaded icons.

Review that:

* Custom icons use the WordPress media library
* The plugin stores attachment IDs, not raw markup
* The frontend resolves icon URLs safely
* Icon URLs are escaped
* Alt text is escaped
* Missing icons fall back to a generic icon
* Broken image URLs do not render
* Raw user SVG or HTML is not rendered

For SVG:

* Flag as high priority if raw SVG upload/rendering exists without sanitization
* Prefer raster formats only for Phase 3 unless safe SVG handling is implemented

Preferred safe custom icon types:

```text id="qhnwe7"
png
jpg
jpeg
webp
```

### Social Icon Layout Review

Check that icons stay fully visible and aligned in the public template.

Review that:

* Icons use fixed width and height
* Icons use `object-fit: contain`
* Icons do not crop
* Icons do not stretch
* Icons do not overflow
* Social links wrap on mobile
* Social links do not create horizontal overflow
* Text aligns cleanly with icons
* Uploaded custom icons behave like built-in icons

Flag as high priority if social icons overflow or break mobile layout.

### Updated Scope Check Rows

Add these rows to the scope check table:

```markdown id="da2xxn"
| Admin settings tabs | Pass/Fail/Partial | Notes |
| Social links repeater | Pass/Fail/Partial | Notes |
| Known platform URL-only flow | Pass/Fail/Partial | Notes |
| Custom social platform field | Pass/Fail/Partial | Notes |
| Custom social icon upload | Pass/Fail/Partial | Notes |
| Social icon layout constraints | Pass/Fail/Partial | Notes |
```

### Additional High-Priority Issues To Flag

Flag as high priority if:

* Settings tabs break saving
* Known social platforms require manual labels
* Social rows save unsafe or unsupported platform values
* Invalid social URLs render publicly
* Custom icons render raw user HTML or unsanitized SVG
* Uploaded icons overflow, crop, stretch, or break layout
* Social links create horizontal overflow on mobile

# Codex Execution Prompt

## Task

Implement Phase 3: Template and Component Registry.

## Context

This is a WordPress plugin project for a maintenance mode / coming soon experience.

The plugin already has a basic maintenance mode screen and admin settings foundation.

You are implementing the frontend architecture needed for polished templates and reusable components.

Use `specs/features/active.md` as the source of truth.

## Goal

Build a safe, extensible public template system that can render a polished maintenance mode page from saved settings.

The implementation should introduce:

* PHP template renderer
* Template registry
* Component registry
* Zone compatibility rules
* Theme variables
* Light/dark/system mode support
* Responsive shell
* Default copy
* Asset loading per template
* Component settings schema
* Hero component
* Social links component
* Contact reveal component
* Login component
* Status/progress component

## Important Constraints

Keep the implementation simple.

Do not build a drag-and-drop builder.

Do not add unnecessary admin complexity.

Do not introduce third-party dependencies.

Do not break the existing Phase 1 or Phase 2 behavior.

Do not rename existing classes or paths unless required.

If the current project structure differs from the target structure, adapt carefully and preserve the existing working plugin behavior.

## Expected Files

Create or update files as needed.

Preferred target structure:

```text
includes/
├── Components/
│   ├── ComponentInterface.php
│   ├── ComponentRegistry.php
│   ├── ContactRevealComponent.php
│   ├── HeroComponent.php
│   ├── LoginComponent.php
│   ├── SocialLinksComponent.php
│   └── StatusProgressComponent.php
├── Frontend/
│   ├── MaintenanceRouter.php
│   ├── TemplateRenderer.php
│   └── TemplateRegistry.php
├── Settings/
│   ├── SettingsRepository.php
│   └── SettingsSchema.php
└── Support/
    └── Escaper.php

templates/
└── public/
    └── default.php

assets/
├── css/
│   └── public-template-default.css
└── js/
    └── public-template-default.js
```

If equivalent files already exist, update them instead of duplicating functionality.

## Implementation Requirements

### 1. Template Renderer

Create a frontend template renderer.

It should:

* Resolve the selected template key
* Fall back to the default template if needed
* Load normalized settings
* Pass settings to the template
* Render registered components
* Respect zone compatibility rules
* Avoid fatal errors on missing templates or components
* Escape output safely

### 2. Template Registry

Create a template registry.

It should register at least one template:

```text
default
```

The default template should define:

* Key
* Name
* Description
* File path
* Supported zones
* Required styles
* Required scripts
* Default component layout

Initial zones:

```text
main
footer
```

### 3. Component Registry

Create a component registry.

It should:

* Register components by key
* Return component metadata
* Render components by key
* Check zone compatibility
* Skip invalid or incompatible components safely

### 4. Component Interface

Create a shared component interface.

The interface should support:

* Component key
* Component label
* Supported zones
* Settings schema
* Render method

### 5. Hero Component

Create a hero component.

It should render:

* Optional eyebrow
* Title
* Message
* Optional primary action
* Optional secondary action

Fallback defaults:

```text
Title: We'll be back soon
Message: Our site is getting a quick update. Please check back shortly.
```

Safety rules:

* Escape all text
* Escape all URLs
* Skip empty action URLs

### 6. Social Links Component

Create a social links component.

It should render valid social links only.

Safety rules:

* Skip empty URLs
* Skip invalid URLs
* Escape labels
* Render nothing if no valid social links exist

Do not invent fake social URLs.

### 7. Contact Reveal Component

Create a contact reveal component.

It should render:

* Contact label
* Contact message
* Optional email link

Fallback defaults:

```text
Contact label: Need help?
Contact message: Contact us for urgent requests.
```

Safety rules:

* Escape all text
* Validate email before rendering `mailto:`
* Render no broken email links

### 8. Login Component

Create a login component.

It should render a WordPress login link when enabled.

Fallback default:

```text
Admin login
```

Safety rules:

* Render nothing when disabled
* Use the WordPress login URL
* Escape URL and label

### 9. Status/Progress Component

Create a status/progress component.

It should render:

* Status label
* Optional progress bar

Fallback defaults:

```text
Status label: Maintenance in progress
Progress value: 65
```

Safety rules:

* Clamp progress between `0` and `100`
* Render valid progress markup
* Escape status label

### 10. Theme Variables

Add theme variables to the public template.

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

Support theme modes:

```text
light
dark
system
```

### Color Picker Fields

Add color picker support for editable color settings.

The admin settings should use WordPress-compatible color picker fields instead of plain text fields for color values.

Color fields should include:

* Background color
* Surface/card color
* Primary/accent color
* Heading text color
* Body text color
* Muted text color
* Link text color
* Button text color
* Border color

Implementation rules:

* Save only sanitized hex color values
* Accept only values like `#000000`
* Reject unsupported color formats
* Fall back to safe defaults when a color is missing or invalid
* Do not allow arbitrary CSS values
* Do not allow raw CSS variables
* Do not allow gradients
* Do not allow JavaScript or malformed values

Use WordPress sanitization helpers where possible.

Expected sanitization behavior:

```php
sanitize_hex_color( $value )
```

If `sanitize_hex_color()` returns empty or invalid output, use the default color for that role.

### Dark Mode Readability Fix

Fix low-contrast text in dark mode.

All public text must remain readable in light, dark, and system theme modes.

Add or update these CSS variables:

```css
--mm-heading-text;
--mm-body-text;
--mm-muted-text;
--mm-link-text;
--mm-button-text;
```

Use these variables across the template and components.

Do not hardcode component text colors.

Required text roles to check:

* Hero heading
* Hero message
* Eyebrow text
* Status label
* Progress value text
* Contact label
* Contact message
* Social link labels
* Login link label
* Button text
* Footer text
* Empty-state text

Suggested default light theme values:

```css
--mm-bg: #f8fafc;
--mm-surface: #ffffff;
--mm-heading-text: #0f172a;
--mm-body-text: #334155;
--mm-muted-text: #64748b;
--mm-link-text: #2563eb;
--mm-primary: #2563eb;
--mm-button-text: #ffffff;
--mm-border: #e2e8f0;
```

Suggested default dark theme values:

```css
--mm-bg: #020617;
--mm-surface: #0f172a;
--mm-heading-text: #f8fafc;
--mm-body-text: #cbd5e1;
--mm-muted-text: #94a3b8;
--mm-link-text: #93c5fd;
--mm-primary: #60a5fa;
--mm-button-text: #020617;
--mm-border: #334155;
```

Dark mode must not reuse low-contrast light mode text colors.

If custom colors are missing or invalid, fall back to the theme defaults.

### Social Icon and Label Choices

Update the social links component so each social link item supports icon/platform selection and custom labels.

Each social link item should support:

* Platform key
* Custom label
* URL
* Open in new tab setting, optional

Initial platform keys:

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

Default labels:

```php
[
    'facebook' => 'Facebook',
    'instagram' => 'Instagram',
    'linkedin' => 'LinkedIn',
    'x' => 'X',
    'youtube' => 'YouTube',
    'github' => 'GitHub',
    'tiktok' => 'TikTok',
    'threads' => 'Threads',
    'website' => 'Website',
    'email' => 'Email',
    'custom' => 'Link',
]
```

Rendering rules:

* Render the selected platform icon when available
* Render a generic link icon for `custom`
* Render a generic website icon for `website`
* Render a mail icon for `email`
* Use the custom label when provided
* Use the default platform label when the custom label is empty
* Skip the item if the URL is missing or invalid
* Skip the item if the platform key is unsupported
* Escape labels
* Escape URLs
* Do not render raw user-provided SVG or HTML

Icon implementation rules:

* Use a controlled internal icon allowlist
* Prefer lightweight inline SVG from internal code
* Do not load a large third-party icon library
* Do not allow user-submitted icon markup
* Provide text fallback if the icon cannot render

For email social links:

* Accept a valid email address and convert it to a safe `mailto:` URL
* Accept a safe `mailto:` URL
* Reject malformed email values
* Escape the final URL before rendering

### Additional Testing Checklist

After implementation, verify:

* Color settings use color picker UI
* Hex color values save correctly
* Invalid color values fall back safely
* Light mode text is readable
* Dark mode text is readable
* System mode text is readable
* Hero text uses theme text variables
* Contact text uses theme text variables
* Social labels use theme text variables
* Login link uses theme text variables
* Button text remains readable against the selected primary color
* Social platform choices render the correct icons
* Custom social labels render correctly
* Empty social labels fall back to platform labels
* Invalid social URLs are skipped
* Unsupported social platform keys are skipped
* No raw SVG or HTML from settings is rendered


### 11. Responsive Shell

Create a polished responsive shell.

It must work on:

* Desktop
* Tablet
* Mobile
* Small mobile

Use scoped CSS.

Avoid horizontal overflow.

Use touch-friendly buttons and links.

### 12. Asset Loading Per Template

Ensure public template assets load only when maintenance mode is active and the selected template is being rendered.

Do not load these assets across the whole WordPress site.

### 13. Empty States

Handle all empty states safely.

The public page should not break when:

* Title is empty
* Message is empty
* Template key is invalid
* Component key is invalid
* Component settings are missing
* Social links are empty
* Email is invalid
* Progress is invalid

## Security Requirements

Escape all public output.

Use WordPress escaping helpers:

* `esc_html()`
* `esc_attr()`
* `esc_url()`
* `wp_kses_post()`

Sanitize option values before rendering.

Do not output untrusted raw HTML.

## Accessibility Requirements

The default public template should include:

* Semantic HTML
* Clear heading structure
* Keyboard-accessible controls
* Visible focus states
* Accessible progress markup
* Good text contrast

## Testing Checklist

After implementation, verify:

* Default template renders with no saved settings
* Default template renders with saved settings
* Invalid template key falls back safely
* Invalid component key is skipped safely
* Hero renders safely
* Social links render only valid links
* Contact email validates before rendering
* Login link shows only when enabled
* Progress value clamps between `0` and `100`
* Light mode works
* Dark mode works
* System mode works
* Desktop layout works
* Tablet layout works
* Mobile layout works
* Small mobile layout works
* Public template assets load only on the maintenance page
* No PHP warnings appear on the frontend

## Deliverables

When complete, provide:

* Summary of changed files
* Summary of implemented behavior
* Any assumptions made
* Any known risks or incomplete items
* Manual testing notes



### Admin Settings Tabs

Update the admin settings page so Phase 3 settings are grouped into simple tabs.

Use these tabs:

```text id="rtmcm2"
General
Template
Design
Components
Social Links
Advanced
```

Expected grouping:

| Tab          | Settings                                         |
| ------------ | ------------------------------------------------ |
| General      | Mode type, page title, message, login visibility |
| Template     | Template selection and layout basics             |
| Design       | Theme mode, colors, text readability settings    |
| Components   | Component enable/disable settings                |
| Social Links | Social links repeater                            |
| Advanced     | Asset/debug-safe settings for later phases       |

Implementation rules:

* Keep the settings page simple
* Use WordPress admin UI conventions
* Preserve the active tab after save when possible
* Do not break settings saving
* Do not add heavy dependencies
* Do not hide required fields in confusing places
* Keep tabs keyboard accessible

### Social Links Repeater

Replace any single social link setting or manual label setup with a repeater-style social links field.

The user should be able to:

* Add a new social item
* Remove a social item
* Choose the platform from a dropdown
* Enter the URL or email value
* Add a custom platform name only when platform is `custom`
* Upload a custom icon only when platform is `custom`
* Save all items safely

Known platforms should ask only for:

* Platform
* URL or email value
* Open in new tab setting, optional

Known platforms should not ask for custom labels.

The visible label should come from the selected platform.

Custom platforms should ask for:

* Platform set to `custom`
* Custom platform name
* URL
* Optional custom icon upload
* Open in new tab setting, optional

Supported platform dropdown values:

```text id="h04f7r"
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

Default labels:

```php id="eyvbz2"
[
    'facebook' => 'Facebook',
    'instagram' => 'Instagram',
    'linkedin' => 'LinkedIn',
    'x' => 'X',
    'youtube' => 'YouTube',
    'github' => 'GitHub',
    'tiktok' => 'TikTok',
    'threads' => 'Threads',
    'website' => 'Website',
    'email' => 'Email',
    'custom' => 'Link',
]
```

### Social Link Saved Data Shape

Use a simple array shape for saved social links.

Recommended shape:

```php id="2j36le"
[
    [
        'platform' => 'instagram',
        'url' => 'https://instagram.com/example',
        'custom_name' => '',
        'custom_icon_id' => 0,
        'open_new_tab' => true,
    ],
    [
        'platform' => 'custom',
        'url' => 'https://example.com/community',
        'custom_name' => 'Community',
        'custom_icon_id' => 123,
        'open_new_tab' => true,
    ],
]
```

Sanitize every item before saving.

Sanitization rules:

* Platform must exist in the allowlist
* URL must be valid for normal platforms
* Email platform must accept a valid email or safe `mailto:` URL
* Custom name must be sanitized as text
* Custom icon ID must be a positive integer attachment ID
* Open in new tab must be normalized to boolean
* Empty or invalid rows should be removed

### Custom Social Icon Upload

For custom social platforms, add an optional icon upload using the WordPress media library.

Preferred safe file types:

```text id="b8ua5b"
png
jpg
jpeg
webp
```

Do not allow raw SVG uploads unless the project already has safe SVG sanitization.

If SVG support is added, it must be sanitized before rendering.

Implementation rules:

* Store the icon as an attachment ID
* Resolve the attachment URL during rendering
* Escape the resolved URL
* Use the custom platform name as alt text
* Fall back to `Link` as alt text when custom name is empty
* Do not store raw image markup
* Do not render raw user-provided SVG or HTML
* Do not render broken image URLs

### Social Icon Rendering

Make sure social icons stay fully visible and aligned.

Use consistent sizing for all known and custom icons.

Required frontend behavior:

* Icons have fixed width and height
* Icons use `object-fit: contain`
* Icons do not crop
* Icons do not stretch
* Icons do not overflow their container
* Text remains aligned with icons
* Social links wrap on mobile
* Social section does not create horizontal overflow

Add or adapt CSS similar to:

```css id="rbr2zc"
.mm-social-links {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    justify-content: center;
}

.mm-social-link {
    max-width: 100%;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    min-height: 2.5rem;
}

.mm-social-icon {
    width: 1.25rem;
    height: 1.25rem;
    flex: 0 0 1.25rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}

.mm-social-icon img,
.mm-social-icon svg {
    width: 100%;
    height: 100%;
    display: block;
    object-fit: contain;
}
```

### Social Links Frontend Rendering Rules

Update the social links component rendering rules:

* Render social links from the saved repeater array
* Use automatic labels for known platforms
* Use custom platform name only for `custom`
* Use internal allowlisted icons for known platforms
* Use uploaded icon only for `custom`
* Fall back to a generic link icon when custom icon is missing
* Skip missing platforms
* Skip unsupported platforms
* Skip invalid URLs
* Skip invalid email values
* Escape labels
* Escape URLs
* Escape image URLs
* Escape alt text
* Add `rel="noopener noreferrer"` when opening in a new tab
* Do not render raw custom SVG or HTML from settings

### Additional Testing Checklist

After implementation, verify:

* Settings are divided into tabs
* The settings save flow still works
* The active tab remains usable after saving
* `Add new social` adds a new item
* Remove deletes a social item
* Platform dropdown works
* Known platforms ask only for platform and URL/email value
* Custom platform shows custom name and icon upload fields
* Custom icon uploads through the WordPress media library
* Saved social links sanitize correctly
* Invalid social rows are removed or skipped
* Known platform labels render automatically
* Custom platform names render safely
* Known platform icons render correctly
* Custom uploaded icons render correctly
* Missing custom icons fall back safely
* Icons remain fully visible
* Icons do not stretch or crop
* Social links wrap on mobile
* Social links do not create horizontal overflow

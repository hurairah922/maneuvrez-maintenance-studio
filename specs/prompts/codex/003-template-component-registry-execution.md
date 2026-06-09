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

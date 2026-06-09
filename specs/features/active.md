# Active Feature Spec

## Feature

Template and Component Registry

## Phase

Phase 3

## Goal

Render polished public maintenance pages and define reusable frontend building blocks.

This phase moves the plugin from a basic settings-driven maintenance screen into a flexible frontend system.

The public page should render through templates, components, saved settings, safe defaults, and responsive layout rules.

## Build Scope

Phase 3 includes the following work:

* PHP template renderer
* Template registry
* Component registry
* Zone compatibility rules
* Theme variables
* Light and dark mode support
* Responsive public shell
* Default copy
* Asset loading per template
* Component settings schema
* Hero component
* Social links component
* Contact reveal component
* Login component
* Status/progress component

## Exit Criteria

Phase 3 is complete when:

* At least one polished template renders cleanly
* The public page works across desktop, tablet, mobile, and small mobile
* Components render from saved settings
* Empty states are handled safely
* Templates only load their required assets
* Components do not break the page when settings are missing
* Public output is escaped safely
* Admin settings remain simple and understandable

## Non-Goals

Phase 3 does not include:

* Drag-and-drop page builder
* Advanced animation system
* Multiple complex templates
* Email capture backend
* Analytics dashboard
* Third-party integrations
* Block editor integration
* Custom CSS editor
* Import/export system

## Current Assumptions

The plugin already has:

* A working plugin shell
* A basic maintenance mode router
* Public maintenance mode rendering
* Admin settings persistence
* Basic page title and message settings
* Theme mode and color controls
* Login button setting
* Asset loading foundation

If any of these are missing, Phase 3 should preserve the current working behavior and add only the minimum structure needed to continue safely.

## Recommended Directory Structure

Use this structure as the canonical Phase 3 target:

```text
maintenance-mode/
├── maintenance-mode.php
├── assets/
│   ├── css/
│   │   └── public-template-default.css
│   └── js/
│       └── public-template-default.js
├── includes/
│   ├── Admin/
│   │   └── Admin.php
│   ├── Components/
│   │   ├── ComponentInterface.php
│   │   ├── ComponentRegistry.php
│   │   ├── ContactRevealComponent.php
│   │   ├── HeroComponent.php
│   │   ├── LoginComponent.php
│   │   ├── SocialLinksComponent.php
│   │   └── StatusProgressComponent.php
│   ├── Frontend/
│   │   ├── MaintenanceRouter.php
│   │   ├── TemplateRenderer.php
│   │   └── TemplateRegistry.php
│   ├── Settings/
│   │   ├── SettingsRepository.php
│   │   └── SettingsSchema.php
│   └── Support/
│       └── Escaper.php
├── templates/
│   └── public/
│       └── default.php
├── specs/
│   ├── features/
│   │   └── active.md
│   └── prompts/
│       └── codex/
│           ├── 003-template-component-registry-execution.md
│           └── 003-template-component-registry-review.md
└── README.md
```

## Architecture Overview

Phase 3 should introduce a clean separation between:

* The router that decides when to show maintenance mode
* The template renderer that renders the selected public template
* The template registry that defines available templates
* The component registry that defines available components
* Component classes that render reusable sections
* Settings schema that defines safe defaults and allowed values
* Assets that load only when the selected template needs them

The public page should not contain hardcoded business logic.

The template should receive normalized settings and render registered components.

## Template Renderer

Create a PHP template renderer responsible for rendering public templates.

The renderer should:

* Accept the selected template key
* Resolve the template from the template registry
* Load normalized settings
* Pass settings into the template
* Render components through the component registry
* Escape output safely
* Fall back to the default template if the selected template is missing
* Avoid fatal errors when templates or components are unavailable

The renderer should not:

* Directly read raw `$_POST`, `$_GET`, or `$_REQUEST`
* Save settings
* Register admin fields
* Contain component-specific business logic
* Echo unsafe values

## Template Registry

Create a template registry that defines available public templates.

Each template should include:

* Template key
* Template name
* Template description
* Template file path
* Supported zones
* Required frontend assets
* Default component layout

The first template should be:

```text
default
```

Suggested template metadata:

```php
[
    'key' => 'default',
    'name' => 'Default',
    'description' => 'A polished maintenance mode page with hero, status, contact, social, and login sections.',
    'file' => 'templates/public/default.php',
    'zones' => [
        'main',
        'footer',
    ],
    'assets' => [
        'styles' => [
            'public-template-default',
        ],
        'scripts' => [
            'public-template-default',
        ],
    ],
]
```

## Component Registry

Create a component registry that defines reusable frontend components.

The registry should support:

* Registering components by key
* Checking whether a component exists
* Getting component metadata
* Rendering a component safely
* Returning available components
* Enforcing zone compatibility

The first registered components should be:

* Hero
* Social links
* Contact reveal
* Login
* Status/progress

## Component Interface

Each component should follow a shared interface.

The interface should support:

* Getting the component key
* Getting the component label
* Getting supported zones
* Getting settings schema
* Rendering output from normalized settings

Suggested interface behavior:

```php
interface ComponentInterface
{
    public function get_key(): string;

    public function get_label(): string;

    public function get_supported_zones(): array;

    public function get_settings_schema(): array;

    public function render(array $settings = []): string;
}
```

## Zone Compatibility Rules

Templates should define zones.

Components should define which zones they can render inside.

Initial zones:

```text
main
footer
```

Suggested compatibility:

| Component       | Main Zone | Footer Zone |
| --------------- | --------: | ----------: |
| Hero            |       Yes |          No |
| Status/progress |       Yes |          No |
| Contact reveal  |       Yes |         Yes |
| Social links    |        No |         Yes |
| Login           |        No |         Yes |

If a component is assigned to an incompatible zone, the renderer should skip it safely.

It should not throw a fatal error.

## Theme Variables

The public template should use CSS variables for theme control.

Required variables:

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

The renderer should expose theme settings as safe class names and CSS variables.

Do not output untrusted CSS without validation.

## Color Picker and Readability Controls

Phase 3 must use proper color picker fields for all editable color values in the admin settings.

The admin settings should not require users to manually type hex color values.

Color settings should include:

* Background color
* Surface/card color
* Primary/accent color
* Heading text color
* Body text color
* Muted text color
* Button text color
* Border color

Each color field should:

* Use a WordPress-compatible color picker UI
* Save a sanitized hex color value
* Fall back to a safe default if the value is missing or invalid
* Avoid saving arbitrary CSS strings
* Avoid allowing unsafe values such as gradients, raw CSS, JavaScript, or malformed color values

Accepted color format for Phase 3:

```text
#000000
```

Do not allow unsupported color formats in saved settings during this phase.

Examples of unsupported values:

```text
rgb(0,0,0)
var(--custom-color)
linear-gradient(...)
red
inherit
```

## Light and Dark Theme Color Constraints

Phase 3 must include readable text color constraints for light and dark modes.

The dark theme currently risks low-contrast text in some areas.

The implementation must make sure all public text remains easy to read in both light and dark modes.

Required readable text roles:

* Page heading
* Body message
* Muted/secondary text
* Status text
* Contact text
* Social link labels
* Login link label
* Button text
* Progress/status label

The renderer and CSS should use theme-specific color variables instead of hardcoded text colors.

Required text variables:

```css
--mm-heading-text;
--mm-body-text;
--mm-muted-text;
--mm-link-text;
--mm-button-text;
```

These should map safely in light mode and dark mode.

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

The dark theme must not reuse low-contrast light theme text colors.

Avoid these dark mode mistakes:

* Muted text that is too close to the background
* Button text that blends into the button color
* Social labels that look disabled
* Contact text that appears faded
* Progress labels that are hard to read
* Links that do not stand out from body text

## Contrast Safety Rules

The implementation should apply simple contrast safety rules.

At minimum:

* Heading text must be clearly readable against the page background and surface
* Body text must be clearly readable against the page background and surface
* Muted text must remain readable, not barely visible
* Button text must be readable against the primary/accent color
* Link text must be visibly different from normal body text
* Focus states must remain visible in light and dark mode

If a custom color value creates unreadable output, the system should fall back to a safe default for that color role.

Phase 3 does not need a full WCAG contrast calculator.

However, it must avoid known bad combinations and provide safe defaults.

## Social Media Icon and Label Choices

The social links component must allow users to choose both the social media icon and the visible label.

Each social link item should support:

* Platform/icon choice
* Custom label
* URL
* Open in new tab setting, optional

Initial supported platform/icon choices:

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

Expected behavior:

* If a known platform is selected, render the matching icon
* If `custom` is selected, render a generic link icon
* If `email` is selected, validate the value as an email or safe `mailto:` URL
* If `website` is selected, render a generic website/link icon
* If the custom label is empty, use the selected platform label
* If both label and platform are empty, skip the item safely
* If the URL is empty or invalid, skip the item safely

Default platform labels:

| Platform  | Default Label |
| --------- | ------------- |
| facebook  | Facebook      |
| instagram | Instagram     |
| linkedin  | LinkedIn      |
| x         | X             |
| youtube   | YouTube       |
| github    | GitHub        |
| tiktok    | TikTok        |
| threads   | Threads       |
| website   | Website       |
| email     | Email         |
| custom    | Link          |

The social links component should not invent social media URLs.

Icons should be implemented in a lightweight way.

Preferred options:

* Inline SVG icons from a controlled internal allowlist
* CSS-based simple icons
* Text fallback if icons are unavailable

Do not load a large icon library in Phase 3.

Do not allow users to paste raw SVG or HTML for icons.

## Updated Phase 3 Exit Criteria

Phase 3 is complete when:

* At least one polished template renders cleanly
* The page works across desktop, tablet, mobile, and small mobile
* Components render from saved settings
* Empty states are handled safely
* Public output is escaped safely
* Template assets load only when needed
* Editable color values use color picker fields
* Invalid color values fall back safely
* Light mode text remains readable
* Dark mode text remains readable
* Theme variables control all public text colors
* Social links allow icon/platform selection
* Social links allow custom labels
* Social links skip invalid URLs safely


## Light and Dark Mode

Support these theme modes:

```text
light
dark
system
```

Expected behavior:

* `light` forces the light theme
* `dark` forces the dark theme
* `system` follows the user device preference with `prefers-color-scheme`

The frontend should avoid flashing broken colors.

The default should be:

```text
system
```

## Responsive Shell

The default template should render cleanly on:

* Desktop
* Tablet
* Mobile
* Small mobile

The shell should include:

* Centered page layout
* Safe spacing
* Readable text sizes
* Flexible component stack
* No horizontal overflow
* Touch-friendly buttons
* Safe empty states

Suggested responsive breakpoints:

```css
@media (max-width: 960px) {}
@media (max-width: 640px) {}
@media (max-width: 420px) {}
```

## Default Copy

Use safe defaults when settings are empty.

Default title:

```text
We'll be back soon
```

Default message:

```text
Our site is getting a quick update. Please check back shortly.
```

Default status label:

```text
Maintenance in progress
```

Default progress value:

```text
65
```

Default contact label:

```text
Need help?
```

Default contact text:

```text
Contact us for urgent requests.
```

Default login label:

```text
Admin login
```

## Asset Loading Per Template

Template assets should load only when maintenance mode is active and the matching template is being rendered.

Do not load public template assets across the whole WordPress site.

The asset loading layer should:

* Register template styles
* Register template scripts
* Enqueue only selected template assets
* Use versioning based on plugin version or file modification time
* Avoid duplicate enqueues

## Component Settings Schema

Each component should declare its own settings schema.

The schema should define:

* Setting key
* Label
* Type
* Default value
* Sanitization expectation
* Whether the field is required
* Allowed values, when applicable

Suggested field types:

```text
text
textarea
url
email
boolean
number
select
repeater
```

Phase 3 does not need a complete dynamic admin UI for every component setting.

However, the frontend renderer should be ready to consume saved component settings safely.

## Hero Component

The hero component should render:

* Eyebrow text, optional
* Title
* Message
* Primary action, optional
* Secondary action, optional

Required safety behavior:

* If title is empty, use default title
* If message is empty, use default message
* If action URL is empty, do not render that action
* Escape all text and URLs

## Social Links Component

The social links component should render a list of social links.

Supported fields:

* Label
* URL

Required safety behavior:

* Skip empty URLs
* Skip invalid URLs
* Escape labels
* Render nothing if no valid links exist

Initial supported social labels may include:

* Facebook
* Instagram
* LinkedIn
* X
* YouTube
* GitHub

Do not hardcode fake social URLs.

## Contact Reveal Component

The contact reveal component should render a simple contact section.

Supported fields:

* Contact label
* Contact message
* Email address, optional

Required safety behavior:

* If email is empty, show only the contact message
* If email exists, render it as a safe `mailto:` link
* Do not expose broken or invalid email links

## Login Component

The login component should render a WordPress login link when enabled.

Supported fields:

* Enabled
* Label

Required safety behavior:

* If disabled, render nothing
* If label is empty, use default login label
* Use the WordPress login URL
* Escape the URL and label

## Status/Progress Component

The status/progress component should render maintenance status.

Supported fields:

* Status label
* Progress value
* Show progress

Required safety behavior:

* Clamp progress between `0` and `100`
* If progress is missing, use the default value
* If show progress is false, show only the status label
* Do not render invalid progress attributes

## Empty State Handling

The public page should not break when:

* A title is missing
* A message is missing
* A component setting is missing
* A component has no valid content
* A template key is invalid
* A component key is invalid
* A zone has no components
* A saved option contains an unexpected type

Fallback behavior should be quiet and safe.

Do not expose PHP notices, warnings, or raw errors on the frontend.

## Security Requirements

All public output must be escaped.

Use WordPress escaping helpers where appropriate:

* `esc_html()`
* `esc_attr()`
* `esc_url()`
* `wp_kses_post()`

Sanitize saved settings before use.

Do not trust values loaded from options.

Do not render raw HTML from settings unless explicitly sanitized with an approved allowlist.

## Accessibility Requirements

The public template should include:

* Semantic HTML
* Clear heading hierarchy
* Readable contrast
* Keyboard-accessible links and buttons
* Visible focus states
* Proper progress semantics when progress is shown

The status/progress component should use accessible progress markup.

Suggested markup:

```html
<progress value="65" max="100">65%</progress>
```

## Performance Requirements

The public template should:

* Load only required assets
* Avoid heavy JavaScript
* Work without JavaScript for core content
* Keep CSS scoped to maintenance mode markup
* Avoid layout shifts where possible

## QA Checklist

Before Phase 3 is considered complete, confirm:

* The default template renders with no saved settings
* The default template renders with saved settings
* Invalid template keys fall back safely
* Invalid component keys are skipped safely
* Empty component settings do not create broken markup
* Light mode works
* Dark mode works
* System mode works
* Desktop layout works
* Tablet layout works
* Mobile layout works
* Small mobile layout works
* Public assets load only on the maintenance page
* Admin users can still access the site normally when expected
* Login link works when enabled
* Login link is hidden when disabled
* Progress value is clamped between `0` and `100`
* Social links skip invalid URLs
* Contact email skips invalid email values

## Definition of Done

Phase 3 is done when the plugin has a polished default public template powered by registries, reusable components, safe settings, responsive styling, and predictable empty-state handling.

The system should be simple enough to extend in later phases without rewriting the frontend architecture.

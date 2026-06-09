# Maintenance Mode Studio — Technical Constitution

## 1. Technical Identity

**Plugin name:** Maintenance Mode Studio

**Slug:** maintenance-mode-studio

**Text domain:** maintenance-mode-studio

**PHP namespace:** Maneuvrez\MaintenanceModeStudio

**Primary prefix:** mmsm_

**Reason for prefix:** Maneuvrez Maintenance Mode Studio.

The prefix must be used for:

- global functions
- option keys
- transients
- cron events
- database tables
- non-namespaced constants
- CSS classes where useful
- JavaScript globals where unavoidable

## 2. WordPress Compatibility Target

V1 should target:

- WordPress 6.4+
- PHP 8.0+
- MySQL 5.7+ / MariaDB compatible versions
- modern evergreen browsers

The final supported versions may be adjusted before release after checking WordPress.org plugin directory expectations.

## 3. WordPress.org Compliance Rules

The plugin must follow WordPress.org plugin directory rules.

Core rules:

- GPL-compatible licensing.
- No required third-party service for core functionality.
- No hidden remote code execution.
- No loading executable code from external servers.
- No tracking without clear admin opt-in.
- No admin spam or aggressive upsells.
- All assets bundled with the plugin must have compatible licenses.
- Functional plugin code must live in the distributed plugin package.

WordPress.org Plugin Directory guidelines define the directory as a safe place for users and require plugins to align with WordPress project goals. The technical implementation must be reviewed against those guidelines before submission.

## 4. Build Tooling

Use modern WordPress tooling.

Recommended V1 stack:

- PHP for plugin core
- React for admin app
- `@wordpress/scripts` for build tooling
- WordPress REST API for admin app data
- PHP-rendered frontend templates
- vanilla JavaScript modules for public interactions
- CSS variables for themes

Do not use a React public frontend in V1 unless a specific component needs it.

Reason:

- faster first paint
- simpler fallback rendering
- fewer caching issues
- better reliability on varied WordPress hosting

## 5. Directory Structure

Phase 1 must use one canonical plugin shell layout. Additional feature folders can be introduced later, but the first implementation milestone should start with this structure:

```text
maintenance-mode-studio/
├── maintenance-mode-studio.php
├── readme.txt
├── uninstall.php
├── composer.json
├── package.json
├── phpcs.xml.dist
├── includes/
│   ├── Admin/
│   │   └── Admin.php
│   ├── Frontend/
│   │   ├── MaintenanceRouter.php
│   │   └── TemplateRenderer.php
│   ├── Security/
│   │   └── Sanitizer.php
│   ├── Activator.php
│   ├── Deactivator.php
│   └── Plugin.php
├── public/
│   ├── templates/
│   │   └── default.php
│   └── assets/
│       ├── public.css
│       └── public.js
```

## 6. Option Keys

Use one main option for global settings:

```text
mmsm_settings
```

Use separate options only when needed for performance or data separation:

```text
mmsm_version
mmsm_db_version
mmsm_active_mode
mmsm_layouts
mmsm_access_rules
mmsm_integrations
```

## 7. Database Tables

Use custom tables for submissions and leaderboard data.

### Submissions Table

```text
{$wpdb->prefix}mmsm_submissions
```

Fields:

```text
id bigint unsigned primary key
experience_id varchar(100)
form_type varchar(80)
name varchar(190) nullable
email varchar(190) nullable
message longtext nullable
form_data longtext
score int nullable
ip_hash varchar(128) nullable
user_agent_hash varchar(128) nullable
status varchar(30) default 'new'
created_at datetime
updated_at datetime nullable
```

### Leaderboard Table

```text
{$wpdb->prefix}mmsm_leaderboard
```

Fields:

```text
id bigint unsigned primary key
experience_id varchar(100)
game_type varchar(80)
player_name varchar(190) nullable
email varchar(190) nullable
score int
level int default 1
duration_ms int nullable
metadata longtext nullable
ip_hash varchar(128) nullable
created_at datetime
```

### Bypass Tokens Table

```text
{$wpdb->prefix}mmsm_bypass_tokens
```

Fields:

```text
id bigint unsigned primary key
token_hash varchar(128)
label varchar(190)
expires_at datetime nullable
max_uses int nullable
used_count int default 0
status varchar(30) default 'active'
created_by bigint unsigned nullable
created_at datetime
last_used_at datetime nullable
```

## 8. REST API Namespace

Use:

```text
mmsm/v1
```

V1 endpoints:

```text
GET    /settings
POST   /settings
GET    /experiences
POST   /experiences
GET    /templates
GET    /components
POST   /submissions
GET    /submissions
DELETE /submissions/{id}
GET    /leaderboard
POST   /leaderboard
GET    /bypass-links
POST   /bypass-links
DELETE /bypass-links/{id}
```

Public endpoints must never expose sensitive settings.

Admin endpoints must check capabilities.

## 9. Capabilities

Base capability:

```text
manage_options
```

Custom capability may be introduced later:

```text
manage_mmsm
```

Custom JS capability rule:

- default: only users with `unfiltered_html`
- optional admin setting may allow other administrator-level users if safe
- no non-admin role should receive custom JS control in V1

## 10. Asset Loading Rules

Frontend assets must load only when maintenance mode output is rendered.

Game scripts must load only when the selected game component is active.

Form scripts must load only when a form component is active.

Admin scripts must load only on plugin admin pages.

Do not globally load CSS/JS across the whole WordPress admin.

## 11. Extension Architecture

Use registries for future Pro extension.

Required registries:

- ModeRegistry
- TemplateRegistry
- ComponentRegistry
- GameRegistry
- FormFieldRegistry
- IntegrationRegistry
- AnimationRegistry

Each registry should support:

- register
- unregister
- get
- get_all
- capability checks
- free/pro metadata without enforcing Pro locks in free V1

## 12. Hooks and Filters

Provide filters for future extension:

```php
mmsm_register_modes
mmsm_register_templates
mmsm_register_components
mmsm_register_games
mmsm_register_form_fields
mmsm_render_component_data
mmsm_allowed_bypass_routes
mmsm_submission_before_save
mmsm_submission_after_save
mmsm_leaderboard_before_save
```

Provide actions:

```php
mmsm_before_render
mmsm_after_render
mmsm_before_component
mmsm_after_component
mmsm_submission_created
mmsm_bypass_token_used
```

## 13. Coding Standards

Follow:

- WordPress Coding Standards
- PHP_CodeSniffer with WordPress rules
- ESLint via WordPress scripts
- Prettier if compatible with WordPress scripts

Important comments are required for:

- security-sensitive logic
- data migration
- bypass access
- custom JS handling
- database table creation
- REST permission callbacks
- public submission handling
- rate limiting

Avoid obvious comments that repeat the code.

## 14. External Services

No external service should be required for V1 core functionality.

Spam protection should begin with:

- nonce
- honeypot
- server-side rate limiting
- hashed IP

Captcha providers should be optional integrations:

- Google reCAPTCHA
- Cloudflare Turnstile later

External services must include admin-facing disclosure and settings.

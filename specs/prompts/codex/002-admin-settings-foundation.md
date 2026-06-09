Phase 1 is complete.

The basic maintenance mode page appears correctly.

Now implement Phase 2: Admin Settings Foundation.

Use this file as the source of truth:

specs/features/active.md

Your task:

Implement only the Phase 2 scope described in the active feature spec.

Build:

- mode type selection
- page title setting
- message setting
- theme mode setting
- primary color setting
- login button visibility setting
- public asset loading foundation
- admin asset loading foundation
- settings UX cleanup

Required behavior:

- Use one WordPress option array named maintenance_mode_settings.
- Add safe default values for every setting.
- Merge saved settings with defaults before use.
- Sanitize all saved settings.
- Escape all public output.
- Keep the title and message plain text only.
- Invalid values must fall back safely.
- Missing option keys must not break the public template.
- Public CSS must load only when the maintenance page renders.
- Admin CSS must load only on the plugin settings page.
- The admin settings page must stay simple and WordPress-native.

Recommended settings:

- mode_type: maintenance or coming_soon
- page_title: text input
- message: textarea
- theme_mode: light or dark
- primary_color: hex color
- show_login_button: 1 or 0

Do not build:

- countdown timers
- email capture
- analytics
- third-party integrations
- advanced templates
- role-based access controls beyond current admin checks
- duplicate class structures
- unrelated refactors

Important implementation rules:

- Preserve the current project structure unless a change is necessary.
- Do not create competing duplicate files or folders.
- Keep responsibilities clear:
  - admin class handles settings page, settings registration, field rendering, sanitization, and admin assets
  - router or public controller handles public rendering, settings loading, and public assets
  - template handles escaped HTML output only
- Do not put sanitization logic inside the public template.
- Do not render unsanitized option values.
- Use WordPress functions where appropriate:
  - sanitize_text_field()
  - sanitize_textarea_field()
  - sanitize_hex_color()
  - esc_html()
  - esc_attr()
  - esc_url()
  - wp_login_url()

Before making changes:

1. Inspect the current project structure.
2. Identify the existing Phase 1 files.
3. Confirm which files need to be edited.
4. Avoid unnecessary files.

After implementation:

1. List every file changed.
2. Explain what changed in each file.
3. Confirm how each Phase 2 requirement was satisfied.
4. Note any assumptions or trade-offs.
5. Do not mark the phase complete unless all exit criteria in specs/features/active.md are met.
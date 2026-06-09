# Codex Task: Normalize Project Docs And Update Active Spec

## Goal

Normalize the Maintenance Mode Studio planning docs so all docs, specs, and prompts agree on one canonical project identity, Phase 1 scope, and folder structure before implementation starts.

## Project Constants

Use these values everywhere:

    Plugin name: Maintenance Mode Studio
    Extended title: Maintenance Mode Studio - Coming Soon, Games, Forms and Interactive Pages
    Publisher: Abu Hurarrah
    Publisher domain: https://abuhurarrah.com
    Creator credit: Abu Hurarrah
    Creator domain: https://abuhurarrah.com
    Contact email: hello@abuhurarrah.com
    Slug: maintenance-mode-studio
    Prefix: mmsm_
    PHP namespace: Maneuvrez\MaintenanceModeStudio
    Text domain: maintenance-mode-studio
    REST namespace: mmsm/v1
    License: GPL-2.0-or-later

## Files To Review And Update

Update these files if needed:

    README.md
    docs/constitution/product.md
    docs/constitution/technical.md
    docs/constitution/design.md
    docs/constitution/security.md
    docs/roadmap/phases.md
    specs/features/active.md
    specs/prompts/codex/001-plugin-shell.md
    specs/prompts/review/001-plugin-shell-review.md

## Required Canonical Folder Structure For Phase 1

All docs and specs must agree that Phase 1 creates this structure:

    maintenance-mode-studio.php
    readme.txt
    uninstall.php
    composer.json
    package.json
    phpcs.xml.dist
    includes/Plugin.php
    includes/Activator.php
    includes/Deactivator.php
    includes/Admin/Admin.php
    includes/Frontend/MaintenanceRouter.php
    includes/Frontend/TemplateRenderer.php
    includes/Security/Sanitizer.php
    public/templates/default.php
    public/assets/public.css
    public/assets/public.js

## Required Active Spec Sections

Update `specs/features/active.md` so it uses these sections:

- Objective
- Scope
- Files to create
- Files not to touch
- Acceptance criteria
- Security rules
- Testing checklist
- Done criteria
- Out of scope
- Implementation notes

The active spec must describe only the first implementation milestone.

## Required Phase 1 Scope

Phase 1 includes:

- Plugin bootstrap
- Activation hook
- Deactivation hook
- Text domain loading
- Basic admin page
- Basic maintenance mode setting
- Basic frontend maintenance router
- Basic default public template
- Scoped frontend CSS and JS foundation
- Logged-in admin bypass
- Login page bypass
- REST, AJAX, cron, and WP-CLI bypass

Phase 1 does not include:

- Games
- Forms
- Surveys
- Drag-and-drop zones
- Visual builder
- Leaderboard
- Secret bypass links
- Custom CSS editor
- Custom JS editor
- reCAPTCHA
- Email integrations
- Pro licensing
- Payments

## Identity Normalization

Replace all incorrect creator values.

Do not use:

    Abu Hurairah
    Shafi
    abuhurairah.com

Use only:

    Abu Hurarrah
    https://abuhurarrah.com

## Technical Normalization

Replace any flat `includes/*.php` class layout with the canonical nested structure.

Use:

    includes/Admin/Admin.php
    includes/Frontend/MaintenanceRouter.php
    includes/Frontend/TemplateRenderer.php
    includes/Security/Sanitizer.php

Do not use:

    includes/Admin.php
    includes/MaintenanceRouter.php
    includes/TemplateRenderer.php
    includes/Sanitizer.php

## Roadmap Normalization

Keep the roadmap simple:

    Phase 0 - Constitution and Specs
    Phase 1 - Plugin Shell and Minimal Maintenance Mode
    Phase 2 - Admin Settings Foundation
    Phase 3 - Template and Component Registry
    Phase 4 - Forms and Submissions
    Phase 5 - Games and Leaderboard
    Phase 6 - Visual Preview and Drag Zones
    Phase 7 - Access Rules and Bypass Links
    Phase 8 - Polish, QA, and WordPress.org Release

Make sure the roadmap does not conflict with `specs/features/active.md`.

## Acceptance Criteria

- All docs use the same plugin identity.
- All docs use the same creator credit.
- All docs use the same folder structure.
- All docs agree on Phase 1 scope.
- `specs/features/active.md` describes only Phase 1.
- Codex prompt `001-plugin-shell.md` matches the active spec.
- Review prompt `001-plugin-shell-review.md` matches the active spec.
- The legacy `000-update-active-spec.md` prompt is no longer the source of truth.
- No docs reference old names, old domains, or flat class paths.
- No implementation files are created in this task.

## Output

After completion, summarize:

- Files changed
- Conflicts fixed
- Remaining risks

# Maintenance Mode Studio — Phase Roadmap

## Phase 0 — Constitution and Active Spec

Goal: lock product and technical rules before coding.

Deliverables:

- product constitution
- technical constitution
- design constitution
- security constitution
- active feature spec
- initial plugin folder plan

Exit criteria:

- product name approved
- prefix approved
- V1 scope approved
- first build milestone defined

## Phase 1 — Plugin Shell and Minimal Maintenance Mode

Goal: create a working WordPress plugin foundation with a safe minimal maintenance-mode flow.

Build:

- main plugin file
- namespaced loader
- activation/deactivation classes
- constants
- admin menu shell
- basic settings registration
- maintenance mode enable/disable
- frontend request interception
- admin bypass
- login page bypass
- REST/AJAX/cron/WP-CLI bypass rules
- basic default maintenance page
- WordPress.org-ready readme scaffold

Exit criteria:

- plugin activates without fatal errors
- admin menu appears
- settings page loads
- logged-out visitors see the default maintenance page only when enabled
- logged-in admins see the real site
- bypass rules work safely

## Phase 2 — Admin Settings Foundation

Goal: expand the basic shell into a more complete settings-driven mode manager.

Build:

- mode type selection
- page title and message settings
- theme mode and color controls
- login button setting
- asset loading foundation
- settings UX cleanup

Exit criteria:

- settings cover the Phase 1 public template
- settings persist safely
- the admin experience remains simple

## Phase 3 — Template and Component Registry

Goal: render polished public pages and define reusable frontend building blocks.

Build:

- PHP template renderer
- template registry
- component registry
- zone compatibility rules
- theme variables
- light/dark mode
- responsive shell
- default copy
- asset loading per template
- component settings schema
- hero component
- social links component
- contact reveal component
- login component
- status/progress component

Exit criteria:

- at least one polished template renders cleanly
- page works across desktop, tablet, mobile, and small mobile
- components render from saved settings
- empty states are handled safely

## Phase 4 — Forms and Submissions

Goal: collect visitor input safely.

Build:

- form templates
- custom fields
- REST submission endpoint
- nonce/honeypot/rate limit
- hashed IP tracking
- database table
- admin submissions table
- email notifications
- CSV export

Exit criteria:

- visitors can submit forms
- admin can view submissions
- admin can export CSV
- spam basics are active

## Phase 5 — Games and Leaderboard

Goal: add the first interactive game.

Build:

- Reaction Challenge game module
- progressive difficulty
- scoring rules
- local leaderboard
- admin leaderboard toggle
- admin score view/clear controls
- optional user info capture after game

Exit criteria:

- game works on desktop and mobile
- leaderboard can be enabled/disabled
- score submissions are rate-limited

## Phase 6 — Visual Preview and Drag Zones

Goal: make admin page configuration visual.

Build:

- React admin app
- preview tab
- drag-and-drop zone editor
- component settings panels
- saved layout structure
- simple/advanced mode switching

Exit criteria:

- admin can reorder components in allowed zones
- preview reflects settings
- saved layout renders on frontend

## Phase 7 — Access Rules and Bypass Links

Goal: expand access controls and visitor bypass behavior safely.

Build:

- secret bypass token foundation
- password-protected visitor access
- multiple experience assignment rules
- route exclusions
- admin controls for bypass management

Exit criteria:

- bypass flows remain safe and revocable
- admins can manage access rules without confusion

## Phase 8 — Polish, QA, and WordPress.org Release

Goal: polish the product and prepare the public release package.

Build/check:

- WordPress Coding Standards
- PHPCS
- ESLint
- build output
- readme.txt
- screenshots
- assets licensing
- uninstall cleanup
- translation readiness
- accessibility pass
- responsive testing
- security review

Exit criteria:

- plugin zip builds cleanly
- no critical coding standard issues
- WordPress.org submission package is ready

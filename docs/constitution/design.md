# Maneuvrez Maintenance Studio — Design Constitution

## 1. Design Direction

The visual direction must be a balanced mix:

- professional SaaS
- playful arcade
- futuristic 3D
- premium landing page

The design must appeal to clients and end users, not only developers.

## 2. Frontend Design Promise

Every generated maintenance page must feel like a high-quality landing page.

The frontend must never feel like a default WordPress settings output.

## 3. Template Philosophy

Templates are curated experiences.

Each template should have:

- strong first impression
- clear status message
- useful visitor action
- interactive element
- contact path
- social path
- login path if enabled
- graceful mobile layout

## 4. V1 Templates

V1 should include at least these templates:

1. **Arcade Launch**
   - game-first layout
   - bold hero
   - animated background
   - leaderboard option

2. **Studio Pause**
   - premium SaaS style
   - clean cards
   - form and contact focused

3. **Neon Console**
   - futuristic dashboard look
   - pointer-reactive objects
   - status card
   - login modal

4. **Calm Coming Soon**
   - elegant launch/waitlist template
   - light/dark support
   - minimal animation

## 5. Layout Zones

Use fixed zones to prevent broken layouts.

Required zones:

```text
Header
Hero
Primary Interactive
Secondary Panel
Footer
Floating Actions
```

Each component must declare compatible zones.

The builder must reject incompatible placements.

## 6. Component Design Rules

Every component must support:

- title
- description
- visibility toggle
- empty-state fallback
- responsive layout behavior
- light/dark styling
- animation setting if relevant

Every component must avoid layout overflow.

Every component must work inside narrow containers.

## 7. Responsive Rules

Supported layout targets:

```text
Small mobile: 320px - 374px
Mobile: 375px - 767px
Tablet: 768px - 1023px
Desktop: 1024px - 1439px
Widescreen: 1440px+
```

Rules:

- mobile-first CSS
- no horizontal scrolling
- no fixed-width content blocks
- forms must fit 320px screens
- games must fit 320px screens
- modals must become sheets or full-screen panels on small screens
- 3D/pointer effects must reduce on touch devices
- long text must wrap safely
- buttons must remain tappable
- floating actions must not block content

## 8. Animation Rules

Animations must be interactive but controlled.

Allowed V1 animation types:

- pointer-reactive tilt cards
- floating objects
- parallax layers
- glowing gradients
- particle-like CSS effects
- progress/status animation
- micro-interactions

Rules:

- respect `prefers-reduced-motion`
- provide admin controls for animation intensity
- disable heavy motion on low-power/mobile contexts where needed
- never let animation block form input, login, or gameplay
- never require animation for meaning

## 9. 3D Asset Rules

Default 3D assets must be lightweight and license-safe.

Admin uploads may be allowed only with clear file validation.

V1 should prefer CSS/JS pseudo-3D over heavy Three.js scenes.

Optional advanced 3D can be added later.

## 10. Theme Controls

Admin should control:

- logo
- background image
- background style
- primary color
- accent color
- text color mode
- button style
- card style
- animation intensity
- dark/light mode

Default palettes must look polished without user effort.

## 11. Admin Preview Rules

Visual preview must show:

- selected template
- active components
- colors
- logo
- basic responsive preview controls

Preview may be approximate in early phases, but final V1 preview should closely match frontend output.

## 12. Accessibility Rules

Frontend must support:

- keyboard navigation
- visible focus states
- readable contrast
- semantic buttons and forms
- labels for form fields
- no animation-only instructions
- reduced motion support

Games should remain playable with basic keyboard/touch support where practical.

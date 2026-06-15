# Maneuvrez Maintenance Studio — Security Constitution

## 1. Security Principle

The plugin handles public forms, admin settings, login access, bypass links, custom CSS, custom JS, uploads, and game scores.

Security must be designed into every module from the start.

## 2. Data Handling Rules

All input must be validated and sanitized before saving.

All output must be escaped at render time.

Never trust:

- admin settings
- public form submissions
- uploaded file metadata
- REST request payloads
- query parameters
- cookies
- custom field definitions
- game score payloads

## 3. Nonce Rules

Use nonces for:

- admin settings updates
- admin REST requests where appropriate
- public form submissions
- game score submissions
- bypass token creation/deletion
- CSV export requests

State-changing requests must never run without capability checks and nonce checks.

## 4. Capability Rules

Admin settings require:

```text
manage_options
```

Custom JS requires:

```text
unfiltered_html
```

The plugin may allow a site administrator to expose custom JS controls to other administrator users, but it must never expose custom JS to editor/author/contributor/subscriber roles.

## 5. Public Form Protection

V1 spam protection must include:

- nonce validation
- honeypot field
- rate limiting
- hashed IP tracking
- validation per field type
- maximum field lengths
- blocked empty payloads

Captcha integrations may be added as optional settings.

## 6. IP Handling

Do not store raw IP addresses by default.

Store a hash for rate limiting and anti-spam logic.

Use a site-specific salt.

## 7. Bypass Link Rules

Secret bypass links must:

- store only token hashes
- show raw token only once during creation
- support expiration
- support revoke/delete
- support optional max uses
- not grant admin access
- not bypass WordPress authentication
- log last used time where appropriate

Bypass cookies must be scoped, time-limited, and revocable.

## 8. Login Rules

Login UI may expose the normal WordPress login flow.

The plugin must not implement its own password authentication system unless strictly needed for visitor password access.

Password-protected visitor access must:

- use hashed password storage
- use rate limiting
- avoid user enumeration
- avoid revealing whether a password is correct until final validation

## 9. Upload Rules

Admin uploads may include:

- logo
- background image
- icons
- optional 3D assets

Rules:

- validate file type
- validate size
- use WordPress media library where possible
- do not execute uploaded files
- do not allow SVG by default unless sanitized through a safe library
- restrict 3D formats and size limits if 3D upload is included

## 10. Custom CSS Rules

Custom CSS may be allowed for administrators.

CSS must be stored as text and rendered only inside the plugin frontend scope when practical.

Warn admins that custom CSS can break the frontend layout.

## 11. Custom JS Rules

Custom JS is high-risk.

Rules:

- disabled by default
- only users with `unfiltered_html` by default
- clear warning before enabling
- rendered only on the maintenance frontend
- never rendered in admin preview without safeguards
- never accepted from public submissions

## 12. Game Score Rules

Game scores are user-submitted and cannot be trusted.

V1 leaderboards should be treated as lightweight engagement, not secure competition.

Rules:

- validate score type/range
- rate limit score submissions
- associate with hashed IP where needed
- block impossible score values where possible
- allow admin moderation/clearing

## 13. Email Rules

Use `wp_mail()` for V1.

Email content must escape or sanitize submitted data.

Do not inject raw submitted HTML into admin emails.

## 14. CSV Export Rules

CSV export must:

- require capability check
- require nonce
- escape cells to reduce formula injection risk
- include only requested submission data
- avoid exposing raw security hashes unless needed

## 15. External Integration Rules

External services must be optional.

Admin must clearly configure:

- provider
- site key
- secret key
- enabled forms/modes

No tracking or external scripts should load until the admin enables the integration.

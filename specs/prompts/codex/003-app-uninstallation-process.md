# Active Task: App Uninstallation Process Feedback and Data Removal

## Branch

`fix/003-plugin-uninstallation-process`

## Objective

Improve the plugin uninstallation/deactivation flow by asking users why they are removing the plugin and whether they want plugin data removed when the plugin is uninstalled.

## Context

Users currently uninstall or deactivate the plugin without giving feedback. This creates two problems:

1. We lose useful product feedback about why users are leaving.
2. Users may be unclear about whether plugin data remains in WordPress after uninstall.

This task should add a non-blocking feedback and data-removal preference flow.

## Required Behavior

When the user attempts to deactivate/remove the plugin from the WordPress admin plugins screen:

1. Show a feedback prompt asking why they are removing the plugin.
2. Provide predefined feedback reasons.
3. Allow optional free-text feedback.
4. Ask whether they want plugin data removed during uninstall.
5. Allow the user to skip feedback.
6. Never block the user from deactivating or uninstalling the plugin.

## Feedback Options

Show the following uninstall/deactivation feedback reasons:

- I no longer need the plugin
- The plugin did not work as expected
- The plugin caused an issue on my site
- The plugin is missing features I need
- The plugin is too difficult to use
- I found a better alternative
- I am troubleshooting temporarily
- Other

If `Other` is selected, allow the user to enter optional text.

## Data Removal Option

Ask the user:

> Do you also want to remove plugin data when uninstalling?

Options:

- Keep plugin data
- Remove plugin data on uninstall

Default: `Keep plugin data`

The user’s choice should be saved as a plugin option, for example:

`plugin_remove_data_on_uninstall`

During actual uninstall, delete plugin-owned data only if this option is enabled.

## Technical Requirements

- Implement the feedback prompt on the plugin deactivation action, not inside the uninstall hook.
- The modal/prompt must be optional and dismissible.
- The user must be able to continue deactivation without submitting feedback.
- Do not automatically send feedback externally without explicit user action.
- If feedback is sent to an external endpoint, clearly disclose this in the UI.
- Use nonce verification for AJAX requests.
- Sanitize and validate all submitted fields.
- Escape all output rendered in wp-admin.
- Only users with the proper plugin-management capability should be able to submit feedback or update uninstall preferences.
- Store the data-removal preference locally in WordPress.
- On uninstall, check the stored preference before deleting plugin data.
- Ensure uninstall cleanup is safe, idempotent, and does not throw fatal errors.

## Privacy and Consent

Feedback submission must be explicit and optional.

If feedback is sent to our server, the UI must clearly state that feedback will be sent externally. The plugin must not silently collect or transmit uninstall feedback, site details, admin email, license data, or other telemetry.

## Acceptance Criteria

- A feedback prompt appears when the user clicks deactivate/remove for the plugin.
- The user can select a reason for uninstalling/deactivating.
- The user can optionally provide text feedback.
- The user can choose whether plugin data should be removed on uninstall.
- The user can skip the prompt and continue deactivation.
- The user’s data-removal choice is saved.
- Plugin data is deleted during uninstall only when the user opted into data removal.
- Plugin data is preserved during uninstall when the user chose to keep data or did not make a choice.
- Feedback is not sent externally unless the user explicitly submits it.
- The uninstall process is not blocked by feedback submission failures.
- Code follows WordPress security practices for capability checks, nonce verification, sanitization, and escaping.

## Notes

Do not ask for feedback inside `uninstall.php` or the uninstall hook. The uninstall hook should only perform cleanup. The feedback UI should happen before uninstall/deactivation while the admin UI is still available.
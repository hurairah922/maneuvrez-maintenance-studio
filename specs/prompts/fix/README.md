````md
# Fix Prompts

This directory contains prompts used to fix issues found after implementation or review.

Fix prompts should be narrow, controlled, and tied to a specific review result.

They should not introduce new features.

---

## Purpose

Use this directory when a phase has already been implemented and a review has found issues that need correction.

Examples:

- failed review checks
- missing requirements
- unsafe sanitization
- unsafe escaping
- broken asset loading
- incorrect file structure
- incomplete settings behavior
- WordPress coding issues
- scope creep from an implementation prompt

---

## When to Use Fix Prompts

Use a fix prompt only after one of these happens:

1. An execution prompt has already been run.
2. A review prompt has identified specific issues.
3. A manual inspection found a clear defect.
4. A test or local WordPress check exposed a bug.

Do not use fix prompts to start new work.

For new work, use:

```text
prompts/execute/
````

For review work, use:

```text
prompts/review/
```

---

## Fix Prompt Rules

Every fix prompt must:

* reference the active feature spec
* reference the latest review findings or issue list
* ask for the smallest safe change
* avoid unrelated refactors
* avoid new features
* preserve the existing project structure
* require a changed-files summary
* require a final verification against the active spec

---

## Standard Fix Prompt Structure

Use this structure for every fix prompt:

```text
Fix the issues found in the latest review.

Source of truth:

- specs/features/active.md
- latest review findings

Your task:

Only fix the listed issues.

Do not add new features.
Do not refactor unrelated code.
Do not rename files unless required.
Do not change the project structure unless required.
Do not modify documentation unless the issue is documentation-specific.

For each fix:

1. Identify the issue.
2. Explain the smallest safe change.
3. Make the change.
4. List the file changed.
5. Explain why the change fixes the issue.
6. Confirm that the result still matches specs/features/active.md.

After fixing:

1. List all changed files.
2. Map each fix back to the review finding.
3. Confirm whether all Phase exit criteria are now met.
4. List any remaining risks or unresolved items.

Do not mark the phase complete if any exit criteria are still missing.
```

---

## Naming Convention

Use this format:

```text
001-fix-phase-name.md
002-fix-phase-name.md
003-fix-phase-name.md
```

Examples:

```text
001-fix-plugin-shell.md
002-fix-admin-settings-foundation.md
003-fix-public-template-settings.md
```

If a fix is tied to a specific review, name it after the phase or feature, not the bug.

Good:

```text
002-fix-admin-settings-foundation.md
```

Avoid:

```text
fix-random-bugs.md
fix-stuff.md
latest-fixes.md
```

---

## Scope Control

Fix prompts must stay smaller than execution prompts.

A fix prompt should not say:

```text
Improve the whole plugin.
Clean up everything.
Make this production ready.
Refactor the architecture.
Add anything missing.
```

Instead, use controlled wording:

```text
Only fix the issues listed below.
Make the smallest safe changes.
Do not add new features.
Do not refactor unrelated files.
```

---

## Required References

Each fix prompt should include:

```text
specs/features/active.md
```

And one of these:

```text
latest review findings
manual issue list
test failure output
specific bug description
```

---

## Output Expectations

A good fix response from Codex should include:

* status of the fix
* files changed
* issues fixed
* requirements verified
* risks or remaining issues

Suggested output format:

```text
Status:

Files changed:

Issues fixed:

Verification:

Remaining risks:
```

---

## Phase Completion Rule

A phase is complete only when:

1. the execution prompt has been run
2. the review prompt has passed
3. all fix prompts have been applied
4. the final review returns PASS
5. the active spec exit criteria are satisfied

Do not treat a fix as phase completion by itself.

---

## Current Project Pattern

The recommended workflow is:

```text
specs/features/active.md
        ↓
prompts/execute/
        ↓
prompts/review/
        ↓
prompts/fix/
        ↓
final review
```

Use this directory only for the fix step.

---

## Notes

Keep fix prompts boring.

Boring prompts are safer.

The goal is not to make Codex creative.

The goal is to make Codex precise.

```
```

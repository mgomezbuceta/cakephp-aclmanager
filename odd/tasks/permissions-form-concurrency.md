# Permissions form: optimistic concurrency and single-field submit (audit phase 2b)

## Objective
1. Saving the permission matrix must never silently overwrite changes made by another administrator after the form was loaded.
2. With JavaScript, the form must post a constant, tiny number of input variables regardless of how many permissions are granted (removes any practical dependence on PHP `max_input_vars`). Without JavaScript the current `resource_ids[]` form keeps working.

## Problem (Sourcery PR #3 comments #3 and #5)
- #3: two admins load the same role; one saves; the other saves a stale form and `replaceRolePermissions()` erases the first admin's changes (delete-all + re-insert).
- #5: one input var per granted id is still O(granted); a role with ~10k granted actions could hit `max_input_vars` (detected by the sentinel but not saveable).

## Decisions
- **Version token**: `PermissionService::getPermissionsVersion(int $roleId): string` = sha1 of the sorted `plugin|controller|action` keys of the role's allowed permissions (timestamp-independent; identical content => identical token). The form carries it in a hidden `permissions_version` field rendered BEFORE the sentinel.
- **Save contract**: `replaceRolePermissions(int $roleId, array $resourceIds, ?string $expectedVersion = null): int`. When `$expectedVersion` is not null it is compared with the current version INSIDE the same transaction as the delete/insert; mismatch throws a dedicated `AclManager\Service\StalePermissionsException` (extends `\RuntimeException`) BEFORE any change. The controller treats a missing/empty `permissions_version` as an invalid form (error flash, no change) and a stale one as a conflict (specific error flash telling the admin that someone else changed the role and the page was reloaded with the current data; redirect to manage). Known limitation (document in the code comment and the CHANGELOG): the check and the write share a transaction but no row lock (portable across SQLite/MySQL/Postgres), so two requests landing within the same few milliseconds can still race.
- **Single-field submit (JS)**: on form submit a small inline script collects the checked `resource_ids[]` checkbox values, writes them comma-separated into a hidden `resource_ids_csv` field, and disables the individual checkboxes so they are not posted. The server accepts `resource_ids_csv` (string of comma-separated positive integers; validated strictly like the array form; empty string = no grants) OR `resource_ids[]` (no-JS fallback). If both are present, CSV wins. The sentinel stays the LAST field.
- JS stays inline in `templates/Permissions/manage.php` (no asset-publishing dependency for host apps), ~15 lines, no framework.

## TDD
Mode: strict. Runner: `PATH=<php8.3 shim>:$PATH composer exec phpunit`.

## Tasks
- [x] C1 Version token + stale detection in service (RED tests first) — delegated writer
- [x] C2 Controller + template: hidden version field, conflict/invalid flashes (translated en_US/es_ES/gl_ES) — delegated writer
- [x] C3 CSV submit: server parsing + inline JS; verify the script for real (jsdom in a scratch dir outside the repo) — delegated writer
- [x] C4 Docs in BOTH languages (README, INSTALL: how saving works, conflict message, no-JS fallback, max_input_vars note) keeping GuidesParityTest green; CHANGELOG — delegated writer

## Acceptance criteria
- Two sequential saves from the same loaded form: first succeeds, second is rejected with the conflict flash and leaves the first save's data intact.
- A save with the current version succeeds; version is stable across reloads when nothing changed and changes when a grant is added/removed.
- Missing/empty version => rejected, nothing changed.
- `resource_ids_csv` with N ids grants exactly the valid ones ('5abc', '0', '-1', '' ignored); array fallback still works; CSV wins when both present.
- With JS (jsdom run): submitting a page with 3,000 checked boxes yields a POST body with at most 5 fields.
- Existing 79 tests stay green.

## Progress / evidence
- Branch `fix/permissions-form-concurrency` from master (c0df977).
- C1 RED: `composer exec phpunit -- tests/TestCase/Service` -> 5 errors (undefined `getPermissionsVersion`). GREEN after implementing (+ `StalePermissionsException`).
- C2/C3 RED: `phpunit tests/TestCase/Controller` -> 6 failures (stale second save, missing version, CSV valid ids, CSV wins, empty CSV, rendered fields/order). GREEN with controller (`submittedResourceIds()`, version check, stale catch) + template (hidden `permissions_version`, disabled `resource_ids_csv`, sentinel last) + translated flashes in en_US/es_ES/gl_ES.
- Design note: `resource_ids_csv` is rendered `disabled` and only enabled by the script on submit, so a no-JS post never sends an empty CSV (which would win and clear the role).
- jsdom (scratchpad, not in repo): 3503 checkboxes (1751 checked); posted entries after submit = 3 (`permissions_version`, `resource_ids_csv`, `permissions_submitted`); csv equals checked ids exactly; checkboxes disabled right after submit, re-enabled after a cancelled submit (timeout 0) and on `pageshow`.
- C4: README/INSTALL (en/es) "how saving works + conflict message" paragraphs, CHANGELOG Added. Parity tests green.
- Full suite: `composer exec phpunit` -> OK (92 tests, 286 assertions). phpcs: no violations on changed lines. Not committed (per instructions).

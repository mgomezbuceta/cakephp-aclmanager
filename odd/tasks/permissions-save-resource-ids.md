# Permissions save via resource ids

## Objective
Saving a role's permission matrix must not depend on PHP `max_input_vars` and must never silently drop or wipe permissions.

## Problem
`templates/Permissions/manage.php` posts ~5 input vars per action (3 hidden + checkbox hidden `0` + checkbox). PHP silently truncates beyond `max_input_vars`; `savePermissions()` then runs `revokeAll()` and re-grants only what arrived, losing the rest. No transaction; N+1 queries per grant.

Previous fix `0f33747` was reverted in `7f62e84`, most likely because it stored `plugin = NULL` for app controllers while `AclManagerPolicy` / `AuthorizationManagerComponent` look up `plugin = 'App'`.

## Decisions
- Form posts `resource_ids[]` (only checked rows, no hidden `0` field). Server loads resources by id, validates they exist and are active.
- App-level plugin stays stored as the string `'App'` in `permissions` (resources keep `NULL`; convert `NULL` -> `'App'` when granting).
- Sentinel hidden field at the end of the form; missing sentinel => abort without touching data (truncated POST).
- Delete + insert inside one transaction, bulk save.
- Tests: PHPUnit 10 + SQLite in-memory, schema via `tests/schema.php` (core SchemaLoader). Run with PHP 8.3 (PHP 8.4 CLI lacks mbstring).

## TDD
Mode: strict (user global config). Runner: `PATH=<php8.3 shim>:$PATH composer exec phpunit`.

## Tasks
- [x] T1 Test infrastructure (phpunit.xml.dist, tests/bootstrap.php, tests/schema.php, fixtures) — route: delegated writer
- [x] T2 RED tests for savePermissions (resource ids, 'App' mapping, invalid ids ignored, truncated POST aborts, clear-all works) — route: delegated writer
- [x] T3 GREEN implementation (template, controller, service bulk/transactional replace) — route: delegated writer
- [x] T4 phpcs on touched files, CHANGELOG entry — route: inline

## Acceptance criteria
- Posting N checked ids creates exactly N `permissions` rows with `allowed = true`, plugin `'App'` for app resources.
- Unknown or inactive ids are ignored.
- POST without sentinel leaves existing permissions untouched and shows an error.
- Posting sentinel with no ids clears the role's permissions.

## Progress / evidence
- Branch `fix/permissions-save-resource-ids` created. Dependencies installed (PHPUnit 10.5.66).
- RED (src/templates stashed, old code): `composer exec phpunit` -> 6 tests, 4 failures (replace, ignore invalid ids, sentinel abort, checkbox render).
- GREEN: `composer exec phpunit` -> OK (6 tests, 18 assertions).
- phpcs (CakePHP) on PermissionsController/PermissionService: 0 errors, 1 pre-existing warning (line length of the success flash, unchanged line).

## Next step
Manual check in a host app with many resources; re-save roles that may hold `plugin = NULL` rows from the 0f33747 period.

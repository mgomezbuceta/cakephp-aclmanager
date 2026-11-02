# Plugin normalization and data integrity (audit phase 2a)

## Objective
One canonical value (`'App'`) for app-level plugin everywhere; permission writes are validated, deduplicated, transactional and free of N+1 queries.

## Problem (audit + Sourcery PR #3)
- H4 / Sourcery #4: `resources.plugin` is NULL for app, `permissions.plugin` is `'App'`; `grant/deny/isAuthorized` default to NULL so API callers (and README examples) write/read rows the policy never matches.
- Sourcery #6 / M11: unique indexes include nullable `plugin`; NULL rows can duplicate, collide when mapped to 'App' and abort saves.
- Sourcery #2: `intval` accepts '5abc' and arrays as resource ids.
- Sourcery #7: `id IN (...)` binds every id in one query.
- Sourcery #8: `testSaveDoesNotTouchOtherRoles` passes on a no-op save.
- M2: `copyPermissions` has no transaction, doesn't clear target, accepts source == target, N+1 saves.
- M8: `scanAndSync` does find+save per action and O(n^2) deactivation, no transaction.
- M11: migrations 0001/0002/0003 inconsistent (0001 create() without hasTable guard; 0003 duplicates 0002 for roles).

## Decisions
- Canonical app plugin value: the string `'App'` in both `resources` and `permissions` (user decision). New migration converts NULL -> 'App' (dedupe first), with `down()` reverting `resources` to NULL.
- Normalize `null -> 'App'` at the service/table API boundary (grant, deny, isAuthorized, setPermission), so old callers keep working.
- Remove the scattered `?? 'App'` conversions once data is canonical (keep a defensive normalization only at the API boundary).
- Delivery: phase 2 split in two PRs, sequential to master: 2a (this doc) data/service; 2b form (optimistic concurrency + single-field JS submit).

## TDD
Mode: strict. Runner: `PATH=<php8.3 shim>:$PATH composer exec phpunit`.

## Tasks
- [x] D1 Normalization: API boundary null->'App', scanner stores 'App', remove scattered conversions, test fixtures use 'App' — route: delegated writer
- [x] D2 Migration 0004 normalize NULL->'App' with dedupe + down(); guard 0001 with hasTable; make 0003 idempotent/no-op where 0002 already did it — route: delegated writer
- [x] D3 replaceRolePermissions: strict id validation, dedupe by permission key, chunked IN; strengthen test #8 — route: delegated writer
- [x] D4 copyPermissions transactional, clear target, reject same role, bulk save — route: delegated writer
- [x] D5 scanAndSync: single preload map, bulk save, transaction — route: delegated writer
- [x] D6 CHANGELOG + README examples — route: delegated writer

## Acceptance criteria
- `grant($r,'Articles','edit')` then `isAuthorized($r,'Articles','edit')` and the policy lookup with 'App' both return true.
- Scanner creates app resources with plugin 'App'; re-scan creates no duplicates.
- Ids like '5abc', [1], '-1', '0' are ignored; valid ids still granted.
- Two resources mapping to the same permission key produce one permission, no error.
- Saves with > chunk size ids work.
- copyPermissions replaces target permissions atomically; same source/target rejected.

## Progress / evidence
- Branch `fix/plugin-normalization-data-integrity` from master (7a1a616).

### Phase 2a implementation (uncommitted)
Mode strict; runner `composer exec phpunit` (PHP 8.3 shim).

RED (baseline 21 green, tests written first -> 37 tests: 2 errors, 8 failures):
- D1: PermissionServiceTest::testGrantWithoutPluginIsStoredAndFoundAsApp (isAuthorized false: null vs 'App'), testGrantAppAndGrantNullDoNotDuplicate; ResourceScannerServiceTest::testRealScanTagsApplicationControllersWithAppPlugin (plugin null).
- D2: AppPluginNormalizationTest (2 tests) error: class AppPluginNormalization not found.
- D3: testReplaceIgnoresMalformedIds (2 granted, expected 1: intval accepted ' 2'/[1]), testReplaceCollapsesResourcesSharingAPermissionKey (2 vs 1).
- D4: testCopyReplacesTargetPermissions, testCopyWithEmptySourceClearsTarget (target kept stale rows), testCopyRejectsSameRoleAndKeepsPermissions (true, expected false).
- D5: scanner tests green-on-arrival except the 'App' one (behavior preserved by the rewrite).
GREEN: `composer exec phpunit`: OK (37 tests, 90 assertions). Migration SQL executed on SQLite via AclManager\Migration\AppPluginNormalization (MySQL/Postgres not executed).
phpcs: no new violations on changed lines (remaining ones pre-existing).
Deviation: 0003 was already guarded per column; only added a hasTable guard.

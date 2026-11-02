# Test coverage and documentation accuracy (audit phase 5)

## Objective
Close the measured coverage gaps with behavior-focused tests, add guards that stop translations and docs from drifting, enforce a coverage floor in CI, and make the documentation say only what the code does.

## Baseline (xdebug, PHP 8.3, this branch before any change)
Lines 91.56% (662/723); methods 65.71% (46/70); classes fully covered 5/11. Gaps (uncovered statements):
- `AuthorizationManagerComponent` 20.69% lines (6/29): the whole authorization path (lines 57-70 user/role/controller/action/plugin resolution), `checkPermission` with and without `cachePermissions` (84-102), `getRoleId` (113-115), cache key (129), cache clearing (139).
- `ResourceScannerService` 87.25% lines, 3/12 methods: plugin controller scanning (142-144), class-name resolution for plugins (239), unreadable/invalid class path (221-223), ignore rules (265, 308, 334), key building with plugin (353), `getGroupedResources` filtering (378-392: ignored actions, controllers and plugins).
- `PermissionsController` 92.12%, 8/14 methods: `manage()` without role (68-70), `addRole`/`editRole`/`deleteRole` failure branches (227-232, 261, 291), `copyPermissions` invalid and same-role branches (309-317), `clearPermissions` invalid role (357-359).
- `AppController` (149/154: login redirect carrying a query string; 236: `is_admin` branch; 245: deny by default), `AclManagerPolicy` line 41 (no role -> false), `PermissionService` line 284 (false branch).

## Decisions
- Tests assert behavior (outcomes visible to a host app), not private structure. Reuse the existing patterns: loginAs session helper, `configApplication(Application::class, [])`, `ThrowingPermissionsTable`-style locator swaps, `FakeScanner`.
- For plugin-controller scanning, add a tiny test plugin under `tests/TestApp/Plugin/` (or the repo's established path) with one controller and load it in the test only; no production code is changed to make it testable unless a real defect is found (then report it and fix with a failing test first).
- **Translation guard test**: every `__d('acl_manager', '<msgid>')` literal found in `src/` and `templates/` exists as a msgid in `resources/locales/{en_US,es_ES,gl_ES}/acl_manager.po`; every msgid in those files is used somewhere in `src/` or `templates/` (flags stale entries); es_ES and gl_ES have no empty `msgstr` for used msgids; the three files contain the same msgid set. Fix any real drift the test finds (missing translations, stale entries).
- **Coverage floor in CI**: new composer script `coverage` (phpunit with clover output into a gitignored path) plus a small check script `tests/bin/check-coverage.php <clover.xml> <min-line-percent>` (plain PHP, no dependencies) that exits non-zero below the threshold; a CI job `coverage` on PHP 8.3 with `coverage: pcov`... NOTE: local runs use xdebug (`XDEBUG_MODE=coverage`); CI should use `coverage: xdebug` or `pcov`, whichever works with the workflow (verify in the PR's CI run). Threshold = the achieved line coverage rounded down to an integer, minus nothing; document it in README Contributing (EN/ES) and CHANGELOG. Keep the existing actions pinned by SHA (reuse the SHAs already in `.github/workflows/ci.yml`).
- **Docs accuracy sweep**: for README.md/README.es.md (Features, "Why", Architecture, Quick Start, Documentation sections), INSTALL.md/INSTALL.es.md, CLAUDE.md and AGENTS.md: verify each factual claim against the code (e.g. "permission caching", "strict permission checking mode", "authorization middleware integration", version numbers, file/class names, route examples, config keys). Fix or remove anything the code does not do; keep EN/ES symmetric (GuidesParityTest green). Do NOT bump the plugin version (`AclManager.version` stays; the maintainer decides the next release number) — only report whether the hard-coded value in `config/bootstrap.php` and docs is consistent.

## TDD
Characterization tests for already-working code pass on arrival (that is expected: report which ones). RED first where a defect or missing behavior is found (report each). Runner: `PATH=<php8.3 shim>:$PATH composer exec phpunit`; coverage: `XDEBUG_MODE=coverage composer exec phpunit -- --coverage-clover <scratch path>`.

## Tasks
- [x] V1 AuthorizationManagerComponent tests
- [x] V2 ResourceScannerService tests (plugin scan, ignore rules, grouped resources, error path)
- [x] V3 PermissionsController failure/edge branches
- [x] V4 AppController/Policy/PermissionService remaining branches
- [x] V5 Translation guard test + fix drift
- [x] V6 Coverage script, check script, CI job, threshold, docs
- [x] V7 Docs accuracy sweep

## Acceptance criteria
- All tests green (>= 155 + new), phpcs and phpstan still pass.
- Line coverage >= 97% and no source class below 90% lines (report any exception with the reason).
- CI coverage job passes on the PR.

## Progress / evidence
- Branch `test/coverage-and-docs` stacked on `ci/github-actions` (PR #10).
- After (xdebug, PHP 8.3): lines 100.00% (723/723), methods 100% (70/70), every source class 100% lines. Tests 155 -> 196 (889 assertions).
- Characterization tests (V1-V4: component, scanner with test-only plugin `tests/TestApp/Plugin/TestPlug`, RoleManagementTest, admin/policy/service branches) passed on arrival. RED: `TranslationCatalogTest::testNoCatalogEntryIsStale` failed first (en/es/gl catalogs held unused `Controller` and `Plugin`); entries removed -> GREEN. RED: `LocaleTest::testPageLanguageAttributeFollowsTheActiveLocale` (layout hard-coded `<html lang="es">`); layout now uses the active locale -> GREEN.
- V6: `composer coverage` + `tests/bin/check-coverage.php` (own test `CoverageCheckScriptTest`), CI job `coverage` (PHP 8.3, xdebug, SHAs reused), threshold 100. CI result pending (verify in the PR run).
- V7: docs sweep done (README/INSTALL pairs, CLAUDE.md, AGENTS.md). `AclManager.version` 3.2.0 is consistent in bootstrap, layout fallback and docs; `config/app_local.example.php` still says 3.0.0 (not changed, maintainer decision).
- phpcs 0 errors, phpstan level 8 OK, GuidesParityTest green. Nothing committed.

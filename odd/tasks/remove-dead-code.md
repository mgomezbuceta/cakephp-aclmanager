# Remove dead code, duplicate routes and global side effects (audit phase 3)

## Objective
Delete what is broken or unused and stop the plugin from changing the host application's global state, without changing behavior that is documented and working.

## Decisions (user approved)
- **Locale**: the plugin no longer calls `I18n::setLocale()` at load. Plugin pages follow the host application's locale. New optional config `AclManager.locale` (default `null`): when set, the plugin's own controllers apply it for that request only (in `AppController::beforeFilter`). Hosts without a locale see the English msgids.
- **Delete** `src/View/Helper/AclManagerHelper.php` (imports the uninstalled `cakephp/acl`; fatal on use) and `src/View/AppView.php` (only purpose is loading that helper; nothing in the plugin selects it). Verify nothing references them (src, templates, config, docs, tests).
- **Remove dead config defaults** from `config/bootstrap.php`: `defaultRoleId`, `cachePermissions`, `cacheDuration`, `permissionMode`, `paginationLimits` and the derived `AclManager.{roles,permissions,resources}.limit` loop. Verify first with `rg -n "Configure::read|Configure::check"` that no code reads them (also `admin`, `version`: keep whichever IS read). Remove the "Planned / not implemented" notes from the guides (all four) since the keys no longer exist; CHANGELOG "Removed" lists the keys. Component unused settings (`userModel`, `cacheConfig`, `cacheDuration` in `AuthorizationManagerComponent`) : remove if truly unread, else leave and report.
- **Remove the legacy `/acl-manager` mount**: delete the `routes()` override in `src/AclManagerPlugin.php` (and the other no-op overrides `middleware()` / `bootstrap()` if they only call parent or return the argument). Routes then come only from `config/routes.php`. Also dedupe the two copy-pasted route blocks in `config/routes.php` (admin prefix vs standard) into one shared closure. Route URLs under `/authorization-manager` and `/admin/authorization-manager` must stay identical (add a routing test that asserts the existing URL -> controller/action matches, written BEFORE refactoring, and one asserting `/acl-manager/...` no longer routes).
- **Docs fix**: README says `$this->loadComponent('AclManager.AclManager')`; the real component is `AuthorizationManager` (`src/Controller/Component/AuthorizationManagerComponent.php`). Fix the snippet and its prose in README.md/README.es.md after reading the component.
- **Small cleanups** (only if verified unused/unreferenced): redundant `use LocatorAwareTrait` in `AppController` if `Controller` already provides it; unused `Resource::_getFullIdentifier`; `__()` -> `__d('acl_manager', ...)` in `AuthorizationManagerComponent` (L2, also add the msgid to the 3 .po files); stale msgids in the .po files that no template/src string uses (verify each against `rg`); unreferenced `webroot/css/default.css` and `webroot/img/*.png`; tracked `README.cp3.md` if tracked (it is already in .gitignore); add `.gitattributes` with `export-ignore` for `tests/`, `odd/`, `docs/`, `.snapshots/`, `phpunit.xml.dist`, `.gitattributes` so Composer dist archives stay lean (keep `docs/sql` out of export-ignore only if the guides tell users to run those files from the installed package — check what the guides say and decide; if they reference `docs/sql`, do NOT export-ignore `docs/`).
- Do NOT touch (later phases): admin-check ordering (M7), mass assignment (M9), flash messages with exception text (L5), static TableRegistry (L7), phpcs bulk fixes, CI.

## TDD
Mode: strict. Runner: `PATH=<php8.3 shim>:$PATH composer exec phpunit`.

## Tasks
- [x] X1 Locale: remove setLocale from bootstrap; `AclManager.locale` applied in AppController; tests (default leaves I18n locale untouched; configured locale applied for the request only, restored/unchanged globally after the request if feasible) — delegated writer
- [x] X2 Routing tests first, then remove `/acl-manager` mount, no-op plugin overrides, dedupe routes.php — delegated writer
- [x] X3 Delete helper + AppView, dead config keys (+ component unused settings), docs fixes in both languages (component name, locale section, config reference, notes removal), parity test green — delegated writer
- [x] X5 Remove the never-working `AclManager.admin` flag (user decision, option 1) and add `AclManager.mountPath` with defensive normalization; routing/dispatch tests first
- [x] X4 Small cleanups listed above, .gitattributes, CHANGELOG (Removed/Changed/Fixed) — delegated writer

## Acceptance criteria
- `rg "I18n::setLocale" src config` finds nothing outside the new per-request `AclManager.locale` handling.
- Existing URLs keep routing identically; `/acl-manager/*` returns 404 (MissingRoute).
- `rg "AclManagerHelper|Acl\\\\Controller" src templates config tests` finds nothing.
- Guides (EN/ES) describe `AclManager.locale`, no longer list removed keys, show the correct component name; GuidesParityTest green.
- All previous tests (92) still green plus the new ones.

## Progress / evidence
- Branch `chore/remove-dead-code` stacked on `fix/permissions-form-concurrency` (PR #6 still open).
- X1 RED: `LocaleTest` 3 of 4 failed (locale forced to es_ES, gl_ES flash not applied). GREEN after removing setLocale and adding `AclManager.locale` in `AppController::beforeFilter`: 96 tests OK.
- X2 RED: `RoutesTest` only `testLegacyAclManagerMountNoLongerRoutes` failed (22 tests; mount characterization tests green before refactor). GREEN after deleting the plugin overrides and sharing one closure in config/routes.php: 118 tests OK.
- Finding (not changed, behavior preserved): with `AclManager.admin = true` the real URL is `/admin/admin/authorization-manager` (prefix path plus the plugin `path` option both add `/admin`); docs say `/admin/authorization-manager`. Pinned by test; needs a decision.
- X3: deleted AclManagerHelper, AppView; removed dead defaults and unused component options (`userModel`, `cacheConfig`, `cacheDuration`); 4 guides updated; GuidesParityTest green.
- X4: removed redundant LocatorAwareTrait, `Resource::_getFullIdentifier` (no references), `webroot/`, `README.cp3.md`, stale msgid `Modified`; `__d` in component (+ test, RED not observed); `.gitattributes` (docs/ kept: guides reference docs/sql); CHANGELOG updated.
- Final: `composer exec phpunit`: OK (119 tests, 388 assertions).
- X5 decision: `AclManager.admin` removed (the Admin-prefix mount raised MissingControllerException and produced /admin/admin/...); `AclManager.mountPath` added. RED: 13 new tests failed (custom mount, messy values, dispatch 200 as admin). GREEN: `composer exec phpunit`: OK (127 tests, 413 assertions); phpcs clean on routes.php and RoutesTest.

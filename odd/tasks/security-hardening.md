# Security hardening (audit phase 4a)

## Objective
Close the remaining low-impact security and robustness gaps found by the audit without changing who is allowed in.

## Decisions (user approved)
- **Admin check semantics are NOT changed**: `AppController::isUserAdmin()` keeps "the first identity field that exists decides" (order: `customCheck` -> `role_name` -> `role_id` -> `is_admin` -> `email`). Changing it to OR would silently grant admin to people who are not admins today. Only harden details that cannot change the outcome for valid configurations: cast the `customCheck` result to bool (a non-bool return currently throws a TypeError under strict_types), compare role ids as normalized strings with strict `in_array` (DB ids often arrive as strings: "1" must still match 1), cast role names to string before `strtolower`. Document the exact precedence in a docblock and in the four guides (it is user-facing security behavior).

## Scope
1. **isUserAdmin hardening** as above (+ tests: precedence is preserved: role_id=2 + is_admin=1 => denied; "1" matches adminRoleIds [1]; customCheck truthy non-bool => allowed without TypeError, falsy => denied; role_name case-insensitive).
2. **Mass assignment**: `Role`, `Permission`, `Resource` entities: no `permissions`, `created`, `modified`, `id`, `role_id` (as applicable) accessible unless the code legitimately patches them; `PermissionsController::addRole/editRole` pass `'fields' => ['name','description','priority','active']` explicitly. Tests: a POST containing `id`, `created`, `modified`, `permissions` does not alter them.
3. **No exception text in flash messages**: every `catch (\Exception $e)` in `PermissionsController` that flashes `$e->getMessage()` must instead log the exception (`Cake\Log\Log::error()` with message + exception class + trace summary; no secrets) and flash a generic translated message (add msgids with translations to en_US/es_ES/gl_ES; remove the now-unused old msgids). Keep the specific, intentional, translated messages (stale permissions, same-role copy, admin role guards). Tests: induce a failure (e.g. replace the Permissions table in the locator with one that throws, or a service subclass) and assert the flash has no exception text and a log entry exists (use `Log::setConfig` with an array/memory engine in the test).
4. **Silent component load failures**: `AppController::initialize()` swallows exceptions when loading Authorization/Authentication components. Keep fail-closed behavior but log a warning with the component name and exception message so integrators can diagnose a 403.
5. **Template output escaping audit**: grep every `<?=` and every `'escape' => false` in `templates/`. User-controlled values (role name/description, controller/action names, plugin names) must be escaped with `h()` (or be inside an escaping helper); `escape => false` is only acceptable for strings the template builds itself (icons) — if a role name is concatenated into such a string (e.g. confirm messages, link labels), escape it. Add a test that renders the roles/manage/index pages with a role named `<script>alert(1)</script>` and asserts the raw tag does not appear unescaped (including inside `data-confirm`/`onclick` JS strings that postLink generates).

## Out of scope
phpcs bulk fixes, phpstan, CI, static TableRegistry, type declarations (phase 4b/4c).

## TDD
Mode: strict. Runner: `PATH=<php8.3 shim>:$PATH composer exec phpunit`.

## Tasks
- [x] H1 isUserAdmin hardening + precedence docs (4 guides, parity green) — delegated writer
- [x] H2 Mass assignment lockdown — delegated writer
- [x] H3 Generic flashes + logging + msgids — delegated writer
- [x] H4 Component load failure logging — delegated writer
- [x] H5 Escaping audit + tests — delegated writer

## Acceptance criteria
- Existing 127 tests green plus new RED-first tests for H1–H5.
- No `getMessage()` inside a Flash call in `src/Controller/`.
- Precedence documented identically in EN and ES guides.

## Progress / evidence
- Branch `fix/security-hardening` stacked on `chore/remove-dead-code` (PR #7, which is stacked on #6).
- Route: single delegated writer for H1-H5 (same session), strict TDD, runner `composer exec phpunit` (PHP 8.3 shim).
- RED (before implementation, 150 tests, 3 errors + 13 failures):
  - H1: `AdminAccessTest::testNonStringRoleNameIsDeniedWithoutError` (TypeError strtolower), `testCustomCheckTruthyNonBoolResultIsAllowed` / `testCustomCheckFalsyNonBoolResultIsDenied` (TypeError return type). Precedence/"1"-vs-1/case tests were already green and are kept as regression guards.
  - H2: `SecurityHardeningTest::testEntitiesDoNotExposeManagedFieldsToMassAssignment`, `testEditRoleIgnoresManagedFields` (created overwritten), `testAddRoleIgnoresManagedFields`.
  - H3: four failure-injection tests (save/sync/copy/clear) failed: flash still carried the exception text and nothing was logged.
  - H4: both component-load tests failed: log was empty.
  - H5: roles, dashboard and manage page tests failed. Real finding: `postLink` with `'escape' => false` rendered the role name raw inside `data-confirm-message` (stored XSS); `for="permission-<?= $fieldIndex ?>"` was unescaped.
- GREEN: `composer exec phpunit`: OK (150 tests, 524 assertions); `php -l` clean on all changed PHP; phpcs reports only pre-existing issues on untouched lines.
- Changes: AppController (cast/strict isUserAdmin + docblock, loadOptionalComponent logging), PermissionsController (ROLE_FIELDS, logFailure + 4 generic msgids), entities `_accessible`, templates (`escapeTitle` instead of `escape` on the two delete postLinks, `h()` on label `for`), en_US/es_ES/gl_ES catalogs, 4 guides (precedence + troubleshooting), CHANGELOG.
- Not committed (per instructions).

# Admin access security hardening (audit phase 1)

## Objective
Only administrators can execute any Authorization Manager action; destructive actions are POST-only and CSRF-protected; admin roles cannot be deleted or deactivated.

## Problem (audit findings)
- H1: `AppController::checkAdminAccess()` calls `redirect()` but the response is discarded (`beforeFilter(): void`), so the action still runs for anonymous/non-admin users.
- H2: when the Authentication component is not loaded, access is granted to everyone (fail-open).
- H3: `clearPermissions` allows GET and `syncResources` has no `allowMethod`; both are linked with plain links -> CSRF via GET.
- M3: admin-role protection (`AclManager.adminAccess.adminRoleIds`) only hides UI buttons; `deleteRole`/`editRole` don't enforce it.

## Decisions
- H1: stop the request from `beforeFilter` by setting the event result to the redirect response (CakePHP 5 way).
- H2: fail closed. Access without Authentication only if the integrator opts in explicitly with `AclManager.adminAccess.allowWithoutAuthentication = true` (default false), or a configured `customCheck` grants it. Breaking change -> CHANGELOG.
- H3: `allowMethod(['post'])` for `clearPermissions` and `syncResources`; UI uses `Form->postLink` (inside `manage.php` form use the `block` option to avoid nested forms).
- M3: server-side guard: admin roles cannot be deleted, and `active` cannot be set to false on them.
- Tests: add `cakephp/authentication` to require-dev; TestApp with an Authentication middleware/identity to exercise the gate.

## TDD
Mode: strict. Runner: `PATH=<php8.3 shim>:$PATH composer exec phpunit`.

## Tasks
- [x] S1 Test app with Authentication + RED tests for H1/H2 — route: delegated writer
- [x] S2 GREEN H1/H2 — route: delegated writer
- [x] S3 RED+GREEN H3 (POST-only, postLink) — route: delegated writer
- [x] S4 RED+GREEN M3 (admin role guard) — route: delegated writer
- [x] S5 CHANGELOG/README/INSTALL notes (breaking change H2, new config key) — route: delegated writer

## Acceptance criteria
- Anonymous GET/POST to any plugin action -> redirect to login, no side effects.
- Logged-in non-admin -> redirect to unauthorized URL, no side effects (e.g. deleteRole does not delete).
- Admin -> actions work as before.
- No Authentication component and no opt-in -> 403/deny; with opt-in -> allowed.
- GET to clearPermissions/syncResources -> 405, no side effects.
- Deleting or deactivating an admin role is rejected with an error flash.

## Progress / evidence
- Branch `fix/admin-access-security` from master (d9aa4ea).
- Dev dep added: `cakephp/authentication:^3.0`. TestApp: AuthenticationMiddleware + Session authenticator (tests log in via `$this->session(['Auth' => [...]])`); `/no-auth` route -> `NoAuthController` (extends AppController, no Authentication component) simulates a host without cakephp/authentication. Existing manage tests now authenticate as admin.
- RED (S1/S3/S4, before any src change; 20 tests, 2 errors + 6 failures): testAnonymousIndexRedirectsToLogin and testAnonymousDeleteRoleRedirectsAndDoesNotDelete (UnauthenticatedException: action/component ran, gate did not stop the request); testNonAdminDeleteRoleRedirectsAndDoesNotDelete (role deleted); testWithoutAuthenticationComponentAccessIsDenied (no ForbiddenException, fail-open); testClearPermissionsRejectsGet (permissions deleted by GET); testSyncResourcesRejectsGet (no 405); testAdminRoleCannotBeDeleted and testAdminRoleCannotBeDeactivated (no error flash, role changed).
- GREEN: `composer exec phpunit`: OK (21 tests, 47 assertions). phpcs (CakePHP standard) clean on added/changed lines; pre-existing violations in touched files left as is.
- Decisions: beforeFilter sets `$event->setResult($response)` + stopPropagation; no component -> ForbiddenException unless `allowWithoutAuthentication=true` or `customCheck(null)` is truthy; editRole deactivation of an admin role redirects to roles with error flash (no save); messages added to en_US/es_ES/gl_ES.

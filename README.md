English | [Español](README.es.md)

<div align="center">

# 🔐 CakePHP Authorization Manager

### Modern Role-Based Permission Management for CakePHP 5.x

[![Latest Version](https://img.shields.io/packagist/v/mgomezbuceta/cakephp-aclmanager.svg?style=flat-square)](https://packagist.org/packages/mgomezbuceta/cakephp-aclmanager)
[![PHP Version](https://img.shields.io/packagist/php-v/mgomezbuceta/cakephp-aclmanager.svg?style=flat-square)](https://packagist.org/packages/mgomezbuceta/cakephp-aclmanager)
[![License](https://img.shields.io/packagist/l/mgomezbuceta/cakephp-aclmanager.svg?style=flat-square)](LICENSE.md)
[![Downloads](https://img.shields.io/packagist/dt/mgomezbuceta/cakephp-aclmanager.svg?style=flat-square)](https://packagist.org/packages/mgomezbuceta/cakephp-aclmanager)

**A powerful, modern web interface for managing role-based permissions using CakePHP's Authorization plugin.**

[Features](#-features) • [Installation](#-installation) • [Quick Start](#-quick-start) • [Documentation](#-documentation) • [Upgrading](#-upgrading-from-320) • [Migration](#-migration-from-v2x)

---

</div>

## 🌟 Features

<table>
<tr>
<td width="50%">

### 🎯 **Role-Based Access Control**
Simple yet powerful RBAC system with role priorities and hierarchical permission management.

### 🔄 **Auto-Discovery**
Automatically scans your application for controllers and actions—no manual configuration needed.

### 🎨 **Modern UI**
Beautiful Bootstrap 5 interface with intuitive permission matrices and visual role management.

### 🌍 **Multilingual Support**
Built-in i18n support with Spanish and Galician translations ready to use.

</td>
<td width="50%">

### ⚡ **High Performance**
Built on CakePHP's Authorization plugin; the permission lookups use your own database.

### 🛡️ **Secure by Default**
Deny by default: an action is only allowed when a permission is granted to the user's role, and only administrators can open the manager.

### 🚀 **CakePHP 5.x Native**
Fully compatible with CakePHP 5.x using modern Authorization instead of deprecated ACL.

</td>
</tr>
</table>

---

## 💡 Why Authorization Manager?

Traditional ACL systems (acos/aros) are **deprecated in CakePHP 5.x**. This plugin provides:

- ✅ **Modern Authorization** - Uses CakePHP's official Authorization plugin
- ✅ **Simplified Structure** - No more complex ACO/ARO trees
- ✅ **Visual Management** - Web interface for managing permissions
- ✅ **Role-Based** - Industry-standard RBAC pattern
- ✅ **Easy Integration** - Drop-in authorization solution
- ✅ **Multilingual** - Spanish and Galician translations included

---

## 📋 Requirements

| Requirement | Version |
|------------|---------|
| PHP | >= 8.1 (with `ext-intl` and `ext-mbstring`) |
| CakePHP | ^5.1 (see `composer.json`) |
| CakePHP Authentication | ^3.0 (required unless you opt out, see Step 2) |
| CakePHP Authorization | ^3.0 |
| CakePHP Migrations | ^4.5 or ^5.0 (only needed for database paths A and B) |
| Database | MySQL/MariaDB, PostgreSQL or SQLite (schema scripts in `docs/sql/`) |

---

## 🚀 Installation

A step-by-step guide starting from an empty CakePHP 5 application. The same guide is also available as a standalone file: [INSTALL.md](INSTALL.md) (Spanish: [INSTALL.es.md](INSTALL.es.md)).

> **Back up your database before running any migration or SQL from this guide.** Nothing runs automatically: the plugin never changes your schema until you run a migration or script yourself.

### Step 1: Install the packages

```bash
composer require mgomezbuceta/cakephp-aclmanager
composer require cakephp/authentication cakephp/authorization
composer require "cakephp/migrations:^4.5 || ^5.0"   # only for database paths A and B
```

`cakephp/migrations` is listed under `suggest` (not `require`) because path C (manual SQL) does not need it. The plugin's migrations extend `Migrations\BaseMigration`, so they need migrations ^4.5 (which has `BaseMigration`) or ^5.0 (which only has `BaseMigration`); versions below 4.5 are not supported. `cakephp/authentication` is also only suggested: without it every request to the manager is rejected with a 403 unless you opt out (see Step 2).

### Step 2: Create the users table and configure Authentication

The manager only lets administrators in, so it must know who the current user is. Your `users` table needs a `role_id` column (added in Step 6). Configure Authentication in `src/Application.php`; this follows the plugin's own test application (`tests/TestApp/Application.php`, Session authenticator with the Password identifier):

```php
use Authentication\AuthenticationService;
use Authentication\AuthenticationServiceInterface;
use Authentication\AuthenticationServiceProviderInterface;
use Authentication\Middleware\AuthenticationMiddleware;
use Cake\Http\BaseApplication;
use Cake\Http\MiddlewareQueue;
use Cake\Routing\Middleware\RoutingMiddleware;
use Psr\Http\Message\ServerRequestInterface;

class Application extends BaseApplication implements AuthenticationServiceProviderInterface
{
    public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
    {
        return $middlewareQueue
            ->add(new RoutingMiddleware($this))
            ->add(new AuthenticationMiddleware($this));
    }

    public function getAuthenticationService(ServerRequestInterface $request): AuthenticationServiceInterface
    {
        $service = new AuthenticationService();
        $identifier = ['Authentication.Password' => ['fields' => ['username' => 'email', 'password' => 'password']]];
        $service->loadAuthenticator('Authentication.Session', ['identifier' => $identifier]);
        $service->loadAuthenticator('Authentication.Form', [
            'identifier' => $identifier,
            'loginUrl' => '/users/login',
        ]);

        return $service;
    }
}
```

Keep your usual middleware (error handler, asset, CSRF, ...) in the queue as well; this snippet only shows what the plugin needs. Provide a login action as described in the [Authentication docs](https://book.cakephp.org/authentication/3/en/index.html). By default the manager redirects anonymous users to `Users::login` (`AclManager.redirects.login`).

**How the manager decides who is an administrator** (`AclManager\Controller\AppController::isUserAdmin()`). The first rule that applies decides, in this order:

1. `AclManager.adminAccess.customCheck`: if it is a callable, it receives the identity and its return value is final.
2. Identity has a non-null `role_name`: admin if it matches (case-insensitive) one of `AclManager.adminAccess.adminRoleNames` (default `admin`, `administrator`, `superadmin`).
3. Identity has a non-null `role_id`: admin if it is in `AclManager.adminAccess.adminRoleIds` (default `[1]`).
4. Identity has a non-null `is_admin`: admin if it is truthy.
5. Identity has an `email` and `AclManager.adminAccess.adminEmails` is not empty: admin if the email is listed.
6. Otherwise: denied.

The first field that is present on the identity decides and the later ones are ignored; the rules are not combined with OR. Example: an identity with `role_id` 2 and `is_admin` 1 is NOT an administrator, because `role_id` is present and `is_admin` is never looked at. `adminEmails` only applies when the identity has none of `role_name`, `role_id` or `is_admin`. The result of `customCheck` is converted to a boolean and role ids are compared as strings (`"1"` matches `1`). Non-admins are redirected to `AclManager.redirects.unauthorized`.

**No `cakephp/authentication`?** Without the Authentication component the plugin fails closed (HTTP 403). Only if you protect the manager some other way (e.g. HTTP auth in the web server) set `AclManager.adminAccess.allowWithoutAuthentication` to `true`, or provide a `customCheck` callable (it is called with `null` in this case).

### Step 3: Authorization: enforcing the permissions in your application

Installing the manager only gives you the screens to edit permissions. Your application decides what to enforce, and the plugin offers two building blocks. Both look up `role_id` and the request's controller/action/plugin, and both are **deny by default**: an action with no granted permission for the role is refused. Application controllers are stored with plugin `'App'`.

**Option 1: policy (`AclManager\Policy\AclManagerPolicy`).** A request policy for `cakephp/authorization`. It reads `role_id` from the original identity data (`$identity->getOriginalData()->role_id`), so your identifier must return an entity or object, not a plain array. Register it for `ServerRequest`:

```php
use AclManager\Policy\AclManagerPolicy;
use Authorization\AuthorizationService;
use Authorization\AuthorizationServiceInterface;
use Authorization\AuthorizationServiceProviderInterface;
use Authorization\Middleware\AuthorizationMiddleware;
use Authorization\Policy\MapResolver;
use Cake\Http\ServerRequest;

// class Application ... implements AuthorizationServiceProviderInterface
public function getAuthorizationService(ServerRequestInterface $request): AuthorizationServiceInterface
{
    return new AuthorizationService(new MapResolver([ServerRequest::class => AclManagerPolicy::class]));
}

// in middleware(), after AuthenticationMiddleware:
$middlewareQueue->add(new AuthorizationMiddleware($this, ['requireAuthorizationCheck' => false]));
```

Then check the request in your own `src/Controller/AppController.php`. Do not apply the check to the manager's own pages: they are guarded by the admin rules above and are excluded from resource sync, so no role could ever be granted them.

```php
use Cake\Http\Exception\ForbiddenException;

public function beforeFilter(\Cake\Event\EventInterface $event): void
{
    parent::beforeFilter($event);

    if ($this->request->getParam('plugin') === 'AclManager') {
        return;
    }
    $identity = $this->request->getAttribute('identity');
    if (!$identity || !$identity->can('access', $this->request)) {
        throw new ForbiddenException();
    }
}
```

The policy decision (`can('access', $request)` through `MapResolver`) is covered by `tests/TestCase/Policy/AclManagerPolicyTest.php`. The `beforeFilter` snippet itself is plain `cakephp/authorization` usage and is not exercised by the test suite. `RequestAuthorizationMiddleware` would also work, but it checks the manager's pages too, so you would have to exclude them yourself.

**Option 2: component (`AclManager.AuthorizationManager`).** A helper, not a hook. CakePHP 5 has no automatic `isAuthorized()` callback, so the component does nothing until you call it:

```php
// AppController::initialize()
$this->loadComponent('AclManager.AuthorizationManager', ['roleField' => 'role_id']);

// wherever you want to enforce, e.g. beforeFilter()
$identity = $this->request->getAttribute('identity');
$user = $identity?->getOriginalData()?->toArray();
if (!$this->AuthorizationManager->isAuthorized($user)) {
    return $this->AuthorizationManager->handleUnauthorized();
}

// check an arbitrary role/resource
$this->AuthorizationManager->checkPermission($roleId, 'Articles', 'edit', 'Blog');
```

`isAuthorized(?array $user)` takes an array and returns `false` for `null` or when `roleField` is missing. Component options: `roleField` (default `role_id`), `unauthorizedRedirect` (default `Users::login`) and `cachePermissions` (default `true`; caches results per component instance, call `clearCache()` to reset).

### Step 4: Load the plugin

In `src/Application.php`:

```php
public function bootstrap(): void
{
    parent::bootstrap();

    $this->addPlugin('AclManager', ['bootstrap' => true, 'routes' => true]);
}
```

The plugin's `config/bootstrap.php` writes the defaults described in the [configuration reference](#configuration-reference) (without overriding values you already set in `config/app.php`).

### Step 5: Create the database tables

Pick one path. **Back up first.**

**Path A: fresh database, use migrations (recommended).** None of `roles`, `permissions`, `resources` exists yet:

```bash
bin/cake migrations migrate -p AclManager
```

The migrations create the three tables with their indexes and foreign key. See [Migration 0004](#migration-0004-never-deletes-data) below for the data normalization step.

**Path B: you already have a `roles` table (or any of the three).** The migrations never alter an existing table by default.

- If the table already has every required column, the migrations leave it untouched and continue.
- If columns are missing, `bin/cake migrations migrate -p AclManager` stops with an error naming the table, the missing columns, the config flag and `docs/sql/`. Choose:
  - **B1, let the migrations add the columns:** set `Configure::write('AclManager.migrations.adoptExistingTables', true);` in `config/app.php` (or `config/bootstrap.php`) **before** running the migration. Missing columns are added with safe defaults; existing data is kept. Only columns are added: indexes and foreign keys of an adopted table are not created (copy them from `docs/sql/` if you want them), and rolling back never drops a table the migrations did not create.
  - **B2, apply the SQL yourself:** run the statements from [`docs/sql/adopt-existing-roles.sql`](docs/sql/adopt-existing-roles.sql) for your engine (compare `permissions` and `resources` with the schema scripts), then run the migrations; they will find a compatible table and do nothing to it.

Required columns (besides `id`): `roles`: `name, description, priority, active, created, modified`; `permissions`: `role_id, controller, action, plugin, allowed, created, modified`; `resources`: `controller, action, plugin, description, active, created, modified`.

**Path C: no migrations plugin.** Run the full schema for your engine once: [`docs/sql/mysql.sql`](docs/sql/mysql.sql), [`docs/sql/postgres.sql`](docs/sql/postgres.sql) or [`docs/sql/sqlite.sql`](docs/sql/sqlite.sql). Only the SQLite script is executed by the test suite; the MySQL and PostgreSQL scripts have not been run in CI, so review them before use.

#### Migration 0004 never deletes data

`20250101000004_NormalizeAppPlugin` converts an empty plugin (`NULL` or `''`) to `'App'` in `resources` and `permissions` (so the policy can match application controllers). It only runs `UPDATE`s. If converting would create a duplicate, it **aborts before changing any row** and tells you how many rows collide in each table:

```
Migration NormalizeAppPlugin aborted, nothing was changed. ... (resources: 2, permissions: 0). Review and clean the duplicates with docs/sql/cleanup-duplicates.sql ...
```

Duplicates mean a NULL row for a controller/action that also exists as `'App'` (or twice as NULL). To resolve: back up, open [`docs/sql/cleanup-duplicates.sql`](docs/sql/cleanup-duplicates.sql), run the detection `SELECT`s, review what would be removed, run the cleanup `DELETE`s for your engine (lowest id is kept), then run the migration again. Rolling the migration back sets `resources.plugin` to NULL; permissions stay `'App'`.

### Step 6: Add `role_id` to your users table

Match the type of `roles.id` (signed `INT` when created by the migrations). Keep the column nullable at first so you can bootstrap the first administrator (Step 7).

```sql
-- MySQL / MariaDB
ALTER TABLE users ADD COLUMN role_id INT NULL;
ALTER TABLE users ADD CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles (id) ON DELETE SET NULL;

-- PostgreSQL
ALTER TABLE users ADD COLUMN role_id INTEGER NULL REFERENCES roles (id) ON DELETE SET NULL;

-- SQLite (no ALTER ... ADD CONSTRAINT; the inline REFERENCES is accepted for new columns)
ALTER TABLE users ADD COLUMN role_id INTEGER NULL REFERENCES roles (id) ON DELETE SET NULL;
```

### Step 7: First run

1. Create an administrator role directly in the database (the manager is not reachable yet because nobody is an admin):

   ```sql
   INSERT INTO roles (name, description, priority, active, created, modified)
   VALUES ('Admin', 'Administrators', 100, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP);
   -- PostgreSQL: use TRUE instead of 1 for active.
   ```
2. Assign it to your user and note the role id: `UPDATE users SET role_id = <role id> WHERE email = 'you@example.com';`
3. If the role id is not `1`, tell the manager: `'AclManager' => ['adminAccess' => ['adminRoleIds' => [<role id>]]]` in `config/app.php`. Roles listed there cannot be deleted or deactivated from the UI.
4. Log in and open `/authorization-manager`.
5. Click **Sync Resources** (POST) to discover your controllers and actions.
6. Create the other roles under **Manage Roles**, then open **Manage Permissions** for each one, tick the actions it may run and save.
7. Give the other users a `role_id`.

The admin check (Step 2) is independent of the permission matrix: administrators can use the manager without any granted permission.

Alternative to steps 1-3 for a one-off bootstrap: while `users.role_id` is NULL for you, list your email in `adminEmails`. This stops working as soon as the identity has a non-null `role_id`, because that field is evaluated first.

---

## ⚡ Quick Start

After the installation above:

1. **Sync Resources** at `/authorization-manager`.
2. **Create roles** at `/authorization-manager/roles`.
3. **Assign permissions** with *Manage Permissions* for each role.
4. Enforce them with the policy or the component from Step 3.

---

## 📚 Documentation

<details open>
<summary><b>🔧 Configuration reference</b></summary>

<a id="configuration-reference"></a>

Only keys that the code reads are listed. Set them in the `AclManager` array of `config/app.php` or with `Configure::write()` before the plugin loads; values you set are not overridden by the plugin's defaults.

| Key | Default | Meaning |
|-----|---------|---------|
| `AclManager.adminAccess.adminRoleIds` | `[1]` | Role ids treated as administrators (checked against `role_id`). These roles cannot be deleted or deactivated in the UI. |
| `AclManager.adminAccess.adminRoleNames` | `['admin', 'administrator', 'superadmin']` | Matched case-insensitively against `role_name`. |
| `AclManager.adminAccess.adminEmails` | `[]` | Emails treated as administrators (only used when the identity has no `role_name`, `role_id` or `is_admin`). |
| `AclManager.adminAccess.customCheck` | not set | `callable(?IdentityInterface $identity): bool`. Highest priority. Also called with `null` when the Authentication component is missing. |
| `AclManager.adminAccess.allowWithoutAuthentication` | `false` | Allow access when `cakephp/authentication` is not available. Only `true` is accepted. |
| `AclManager.redirects.login` | `['plugin' => false, 'controller' => 'Users', 'action' => 'login']` | Where anonymous users are sent (a `?redirect=` query is appended). Array or string URL. |
| `AclManager.redirects.unauthorized` | `['plugin' => false, 'controller' => 'Pages', 'action' => 'display', 'home']` | Where non-admins are sent. |
| `AclManager.ignoreActions` | list in `config/bootstrap.php` | Methods, controllers and `Plugin.*` patterns skipped by Sync Resources. **Setting it replaces the whole default list**, so copy the defaults you still need. |
| `AclManager.mountPath` | `'/authorization-manager'` | URL path where the manager is served, for example `'/admin/authorization-manager'`. Generated links follow it. Access is still limited by the plugin's own administrator check; the path does not add any protection. An invalid or empty value falls back to the default. |
| `AclManager.locale` | `null` | Locale applied to the plugin's own pages for the request (for example `'es_ES'`). `null` follows your application's locale. |
| `AclManager.migrations.adoptExistingTables` | `false` | `true` lets migrations 0001-0003 add missing columns to already existing `roles`/`permissions`/`resources` tables. |
| `AclManager.version` | `'3.2.0'` | Informational (shown in the footer). |

Example:

```php
// config/app.php
return [
    // ...
    'AclManager' => [
        'adminAccess' => [
            'adminRoleIds' => [1, 2],
            'adminRoleNames' => ['admin'],
            'customCheck' => null, // or fn($identity): bool => ...
        ],
        'redirects' => [
            'login' => ['plugin' => false, 'controller' => 'Users', 'action' => 'login'],
            'unauthorized' => '/dashboard',
        ],
        'migrations' => ['adoptExistingTables' => false],
    ],
];
```

</details>

<details>
<summary><b>🔁 Redirect after login</b></summary>

When a session expires the manager redirects to the login page with a `redirect` query parameter. Honor it in your login action:

```php
public function login()
{
    $result = $this->Authentication->getResult();

    if ($result->isValid()) {
        $redirect = $this->request->getQuery('redirect');
        if ($redirect) {
            return $this->redirect($redirect);
        }

        return $this->redirect($this->Authentication->getLoginRedirect() ?? '/');
    }

    if ($this->request->is('post') && !$result->isValid()) {
        $this->Flash->error(__('Invalid username or password'));
    }
}
```

</details>

<details>
<summary><b>🌍 Internationalization and locale</b></summary>

Translations (domain `acl_manager`) ship for `es_ES`, `gl_ES` and `en_US`.

The plugin does not set a global locale: its pages use the locale your application has set for the request, and the texts appear in English (the message ids) when none is set. To choose the language for the whole application, set the host locale, for example with CakePHP's `LocaleSelectorMiddleware`, added to the queue after `RoutingMiddleware`:

```php
use Cake\I18n\Middleware\LocaleSelectorMiddleware;

$middlewareQueue->add(new LocaleSelectorMiddleware(['en_US', 'es_ES', 'gl_ES']));
```

To force a language only on the plugin pages, set `AclManager.locale` (for example `'es_ES'`) in `config/app.php`. The plugin's controllers apply it at the start of each request they handle; the default `null` leaves the locale untouched.

To add your own translation, copy `resources/locales/es_ES/acl_manager.po` to `resources/locales/{locale}/acl_manager.po` and translate the `msgstr` values.

</details>

<details>
<summary><b>🗄️ Database schema</b></summary>

Three tables (full DDL per engine in [`docs/sql/`](docs/sql/)):

- **roles**: `id, name, description, priority, active, created, modified` (unique `name`)
- **permissions**: `id, role_id, controller, action, plugin, allowed, created, modified` (unique `role_id, controller, action, plugin`; FK to `roles` with `ON DELETE CASCADE`)
- **resources**: `id, controller, action, plugin, description, active, created, modified` (unique `controller, action, plugin`)

`plugin` is `'App'` for application controllers. The column stays nullable in the schema; the application never writes NULL.

</details>

<details>
<summary><b>🎯 Service layer</b></summary>

```php
use AclManager\Service\PermissionService;
use AclManager\Service\ResourceScannerService;

$permissionService = new PermissionService();

// Grant permission (no plugin = application controller, stored as 'App')
$permissionService->grant($roleId, 'Articles', 'edit');
$permissionService->grant($roleId, 'Comments', 'view', 'Blog'); // plugin controller

$permissionService->deny($roleId, 'Articles', 'delete');
$matrix = $permissionService->getPermissionMatrix($roleId);

// Copy permissions between roles (replaces the target's permissions; roles must differ)
$permissionService->copyPermissions($sourceRoleId, $targetRoleId);

// Resource scanning
$scannerService = new ResourceScannerService();
$stats = $scannerService->scanAndSync();
$resources = $scannerService->getGroupedResources();
```

</details>

<details>
<summary><b>🐛 Troubleshooting</b></summary>

**Every request to `/authorization-manager` returns 403.** `cakephp/authentication` is not installed or `AuthenticationMiddleware` is not in the queue, so nobody is identified and the plugin fails closed. Install and configure it (Step 2), or, if you protect the manager elsewhere, set `AclManager.adminAccess.allowWithoutAuthentication` to `true`.

**Redirected to the login or "no permission" page.** You are not an administrator under the rules in Step 2. Check `role_id` against `adminRoleIds` (or the other fields) and remember a non-null `role_id` takes precedence over `adminEmails`.

**Migration aborts with "NormalizeAppPlugin aborted, nothing was changed".** Duplicate rows would collide once `NULL` becomes `'App'`. Nothing was modified. Follow "Migration 0004 never deletes data" (back up, review and run `docs/sql/cleanup-duplicates.sql`, rerun).

**Migration fails with "Table 'roles' already exists and is missing required columns".** Path B: set `AclManager.migrations.adoptExistingTables` to `true` or apply `docs/sql/adopt-existing-roles.sql`, then rerun.

**Migration fails with a message about the legacy `AbstractMigration`.** That is the error `cakephp/migrations` 5.x raises for migration files that still extend `AbstractMigration`, i.e. you have an old version of this plugin. Update the plugin (its migrations extend `BaseMigration`) and use `cakephp/migrations` ^4.5 or ^5.0. On migrations older than 4.5 `BaseMigration` does not exist: upgrade it.

**Saving permissions with many controllers.** With JavaScript enabled (the normal case) the form posts a handful of fields no matter how many actions are granted: the granted ids travel in a single comma-separated `resource_ids_csv` field, so PHP `max_input_vars` no longer limits saving. Without JavaScript the form falls back to one `resource_ids[]` value per granted action, which can still hit `max_input_vars` on very large roles; in that case a truncated POST is detected through a trailing sentinel field and rejected without touching existing permissions.

**How saving works and the conflict message.** Each form carries a version token (a hash of the role's currently granted permissions). On save, the server compares it with the current state inside the same transaction and refuses to overwrite changes made after the form was loaded. If you see "Another administrator changed the permissions of this role while you were editing", someone else saved that role first: the page has been reloaded with the current data, so review it and apply your changes again. A form without the token is rejected as incomplete. Known limitation: the check does not lock rows, so two saves landing within the same few milliseconds can still race.

**Sync Resources or Clear Permissions return 405.** They only accept POST (buttons use `postLink`); a GET link or bookmark will not work, and your layout must include the CSRF token if you use `CsrfProtectionMiddleware`.

**A flash message says an operation "could not be saved", "synchronized", "copied" or "cleared".** Unexpected errors no longer show the exception text in the browser, because it can expose SQL, paths or other internals. The full exception (class, message and stack trace) is written to the application log: look in `logs/error.log`.

**No resources showing.** Open `/authorization-manager` and use *Sync Resources*. Actions matching `AclManager.ignoreActions` are skipped.

**Access denied after enabling the policy.** The user needs a `role_id`, and the role needs the action ticked in *Manage Permissions*. Everything not granted is denied.

**Texts appear in English.** The plugin follows your application's locale and no longer forces one. Set the host locale (for example with `LocaleSelectorMiddleware`, see the internationalization section) or set `AclManager.locale` to `'es_ES'`, `'gl_ES'` or another locale that has a translation.

**Table prefixes are not supported.** The plugin's tables are always named `roles`, `permissions` and `resources`, and the migration SQL does not apply datasource table prefixes (CakePHP 4+ no longer supports them).

</details>

---

## 🔼 Upgrading from 3.2.0

1. Back up the database, then `composer update mgomezbuceta/cakephp-aclmanager`.
2. Run `bin/cake migrations migrate -p AclManager`. The new migration `20250101000004_NormalizeAppPlugin` converts `plugin IS NULL` to `'App'`. It never deletes rows: if duplicates would collide it aborts with the counts and nothing is changed (see "Migration 0004 never deletes data").
3. Migrations 0001-0003 no longer alter pre-existing tables unless `AclManager.migrations.adoptExistingTables` is `true`. If you already had a custom `roles` table and relied on the old automatic column additions, either set the flag or add the columns from `docs/sql/adopt-existing-roles.sql`.
4. **Breaking:** without `cakephp/authentication` the manager now returns 403 instead of granting access to everyone. Install it, or set `AclManager.adminAccess.allowWithoutAuthentication` to `true` (or provide a `customCheck`).
5. `clearPermissions` and `syncResources` are POST-only.
6. The plugin no longer forces `es_ES` globally: pages follow your application's locale. To keep Spanish, set the host locale or `AclManager.locale` to `'es_ES'`.
7. Removed: the unused `AclManagerHelper` and `AppView`, the legacy `/acl-manager` URL (use `/authorization-manager`) and the no-op configuration keys `defaultRoleId`, `cachePermissions`, `cacheDuration`, `permissionMode`, `paginationLimits` and `{roles,permissions,resources}.limit`.
8. **Breaking:** `AclManager.admin` was removed because it never worked (the `Admin` prefix mount had no controller and produced the wrong URL). To serve the manager elsewhere, set `AclManager.mountPath`.

---

## 🔄 Migration from v2.x

> **⚠️ BREAKING CHANGE**: Version 3.0 uses Authorization plugin instead of deprecated ACL.

### Migration Steps:

1. **Backup your data**
```sql
CREATE TABLE backup_aros_acos AS SELECT * FROM aros_acos;
```

2. **Update composer.json**
```bash
composer remove cakephp/acl
composer require cakephp/authorization
composer update mgomezbuceta/cakephp-aclmanager
```

3. **Run new migrations**
```bash
bin/cake migrations migrate -p AclManager
```

4. **Update routes**
   - Old: `/acl-manager`
   - New: `/authorization-manager`

5. **Update component**
```php
// Old
$this->loadComponent('AclManager.AclManager');

// New
$this->loadComponent('AclManager.AuthorizationManager');
```

6. **Rebuild permissions**
   - Create new roles matching your old ARO structure
   - Use "Sync Resources" to discover controllers
   - Manually assign permissions (old ACL data cannot be migrated)

---

## 🏗️ Architecture

```
┌─────────────────────────────────────────┐
│      PermissionsController               │
│      (Web Interface)                     │
└──────────────┬──────────────────────────┘
               │
      ┌────────┴────────┐
      │                 │
┌─────▼──────────┐ ┌───▼──────────────────┐
│ Permission     │ │ ResourceScanner      │
│ Service        │ │ Service              │
│                │ │                      │
│ • Check Auth   │ │ • Scan Controllers   │
│ • Grant/Deny   │ │ • Sync Resources     │
│ • Copy Perms   │ │ • Auto-Discovery     │
└────────────────┘ └──────────────────────┘
        │                     │
        └──────────┬──────────┘
                   │
      ┌────────────▼─────────────┐
      │  Database Tables          │
      │  • roles                  │
      │  • permissions            │
      │  • resources              │
      └───────────────────────────┘
```

---

## 🤝 Contributing

Contributions are welcome!

1. 🍴 Fork the repository
2. 🌿 Create your feature branch (`git checkout -b feature/amazing-feature`)
3. 💻 Write clean, documented code following PSR-12
4. ✅ Add tests for new functionality
5. 📝 Commit your changes (`git commit -m 'Add amazing feature'`)
6. 🚀 Push to the branch (`git push origin feature/amazing-feature`)
7. 🎉 Open a Pull Request

---

## 📄 License

This project is licensed under the **MIT License** - see [LICENSE.md](LICENSE.md) for details.

```
Copyright (c) 2025 Marcos Gómez Buceta
Copyright (c) 2016 Iván Amat
```

---

## 👨‍💻 Author

<div align="center">

**Marcos Gómez Buceta**

[![GitHub](https://img.shields.io/badge/GitHub-mgomezbuceta-181717?style=flat-square&logo=github)](https://github.com/mgomezbuceta)
[![Email](https://img.shields.io/badge/Email-mgomezbuceta%40gmail.com-EA4335?style=flat-square&logo=gmail&logoColor=white)](mailto:mgomezbuceta@gmail.com)

</div>

---

## 🙏 Acknowledgments

This project evolved from the excellent ACL Manager foundation:

- **[Iván Amat](https://github.com/ivanamat)** - Original CakePHP 4.x Acl Manager
- **[Frédéric Massart (FMCorz)](https://github.com/FMCorz)** - Original CakePHP 2.x AclManager

Special thanks to the CakePHP community for their continuous support.

---

<div align="center">

**⭐ If you find this plugin useful, please give it a star! ⭐**

Made with ❤️ for the CakePHP community

[Report Bug](https://github.com/mgomezbuceta/cakephp-aclmanager/issues) • [Request Feature](https://github.com/mgomezbuceta/cakephp-aclmanager/issues)

</div>

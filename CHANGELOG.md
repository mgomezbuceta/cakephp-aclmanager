# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- GitHub Actions CI (`.github/workflows/ci.yml`): the test suite on PHP 8.1 to 8.4 (plus a run with the lowest supported dependencies on PHP 8.1), and a job with `composer validate`, phpcs and phpstan. Actions are pinned by commit SHA.
- `AclManager.mountPath` (default `'/authorization-manager'`): URL path where the manager is mounted, normalized defensively with a fallback to the default.
- Optimistic concurrency on the permissions form: it carries a version token (`PermissionService::getPermissionsVersion()`, a hash of the role's allowed permissions) and `replaceRolePermissions()` accepts an optional `$expectedVersion`. A stale token throws `StalePermissionsException` before anything is changed and the UI shows a translated conflict message; a missing token is rejected as an incomplete form. Known limitation: the check and the write share a transaction but no row lock (portable across SQLite/MySQL/Postgres), so two saves landing within milliseconds can still race.
- Single-field submit: with JavaScript the form posts the granted ids as one comma-separated `resource_ids_csv` field (a constant handful of POST variables, so `max_input_vars` no longer limits saving). Without JavaScript the `resource_ids[]` checkboxes keep working; if both are sent, the CSV wins.
- Test coverage: behavior tests for the authorization component (role resolution, permission cache), plugin controller scanning (through a test-only plugin), ignore rules, grouped resources, role and permission failure branches and the remaining admin-check branches, taking line coverage from 91.56% to 100%.
- `TranslationCatalogTest`: every `__d('acl_manager', ...)` message exists in the en_US, es_ES and gl_ES catalogs, no catalog entry is unused, the three catalogs hold the same messages, es_ES and gl_ES have no empty translation and placeholders (`{0}`) are preserved.
- `composer coverage` runs the suite with a Clover report and `tests/bin/check-coverage.php` fails below a minimum line coverage (100%); a `coverage` CI job (PHP 8.3, Xdebug) enforces it.

### Security
- Access control is now enforced: `beforeFilter` stops the request with the login/unauthorized redirect, so the action no longer runs for anonymous or non-admin users.
- `clearPermissions` and `syncResources` only accept POST (GET returns 405); the UI uses `Form->postLink`, closing a CSRF-via-GET hole.
- Roles listed in `AclManager.adminAccess.adminRoleIds` can no longer be deleted or deactivated (enforced server-side, not only hidden in the UI).
- **BREAKING:** the plugin now fails closed when the Authentication component is not available (403) instead of granting access to everyone. Apps without `cakephp/authentication` must set `AclManager.adminAccess.allowWithoutAuthentication` to `true` (new key, default `false`) or provide a `customCheck` callback.
- Mass assignment lockdown: `Role`, `Permission` and `Resource` no longer expose `id`, `created`, `modified` or the `permissions`/`role` associations to `newEntity()`/`patchEntity()`, and `addRole`/`editRole` only accept `name`, `description`, `priority` and `active`.
- Unexpected errors in `savePermissions`, `syncResources`, `copyPermissions` and `clearPermissions` no longer flash the exception message (it could leak SQL or paths); the exception is logged with `Log::error()` and a generic translated message is shown.
- Output escaping: the role name inside the delete confirmation of the roles and dashboard pages was rendered unescaped in an HTML attribute (stored XSS by a role name); the checkbox label `for` attribute is escaped too.

### Removed
- **Breaking:** `AclManager.admin`. It never worked: the `Admin` prefix mount had no controller (`MissingControllerException`) and produced `/admin/admin/authorization-manager`. Use `AclManager.mountPath` instead.
- `AclManagerHelper` (imported the uninstalled `cakephp/acl`, fatal on use) and the `AppView` that only loaded it.
- Dead configuration defaults that no code read: `defaultRoleId`, `cachePermissions`, `cacheDuration`, `permissionMode`, `paginationLimits` and `{roles,permissions,resources}.limit`; the unused `userModel`, `cacheConfig` and `cacheDuration` options of `AuthorizationManagerComponent`; the "Planned / not implemented" notes in the guides.
- The legacy `/acl-manager` fallback mount and the no-op `routes()`, `middleware()` and `bootstrap()` overrides in `AclManagerPlugin`. The manager is served only under `/authorization-manager` by default (configurable with `AclManager.mountPath`).
- Unreferenced `webroot/` assets, the tracked `README.cp3.md` and the unused `Resource::_getFullIdentifier()`.

### Fixed
- The `Controller` and `Plugin` messages were left in the en_US, es_ES and gl_ES catalogs without being used by any template; removed.
- `isUserAdmin()` tolerates a `customCheck` returning a non-boolean (it is cast to bool instead of raising a `TypeError`), role names that are not strings, and compares role ids as strings with strict comparison (`"1"` still matches `1`). The precedence (`customCheck`, `role_name`, `role_id`, `is_admin`, `email`; the first present field decides) is unchanged and now documented.
- A failure while loading the optional Authorization/Authentication components is logged as a warning (component name and message) instead of being swallowed silently; access stays fail-closed.
- Texts in `AuthorizationManagerComponent::handleUnauthorized()` use the `acl_manager` translation domain (`__d`) and the msgid was added to the `en_US`, `es_ES` and `gl_ES` catalogs; the stale `Modified` msgid was removed.
- Saving permissions no longer depends on PHP `max_input_vars`: the form posts one `resource_ids[]` value per granted action instead of ~5 fields per action.
- A truncated POST is detected through a trailing sentinel field and aborts without touching existing permissions.
- Permissions are replaced in a single transaction with a bulk save; app-level permissions keep the `'App'` plugin value expected by the policy.
- `'App'` is now the single plugin value for application-level resources and permissions. `PermissionService::grant()`, `deny()` and `isAuthorized()` normalize a missing plugin to `'App'`, so API callers no longer write or read rows the policy never matches.
- The resource scanner stores application controllers with plugin `'App'` and no longer creates duplicates on re-scan.
- `replaceRolePermissions()` only accepts positive integer ids (`'5abc'`, arrays, `'-1'`, `'0'` are ignored), collapses resources that map to the same permission, and loads resources in chunks of 500 ids.
- `copyPermissions()` is transactional, replaces the target role's permissions, saves in bulk and rejects copying a role onto itself (the UI shows an error).
- Migrations: `0001` skips tables that already exist and `0003` is a no-op when the roles table is missing or already complete.

### Changed
- **Requirements:** `cakephp/cakephp` is now `^5.1` (the code uses `SelectQuery::orderBy()`, which does not exist in 5.0) and `composer.json` requires `ext-intl` and `ext-mbstring`. The requirements table of the README and INSTALL guides (EN/ES) was updated.
- Code quality: the code passes `phpcs` (CakePHP standard, `phpcs.xml.dist`) and `phpstan` level 8 without a baseline; run them with `composer check` (`cs-check`, `stan`, `test`). All PHP files declare `strict_types`, `AclManagerPolicy` is typed, and `PermissionService` and `ResourceScannerService` fetch their tables through `LocatorAwareTrait` and accept an optional table locator (`new PermissionService($locator)`); the public constructors stay compatible. Controller actions that return a redirect are typed `?Response`, matching `redirect()` when a `beforeRedirect` listener stops the event.
- Templates: Bootstrap 4 badge classes (`badge-success`, `badge-secondary`, `badge-danger`) became Bootstrap 5 `text-bg-*` classes (brand colors kept), and the CDN assets of the layout (Bootstrap 5.3.0 CSS and JS, Font Awesome 6.4.0) carry Subresource Integrity hashes with `crossorigin="anonymous"`.
- Flash messages for unexpected failures are generic: `Error saving permissions: {0}`, `Error synchronizing resources: {0}` and `Error: {0}` were replaced by four fixed messages (saved, synchronized, copied, cleared) in the `en_US`, `es_ES` and `gl_ES` catalogs. Details go to the application log (`logs/error.log`).
- **Locale is no longer forced.** The plugin's `config/bootstrap.php` stopped calling `I18n::setLocale('es_ES')`, which changed the locale of the whole host application. Plugin pages follow the host locale (English texts when none is set). New optional `AclManager.locale` (default `null`): when set, the plugin's controllers apply it for the request.
- `scanAndSync()` preloads resources once and saves in bulk inside a single transaction (same statistics array).
- **Run the new migration**: `bin/cake migrations migrate -p AclManager` (`20250101000004_NormalizeAppPlugin`). It converts NULL plugins to `'App'` in `resources` and `permissions` and **never deletes data**: if the conversion would collide with an existing row it aborts before changing anything, reports the counts per table and points to `docs/sql/cleanup-duplicates.sql` (back up, review, run, retry). Rolling it back sets `resources.plugin` to NULL again; permissions stay `'App'`.
- **Migrations now extend `Migrations\BaseMigration`** and require `cakephp/migrations` ^4.5 or ^5.0. This fixes failures on migrations 5.x, which rejects the legacy `AbstractMigration`. Migration 0004 no longer touches the connection directly: it uses the migration's own `fetchAll()` and `execute()`. Verified by running the four migrations against SQLite with migrations 4.6.6 and 5.x.
- **Migration `0001` is reversible**: it has explicit `up()`/`down()`. Rolling back drops `permissions`, `resources` and `roles` only when they carry the unique index this migration created, so an adopted host table (and its rows) survives a full rollback. `0002` and `0003` have a documented no-op `down()`. Adoption (`adoptExistingTables`) now adds missing columns only, never indexes or foreign keys to a host table.
- Migration `0004` and `docs/sql/cleanup-duplicates.sql` treat an empty-string plugin like NULL (matching `normalizePlugin()`), for both collision detection and the conversion.
- `ResourceScannerService::scanAndSync()` skips duplicate found entries, handles legacy rows (plugin NULL and `'App'` for the same controller/action) as a list per key, and is now all-or-nothing: it saves with `saveManyOrFail`, so the first validation failure aborts the whole sync with the entity errors instead of silently skipping rows.
- Table prefixes are not supported (CakePHP 4+ no longer supports datasource prefixes); the migration SQL uses plain table names.
- Migrations `0001`-`0003` share one entry point, `SchemaCompatibility::decideForMigration()`.
- Migrations `0001`-`0003` no longer alter pre-existing `roles`/`permissions`/`resources` tables by default. If a table lacks required columns they throw naming the table, the missing columns, the config flag and `docs/sql/`. Set `AclManager.migrations.adoptExistingTables` to `true` to restore the old behavior of adding the missing columns.

### Added
- `.gitattributes` with `export-ignore` for tests and development files so Composer dist archives stay lean (`docs/sql` is kept because the guides reference it).
- PHPUnit test suite (SQLite in-memory) covering permission saving.
- `AclManager.migrations.adoptExistingTables` config flag (default `false`).
- `docs/sql/`: full schema for MySQL, PostgreSQL and SQLite, duplicate detection and cleanup SQL, and ALTER statements to adopt an existing `roles` table. The SQLite script and the cleanup are run by the test suite; the MySQL and PostgreSQL scripts are not executed in CI.
- `cakephp/migrations` in `require-dev` and `MigrationsRunTest`, which runs the plugin migrations in-process against SQLite (fresh install, full rollback, adoption, collisions).
- Tests for `AclManagerPolicy` wiring through `MapResolver`.
- `composer.json` `suggest`: `cakephp/migrations` (^4.5 || ^5.0) and `cakephp/authentication`.

### Documentation
- Installation and configuration guides are available in English (README.md, INSTALL.md) and Spanish (README.es.md, INSTALL.es.md); a test (`GuidesParityTest`) keeps their structure identical.
- README.md and INSTALL.md rewritten as an ordered, step-by-step installation guide (authentication, authorization, plugin load, database paths A/B/C, `role_id`, first run, troubleshooting, upgrade notes).
- The configuration reference lists only keys the code reads. `permissionMode`, `cachePermissions`, `cacheDuration`, `defaultRoleId`, `paginationLimits` and `{roles,permissions,resources}.limit` are documented as not implemented (their bootstrap defaults remain but nothing reads them).
- Documented the real `isUserAdmin()` rules and that the plugin bootstrap forces the `es_ES` locale.

## [3.2.0] - 2025-01-10

### 🌍 Added

#### Internationalization (i18n) Support
- **Full i18n implementation** using `acl_manager` translation domain
- **English (en_US) translation**: Complete translation file with 80+ strings
- **Spanish (es_ES) translation**: Complete translation file with 80+ strings (default)
- **Galician (gl_ES) translation**: Complete translation file with 80+ strings
- **CakePHP i18n integration**: All strings extractable via `bin/cake i18n extract`
- Spanish set as default locale in bootstrap configuration

#### Session Management
- **Redirect after login**: When session expires, users are redirected to login with return URL
- **Preserve navigation state**: After authentication, users return to the original Authorization Manager page
- **Query parameter support**: Login URL includes `redirect` parameter with original URL

#### UI Improvements
- **Brand color integration**: All templates updated with `#1db58c` primary color
- **Consistent theming**: CSS variables for easy color customization
- **Modern design**: Sober and professional interface with Bootstrap 5

### 🔄 Changed

- **All template files**: Updated to use `__d('acl_manager', ...)` for translations
  - `templates/Permissions/index.php`
  - `templates/Permissions/roles.php`
  - `templates/Permissions/add_role.php`
  - `templates/Permissions/manage.php`
  - `templates/layout/default.php`
- **Controller messages**: All flash messages now use translation domain
  - `src/Controller/PermissionsController.php`
  - `src/Controller/AppController.php`
- **Color scheme**: Changed from blue/gray to brand green (#1db58c)

### 🐛 Fixed

- **Cake\Filesystem\Folder removal**: Fixed "Class Cake\Filesystem\Folder not found" error
  - Replaced deprecated `Cake\Filesystem\Folder` with native PHP `DirectoryIterator`
  - Added `findControllerFiles()` method for scanning controller directories
  - CakePHP 5.x removed the Folder class in favor of native PHP functions
- **Configure class import**: Fixed "Class Configure not found" error in layout template
- **Component loading**: Added graceful fallback when Authorization/Authentication components not available
- **Version number**: Updated to 3.2.0 to reflect i18n features

### 📚 Documentation

- **README.md**: Added i18n configuration section
- **Translation files**: Complete Spanish and Galician .po files
- **Locale structure**: Proper directory structure in `resources/locales/`

---

## [3.0.0] - 2025-01-10

### ⚡ BREAKING CHANGES

**Complete architectural rewrite - Migration from deprecated ACL to modern Authorization system**

This is a major breaking change that requires migration from the old ACL system to the new Authorization-based system.

#### What Changed
- **Replaced**: `cakephp/acl` (deprecated) → `cakephp/authorization` (official CakePHP 5.x plugin)
- **Database**: New simplified schema (`roles`, `permissions`, `resources`) replaces old ACL tables (`acos`, `aros`, `aros_acos`)
- **Routes**: Changed from `/acl-manager` to `/authorization-manager`
- **Component**: `AclManagerComponent` → `AuthorizationManagerComponent`
- **Architecture**: Moved to modern RBAC (Role-Based Access Control) pattern

### 🚀 Added

#### New Architecture
- **PermissionService**: Modern service for permission evaluation and management
- **ResourceScannerService**: Automatic controller/action discovery system
- **AuthorizationManagerComponent**: New component for authorization checking
- **PermissionsController**: Complete web interface for managing permissions

#### New Models
- **RolesTable & Role Entity**: User role management
- **PermissionsTable & Permission Entity**: Controller/action permissions per role
- **ResourcesTable & Resource Entity**: Application resource catalog

#### New Features
- **Role-Based Permissions**: Industry-standard RBAC pattern
- **Auto-Discovery**: Automatic scanning of controllers and actions
- **Permission Caching**: Built-in caching for performance
- **Role Priorities**: Hierarchical role system with priorities
- **Modern UI**: Bootstrap 5 interface with intuitive permission matrices
- **Bulk Operations**: Copy permissions between roles, clear all permissions

#### New Configuration
- Simplified configuration options
- Permission checking modes (strict/permissive)
- Configurable caching settings
- Default role assignment for new users

### 🔄 Changed

- **Plugin Name**: Remains `AclManager` for backward compatibility, but internally uses Authorization
- **Version**: Bumped to 3.0.0 to reflect breaking changes
- **Routes**: New modern routing system with `/authorization-manager` base path
- **Templates**: Completely redesigned with modern Bootstrap 5 UI
- **Documentation**: Complete rewrite focusing on Authorization system

### 🗑️ Removed

- **Deprecated ACL System**:
  - Removed `AclController`
  - Removed `AclManagerComponent` (old)
  - Removed `AclExtras`
  - Removed ACL-specific services
  - Removed old templates

- **Database Tables**: No longer uses:
  - `acos` (Access Control Objects)
  - `aros` (Access Request Objects)
  - `aros_acos` (Junction table)

### 📚 Documentation

- **README.md**: Complete rewrite with Authorization-focused documentation
- **Migration Guide**: Detailed v2.x to v3.0 migration instructions
- **Configuration Examples**: Updated for new system
- **Architecture Diagrams**: New service layer documentation

### 🔧 Migration Required

> **⚠️ WARNING**: This is a BREAKING CHANGE. Migration from v2.x required.

**Migration Steps:**

1. Backup your ACL data
2. Update composer dependencies
3. Run new migrations
4. Update routes in your application
5. Update component usage
6. Rebuild permissions in new system

See README.md for detailed migration instructions.

---

## [2.0.0] - 2024-12-19

### ⚡ BREAKING CHANGES

**Complete rewrite for CakePHP 5.x with modern PHP 8.1+ features**

- **Minimum Requirements**: PHP 8.1+, CakePHP 5.x
- **Template Location**: Moved from `src/Template/` to `templates/`
- **Template Extension**: Changed from `.ctp` to `.php`
- **Routing**: Completely rewritten using modern CakePHP 5.x closure-based routing
- **Service Architecture**: Business logic extracted to dedicated service classes

### 🚀 Added

- **Service Layer Architecture**:
  - `AclPermissionService`: Handles permission evaluation and matrix building
  - `AclSynchronizationService`: Manages ACO/ARO synchronization and database operations
- **Modern PHP Features**:
  - Strict type declarations (`declare(strict_types=1)`)
  - Typed properties and return types
  - PHP 8.1+ match expressions and nullsafe operators
- **Enhanced UI**:
  - Bootstrap 5-based responsive interface
  - Accessibility features (ARIA labels, semantic HTML5)
  - Modern card-based layout design
- **Developer Guide**: Enhanced CLAUDE.md with modern architecture details

### 🔄 Changed

- **Controller Refactoring**:
  - `AclController`: Split into smaller, focused methods
  - Better error handling with try-catch blocks
  - Service dependency injection
- **Component Modernization**:
  - `AclManagerComponent`: Rewritten with typed properties
  - Better separation of concerns
  - Modern PHP patterns and practices
- **Template Improvements**:
  - Modern HTML5 semantic elements
  - Better accessibility and responsive design
  - Enhanced user experience with better visual feedback

### 🛠️ Technical Improvements

- **Clean Code Principles**:
  - Single Responsibility Principle adherence
  - Descriptive method and variable names
  - Elimination of code duplication
- **Security**:
  - Enhanced input validation
  - Better error handling
  - Type safety improvements

---

## Legacy Versions (CakePHP 3.x/4.x)

### [1.3] - 2016-xx-xx

#### Added
- `AclManager.hideDenied`: Hide plugins, controllers and actions denied in ACLs lists

#### Changed
- Enhanced `AclManager.ignoreActions` configuration
- Updated templates with better permission display
- Fixed ACO synchronization issues

### [1.2] - 2016-xx-xx

#### Added
- `AclManager.ignoreActions`: Ignore specific actions during ACO synchronization

### [1.1] - 2016-xx-xx

#### Changed
- Fixed ARO alias naming
- Updated plugin installer requirements
- Documentation improvements

#### Contributors
- [@pfuri](https://github.com/pfuri)
- [@tjanssl](https://github.com/tjanssl)

### [1.0.5] - 2016-xx-xx

#### Changed
- Fixed "Update ACOs" functionality
- Enhanced ARO model configuration
- Added admin prefix support

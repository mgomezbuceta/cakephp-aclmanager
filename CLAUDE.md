# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a modern CakePHP plugin for managing role-based permissions using CakePHP's Authorization plugin for CakePHP 5.x applications. The project has been completely refactored from the deprecated ACL system to the modern Authorization system, following Clean Code principles and modern PHP 8.1+ standards.

**Version**: 5.0.0
**Status**: Production-ready for CakePHP 5.x applications.

### Key Features (v5.0.0)
- **Modern Authorization**: Uses CakePHP's official Authorization plugin (not deprecated ACL)
- **Role-Based Access Control (RBAC)**: Industry-standard permission system
- **i18n**: Spanish (es_ES), Galician (gl_ES) and English (en_US) catalogs included; the plugin follows the host application's locale
- **Modern UI**: Bootstrap 5 with brand colors (#1db58c)
- **Auto-Discovery**: Automatic controller/action scanning
- **Service Layer**: Clean architecture with dedicated services

## Core Architecture

### Plugin Structure
- **src/Controller/PermissionsController.php**: Main controller providing web interface for permission management
- **src/Controller/AppController.php**: Base controller with admin access control
- **src/Controller/Component/AuthorizationManagerComponent.php**: Optional helper for host controllers (`isAuthorized()`, `checkPermission()`, per-instance result cache)
- **src/Policy/AclManagerPolicy.php**: Request policy for `cakephp/authorization` (deny by default)
- **src/Service/PermissionService.php**: Permission evaluation and management
- **src/Service/ResourceScannerService.php**: Auto-discovery of controllers/actions
- **templates/Permissions/**: Modern Bootstrap 5 templates with i18n support
- **templates/layout/default.php**: Plugin layout with brand colors
- **resources/locales/**: Translation files (en_US, es_ES, gl_ES)

### Key Configuration
Only keys the code reads (`config/bootstrap.php` writes the defaults without overriding values you set):

- **AclManager.adminAccess.\***: `adminRoleIds` (default `[1]`), `adminRoleNames`, `adminEmails`, `customCheck`, `allowWithoutAuthentication` (default `false`; without the Authentication component the plugin fails closed with a 403)
- **AclManager.redirects.\***: `login` and `unauthorized` redirect URLs
- **AclManager.ignoreActions**: actions, controllers (`Controller/*`) and plugins (`Plugin.*`) skipped by Sync Resources; setting it replaces the whole default list
- **AclManager.mountPath**: URL path of the manager (default `/authorization-manager`)
- **AclManager.locale**: optional locale applied to the plugin pages for the request (null = follow the host app)
- **AclManager.migrations.adoptExistingTables**: let migrations add missing columns to existing tables (default `false`)
- **AclManager.version**: informational version shown in the footer (5.0.0)

### Internationalization
- **Locale**: follows the host application; set `AclManager.locale` to force one on plugin pages
- **Translation Domain**: 'acl_manager' for all plugin strings
- **Available Locales**:
  - English (en_US) - resources/locales/en_US/acl_manager.po
  - Spanish (es_ES) - resources/locales/es_ES/acl_manager.po
  - Galician (gl_ES) - resources/locales/gl_ES/acl_manager.po
- **All templates use**: `__d('acl_manager', 'text')` for translation
- `TranslationCatalogTest` keeps the three catalogs and the `__d()` calls in sync

### Dependencies
- Requires PHP 8.1 or higher
- Requires CakePHP 5.x framework (^5.1) and ext-intl/ext-mbstring
- Depends on cakephp/authorization (^3.0); cakephp/authentication is needed to identify users (without it the manager returns 403) and cakephp/migrations (^4.5 || ^5.0) is optional
- Uses modern PHP features (declare(strict_types=1), typed properties, typed returns)

## Development Commands

### Installation
```bash
composer require mgomezbuceta/cakephp-aclmanager
composer require cakephp/authentication cakephp/authorization
```

### Database Setup
```bash
# Run plugin migrations to create roles, permissions, resources tables
bin/cake migrations migrate -p AclManager
```

### Plugin Configuration
Add to your application's src/Application.php:
```php
public function bootstrap(): void
{
    parent::bootstrap();
    $this->addPlugin('AclManager', ['bootstrap' => true, 'routes' => true]);
}
```

### Change Language (Optional)
The plugin does not force a locale. Set the host locale, for example in your application's config/bootstrap.php (or use `AclManager.locale` for the plugin pages only):
```php
use Cake\I18n\I18n;

// Spanish
I18n::setLocale('es_ES');

// Or Galician
I18n::setLocale('gl_ES');
```

## Permission Management Workflow

### Database Tables
The plugin uses three main tables:
1. **roles**: User roles (name, description, priority, active)
2. **permissions**: Controller/action permissions per role
3. **resources**: Auto-discovered application resources

### Resource Synchronization
The plugin provides automatic resource discovery by scanning controllers and actions:
- Visit `/authorization-manager`
- Click "Sync Resources" to scan all controllers/actions
- Resources are categorized by plugin and controller

### Permission Management
- Access via `/authorization-manager`
- Create roles via "Manage Roles"
- Assign permissions via "Manage Permissions" for each role
- Permissions are stored as allow/deny per controller/action

### Admin Access Control
Only administrators can access the permission manager:
- The first identity field present decides: `customCheck` callback, then `role_name`, `role_id`, `is_admin`, then the `adminEmails` whitelist; otherwise denied
- Configurable in config/app.php under 'AclManager.adminAccess'
- Requires the Authentication component; without it access is denied (403) unless `allowWithoutAuthentication` or a `customCheck` grants it

## Modern Architecture Features

### Service Layer Architecture
The plugin uses a service layer for better separation of concerns:
- **PermissionService**: Handles permission evaluation, grant/deny operations, and permission matrix building
- **ResourceScannerService**: Manages automatic controller/action discovery and resource synchronization

### Clean Code Improvements
- Strict type declarations throughout the codebase (`declare(strict_types=1)`)
- Typed properties and return types in all classes
- Modern PHP 8.1+ features (typed properties, nullsafe operators)
- Single Responsibility Principle adherence
- Descriptive method and variable names
- Service layer pattern for business logic

### UI/UX Features (v5.0.0)
- **Bootstrap 5** responsive design
- **Brand Colors**: Primary color #1db58c (customizable via CSS variables)
- **CSS Variables**: `--primary-color`, `--primary-dark`, `--primary-light`, etc.
- **Responsive**: viewport meta tag and Bootstrap grid
- **Modern Components**: Cards, badges, buttons with consistent styling
- **Multilingual**: All text translatable via CakePHP i18n system

## Route Configuration

Routes use modern CakePHP 5.x routing with closures:
- Base URL: `/authorization-manager` (configurable with `AclManager.mountPath`)
- Dashboard: `/authorization-manager`
- Roles: `/authorization-manager/roles`
- Manage Permissions: `/authorization-manager/manage/{roleId}`
- Sync Resources: `/authorization-manager/sync-resources`

## Template Structure

Templates are located in `templates/` directory (CakePHP 5.x standard):
- **templates/Permissions/index.php**: Main dashboard with role statistics
- **templates/Permissions/roles.php**: Role listing and management
- **templates/Permissions/add_role.php**: Create/Edit role form
- **templates/Permissions/manage.php**: Permission matrix interface
- **templates/layout/default.php**: Plugin layout with brand styling

All templates:
- Use `__d('acl_manager', 'text')` for translations
- Implement Bootstrap 5 responsive design
- Use CSS variables for theming
- Include modern HTML5 semantic elements

## Testing and Quality

### Commands
```bash
composer cs-check   # phpcs
composer stan       # phpstan (level 8, no baseline)
composer test       # phpunit
composer check      # all three
composer coverage   # tests + minimum line coverage gate (needs Xdebug or PCOV)
```

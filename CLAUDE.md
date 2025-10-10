# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a modern CakePHP plugin for managing role-based permissions using CakePHP's Authorization plugin for CakePHP 5.x applications. The project has been completely refactored from the deprecated ACL system to the modern Authorization system, following Clean Code principles and modern PHP 8.1+ standards.

**Version**: 3.2.0
**Status**: Production-ready for CakePHP 5.x applications.

### Key Features (v3.2.0)
- **Modern Authorization**: Uses CakePHP's official Authorization plugin (not deprecated ACL)
- **Role-Based Access Control (RBAC)**: Industry-standard permission system
- **Full i18n Support**: Spanish (es_ES) and Galician (gl_ES) translations included
- **Modern UI**: Bootstrap 5 with brand colors (#1db58c)
- **Auto-Discovery**: Automatic controller/action scanning
- **Service Layer**: Clean architecture with dedicated services

## Core Architecture

### Plugin Structure
- **src/Controller/PermissionsController.php**: Main controller providing web interface for permission management
- **src/Controller/AppController.php**: Base controller with admin access control
- **src/Service/PermissionService.php**: Permission evaluation and management
- **src/Service/ResourceScannerService.php**: Auto-discovery of controllers/actions
- **templates/Permissions/**: Modern Bootstrap 5 templates with i18n support
- **templates/layout/default.php**: Plugin layout with brand colors
- **resources/locales/**: Translation files (es_ES, gl_ES)

### Key Configuration
The plugin relies heavily on CakePHP's Configure class for settings:

- **AclManager.version**: Plugin version (3.2.0)
- **AclManager.ignoreActions**: Array of actions/controllers to ignore during resource synchronization
- **AclManager.permissionMode**: 'strict' or 'permissive' permission checking
- **AclManager.cachePermissions**: Enable/disable permission caching
- **AclManager.adminAccess**: Admin access control configuration
- **AclManager.redirects**: Login and unauthorized redirect URLs

### Internationalization
- **Default Locale**: Spanish (es_ES) set in config/bootstrap.php
- **Translation Domain**: 'acl_manager' for all plugin strings
- **Available Locales**:
  - Spanish (es_ES) - resources/locales/es_ES/acl_manager.po
  - Galician (gl_ES) - resources/locales/gl_ES/acl_manager.po
- **All templates use**: `__d('acl_manager', 'text')` for translation

### Dependencies
- Requires PHP 8.1 or higher
- Requires CakePHP 5.x framework (^5.0)
- Depends on cakephp/authorization plugin (^3.0)
- Uses modern PHP features (declare(strict_types=1), typed properties, typed returns)

## Development Commands

### Installation
```bash
composer require mgomezbuceta/cakephp-aclmanager
composer require cakephp/authorization
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
Add to your application's config/bootstrap.php:
```php
use Cake\I18n\I18n;

// Spanish (default)
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
- Checks role_id, role_name, is_admin flag, or email whitelist
- Configurable in config/app.php under 'AclManager.adminAccess'
- Uses Authentication component if available

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

### UI/UX Features (v3.2.0)
- **Bootstrap 5** responsive design
- **Brand Colors**: Primary color #1db58c (customizable via CSS variables)
- **CSS Variables**: `--primary-color`, `--primary-dark`, `--primary-light`, etc.
- **Accessibility**: Semantic HTML5, ARIA labels, responsive design
- **Modern Components**: Cards, badges, buttons with consistent styling
- **Multilingual**: All text translatable via CakePHP i18n system

## Route Configuration

Routes use modern CakePHP 5.x routing with closures:
- Base URL: `/authorization-manager`
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

### Recommended Commands
```bash
# Run code style checks
vendor/bin/phpcs --standard=vendor/cakephp/cakephp-codesniffer/CakePHP src/

# Run static analysis
vendor/bin/phpstan analyse src/

# Run unit tests (when implemented)
vendor/bin/phpunit
```
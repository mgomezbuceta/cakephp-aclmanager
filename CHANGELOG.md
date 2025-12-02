# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased] - 2025-12-02

### 🔎 Resumen

- Se han realizado varias mejoras y ajustes en configuración, plantillas, servicios y traducciones.
- Cambios principales: ajustes en escaneo/filtrado de recursos, control de eliminación de roles admin en UI, actualizaciones de tipos de columna boolean en tablas y pequeñas correcciones en componentes y controladores.

### ✨ Cambios destacados

- `config/bootstrap.php` / `config/app_local.example.php`: se añade `Utilities/*` a `ignoreActions` para excluir utilidades del escaneo de recursos.
- `src/Controller/Component/AuthorizationManagerComponent.php`: se añade fallback del parámetro `plugin` a `'App'` cuando no existe.
- `src/Model/Table/PermissionsTable.php` y `src/Model/Table/ResourcesTable.php`: se define el tipo de columna boolean para `allowed` y `active` respectivamente.
- `src/Service/ResourceScannerService.php`: `getGroupedResources()` ahora filtra plugins/controladores/acciones según las reglas de `ignoreActions` antes de devolverlos.
- Plantillas (`templates/Permissions/*`): integración de `adminRoleIds` para desactivar acciones peligrosas (borrar rol) para roles administrativos; cambio en el enlace de "Clear All" para invocar la acción `clearPermissions`; ajuste en el campo oculto `plugin` para mantener su valor real.
- Locales (`resources/locales/*/acl_manager.po`): se ha eliminado la cadena `"Deny"` en los ficheros de idiomas (limpieza/translations).
- Nueva política: `src/Policy/AclManagerPolicy.php` añadida para usar `PermissionService` en la verificación de solicitudes.
- Interfaz/CSS: añadido estilo `.disabled-link` en `templates/layout/default.php` para deshabilitar enlaces de acción que no deben ser interactivos.

### 📝 Notas para el desarrollador

- Revisar si los archivos `.snapshots/*` deben incluirse en el repositorio (actualmente aparecen añadidos). Normalmente no deberían comitearse.
- Verificar que las modificaciones en `clearPermissions` acepten métodos `GET` (cambio realizado) y que esto entre en línea con la política de seguridad de la app.

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

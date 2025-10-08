# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is an enhanced CakePHP plugin for managing Access Control Lists (ACL) in CakePHP 5.x applications. The project has been completely refactored and modernized from the original CakePHP 3.x version, following Clean Code principles and modern PHP 8.1+ standards.

**Status**: Production-ready for CakePHP 5.x applications.

## Core Architecture

### Plugin Structure
- **src/Controller/AclController.php**: Main controller providing the web interface for ACL management
- **src/Controller/Component/AclManagerComponent.php**: Core component handling ACL operations and business logic
- **src/AclExtras.php**: Utility class for ACL operations, based on Mark Story's AclExtras
- **src/View/Helper/AclManagerHelper.php**: View helper for ACL-related display functionality
- **src/Template/Acl/**: CakePHP template files for the management interface

### Key Configuration
The plugin relies heavily on CakePHP's Configure class for settings:

- **AclManager.aros**: Required array defining ARO hierarchy (e.g., ['Groups', 'Roles', 'Users'])
- **AclManager.admin**: Boolean to enable admin prefix routing
- **AclManager.hideDenied**: Hide denied permissions in ACL lists
- **AclManager.ignoreActions**: Array of actions/controllers to ignore during ACO synchronization

### Dependencies
- Requires PHP 8.1 or higher
- Requires CakePHP 5.x framework (^5.0)
- Depends on cakephp/acl plugin (^2.0)
- Uses modern PHP features (declare(strict_types=1), typed properties, match expressions)

## Development Commands

### Installation
```bash
composer require ivanamat/cakephp5-aclmanager
```

### Database Setup
```bash
# Create ACL tables using migrations
bin/cake migrations migrate -p Acl
```

### Plugin Configuration
Add to your application's config/bootstrap.php:
```php
use Cake\Core\Configure;

Configure::write('AclManager.aros', ['Groups', 'Roles', 'Users']);
$this->addPlugin('Acl', ['bootstrap' => true]);
$this->addPlugin('AclManager', ['bootstrap' => true, 'routes' => true]);
```

## ACL Management Workflow

### ARO Hierarchy
The plugin expects a hierarchical ARO structure:
1. **Groups** (top level)
2. **Roles** (child of Groups)
3. **Users** (child of Roles)

Each model must implement the `parentNode()` method and use the Acl behavior.

### ACO Synchronization
The plugin provides automatic ACO synchronization by scanning controllers and actions across the application and loaded plugins.

### Permission Management
- Access via `/AclManager` (or `/admin/AclManager` if admin prefix enabled)
- Use "Update ACOs and AROs and set default values" to initialize
- Manage permissions through the web interface

## Modern Architecture Features

### Service Layer Architecture
The plugin now uses a service layer for better separation of concerns:
- **AclPermissionService**: Handles permission evaluation and matrix building
- **AclSynchronizationService**: Manages ACO/ARO synchronization and database operations

### Clean Code Improvements
- Strict type declarations throughout the codebase
- Typed properties and return types
- Modern PHP 8.1+ features (match expressions, nullsafe operators)
- Single Responsibility Principle adherence
- Descriptive method and variable names

### Entity Requirements
ARO entities must implement:
```php
public function parentNode(): ?array {
    // Return parent ARO reference or null for root
    return ['ParentModel' => ['id' => $parentId]];
}
```

### Table Requirements
ARO tables must use the Acl behavior:
```php
public function initialize(array $config): void {
    parent::initialize($config);
    $this->addBehavior('Acl.Acl', ['type' => 'requester']);
}
```

## Route Configuration

Routes use modern CakePHP 5.x routing with closures:
- Standard: `/acl-manager` and related actions
- Admin prefix: `/admin/acl-manager` when enabled
- RESTful route patterns with proper parameter passing

## Template Structure

Templates are located in `templates/` directory (CakePHP 5.x standard):
- `templates/Acl/index.php`: Main dashboard with Bootstrap 5 styling
- `templates/Acl/permissions.php`: Permission management interface
- Uses modern HTML5 semantic elements and accessibility features

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
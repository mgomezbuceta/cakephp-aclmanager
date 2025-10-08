# CakePHP 5.x ACL Manager

[![Latest Stable Version](https://img.shields.io/packagist/v/mgomezbuceta/cakephp-aclmanager.svg)](https://packagist.org/packages/mgomezbuceta/cakephp-aclmanager)
[![PHP Version Require](https://img.shields.io/packagist/php-v/mgomezbuceta/cakephp-aclmanager.svg)](https://packagist.org/packages/mgomezbuceta/cakephp-aclmanager)
[![License](https://img.shields.io/packagist/l/mgomezbuceta/cakephp-aclmanager.svg)](https://github.com/mgomezbuceta/cakephp-aclmanager/blob/main/LICENSE.md)

**Enhanced ACL permissions management system for CakePHP 5.x applications**

This plugin provides a comprehensive web interface for managing Access Control Lists (ACL) in CakePHP 5.x applications. It's been completely refactored following Clean Code principles and modern PHP standards.

> **Note**: This is a fork of the original [CakePHP AclManager](https://github.com/ivanamat/cakephp-aclmanager) by Iván Amat, updated and maintained by Marcos Gómez Buceta.

## Key Features

- 🔐 **Comprehensive ACL Management**: Complete web interface for managing permissions
- 🎯 **Hierarchical ARO Support**: Supports complex role hierarchies (Groups → Roles → Users)
- 🔄 **Automatic Synchronization**: Auto-sync ACOs and AROs with your application structure
- 🎨 **Modern UI**: Bootstrap-based responsive interface with accessibility features
- 🚀 **CakePHP 5.x Native**: Built specifically for CakePHP 5.x with modern PHP 8.1+ features
- 🧹 **Clean Architecture**: Service layer separation and SOLID principles implementation

For CakePHP 3.x/4.x versions, visit: https://github.com/ivanamat/cakephp3-aclmanager

## Requirements

- PHP 8.1 or higher
- CakePHP 5.0 or higher
- CakePHP ACL Plugin 2.0 or higher

## Installation

### Using Composer (Recommended)

```bash
composer require mgomezbuceta/cakephp-aclmanager
```

### Dependencies

First, install the CakePHP ACL plugin:

```bash
composer require cakephp/acl
```


## Quick Start

### 1. Configure ACL Models

In your `config/app_local.php` or `config/bootstrap.php`, configure your ARO hierarchy:

```php
use Cake\Core\Configure;

// Configure ARO hierarchy (parent to children)
Configure::write('AclManager.aros', ['Groups', 'Roles', 'Users']);

// Optional: Configure admin prefix
Configure::write('AclManager.admin', false);

// Optional: Hide denied permissions in lists
Configure::write('AclManager.hideDenied', true);
```

### 2. Load the Plugins

Add to your `config/bootstrap.php`:

```php
// Load required plugins
$this->addPlugin('Acl', ['bootstrap' => true]);
$this->addPlugin('AclManager', ['bootstrap' => true, 'routes' => true]);
```

### 3. Create Database Tables

Run the ACL migrations:

```bash
bin/cake migrations migrate -p Acl
```

## Configuration Options

All configuration options should be set in your `config/bootstrap.php` before loading the plugin:

### Required Configuration

```php
// Define your ARO hierarchy (parent to children relationship)
Configure::write('AclManager.aros', ['Groups', 'Roles', 'Users']);
```

### Optional Configuration

```php
// Enable admin prefix routing (default: false)
Configure::write('AclManager.admin', true);

// Hide denied permissions in ACL lists (default: true)
Configure::write('AclManager.hideDenied', true);

// Actions to ignore during ACO synchronization
Configure::write('AclManager.ignoreActions', [
    'isAuthorized',
    'beforeFilter',
    'afterFilter',
    'initialize',
    'Acl.*',           // Ignore entire ACL plugin
    'Error/*',         // Ignore Error controller
    'DebugKit.*',      // Ignore DebugKit plugin
    'Plugin.Controller/action'  // Ignore specific plugin action
]);

// Custom pagination limits per ARO model
Configure::write('AclManager.Groups.limit', 10);
Configure::write('AclManager.Roles.limit', 15);
Configure::write('AclManager.Users.limit', 20);
```

## Model Setup

### 1. Add ACL Behavior to Tables

Add the ACL behavior to your ARO table classes:

```php
// src/Model/Table/GroupsTable.php
public function initialize(array $config): void
{
    parent::initialize($config);
    $this->addBehavior('Acl.Acl', ['type' => 'requester']);
}

// src/Model/Table/RolesTable.php
public function initialize(array $config): void
{
    parent::initialize($config);
    $this->addBehavior('Acl.Acl', ['type' => 'requester']);
}

// src/Model/Table/UsersTable.php
public function initialize(array $config): void
{
    parent::initialize($config);
    $this->addBehavior('Acl.Acl', ['type' => 'requester']);
}
```

### 2. Implement parentNode in Entities

#### Group Entity (Root level)
```php
// src/Model/Entity/Group.php
public function parentNode(): ?array
{
    return null; // Root level has no parent
}
```

#### Role Entity
```php
// src/Model/Entity/Role.php
use Cake\ORM\Locator\LocatorAwareTrait;

public function parentNode(): ?array
{
    if (!$this->id || !$this->group_id) {
        return null;
    }

    return ['Groups' => ['id' => $this->group_id]];
}
```

#### User Entity
```php
// src/Model/Entity/User.php
use Cake\ORM\Locator\LocatorAwareTrait;

public function parentNode(): ?array
{
    if (!$this->id || !$this->role_id) {
        return null;
    }

    return ['Roles' => ['id' => $this->role_id]];
}
```

## Authentication Setup

### Using CakePHP 5.x Authentication Plugin

```php
// src/Controller/AppController.php
public function initialize(): void
{
    parent::initialize();

    $this->loadComponent('Authentication.Authentication');
    $this->loadComponent('Authorization.Authorization');
    $this->loadComponent('Acl', ['className' => 'Acl.Acl']);
}
```

### Configure Authorization

```php
// src/Application.php
public function getAuthorizationService(ServerRequestInterface $request): AuthorizationServiceInterface
{
    $resolver = new OrmResolver();

    return new AuthorizationService($resolver);
}
```

## Usage

### 1. Access the Management Interface

Navigate to:
- Standard: `http://yourdomain.com/acl-manager`
- Admin mode: `http://yourdomain.com/admin/acl-manager` (if admin prefix enabled)

### 2. Initialize ACL Structure

1. Click **"Reset to defaults"** to initialize the complete ACL structure
2. This will:
   - Create ACOs for all controllers and actions
   - Build AROs from your configured models
   - Set default permissions for the first user

### 3. Manage Permissions

- Use the **"Manage [Model]"** links to set permissions for each ARO type
- **Update ACOs/AROs** to synchronize with code changes
- **Revoke permissions** to reset all permissions to defaults

## Example Database Schema

Here's an example schema for the Groups → Roles → Users hierarchy:

```sql
CREATE TABLE groups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created DATETIME,
    modified DATETIME
);

CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    group_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created DATETIME,
    modified DATETIME,
    FOREIGN KEY (group_id) REFERENCES groups(id)
);

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_id INT NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created DATETIME,
    modified DATETIME,
    FOREIGN KEY (role_id) REFERENCES roles(id)
);
```

## Advanced Features

### Service Layer Architecture

The plugin uses a modern service layer for better code organization:

- **AclPermissionService**: Handles permission evaluation and matrix building
- **AclSynchronizationService**: Manages ACO/ARO synchronization and database operations

### Custom Integration

```php
// Inject services in your controllers
use AclManager\Service\AclPermissionService;
use AclManager\Service\AclSynchronizationService;

public function initialize(): void
{
    parent::initialize();

    $this->permissionService = new AclPermissionService($this->Acl);
    $this->syncService = new AclSynchronizationService($this->Acl, $this->AclManager);
}
```

## Troubleshooting

### Common Issues

1. **Missing permissions**: Run ACO/ARO synchronization after adding new controllers or actions
2. **Access denied**: Ensure your user hierarchy is properly configured with parentNode() methods
3. **Performance issues**: Consider enabling permission caching in production

### Debug Mode

Enable debug mode to see detailed ACL information:

```php
Configure::write('debug', true);
Configure::write('AclManager.debug', true);
```

## Contributing

1. Fork the repository
2. Create a feature branch
3. Follow PSR-12 coding standards
4. Add tests for new functionality
5. Submit a pull request

## License

This project is licensed under the MIT License - see the [LICENSE.md](LICENSE.md) file for details.

## Author

**Marcos Gómez Buceta**
- GitHub: [@mgomezbuceta](https://github.com/mgomezbuceta)
- Email: [mgomezbuceta@gmail.com](mailto:mgomezbuceta@gmail.com)

## Acknowledgments

This project is a fork and continuation of the excellent work done by:
- **Iván Amat** ([@ivanamat](https://github.com/ivanamat)) - Original [CakePHP 4.x Acl Manager](https://github.com/ivanamat/cakephp-aclmanager)
- **Frédéric Massart (FMCorz)** ([@FMCorz](https://github.com/FMCorz)) - Original [AclManager](https://github.com/FMCorz/AclManager) for CakePHP 2.x

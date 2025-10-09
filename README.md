<div align="center">

# 🔐 CakePHP Authorization Manager

### Modern Role-Based Permission Management for CakePHP 5.x

[![Latest Version](https://img.shields.io/packagist/v/mgomezbuceta/cakephp-aclmanager.svg?style=flat-square)](https://packagist.org/packages/mgomezbuceta/cakephp-aclmanager)
[![PHP Version](https://img.shields.io/packagist/php-v/mgomezbuceta/cakephp-aclmanager.svg?style=flat-square)](https://packagist.org/packages/mgomezbuceta/cakephp-aclmanager)
[![License](https://img.shields.io/packagist/l/mgomezbuceta/cakephp-aclmanager.svg?style=flat-square)](LICENSE.md)
[![Downloads](https://img.shields.io/packagist/dt/mgomezbuceta/cakephp-aclmanager.svg?style=flat-square)](https://packagist.org/packages/mgomezbuceta/cakephp-aclmanager)

**A powerful, modern web interface for managing role-based permissions using CakePHP's Authorization plugin.**

[Features](#-features) • [Installation](#-installation) • [Quick Start](#-quick-start) • [Documentation](#-documentation) • [Migration](#-migration-from-v2x)

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

</td>
<td width="50%">

### ⚡ **High Performance**
Built on CakePHP's Authorization plugin with permission caching for optimal performance.

### 🛡️ **Secure by Default**
Strict permission checking mode with comprehensive authorization middleware integration.

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

---

## 📋 Requirements

| Requirement | Version |
|------------|---------|
| PHP | ≥ 8.1 |
| CakePHP | ≥ 5.0 |
| CakePHP Authorization | ≥ 3.0 |

---

## 🚀 Installation

### Step 1: Install via Composer

```bash
composer require mgomezbuceta/cakephp-aclmanager
composer require cakephp/authorization
```

### Step 2: Load the Plugin

Add to your `src/Application.php`:

```php
public function bootstrap(): void
{
    parent::bootstrap();

    $this->addPlugin('AclManager', ['bootstrap' => true, 'routes' => true]);
}
```

### Step 3: Run Migrations

```bash
bin/cake migrations migrate -p AclManager
```

### Step 4: Sync Resources

Visit `/authorization-manager` and click **"Sync Resources"** to discover all your controllers and actions.

**That's it!** 🎉

---

## ⚡ Quick Start

### Basic Setup

1. **Create Roles**
   - Visit `/authorization-manager/roles`
   - Click "New Role"
   - Create roles like: Administrator, Editor, Viewer

2. **Assign Permissions**
   - Click "Manage Permissions" for a role
   - Check/uncheck permissions for each controller/action
   - Click "Save Permissions"

3. **Integrate with Your Auth**

```php
// In your AppController or specific controller
public function initialize(): void
{
    parent::initialize();

    $this->loadComponent('AclManager.AuthorizationManager', [
        'userModel' => 'Users',
        'roleField' => 'role_id'
    ]);
}

public function isAuthorized($user = null): bool
{
    return $this->AuthorizationManager->isAuthorized($user);
}
```

### Add role_id to Your Users Table

```sql
ALTER TABLE users ADD COLUMN role_id INT NOT NULL;
ALTER TABLE users ADD FOREIGN KEY (role_id) REFERENCES roles(id);
```

---

## 📚 Documentation

<details>
<summary><b>🔧 Configuration Options</b></summary>

In your `config/bootstrap.php`:

```php
use Cake\Core\Configure;

// Enable admin prefix
Configure::write('AclManager.admin', true);

// Actions to ignore during resource scan
Configure::write('AclManager.ignoreActions', [
    'isAuthorized',
    'beforeFilter',
    'initialize',
    'AclManager.*',      // Ignore plugin
    'DebugKit.*'         // Ignore DebugKit
]);

// Permission checking mode
Configure::write('AclManager.permissionMode', 'strict'); // or 'permissive'

// Enable permission caching
Configure::write('AclManager.cachePermissions', true);
Configure::write('AclManager.cacheDuration', '+1 hour');

// Default role for new users
Configure::write('AclManager.defaultRoleId', 2);
```

</details>

<details>
<summary><b>🗄️ Database Schema</b></summary>

The plugin creates three tables:

**roles** - User roles
```sql
id, name, description, priority, active, created, modified
```

**permissions** - Role permissions
```sql
id, role_id, controller, action, plugin, allowed, created, modified
```

**resources** - Available resources (auto-discovered)
```sql
id, controller, action, plugin, description, active, created, modified
```

</details>

<details>
<summary><b>🔌 Component Usage</b></summary>

```php
// Load the component
$this->loadComponent('AclManager.AuthorizationManager');

// Check if user is authorized
$allowed = $this->AuthorizationManager->isAuthorized($user);

// Check specific permission
$allowed = $this->AuthorizationManager->checkPermission(
    $roleId,
    'Articles',
    'edit',
    'Blog' // plugin name (optional)
);

// Clear permission cache
$this->AuthorizationManager->clearCache();

// Handle unauthorized access
return $this->AuthorizationManager->handleUnauthorized();
```

</details>

<details>
<summary><b>🎯 Service Layer</b></summary>

```php
use AclManager\Service\PermissionService;
use AclManager\Service\ResourceScannerService;

// Permission management
$permissionService = new PermissionService();

// Grant permission
$permissionService->grant($roleId, 'Articles', 'edit');

// Deny permission
$permissionService->deny($roleId, 'Articles', 'delete');

// Get permission matrix
$matrix = $permissionService->getPermissionMatrix($roleId);

// Copy permissions between roles
$permissionService->copyPermissions($sourceRoleId, $targetRoleId);

// Resource scanning
$scannerService = new ResourceScannerService();
$stats = $scannerService->scanAndSync();

// Get grouped resources
$resources = $scannerService->getGroupedResources();
```

</details>

<details>
<summary><b>🐛 Troubleshooting</b></summary>

**No resources showing?**
```bash
Visit /authorization-manager and click "Sync Resources"
```

**Permission changes not taking effect?**
```php
// Clear cache
Configure::write('AclManager.cachePermissions', false);
// Or clear specific cache
$this->AuthorizationManager->clearCache();
```

**Getting "access denied" after setup?**
```
1. Make sure your User has a role_id assigned
2. Verify permissions are granted for that role
3. Check isAuthorized() is properly implemented
```

</details>

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

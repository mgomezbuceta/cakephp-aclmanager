<div align="center">

# 🔐 CakePHP ACL Manager

### Modern Access Control List Management for CakePHP 5.x

[![Latest Version](https://img.shields.io/packagist/v/mgomezbuceta/cakephp-aclmanager.svg?style=flat-square)](https://packagist.org/packages/mgomezbuceta/cakephp-aclmanager)
[![PHP Version](https://img.shields.io/packagist/php-v/mgomezbuceta/cakephp-aclmanager.svg?style=flat-square)](https://packagist.org/packages/mgomezbuceta/cakephp-aclmanager)
[![License](https://img.shields.io/packagist/l/mgomezbuceta/cakephp-aclmanager.svg?style=flat-square)](LICENSE.md)
[![Downloads](https://img.shields.io/packagist/dt/mgomezbuceta/cakephp-aclmanager.svg?style=flat-square)](https://packagist.org/packages/mgomezbuceta/cakephp-aclmanager)

**A powerful, modern, and intuitive web interface for managing Access Control Lists in CakePHP 5.x applications.**

[Features](#-features) • [Installation](#-installation) • [Quick Start](#-quick-start) • [Documentation](#-documentation) • [Contributing](#-contributing)

---

</div>

## 🌟 Features

<table>
<tr>
<td width="50%">

### 🎯 **Smart Permission Management**
Intuitive web interface with visual permission matrices for managing complex ACL structures with ease.

### 🏗️ **Modern Architecture**
Built with PHP 8.1+ strict typing, service layer pattern, and SOLID principles for maintainable code.

### 🔄 **Auto-Sync**
Automatically synchronize your ACOs and AROs with your application structure—no manual updates needed.

</td>
<td width="50%">

### 🎨 **Beautiful UI**
Responsive Bootstrap 5 interface with accessibility features and modern design patterns.

### 🚀 **High Performance**
Optimized queries, efficient caching strategies, and clean architecture for production environments.

### 📊 **Hierarchical Support**
Full support for complex role hierarchies (Groups → Roles → Users) with inheritance.

</td>
</tr>
</table>

---

## 💡 Why This Plugin?

Managing permissions in CakePHP applications can be complex and time-consuming. **CakePHP ACL Manager** simplifies this process by providing:

- ✅ **Visual Interface** - No more command-line ACL management
- ✅ **Real-time Updates** - See permission changes immediately
- ✅ **Error Prevention** - Built-in validation and safety checks
- ✅ **Developer Friendly** - Clean API and comprehensive documentation
- ✅ **Production Ready** - Battle-tested code with modern PHP standards

---

## 📋 Requirements

| Requirement | Version |
|------------|---------|
| PHP | ≥ 8.1 |
| CakePHP | ≥ 5.0 |
| CakePHP ACL Plugin | ≥ 2.0 |

---

## 🚀 Installation

### Step 1: Install via Composer

```bash
composer require mgomezbuceta/cakephp-aclmanager
composer require cakephp/acl
```

### Step 2: Load the Plugins

Add to your `config/bootstrap.php`:

```php
$this->addPlugin('Acl', ['bootstrap' => true]);
$this->addPlugin('AclManager', ['bootstrap' => true, 'routes' => true]);
```

### Step 3: Create Database Tables

```bash
bin/cake migrations migrate -p Acl
```

**That's it!** 🎉 Access your ACL manager at `/acl-manager`

---

## ⚡ Quick Start

### Basic Configuration

Add this to your `config/bootstrap.php`:

```php
use Cake\Core\Configure;

// Define your ARO hierarchy (required)
Configure::write('AclManager.aros', ['Groups', 'Roles', 'Users']);

// Optional: Enable admin prefix
Configure::write('AclManager.admin', false);

// Optional: Hide denied permissions
Configure::write('AclManager.hideDenied', true);
```

### Set Up Your Models

#### 1️⃣ Add ACL Behavior to Tables

```php
// src/Model/Table/UsersTable.php
public function initialize(array $config): void
{
    parent::initialize($config);
    $this->addBehavior('Acl.Acl', ['type' => 'requester']);
}
```

#### 2️⃣ Implement parentNode in Entities

```php
// src/Model/Entity/User.php
public function parentNode(): ?array
{
    if (!$this->id || !$this->role_id) {
        return null;
    }
    return ['Roles' => ['id' => $this->role_id]];
}
```

### Initialize Your ACL

1. Navigate to `/acl-manager`
2. Click **"Reset to defaults"**
3. Start managing permissions! ✨

---

## 📚 Documentation

<details>
<summary><b>🔧 Advanced Configuration</b></summary>

```php
// Ignore specific actions during ACO sync
Configure::write('AclManager.ignoreActions', [
    'isAuthorized',
    'beforeFilter',
    'Acl.*',        // Ignore entire plugin
    'Error/*',      // Ignore controller
    'DebugKit.*'    // Ignore DebugKit
]);

// Custom pagination limits
Configure::write('AclManager.Groups.limit', 10);
Configure::write('AclManager.Roles.limit', 15);
Configure::write('AclManager.Users.limit', 20);
```

</details>

<details>
<summary><b>🗄️ Database Schema Example</b></summary>

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

</details>

<details>
<summary><b>🔌 Service Layer Usage</b></summary>

```php
use AclManager\Service\AclPermissionService;
use AclManager\Service\AclSynchronizationService;

// In your controller
public function initialize(): void
{
    parent::initialize();

    $this->permissionService = new AclPermissionService($this->Acl);
    $this->syncService = new AclSynchronizationService($this->Acl, $this->AclManager);
}
```

</details>

<details>
<summary><b>🐛 Troubleshooting</b></summary>

**Missing Permissions?**
```bash
# Re-sync ACOs after adding new controllers
Navigate to /acl-manager → Click "Update ACOs"
```

**Access Denied?**
```php
// Verify parentNode() implementation in your entities
// Check ARO hierarchy in configuration
```

**Debug Mode**
```php
Configure::write('debug', true);
Configure::write('AclManager.debug', true);
```

</details>

---

## 🏗️ Architecture Highlights

### Service Layer Pattern

```
┌─────────────────────────────────────────┐
│         AclController                    │
│         (Presentation Layer)             │
└──────────────┬──────────────────────────┘
               │
      ┌────────┴────────┐
      │                 │
┌─────▼──────────┐ ┌───▼──────────────────┐
│ Permission     │ │ Synchronization      │
│ Service        │ │ Service              │
│                │ │                      │
│ • Evaluate     │ │ • ACO/ARO Sync       │
│ • Build Matrix │ │ • DB Operations      │
└────────────────┘ └──────────────────────┘
```

**Benefits:**
- 🎯 Single Responsibility
- 🧪 Easy to Test
- 🔄 Reusable Logic
- 📦 Clean Dependencies

---

## 🤝 Contributing

We welcome contributions! Here's how you can help:

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

This project builds upon the excellent foundation laid by:

- **[Iván Amat](https://github.com/ivanamat)** - Original CakePHP 4.x Acl Manager
- **[Frédéric Massart (FMCorz)](https://github.com/FMCorz)** - Original CakePHP 2.x AclManager

Special thanks to the CakePHP community for their continuous support and contributions.

---

<div align="center">

**⭐ If you find this plugin useful, please consider giving it a star! ⭐**

Made with ❤️ for the CakePHP community

[Report Bug](https://github.com/mgomezbuceta/cakephp-aclmanager/issues) • [Request Feature](https://github.com/mgomezbuceta/cakephp-aclmanager/issues) • [View Documentation](https://github.com/mgomezbuceta/cakephp-aclmanager/wiki)

</div>

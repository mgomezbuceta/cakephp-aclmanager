# Migration Guide: CakePHP 3.x/4.x to 5.x ACL Manager

This guide will help you migrate from the legacy CakePHP 3.x/4.x ACL Manager to the new CakePHP 5.x version.

## Overview

CakePHP 5.x ACL Manager (v2.0.0) is a complete rewrite that brings modern PHP 8.1+ features, Clean Code principles, and improved architecture while maintaining the same core functionality.

## Before You Start

### Prerequisites

- PHP 8.1 or higher
- CakePHP 5.0 or higher
- CakePHP ACL Plugin 2.0 or higher

### Backup Your Data

**⚠️ Important**: Before starting the migration, make sure to backup your ACL data:

```sql
-- Backup your ACL tables
CREATE TABLE backup_acos AS SELECT * FROM acos;
CREATE TABLE backup_aros AS SELECT * FROM aros;
CREATE TABLE backup_aros_acos AS SELECT * FROM aros_acos;
```

## Step-by-Step Migration

### 1. Update Dependencies

Replace the old package with the new one:

```bash
# Remove old package
composer remove ivanamat/cakephp3-aclmanager

# Install new package
composer require ivanamat/cakephp5-aclmanager
```

### 2. Update Configuration

#### Before (CakePHP 3.x/4.x)
```php
// config/bootstrap.php (old)
Configure::write('AclManager.aros', array('Groups', 'Roles', 'Users'));

Plugin::load('Acl', ['bootstrap' => true]);
Plugin::load('AclManager', ['bootstrap' => true, 'routes' => true]);
```

#### After (CakePHP 5.x)
```php
// config/bootstrap.php (new)
use Cake\Core\Configure;

Configure::write('AclManager.aros', ['Groups', 'Roles', 'Users']);

$this->addPlugin('Acl', ['bootstrap' => true]);
$this->addPlugin('AclManager', ['bootstrap' => true, 'routes' => true]);
```

### 3. Update Route Paths

The route paths have changed for better organization:

#### Before
- Standard: `/AclManager`
- Admin: `/admin/AclManager`

#### After
- Standard: `/acl-manager`
- Admin: `/admin/acl-manager`

### 4. Update Your Models

#### Table Classes

Update your ARO table classes to use modern syntax:

**Before (CakePHP 3.x/4.x)**
```php
public function initialize(array $config) {
    parent::initialize($config);
    $this->addBehavior('Acl.Acl', ['type' => 'requester']);
}
```

**After (CakePHP 5.x)**
```php
public function initialize(array $config): void {
    parent::initialize($config);
    $this->addBehavior('Acl.Acl', ['type' => 'requester']);
}
```

#### Entity Classes

Update your entity `parentNode()` methods with type hints:

**Before (CakePHP 3.x/4.x)**
```php
public function parentNode() {
    if (!$this->id) {
        return null;
    }
    // ... logic
    return ['ParentModel' => ['id' => $parentId]];
}
```

**After (CakePHP 5.x)**
```php
public function parentNode(): ?array {
    if (!$this->id) {
        return null;
    }
    // ... logic
    return ['ParentModel' => ['id' => $parentId]];
}
```

### 5. Update Authentication (If Using)

If you were using the legacy AuthComponent, update to the modern Authentication plugin:

**Before (CakePHP 3.x/4.x)**
```php
$this->loadComponent('Auth', [
    'authorize' => [
        'Acl.Actions' => ['actionPath' => 'controllers/']
    ],
    // ... other config
]);
```

**After (CakePHP 5.x)**
```php
// src/Application.php
$this->addPlugin('Authentication');
$this->addPlugin('Authorization');

// src/Controller/AppController.php
$this->loadComponent('Authentication.Authentication');
$this->loadComponent('Authorization.Authorization');
$this->loadComponent('Acl', ['className' => 'Acl.Acl']);
```

### 6. Custom Templates (If Any)

If you had custom templates, they need to be updated:

#### Location Change
- **Before**: `src/Template/Acl/`
- **After**: `templates/Acl/`

#### File Extension Change
- **Before**: `.ctp`
- **After**: `.php`

#### Template Syntax Updates
```php
// Before
<?php echo __('Text'); ?>
<?php echo $this->Html->link('Link', ['action' => 'index']); ?>

// After
<?= __('Text') ?>
<?= $this->Html->link('Link', ['action' => 'index']) ?>
```

## Configuration Changes

### New Configuration Options

The new version provides enhanced configuration with defaults:

```php
// Enhanced configuration (optional)
Configure::write('AclManager.paginationLimits', [
    'Groups' => 10,
    'Roles' => 15,
    'Users' => 20
]);

// Improved ignore actions
Configure::write('AclManager.ignoreActions', [
    'isAuthorized',
    'beforeFilter',
    'afterFilter',
    'initialize',
    'Acl.*',
    'Error/*',
    'DebugKit.*'
]);
```

### Removed Configuration

These configurations are no longer needed (handled automatically):

- `AclManager.uglyIdent` - Styling is now handled by CSS
- Manual model loading - Now handled automatically

## Data Migration

Your existing ACL data should work without changes, but follow these steps to ensure compatibility:

### 1. Verify Data Integrity

```sql
-- Check your ACL tables
SELECT COUNT(*) FROM acos;
SELECT COUNT(*) FROM aros;
SELECT COUNT(*) FROM aros_acos;
```

### 2. Test Basic Functionality

1. Access the new interface at `/acl-manager`
2. Click "Update ACOs" to synchronize with any code changes
3. Click "Update AROs" to refresh user/role data
4. Verify permissions are working correctly

### 3. Re-synchronize if Needed

If you encounter issues, you can reset to defaults:

1. Go to `/acl-manager`
2. Click "Reset to defaults"
3. This will rebuild the entire ACL structure

## Breaking Changes Summary

| Feature | Old Version | New Version | Action Required |
|---------|-------------|-------------|-----------------|
| PHP Version | 5.4+ | 8.1+ | ✅ Update PHP |
| CakePHP Version | 3.x/4.x | 5.x | ✅ Update CakePHP |
| Package Name | `ivanamat/cakephp3-aclmanager` | `ivanamat/cakephp5-aclmanager` | ✅ Update composer |
| Template Location | `src/Template/` | `templates/` | ⚠️ If customized |
| Template Extension | `.ctp` | `.php` | ⚠️ If customized |
| Route Paths | `/AclManager` | `/acl-manager` | ⚠️ Update bookmarks |
| Plugin Loading | `Plugin::load()` | `$this->addPlugin()` | ✅ Update bootstrap |

## Testing Your Migration

### 1. Functional Testing

Test these core features:

- [ ] Access the management interface
- [ ] View permissions for different ARO types
- [ ] Update ACOs and AROs
- [ ] Modify permissions
- [ ] Reset to defaults

### 2. Integration Testing

- [ ] Verify ACL authorization still works in your application
- [ ] Test user authentication and permissions
- [ ] Check hierarchical permissions (Groups → Roles → Users)

### 3. Performance Testing

The new version should perform better due to:
- Modern PHP optimizations
- Improved service architecture
- Better database queries

## Common Issues and Solutions

### Issue: "Class not found" errors

**Solution**: Clear your application cache:
```bash
bin/cake cache clear_all
```

### Issue: Routes not working

**Solution**: Ensure you're using the new route paths (`/acl-manager` instead of `/AclManager`)

### Issue: Templates not loading

**Solution**: Check template locations have moved from `src/Template/` to `templates/`

### Issue: Authentication errors

**Solution**: Update to CakePHP 5.x Authentication/Authorization plugins

## Need Help?

- **Documentation**: Check the updated [README.md](README.md)
- **Issues**: Report problems on [GitHub Issues](https://github.com/ivanamat/cakephp-aclmanager/issues)
- **Discussions**: Ask questions in [GitHub Discussions](https://github.com/ivanamat/cakephp-aclmanager/discussions)

## Rollback Plan

If you need to rollback to the previous version:

1. Restore your data from backups
2. Revert composer changes:
   ```bash
   composer remove ivanamat/cakephp5-aclmanager
   composer require ivanamat/cakephp3-aclmanager
   ```
3. Restore your original configuration files
4. Clear cache: `bin/cake cache clear_all`

---

**Note**: This migration guide assumes you're following CakePHP's own migration path from 3.x/4.x to 5.x. If you haven't migrated your main application yet, please refer to the [official CakePHP migration guide](https://book.cakephp.org/5/en/appendices/5-0-migration-guide.html) first.
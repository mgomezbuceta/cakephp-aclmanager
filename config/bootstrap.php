<?php
declare(strict_types=1);

/**
 * Authorization Manager Bootstrap
 *
 * Modern bootstrap configuration for CakePHP 5.x Authorization Manager
 *
 * Licensed under The MIT License
 *
 * @author Marcos Gómez Buceta <mgomezbuceta@gmail.com>
 * @copyright Copyright 2025, Marcos Gómez Buceta
 * @license MIT http://opensource.org/licenses/MIT
 * @link https://github.com/mgomezbuceta/cakephp-aclmanager
 */

use Cake\Core\Configure;
use Cake\I18n\I18n;

/**
 * Plugin version
 */
Configure::write('AclManager.version', '3.2.0');

/**
 * Load plugin translations
 * Default language: Spanish (es_ES)
 */
I18n::setLocale('es_ES');

/**
 * Default configuration values
 */
$defaultConfig = [
    // Enable admin prefix routing
    'admin' => false,

    // Actions to ignore during resource synchronization
    'ignoreActions' => [
        'isAuthorized',
        'beforeFilter',
        'afterFilter',
        'initialize',
        'beforeRender',
        'AclManager.*',
        'Authorization.*',
        'Authentication.*',
        'Error/*',
        'DebugKit.*',
        'Bake.*',
        'Migrations.*'
    ],

    // Default role for new users (optional)
    'defaultRoleId' => null,

    // Cache permissions for better performance
    'cachePermissions' => true,
    'cacheDuration' => '+1 hour',

    // Permission checking mode: 'strict' or 'permissive'
    // strict: deny if permission not found
    // permissive: allow if permission not found
    'permissionMode' => 'strict',

    // Default pagination limits
    'paginationLimits' => [
        'roles' => 20,
        'permissions' => 50,
        'resources' => 100
    ]
];

// Apply default configuration if not already set
foreach ($defaultConfig as $key => $value) {
    if (!Configure::check("AclManager.{$key}")) {
        Configure::write("AclManager.{$key}", $value);
    }
}

// Ensure ignoreActions configuration is an array
$ignoreActions = Configure::read('AclManager.ignoreActions');
if (!is_array($ignoreActions)) {
    Configure::write('AclManager.ignoreActions', [$ignoreActions]);
}

// Set individual pagination limits
$defaultLimits = Configure::read('AclManager.paginationLimits', []);
foreach (['roles', 'permissions', 'resources'] as $entity) {
    if (!Configure::check("AclManager.{$entity}.limit")) {
        $limit = $defaultLimits[$entity] ?? 20;
        Configure::write("AclManager.{$entity}.limit", $limit);
    }
}

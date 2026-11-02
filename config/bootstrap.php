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

/**
 * Plugin version
 */
Configure::write('AclManager.version', '3.2.0');

/**
 * Default configuration values
 */
$defaultConfig = [
    // URL path where the manager is mounted (e.g. '/admin/authorization-manager')
    'mountPath' => '/authorization-manager',

    // When set (e.g. 'es_ES'), the plugin pages use this locale for the request.
    // null = follow the host application's locale.
    'locale' => null,

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
        'Migrations.*',
        'Utilities/*',
    ],

    // Admin access: set 'allowWithoutAuthentication' => true to allow access when the Authentication component is missing
    'adminAccess' => [
        'allowWithoutAuthentication' => false,
    ],

    // Migrations never alter pre-existing roles/permissions/resources tables unless this is true
    'migrations' => ['adoptExistingTables' => false],

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

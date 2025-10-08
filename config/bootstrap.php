<?php

declare(strict_types=1);

/**
 * CakePHP 5.x - ACL Manager Bootstrap
 *
 * Enhanced bootstrap configuration for CakePHP 5.x
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @author Ivan Amat <dev@ivanamat.es>
 * @copyright Copyright 2024, Iván Amat
 * @license MIT http://opensource.org/licenses/MIT
 * @link https://github.com/ivanamat/cakephp-aclmanager
 */

use Cake\Core\Configure;

/**
 * Plugin version
 */
Configure::write('AclManager.version', '2.0.0');

/**
 * Default configuration values
 */
$defaultConfig = [
    // List of ARO models (hierarchy: parent to children)
    'aros' => ['Groups', 'Roles', 'Users'],

    // Enable admin prefix routing
    'admin' => false,

    // Hide denied permissions in ACL lists
    'hideDenied' => true,

    // Use ugly indentation (for non-CSS styling)
    'uglyIdent' => true,

    // Actions to ignore during ACO synchronization
    'ignoreActions' => [
        'isAuthorized',
        'beforeFilter',
        'afterFilter',
        'initialize',
        'Acl.*',
        'Error/*',
        'DebugKit.*'
    ],

    // Default pagination limits per ARO model
    'paginationLimits' => [
        'Groups' => 10,
        'Roles' => 15,
        'Users' => 20
    ]
];

// Apply default configuration if not already set
foreach ($defaultConfig as $key => $value) {
    if (!Configure::check("AclManager.{$key}")) {
        Configure::write("AclManager.{$key}", $value);
    }
}

// Ensure AROs configuration is an array
$aros = Configure::read('AclManager.aros');
if (!is_array($aros)) {
    Configure::write('AclManager.aros', [$aros]);
}

// Ensure ignoreActions configuration is an array
$ignoreActions = Configure::read('AclManager.ignoreActions');
if (!is_array($ignoreActions)) {
    Configure::write('AclManager.ignoreActions', [$ignoreActions]);
}

// Set models configuration if not specified
if (!Configure::read('AclManager.models')) {
    Configure::write('AclManager.models', Configure::read('AclManager.aros'));
}

// Set individual pagination limits if specified
$aros = Configure::read('AclManager.aros', []);
$defaultLimits = Configure::read('AclManager.paginationLimits', []);

foreach ($aros as $aro) {
    if (!Configure::check("AclManager.{$aro}.limit")) {
        $limit = $defaultLimits[$aro] ?? 10;
        Configure::write("AclManager.{$aro}.limit", $limit);
    }
}

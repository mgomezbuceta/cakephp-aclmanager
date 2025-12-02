<?php
/**
 * Authorization Manager Configuration Example
 *
 * Add this configuration to your application's config/app.php file
 * (merge with existing configuration array).
 *
 * DO NOT copy this entire file - just add the AclManager section
 * to your existing config/app.php return array.
 */

// Example configuration - add to your config/app.php:
return [
    'AclManager' => [
        /**
         * Admin Access Configuration
         *
         * Define how the plugin determines if a user is an administrator
         */
        'adminAccess' => [
            // Method to check admin access: 'role_id', 'role_name', 'is_admin', 'email', or 'custom'
            'checkMethod' => 'role_id',

            // For 'role_id' method: Which role IDs are considered admins
            'adminRoleIds' => [1],

            // For 'role_name' method: Which role names are considered admins
            'adminRoleNames' => ['admin', 'administrator', 'superadmin'],

            // For 'email' method: Which emails have admin access (useful for initial setup)
            'adminEmails' => [
                // 'admin@example.com',
            ],

            // Custom callback function for admin check
            // Example: 'customCheck' => function($identity) { return $identity->hasPermission('manage_authorization'); }
            'customCheck' => null,
        ],

        /**
         * Redirect URLs
         */
        'redirects' => [
            // Where to redirect when user is not logged in
            'login' => ['plugin' => false, 'controller' => 'Users', 'action' => 'login'],

            // Where to redirect when user is not authorized
            'unauthorized' => ['plugin' => false, 'controller' => 'Pages', 'action' => 'display', 'home'],
        ],

        /**
         * Other Plugin Settings
         */
        'version' => '3.0.0',

        // Actions/Controllers to ignore during resource scanning
        'ignoreActions' => [
            'isAuthorized',
            'beforeFilter',
            'initialize',
            'AclManager.*',
            'Authorization.*',
            'Authentication.*',
            'Error/*',
            'DebugKit.*',
            'Utilities/*'
        ],
    ],
];

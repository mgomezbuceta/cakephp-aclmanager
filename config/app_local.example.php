<?php
/**
 * Authorization Manager Configuration Example
 *
 * Copy this file to your application's config/app_local.php or config/app.php
 * and customize the settings according to your needs.
 */

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
        ],
    ],
];

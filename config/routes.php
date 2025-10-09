<?php
declare(strict_types=1);

/**
 * Authorization Manager Routes
 *
 * Modern routing configuration for CakePHP 5.x Authorization Manager
 *
 * Licensed under The MIT License
 *
 * @author Marcos Gómez Buceta <mgomezbuceta@gmail.com>
 * @copyright Copyright 2025, Marcos Gómez Buceta
 * @license MIT http://opensource.org/licenses/MIT
 * @link https://github.com/mgomezbuceta/cakephp-aclmanager
 */

use Cake\Routing\RouteBuilder;
use Cake\Core\Configure;

return function (RouteBuilder $routes): void {
    $isAdminMode = Configure::read('AclManager.admin', false);

    if ($isAdminMode) {
        // Admin prefix routes
        $routes->prefix('Admin', function (RouteBuilder $builder): void {
            $builder->plugin('AclManager', ['path' => '/admin/authorization-manager'], function (RouteBuilder $builder): void {
                // Dashboard
                $builder->connect('/', ['controller' => 'Permissions', 'action' => 'index']);

                // Permission management
                $builder->connect('/manage/{roleId}', [
                    'controller' => 'Permissions',
                    'action' => 'manage'
                ], ['pass' => ['roleId'], 'roleId' => '\d+']);

                // Resource synchronization
                $builder->connect('/sync-resources', [
                    'controller' => 'Permissions',
                    'action' => 'syncResources'
                ]);

                // Role management
                $builder->connect('/roles', ['controller' => 'Permissions', 'action' => 'roles']);
                $builder->connect('/roles/add', ['controller' => 'Permissions', 'action' => 'addRole']);
                $builder->connect('/roles/edit/{id}', [
                    'controller' => 'Permissions',
                    'action' => 'editRole'
                ], ['pass' => ['id'], 'id' => '\d+']);
                $builder->connect('/roles/delete/{id}', [
                    'controller' => 'Permissions',
                    'action' => 'deleteRole'
                ], ['pass' => ['id'], 'id' => '\d+']);

                // Permission operations
                $builder->connect('/copy-permissions/{sourceId}/{targetId}', [
                    'controller' => 'Permissions',
                    'action' => 'copyPermissions'
                ], ['pass' => ['sourceId', 'targetId'], 'sourceId' => '\d+', 'targetId' => '\d+']);

                $builder->connect('/clear-permissions/{roleId}', [
                    'controller' => 'Permissions',
                    'action' => 'clearPermissions'
                ], ['pass' => ['roleId'], 'roleId' => '\d+']);

                $builder->fallbacks();
            });
        });
    } else {
        // Standard routes (no admin prefix)
        $routes->plugin('AclManager', ['path' => '/authorization-manager'], function (RouteBuilder $builder): void {
            // Dashboard
            $builder->connect('/', ['controller' => 'Permissions', 'action' => 'index']);

            // Permission management
            $builder->connect('/manage/{roleId}', [
                'controller' => 'Permissions',
                'action' => 'manage'
            ], ['pass' => ['roleId'], 'roleId' => '\d+']);

            // Resource synchronization
            $builder->connect('/sync-resources', [
                'controller' => 'Permissions',
                'action' => 'syncResources'
            ]);

            // Role management
            $builder->connect('/roles', ['controller' => 'Permissions', 'action' => 'roles']);
            $builder->connect('/roles/add', ['controller' => 'Permissions', 'action' => 'addRole']);
            $builder->connect('/roles/edit/{id}', [
                'controller' => 'Permissions',
                'action' => 'editRole'
            ], ['pass' => ['id'], 'id' => '\d+']);
            $builder->connect('/roles/delete/{id}', [
                'controller' => 'Permissions',
                'action' => 'deleteRole'
            ], ['pass' => ['id'], 'id' => '\d+']);

            // Permission operations
            $builder->connect('/copy-permissions/{sourceId}/{targetId}', [
                'controller' => 'Permissions',
                'action' => 'copyPermissions'
            ], ['pass' => ['sourceId', 'targetId'], 'sourceId' => '\d+', 'targetId' => '\d+']);

            $builder->connect('/clear-permissions/{roleId}', [
                'controller' => 'Permissions',
                'action' => 'clearPermissions'
            ], ['pass' => ['roleId'], 'roleId' => '\d+']);

            $builder->fallbacks();
        });
    }
};

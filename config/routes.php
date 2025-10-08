<?php

declare(strict_types=1);

/**
 * CakePHP 5.x - ACL Manager Routes
 *
 * Enhanced routing configuration for CakePHP 5.x
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @author Ivan Amat <dev@ivanamat.es>
 * @copyright Copyright 2024, Iván Amat
 * @license MIT http://opensource.org/licenses/MIT
 * @link https://github.com/ivanamat/cakephp-aclmanager
 */

use Cake\Routing\RouteBuilder;
use Cake\Core\Configure;

return function (RouteBuilder $routes): void {
    $isAdminMode = Configure::read('AclManager.admin', false);

    if ($isAdminMode) {
        $routes->prefix('Admin', function (RouteBuilder $builder): void {
            $builder->plugin('AclManager', ['path' => '/admin/acl-manager'], function (RouteBuilder $builder): void {
                $builder->fallbacks();
            });
        });
    } else {
        $routes->plugin('AclManager', ['path' => '/acl-manager'], function (RouteBuilder $builder): void {
            $builder->connect(
                '/',
                ['controller' => 'Acl', 'action' => 'index']
            );

            $builder->connect(
                '/permissions/{model}',
                ['controller' => 'Acl', 'action' => 'permissions'],
                ['pass' => ['model']]
            );

            $builder->connect(
                '/update-acos',
                ['controller' => 'Acl', 'action' => 'updateAcos']
            );

            $builder->connect(
                '/update-aros',
                ['controller' => 'Acl', 'action' => 'updateAros']
            );

            $builder->connect(
                '/revoke-permissions',
                ['controller' => 'Acl', 'action' => 'revokePerms']
            );

            $builder->connect(
                '/drop',
                ['controller' => 'Acl', 'action' => 'drop']
            );

            $builder->connect(
                '/defaults',
                ['controller' => 'Acl', 'action' => 'defaults']
            );

            $builder->fallbacks();
        });
    }
};
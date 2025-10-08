<?php

declare(strict_types=1);

/**
 * CakePHP 5.x - ACL Manager Index Template
 *
 * Enhanced index template for CakePHP 5.x with modern HTML5 and accessibility
 *
 * @author Ivan Amat <dev@ivanamat.es>
 * @copyright Copyright 2024, Iván Amat
 * @license MIT http://opensource.org/licenses/MIT
 * @link https://github.com/ivanamat/cakephp-aclmanager
 */

$this->Html->css('AclManager.default', ['block' => true]);
$this->assign('title', __('ACL Manager'));
?>

<div class="acl-manager-index">
    <header class="page-header">
        <div class="container-fluid">
            <h1 class="page-title">
                <?= __('CakePHP 5.x - ACL Manager') ?>
                <small class="text-muted"><?= __('Version {0}', $this->Configure->read('AclManager.version', '2.0.0')) ?></small>
            </h1>
        </div>
    </header>

    <main class="page-content">
        <div class="container-fluid">
            <div class="row">
                <!-- Management Section -->
                <section class="col-lg-4 col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h3 class="card-title h5 mb-0">
                                <i class="fas fa-users-cog" aria-hidden="true"></i>
                                <?= __('Manage Permissions') ?>
                            </h3>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled mb-0">
                                <?php foreach ($manage as $item): ?>
                                    <li class="mb-2">
                                        <?= $this->Html->link(
                                            __('Manage {0}', h(strtolower($item))),
                                            ['controller' => 'Acl', 'action' => 'permissions', $item],
                                            [
                                                'class' => 'btn btn-outline-primary btn-sm',
                                                'title' => __('Manage permissions for {0}', h($item))
                                            ]
                                        ) ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </section>

                <!-- Update Section -->
                <section class="col-lg-4 col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h3 class="card-title h5 mb-0">
                                <i class="fas fa-sync-alt" aria-hidden="true"></i>
                                <?= __('Synchronization') ?>
                            </h3>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled mb-0">
                                <li class="mb-2">
                                    <?= $this->Html->link(
                                        __('Update ACOs'),
                                        ['controller' => 'Acl', 'action' => 'updateAcos'],
                                        [
                                            'class' => 'btn btn-info btn-sm',
                                            'title' => __('Synchronize Access Control Objects')
                                        ]
                                    ) ?>
                                </li>
                                <li class="mb-2">
                                    <?= $this->Html->link(
                                        __('Update AROs'),
                                        ['controller' => 'Acl', 'action' => 'updateAros'],
                                        [
                                            'class' => 'btn btn-info btn-sm',
                                            'title' => __('Synchronize Access Request Objects')
                                        ]
                                    ) ?>
                                </li>
                            </ul>
                        </div>
                    </div>
                </section>

                <!-- Maintenance Section -->
                <section class="col-lg-4 col-md-12 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h3 class="card-title h5 mb-0">
                                <i class="fas fa-tools" aria-hidden="true"></i>
                                <?= __('Maintenance') ?>
                            </h3>
                        </div>
                        <div class="card-body">
                            <ul class="list-unstyled mb-0">
                                <li class="mb-2">
                                    <?= $this->Html->link(
                                        __('Revoke permissions and set defaults'),
                                        ['controller' => 'Acl', 'action' => 'revokePerms'],
                                        [
                                            'class' => 'btn btn-warning btn-sm',
                                            'confirm' => __('Do you really want to revoke all permissions? This will remove all assigned permissions and set defaults. Only the first item of the last ARO will have access to the panel.'),
                                            'title' => __('Reset permissions to defaults')
                                        ]
                                    ) ?>
                                </li>
                                <li class="mb-2">
                                    <?= $this->Html->link(
                                        __('Drop ACOs and AROs'),
                                        ['controller' => 'Acl', 'action' => 'drop'],
                                        [
                                            'class' => 'btn btn-danger btn-sm',
                                            'confirm' => __('Do you really want to delete ACOs and AROs? This will remove all assigned permissions.'),
                                            'title' => __('Delete all ACL data')
                                        ]
                                    ) ?>
                                </li>
                                <li class="mb-2">
                                    <?= $this->Html->link(
                                        __('Reset to defaults'),
                                        ['controller' => 'Acl', 'action' => 'defaults'],
                                        [
                                            'class' => 'btn btn-success btn-sm',
                                            'confirm' => __('Do you want to restore defaults? This will remove all assigned permissions and recreate the ACL structure. Only the first item of the last ARO will have access to the panel.'),
                                            'title' => __('Restore complete ACL structure with defaults')
                                        ]
                                    ) ?>
                                </li>
                            </ul>
                        </div>
                    </div>
                </section>
            </div>

            <!-- Flash Messages -->
            <?php if ($this->Flash->render()): ?>
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="alert-container">
                            <?= $this->Flash->render() ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- About Section -->
            <footer class="row mt-5">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body text-center">
                            <h4 class="card-title"><?= __('About CakePHP 5.x - ACL Manager') ?></h4>
                            <p class="card-text text-muted">
                                <?= __('Enhanced ACL permissions management system for CakePHP 5.x applications') ?>
                            </p>
                            <p class="mb-0">
                                <?= $this->Html->link(
                                    __('View on GitHub'),
                                    'https://github.com/ivanamat/cakephp-aclmanager',
                                    [
                                        'target' => '_blank',
                                        'rel' => 'noopener noreferrer',
                                        'class' => 'btn btn-outline-secondary',
                                        'title' => __('Open project repository in new tab')
                                    ]
                                ) ?>
                            </p>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </main>
</div>

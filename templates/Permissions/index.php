<?php
/**
 * @var \App\View\AppView $this
 * @var array $roles
 * @var int $resourceCount
 */
$this->assign('title', __d('acl_manager', 'Dashboard'));
?>

<div class="authorization-manager-index">
    <div class="row">
        <div class="col-md-12">
            <div class="page-header">
                <h1><?= __d('acl_manager', 'Manage Roles & Permissions') ?></h1>
                <p class="lead"><?= __d('acl_manager', 'Overview of all roles and their permissions') ?></p>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-12">
            <div class="btn-toolbar" role="toolbar">
                <?= $this->Html->link(
                    '<i class="fas fa-sync"></i> ' . __d('acl_manager', 'Sync Resources'),
                    ['action' => 'syncResources'],
                    ['class' => 'btn btn-primary me-2', 'escape' => false]
                ) ?>
                <?= $this->Html->link(
                    '<i class="fas fa-users"></i> ' . __d('acl_manager', 'Manage Roles'),
                    ['action' => 'roles'],
                    ['class' => 'btn btn-secondary', 'escape' => false]
                ) ?>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card text-white" style="background-color: var(--primary-color);">
                <div class="card-body">
                    <h5 class="card-title"><?= __d('acl_manager', 'Total Roles') ?></h5>
                    <p class="display-4"><?= count($roles) ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white" style="background-color: var(--primary-color);">
                <div class="card-body">
                    <h5 class="card-title"><?= __d('acl_manager', 'Total Resources') ?></h5>
                    <p class="display-4"><?= $resourceCount ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white" style="background-color: var(--primary-color);">
                <div class="card-body">
                    <h5 class="card-title"><?= __d('acl_manager', 'Total Permissions') ?></h5>
                    <p class="display-4"><?= array_sum(array_column($roles, 'permission_count')) ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="mb-0"><?= __d('acl_manager', 'Roles') ?></h3>
                </div>
                <div class="card-body">
                    <?php if (!empty($roles)): ?>
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th><?= __d('acl_manager', 'Role') ?></th>
                                    <th><?= __d('acl_manager', 'Priority') ?></th>
                                    <th><?= __d('acl_manager', 'Permissions') ?></th>
                                    <th><?= __d('acl_manager', 'Status') ?></th>
                                    <th class="actions"><?= __d('acl_manager', 'Actions') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($roles as $roleData): ?>
                                    <?php $role = $roleData['role']; ?>
                                    <tr>
                                        <td>
                                            <strong><?= h($role->name) ?></strong>
                                            <?php if ($role->description): ?>
                                                <br>
                                                <small class="text-muted"><?= h($role->description) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span class="badge badge-secondary"><?= h($role->priority) ?></span>
                                        </td>
                                        <td>
                                            <span class="badge badge-success"><?= $roleData['allowed_count'] ?></span>
                                            <?= __d('acl_manager', 'Allow') ?>
                                            <span class="badge badge-danger ms-2"><?= $roleData['denied_count'] ?></span>
                                            <?= __d('acl_manager', 'Deny') ?>
                                        </td>
                                        <td>
                                            <?php if ($role->active): ?>
                                                <span class="badge badge-success"><?= __d('acl_manager', 'Active') ?></span>
                                            <?php else: ?>
                                                <span class="badge badge-secondary"><?= __d('acl_manager', 'Inactive') ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="actions">
                                            <?= $this->Html->link(
                                                '<i class="fas fa-key"></i>',
                                                ['action' => 'manage', $role->id],
                                                ['class' => 'btn btn-sm btn-primary', 'escape' => false, 'title' => __d('acl_manager', 'Manage Permissions')]
                                            ) ?>
                                            <?= $this->Html->link(
                                                '<i class="fas fa-edit"></i>',
                                                ['action' => 'editRole', $role->id],
                                                ['class' => 'btn btn-sm btn-secondary', 'escape' => false, 'title' => __d('acl_manager', 'Edit')]
                                            ) ?>
                                            <?php if(!in_array($role->id, $adminRoleIds)) : ?>
                                                <?= $this->Form->postLink(
                                                    '<i class="fas fa-trash"></i>',
                                                    ['action' => 'deleteRole', $role->id],
                                                    [
                                                        'confirm' => __d('acl_manager', 'Are you sure you want to delete {0}?', $role->name),
                                                        'class' => 'btn btn-sm btn-danger',
                                                        'escape' => false,
                                                        'title' => __d('acl_manager', 'Delete')
                                                    ]
                                                ) ?>
                                            <?php else: ?>
                                                <?= $this->Html->link(
                                                    '<i class="fas fa-trash"></i>',
                                                    '#',
                                                    ['class' => 'btn btn-sm btn-danger disabled-link', 'escape' => false, 'title' => __d('acl_manager', 'Delete'), 'onclick' => 'return false;']
                                                ) ?>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="alert alert-info text-center">
                            <i class="fas fa-info-circle fa-2x mb-2"></i>
                            <h5><?= __d('acl_manager', 'No roles found') ?></h5>
                            <p><?= __d('acl_manager', 'Get started by creating your first role.') ?></p>
                            <?= $this->Html->link(
                                '<i class="fas fa-plus"></i> ' . __d('acl_manager', 'Create First Role'),
                                ['action' => 'addRole'],
                                ['class' => 'btn btn-primary', 'escape' => false]
                            ) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.authorization-manager-index .display-4 {
    font-size: 2.5rem;
    font-weight: 300;
    margin: 0;
}

.authorization-manager-index .card {
    margin-bottom: 20px;
}

.authorization-manager-index .btn-toolbar .btn {
    margin-right: 10px;
}

.authorization-manager-index table .actions {
    white-space: nowrap;
    width: 1%;
}

.authorization-manager-index table .actions .btn {
    margin-right: 5px;
}
</style>

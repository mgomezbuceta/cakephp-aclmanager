<?php
/**
 * @var \App\View\AppView $this
 * @var array $roles
 * @var int $resourceCount
 */
$this->assign('title', __('Authorization Manager'));
?>

<div class="authorization-manager-index">
    <div class="row">
        <div class="col-md-12">
            <div class="page-header">
                <h1><?= __('Authorization Manager') ?></h1>
                <p class="lead"><?= __('Manage role-based permissions for your application') ?></p>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-12">
            <div class="btn-toolbar" role="toolbar">
                <?= $this->Html->link(
                    '<i class="fas fa-sync"></i> ' . __('Sync Resources'),
                    ['action' => 'syncResources'],
                    ['class' => 'btn btn-primary', 'escape' => false]
                ) ?>
                <?= $this->Html->link(
                    '<i class="fas fa-users"></i> ' . __('Manage Roles'),
                    ['action' => 'roles'],
                    ['class' => 'btn btn-secondary', 'escape' => false]
                ) ?>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title"><?= __('Total Roles') ?></h5>
                    <p class="display-4"><?= count($roles) ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title"><?= __('Available Resources') ?></h5>
                    <p class="display-4"><?= $resourceCount ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title"><?= __('Total Permissions') ?></h5>
                    <p class="display-4"><?= array_sum(array_column($roles, 'permission_count')) ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><?= __('Roles Overview') ?></h3>
                </div>
                <div class="card-body">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th><?= __('Role') ?></th>
                                <th><?= __('Priority') ?></th>
                                <th><?= __('Total Permissions') ?></th>
                                <th><?= __('Allowed') ?></th>
                                <th><?= __('Denied') ?></th>
                                <th><?= __('Status') ?></th>
                                <th class="actions"><?= __('Actions') ?></th>
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
                                    <td><?= $roleData['permission_count'] ?></td>
                                    <td>
                                        <span class="badge badge-success"><?= $roleData['allowed_count'] ?></span>
                                    </td>
                                    <td>
                                        <span class="badge badge-danger"><?= $roleData['denied_count'] ?></span>
                                    </td>
                                    <td>
                                        <?php if ($role->active): ?>
                                            <span class="badge badge-success"><?= __('Active') ?></span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary"><?= __('Inactive') ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="actions">
                                        <?= $this->Html->link(
                                            '<i class="fas fa-key"></i>',
                                            ['action' => 'manage', $role->id],
                                            ['class' => 'btn btn-sm btn-primary', 'escape' => false, 'title' => __('Manage Permissions')]
                                        ) ?>
                                        <?= $this->Html->link(
                                            '<i class="fas fa-edit"></i>',
                                            ['action' => 'editRole', $role->id],
                                            ['class' => 'btn btn-sm btn-secondary', 'escape' => false, 'title' => __('Edit Role')]
                                        ) ?>
                                        <?= $this->Form->postLink(
                                            '<i class="fas fa-trash"></i>',
                                            ['action' => 'deleteRole', $role->id],
                                            [
                                                'confirm' => __('Are you sure you want to delete {0}?', $role->name),
                                                'class' => 'btn btn-sm btn-danger',
                                                'escape' => false,
                                                'title' => __('Delete Role')
                                            ]
                                        ) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <?php if (empty($roles)): ?>
                        <div class="alert alert-info">
                            <?= __('No roles found. Create your first role to get started.') ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.authorization-manager-index .display-4 {
    font-size: 3rem;
    font-weight: 300;
    margin: 0;
}

.authorization-manager-index .card {
    margin-bottom: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,.1);
}

.authorization-manager-index .btn-toolbar {
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

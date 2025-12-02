<?php
/**
 * @var \App\View\AppView $this
 * @var \AclManager\Model\Entity\Role[] $roles
 */
$this->assign('title', __d('acl_manager', 'Roles'));
?>

<div class="roles-index">
    <div class="row">
        <div class="col-md-12">
            <div class="page-header d-flex justify-content-between align-items-center">
                <div>
                    <h1><?= __d('acl_manager', 'Manage Roles') ?></h1>
                    <p class="lead"><?= __d('acl_manager', 'Create and manage user roles') ?></p>
                </div>
                <div>
                    <?= $this->Html->link(
                        '<i class="fas fa-arrow-left"></i> ' . __d('acl_manager', 'Back to Dashboard'),
                        ['action' => 'index'],
                        ['class' => 'btn btn-secondary', 'escape' => false]
                    ) ?>
                    <?= $this->Html->link(
                        '<i class="fas fa-plus"></i> ' . __d('acl_manager', 'New Role'),
                        ['action' => 'addRole'],
                        ['class' => 'btn btn-success', 'escape' => false]
                    ) ?>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <?php if (!empty($roles)): ?>
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th><?= __d('acl_manager', 'ID') ?></th>
                                    <th><?= __d('acl_manager', 'Name') ?></th>
                                    <th><?= __d('acl_manager', 'Description') ?></th>
                                    <th><?= __d('acl_manager', 'Priority') ?></th>
                                    <th><?= __d('acl_manager', 'Status') ?></th>
                                    <th><?= __d('acl_manager', 'Created') ?></th>
                                    <th class="actions"><?= __d('acl_manager', 'Actions') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($roles as $role): ?>
                                    <tr>
                                        <td><?= $this->Number->format($role->id) ?></td>
                                        <td><strong><?= h($role->name) ?></strong></td>
                                        <td><?= h($role->description) ?></td>
                                        <td>
                                            <span class="badge badge-secondary"><?= h($role->priority) ?></span>
                                        </td>
                                        <td>
                                            <?php if ($role->active): ?>
                                                <span class="badge badge-success"><?= __d('acl_manager', 'Active') ?></span>
                                            <?php else: ?>
                                                <span class="badge badge-secondary"><?= __d('acl_manager', 'Inactive') ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= h($role->created->format('Y-m-d H:i')) ?></td>
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
                            <i class="fas fa-info-circle fa-3x mb-3"></i>
                            <h4><?= __d('acl_manager', 'No roles found') ?></h4>
                            <p><?= __d('acl_manager', 'Get started by creating your first role.') ?></p>
                            <?= $this->Html->link(
                                '<i class="fas fa-plus"></i> ' . __d('acl_manager', 'Create First Role'),
                                ['action' => 'addRole'],
                                ['class' => 'btn btn-success btn-lg', 'escape' => false]
                            ) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.roles-index .actions {
    white-space: nowrap;
    width: 1%;
}

.roles-index .actions .btn {
    margin-right: 5px;
}

.roles-index .card {
    box-shadow: 0 2px 4px rgba(0,0,0,.1);
}
</style>

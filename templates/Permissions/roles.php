<?php
/**
 * @var \App\View\AppView $this
 * @var \AclManager\Model\Entity\Role[] $roles
 */
$this->assign('title', __('Manage Roles'));
?>

<div class="roles-index">
    <div class="row">
        <div class="col-md-12">
            <div class="page-header d-flex justify-content-between align-items-center">
                <div>
                    <h1><?= __('Manage Roles') ?></h1>
                    <p class="lead"><?= __('Create and manage user roles') ?></p>
                </div>
                <div>
                    <?= $this->Html->link(
                        '<i class="fas fa-arrow-left"></i> ' . __('Back to Dashboard'),
                        ['action' => 'index'],
                        ['class' => 'btn btn-secondary', 'escape' => false]
                    ) ?>
                    <?= $this->Html->link(
                        '<i class="fas fa-plus"></i> ' . __('New Role'),
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
                                    <th><?= __('ID') ?></th>
                                    <th><?= __('Name') ?></th>
                                    <th><?= __('Description') ?></th>
                                    <th><?= __('Priority') ?></th>
                                    <th><?= __('Status') ?></th>
                                    <th><?= __('Created') ?></th>
                                    <th class="actions"><?= __('Actions') ?></th>
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
                                                <span class="badge badge-success"><?= __('Active') ?></span>
                                            <?php else: ?>
                                                <span class="badge badge-secondary"><?= __('Inactive') ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= h($role->created->format('Y-m-d H:i')) ?></td>
                                        <td class="actions">
                                            <?= $this->Html->link(
                                                '<i class="fas fa-key"></i>',
                                                ['action' => 'manage', $role->id],
                                                ['class' => 'btn btn-sm btn-primary', 'escape' => false, 'title' => __('Manage Permissions')]
                                            ) ?>
                                            <?= $this->Html->link(
                                                '<i class="fas fa-edit"></i>',
                                                ['action' => 'editRole', $role->id],
                                                ['class' => 'btn btn-sm btn-info', 'escape' => false, 'title' => __('Edit')]
                                            ) ?>
                                            <?= $this->Form->postLink(
                                                '<i class="fas fa-trash"></i>',
                                                ['action' => 'deleteRole', $role->id],
                                                [
                                                    'confirm' => __('Are you sure you want to delete {0}?', $role->name),
                                                    'class' => 'btn btn-sm btn-danger',
                                                    'escape' => false,
                                                    'title' => __('Delete')
                                                ]
                                            ) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="alert alert-info text-center">
                            <i class="fas fa-info-circle fa-3x mb-3"></i>
                            <h4><?= __('No roles found') ?></h4>
                            <p><?= __('Get started by creating your first role.') ?></p>
                            <?= $this->Html->link(
                                '<i class="fas fa-plus"></i> ' . __('Create First Role'),
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

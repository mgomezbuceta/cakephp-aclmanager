<?php
/**
 * @var \App\View\AppView $this
 * @var \AclManager\Model\Entity\Role $role
 */
$isEdit = !$role->isNew();
$this->assign('title', $isEdit ? __d('acl_manager', 'Edit Role') : __d('acl_manager', 'Add Role'));
?>

<div class="role-form">
    <div class="row">
        <div class="col-md-12">
            <div class="page-header">
                <h1><?= $isEdit ? __d('acl_manager', 'Edit Role') : __d('acl_manager', 'Add Role') ?></h1>
                <p class="lead">
                    <?= $isEdit ? __d('acl_manager', 'Update role information') : __d('acl_manager', 'Create a new role for your application') ?>
                </p>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-body">
                    <?= $this->Form->create($role) ?>
                    <fieldset>
                        <legend><?= __d('acl_manager', 'Role Information') ?></legend>

                        <?= $this->Form->control('name', [
                            'label' => __d('acl_manager', 'Name'),
                            'class' => 'form-control',
                            'placeholder' => __d('acl_manager', 'e.g., Administrator, Editor, Viewer'),
                            'required' => true
                        ]) ?>

                        <?= $this->Form->control('description', [
                            'label' => __d('acl_manager', 'Description'),
                            'class' => 'form-control',
                            'type' => 'textarea',
                            'rows' => 3,
                            'placeholder' => __d('acl_manager', 'Brief description of this role and its purpose')
                        ]) ?>

                        <?= $this->Form->control('priority', [
                            'label' => __d('acl_manager', 'Priority'),
                            'class' => 'form-control',
                            'type' => 'number',
                            'min' => 0,
                            'max' => 100,
                            'default' => 0,
                            'help' => __d('acl_manager', 'Higher priority roles have precedence. Range: 0-100')
                        ]) ?>

                        <?= $this->Form->control('active', [
                            'label' => __d('acl_manager', 'Status'),
                            'class' => 'form-check-input',
                            'type' => 'checkbox',
                            'checked' => $isEdit ? $role->active : true
                        ]) ?>
                    </fieldset>

                    <div class="form-actions mt-4">
                        <?= $this->Form->button(__d('acl_manager', 'Save Role'), [
                            'class' => 'btn btn-primary btn-lg'
                        ]) ?>
                        <?= $this->Html->link(
                            __d('acl_manager', 'Cancel'),
                            ['action' => 'roles'],
                            ['class' => 'btn btn-secondary btn-lg']
                        ) ?>
                    </div>
                    <?= $this->Form->end() ?>
                </div>
            </div>

            <?php if ($isEdit): ?>
                <div class="card mt-4">
                    <div class="card-header" style="background-color: var(--primary-color); color: white;">
                        <h5 class="mb-0"><?= __d('acl_manager', 'Quick Actions') ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group">
                            <?= $this->Html->link(
                                '<i class="fas fa-key"></i> ' . __d('acl_manager', 'Manage Permissions'),
                                ['action' => 'manage', $role->id],
                                ['class' => 'list-group-item list-group-item-action', 'escape' => false]
                            ) ?>
                            <?= $this->Form->postLink(
                                '<i class="fas fa-times-circle"></i> ' . __d('acl_manager', 'Clear All Permissions'),
                                ['action' => 'clearPermissions', $role->id],
                                [
                                    'confirm' => __d('acl_manager', 'Are you sure you want to clear all permissions for this role?'),
                                    'class' => 'list-group-item list-group-item-action text-danger',
                                    'escape' => false
                                ]
                            ) ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.role-form .card {
    box-shadow: 0 2px 4px rgba(0,0,0,.1);
    margin-bottom: 20px;
}

.role-form .form-actions {
    border-top: 1px solid #dee2e6;
    padding-top: 20px;
}

.role-form .form-control,
.role-form .form-check-input {
    border-radius: 4px;
}

.role-form .list-group-item i {
    margin-right: 10px;
    width: 20px;
    text-align: center;
}
</style>

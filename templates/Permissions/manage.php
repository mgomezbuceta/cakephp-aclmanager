<?php
/**
 * @var \App\View\AppView $this
 * @var \AclManager\Model\Entity\Role $role
 * @var array $resources
 * @var array $permissions
 */
$this->assign('title', __('Manage Permissions for {0}', $role->name));
?>

<div class="permissions-manage">
    <div class="row">
        <div class="col-md-12">
            <div class="page-header d-flex justify-content-between align-items-center">
                <div>
                    <h1><?= __('Manage Permissions') ?></h1>
                    <p class="lead"><?= __('Role: {0}', h($role->name)) ?></p>
                </div>
                <div>
                    <?= $this->Html->link(
                        '<i class="fas fa-arrow-left"></i> ' . __('Back'),
                        ['action' => 'index'],
                        ['class' => 'btn btn-secondary', 'escape' => false]
                    ) ?>
                </div>
            </div>
        </div>
    </div>

    <?= $this->Form->create(null, ['type' => 'post']) ?>

    <div class="row mb-3">
        <div class="col-md-12">
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="fas fa-save"></i> <?= __('Save Permissions') ?>
            </button>
            <?= $this->Form->postLink(
                '<i class="fas fa-times-circle"></i> ' . __('Clear All'),
                ['action' => 'clearPermissions', $role->id],
                [
                    'confirm' => __('Are you sure you want to clear all permissions for this role?'),
                    'class' => 'btn btn-danger btn-lg',
                    'escape' => false
                ]
            ) ?>
        </div>
    </div>

    <?php foreach ($resources as $plugin => $controllers): ?>
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="mb-0">
                    <i class="fas fa-puzzle-piece"></i> <?= h($plugin) ?>
                </h3>
            </div>
            <div class="card-body">
                <?php foreach ($controllers as $controller => $actions): ?>
                    <div class="controller-section mb-4">
                        <h4 class="controller-title">
                            <i class="fas fa-folder"></i> <?= h($controller) ?>
                        </h4>
                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead class="thead-light">
                                    <tr>
                                        <th style="width: 40%"><?= __('Action') ?></th>
                                        <th style="width: 60%"><?= __('Permission') ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($actions as $action): ?>
                                        <?php
                                        $actionName = is_object($action) ? $action->action : $action;
                                        $isAllowed = isset($permissions[$plugin][$controller][$actionName]) &&
                                                    $permissions[$plugin][$controller][$actionName];
                                        $fieldIndex = $plugin . '_' . $controller . '_' . $actionName;
                                        ?>
                                        <tr>
                                            <td>
                                                <code><?= h($actionName) ?></code>
                                            </td>
                                            <td>
                                                <div class="form-check form-check-inline">
                                                    <?= $this->Form->hidden("permissions.{$fieldIndex}.controller", ['value' => $controller]) ?>
                                                    <?= $this->Form->hidden("permissions.{$fieldIndex}.action", ['value' => $actionName]) ?>
                                                    <?= $this->Form->hidden("permissions.{$fieldIndex}.plugin", ['value' => $plugin === 'App' ? null : $plugin]) ?>
                                                    <?= $this->Form->checkbox("permissions.{$fieldIndex}.allowed", [
                                                        'checked' => $isAllowed,
                                                        'class' => 'form-check-input',
                                                        'id' => "permission-{$fieldIndex}"
                                                    ]) ?>
                                                    <label class="form-check-label" for="permission-<?= $fieldIndex ?>">
                                                        <?= __('Allow') ?>
                                                    </label>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>

    <?php if (empty($resources)): ?>
        <div class="alert alert-warning">
            <i class="fas fa-exclamation-triangle"></i>
            <?= __('No resources found. Please run "Sync Resources" first.') ?>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-12">
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="fas fa-save"></i> <?= __('Save Permissions') ?>
            </button>
            <?= $this->Html->link(
                __('Cancel'),
                ['action' => 'index'],
                ['class' => 'btn btn-secondary btn-lg']
            ) ?>
        </div>
    </div>

    <?= $this->Form->end() ?>
</div>

<style>
.permissions-manage .controller-section {
    border-left: 3px solid #007bff;
    padding-left: 15px;
}

.permissions-manage .controller-title {
    color: #495057;
    font-size: 1.1rem;
    margin-bottom: 15px;
}

.permissions-manage code {
    background-color: #f8f9fa;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 0.9em;
}

.permissions-manage .card-header h3 {
    color: #fff;
}

.permissions-manage .card-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add select all functionality per controller
    document.querySelectorAll('.controller-section').forEach(function(section) {
        const checkboxes = section.querySelectorAll('input[type="checkbox"]');
        const header = section.querySelector('.controller-title');

        const selectAllBtn = document.createElement('button');
        selectAllBtn.type = 'button';
        selectAllBtn.className = 'btn btn-sm btn-outline-primary ml-2';
        selectAllBtn.innerHTML = '<i class="fas fa-check-square"></i> Select All';
        selectAllBtn.onclick = function() {
            checkboxes.forEach(cb => cb.checked = true);
        };

        const deselectAllBtn = document.createElement('button');
        deselectAllBtn.type = 'button';
        deselectAllBtn.className = 'btn btn-sm btn-outline-secondary ml-2';
        deselectAllBtn.innerHTML = '<i class="fas fa-square"></i> Deselect All';
        deselectAllBtn.onclick = function() {
            checkboxes.forEach(cb => cb.checked = false);
        };

        header.appendChild(selectAllBtn);
        header.appendChild(deselectAllBtn);
    });
});
</script>

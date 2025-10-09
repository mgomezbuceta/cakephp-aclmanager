<?php
declare(strict_types=1);

namespace AclManager\Controller;

use AclManager\Service\PermissionService;
use AclManager\Service\ResourceScannerService;

/**
 * Permissions Controller
 *
 * Manages role-based permissions for the Authorization system
 */
class PermissionsController extends AppController
{
    private PermissionService $permissionService;
    private ResourceScannerService $scannerService;

    /**
     * Initialize controller
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->permissionService = new PermissionService();
        $this->scannerService = new ResourceScannerService();
    }

    /**
     * Index method - Dashboard
     *
     * @return \Cake\Http\Response|null
     */
    public function index(): \Cake\Http\Response|null
    {
        $roles = $this->permissionService->getRolesWithPermissionCount();
        $resourceCount = $this->fetchTable('AclManager.Resources')->find()->where(['active' => true])->count();

        $this->set(compact('roles', 'resourceCount'));
    }

    /**
     * Manage permissions for a specific role
     *
     * @param int|null $roleId Role ID
     * @return \Cake\Http\Response|null
     */
    public function manage(?int $roleId = null): \Cake\Http\Response|null
    {
        if (!$roleId) {
            $this->Flash->error(__('Please select a role.'));
            return $this->redirect(['action' => 'index']);
        }

        $role = $this->fetchTable('AclManager.Roles')->get($roleId);
        $resources = $this->scannerService->getGroupedResources();
        $permissions = $this->permissionService->getPermissionMatrix($roleId);

        if ($this->request->is(['post', 'put'])) {
            return $this->savePermissions($roleId);
        }

        $this->set(compact('role', 'resources', 'permissions'));
    }

    /**
     * Save permissions for a role
     *
     * @param int $roleId Role ID
     * @return \Cake\Http\Response
     */
    protected function savePermissions(int $roleId): \Cake\Http\Response
    {
        $data = $this->request->getData();

        try {
            // Clear existing permissions
            $this->permissionService->revokeAll($roleId);

            // Save new permissions
            $saved = 0;
            if (isset($data['permissions']) && is_array($data['permissions'])) {
                foreach ($data['permissions'] as $permission) {
                    if (!isset($permission['controller'], $permission['action'])) {
                        continue;
                    }

                    $allowed = isset($permission['allowed']) && $permission['allowed'] == '1';

                    if ($allowed) {
                        $this->permissionService->grant(
                            $roleId,
                            $permission['controller'],
                            $permission['action'],
                            $permission['plugin'] ?? null
                        );
                        $saved++;
                    }
                }
            }

            $this->Flash->success(__('Permissions saved successfully. {0} permissions granted.', $saved));
        } catch (\Exception $e) {
            $this->Flash->error(__('Error saving permissions: {0}', $e->getMessage()));
        }

        return $this->redirect(['action' => 'manage', $roleId]);
    }

    /**
     * Scan and synchronize resources
     *
     * @return \Cake\Http\Response
     */
    public function syncResources(): \Cake\Http\Response
    {
        try {
            $stats = $this->scannerService->scanAndSync();

            $message = __(
                'Resources synchronized. Found: {0}, Created: {1}, Updated: {2}, Deactivated: {3}',
                $stats['found'],
                $stats['created'],
                $stats['updated'],
                $stats['deactivated']
            );

            $this->Flash->success($message);
        } catch (\Exception $e) {
            $this->Flash->error(__('Error synchronizing resources: {0}', $e->getMessage()));
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * View and manage roles
     *
     * @return \Cake\Http\Response|null
     */
    public function roles(): \Cake\Http\Response|null
    {
        $roles = $this->fetchTable('AclManager.Roles')->find()
            ->order(['priority' => 'DESC'])
            ->all();

        $this->set(compact('roles'));
    }

    /**
     * Add a new role
     *
     * @return \Cake\Http\Response|null
     */
    public function addRole(): \Cake\Http\Response|null
    {
        $rolesTable = $this->fetchTable('AclManager.Roles');
        $role = $rolesTable->newEmptyEntity();

        if ($this->request->is('post')) {
            $role = $rolesTable->patchEntity($role, $this->request->getData());

            if ($rolesTable->save($role)) {
                $this->Flash->success(__('Role created successfully.'));
                return $this->redirect(['action' => 'roles']);
            }

            $this->Flash->error(__('Unable to create role. Please try again.'));
        }

        $this->set(compact('role'));
    }

    /**
     * Edit a role
     *
     * @param int|null $id Role ID
     * @return \Cake\Http\Response|null
     */
    public function editRole(?int $id = null): \Cake\Http\Response|null
    {
        $rolesTable = $this->fetchTable('AclManager.Roles');
        $role = $rolesTable->get($id);

        if ($this->request->is(['patch', 'post', 'put'])) {
            $role = $rolesTable->patchEntity($role, $this->request->getData());

            if ($rolesTable->save($role)) {
                $this->Flash->success(__('Role updated successfully.'));
                return $this->redirect(['action' => 'roles']);
            }

            $this->Flash->error(__('Unable to update role. Please try again.'));
        }

        $this->set(compact('role'));
        $this->render('add_role');
    }

    /**
     * Delete a role
     *
     * @param int|null $id Role ID
     * @return \Cake\Http\Response
     */
    public function deleteRole(?int $id = null): \Cake\Http\Response
    {
        $this->request->allowMethod(['post', 'delete']);

        $rolesTable = $this->fetchTable('AclManager.Roles');
        $role = $rolesTable->get($id);

        if ($rolesTable->delete($role)) {
            $this->Flash->success(__('Role deleted successfully.'));
        } else {
            $this->Flash->error(__('Unable to delete role. Please try again.'));
        }

        return $this->redirect(['action' => 'roles']);
    }

    /**
     * Copy permissions from one role to another
     *
     * @param int|null $sourceId Source role ID
     * @param int|null $targetId Target role ID
     * @return \Cake\Http\Response
     */
    public function copyPermissions(?int $sourceId = null, ?int $targetId = null): \Cake\Http\Response
    {
        $this->request->allowMethod(['post']);

        if (!$sourceId || !$targetId) {
            $this->Flash->error(__('Invalid roles specified.'));
            return $this->redirect(['action' => 'index']);
        }

        try {
            $rolesTable = $this->fetchTable('AclManager.Roles');
            $sourceRole = $rolesTable->get($sourceId);
            $targetRole = $rolesTable->get($targetId);

            if ($this->permissionService->copyPermissions($sourceId, $targetId)) {
                $this->Flash->success(__(
                    'Permissions copied successfully from "{0}" to "{1}".',
                    $sourceRole->name,
                    $targetRole->name
                ));
            } else {
                $this->Flash->error(__('Error copying permissions.'));
            }
        } catch (\Exception $e) {
            $this->Flash->error(__('Error: {0}', $e->getMessage()));
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Clear all permissions for a role
     *
     * @param int|null $roleId Role ID
     * @return \Cake\Http\Response
     */
    public function clearPermissions(?int $roleId = null): \Cake\Http\Response
    {
        $this->request->allowMethod(['post']);

        if (!$roleId) {
            $this->Flash->error(__('Invalid role specified.'));
            return $this->redirect(['action' => 'index']);
        }

        try {
            $role = $this->fetchTable('AclManager.Roles')->get($roleId);
            $count = $this->permissionService->revokeAll($roleId);

            $this->Flash->success(__(
                'All permissions cleared for role "{0}". {1} permissions removed.',
                $role->name,
                $count
            ));
        } catch (\Exception $e) {
            $this->Flash->error(__('Error: {0}', $e->getMessage()));
        }

        return $this->redirect(['action' => 'manage', $roleId]);
    }
}

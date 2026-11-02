<?php
declare(strict_types=1);

namespace AclManager\Controller;

use AclManager\Service\PermissionService;
use AclManager\Service\ResourceScannerService;
use AclManager\Service\StalePermissionsException;
use Cake\Core\Configure;
use Cake\Http\Response;
use Cake\Log\Log;
use Exception;
use Throwable;

/**
 * Permissions Controller
 *
 * Manages role-based permissions for the Authorization system
 */
class PermissionsController extends AppController
{
    /** Role fields an administrator may submit through the role forms. */
    private const ROLE_FIELDS = ['name', 'description', 'priority', 'active'];

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
    public function index(): ?Response
    {
        $roles = $this->permissionService->getRolesWithPermissionCount();
        $resourceCount = $this->fetchTable('AclManager.Resources')->find()->where(['active' => true])->count();

        // Load configuration from config/app.php
        $adminRoleIds = Configure::read('AclManager.adminAccess.adminRoleIds', [1]);

        $this->set(compact('roles', 'resourceCount', 'adminRoleIds'));

        return null;
    }

    /**
     * Manage permissions for a specific role
     *
     * @param int|null $roleId Role ID
     * @return \Cake\Http\Response|null
     */
    public function manage(?int $roleId = null): ?Response
    {
        if (!$roleId) {
            $this->Flash->error(__d('acl_manager', 'Please select a role.'));

            return $this->redirect(['action' => 'index']);
        }

        $role = $this->fetchTable('AclManager.Roles')->get($roleId);
        $resources = $this->scannerService->getGroupedResources();
        $permissions = $this->permissionService->getPermissionMatrix($roleId);

        if ($this->request->is(['post', 'put'])) {
            return $this->savePermissions($roleId);
        }

        $permissionsVersion = $this->permissionService->getPermissionsVersion($roleId);

        $this->set(compact('role', 'resources', 'permissions', 'permissionsVersion'));

        return null;
    }

    /**
     * Save permissions for a role
     *
     * @param int $roleId Role ID
     * @return \Cake\Http\Response|null
     */
    protected function savePermissions(int $roleId): ?Response
    {
        // The sentinel is the last form field; if PHP truncated the POST (max_input_vars) it is missing.
        if (!$this->request->getData('permissions_submitted')) {
            $this->Flash->error(__d(
                'acl_manager',
                'The form data was incomplete (check the PHP max_input_vars setting). No permissions were changed.',
            ));

            return $this->redirect(['action' => 'manage', $roleId]);
        }

        $version = $this->request->getData('permissions_version');
        if (!is_string($version) || $version === '') {
            $this->Flash->error(__d(
                'acl_manager',
                'The form was missing its version token. No permissions were changed; reload the page and try again.',
            ));

            return $this->redirect(['action' => 'manage', $roleId]);
        }

        try {
            $saved = $this->permissionService->replaceRolePermissions($roleId, $this->submittedResourceIds(), $version);

            $this->Flash->success(
                __d('acl_manager', 'Permissions saved successfully. {0} permissions granted.', $saved),
            );
        } catch (StalePermissionsException) {
            $this->Flash->error(__d(
                'acl_manager',
                'Another administrator changed the permissions of this role while you were editing. '
                . 'The page has been reloaded with the current data; apply your changes again.',
            ));
        } catch (Exception $e) {
            $this->logFailure('savePermissions', $e);
            $this->Flash->error(__d(
                'acl_manager',
                'The permissions could not be saved. Check the application log for details.',
            ));
        }

        return $this->redirect(['action' => 'manage', $roleId]);
    }

    /**
     * Resource ids posted by the form: the single CSV field (JS) wins over the checkbox array (no-JS fallback).
     *
     * Items are only trimmed and split here; replaceRolePermissions() does the strict validation.
     *
     * @return array<int, mixed>
     */
    private function submittedResourceIds(): array
    {
        $csv = $this->request->getData('resource_ids_csv');
        if (is_string($csv)) {
            return array_values(array_filter(array_map('trim', explode(',', $csv)), fn(string $v): bool => $v !== ''));
        }

        return (array)$this->request->getData('resource_ids', []);
    }

    /**
     * Scan and synchronize resources
     *
     * @return \Cake\Http\Response|null
     */
    public function syncResources(): ?Response
    {
        $this->request->allowMethod(['post']);

        try {
            $stats = $this->scannerService->scanAndSync();

            $message = __d(
                'acl_manager',
                'Resources synchronized. Found: {0}, Created: {1}, Updated: {2}, Deactivated: {3}',
                $stats['found'],
                $stats['created'],
                $stats['updated'],
                $stats['deactivated'],
            );

            $this->Flash->success($message);
        } catch (Exception $e) {
            $this->logFailure('syncResources', $e);
            $this->Flash->error(__d(
                'acl_manager',
                'The resources could not be synchronized. Check the application log for details.',
            ));
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * View and manage roles
     *
     * @return \Cake\Http\Response|null
     */
    public function roles(): ?Response
    {
        $roles = $this->fetchTable('AclManager.Roles')->find()
            ->orderBy(['priority' => 'DESC'])
            ->all();

        // Load configuration from config/app.php
        $adminRoleIds = Configure::read('AclManager.adminAccess.adminRoleIds', [1]);

        $this->set(compact('roles', 'adminRoleIds'));

        return null;
    }

    /**
     * Add a new role
     *
     * @return \Cake\Http\Response|null
     */
    public function addRole(): ?Response
    {
        $rolesTable = $this->fetchTable('AclManager.Roles');
        $role = $rolesTable->newEmptyEntity();

        if ($this->request->is('post')) {
            $role = $rolesTable->patchEntity($role, $this->request->getData(), ['fields' => self::ROLE_FIELDS]);

            if ($rolesTable->save($role)) {
                $this->Flash->success(__d('acl_manager', 'Role created successfully.'));

                return $this->redirect(['action' => 'roles']);
            }

            $this->Flash->error(__d('acl_manager', 'Unable to create role. Please try again.'));
        }

        $this->set(compact('role'));

        return null;
    }

    /**
     * Edit a role
     *
     * @param int|null $id Role ID
     * @return \Cake\Http\Response|null
     */
    public function editRole(?int $id = null): ?Response
    {
        $rolesTable = $this->fetchTable('AclManager.Roles');
        $role = $rolesTable->get($id);

        if ($this->request->is(['patch', 'post', 'put'])) {
            if ($this->isProtectedRole((int)$role->id) && !$this->request->getData('active', true)) {
                $this->Flash->error(__d('acl_manager', 'Administrator roles cannot be deactivated.'));

                return $this->redirect(['action' => 'roles']);
            }

            $role = $rolesTable->patchEntity($role, $this->request->getData(), ['fields' => self::ROLE_FIELDS]);

            if ($rolesTable->save($role)) {
                $this->Flash->success(__d('acl_manager', 'Role updated successfully.'));

                return $this->redirect(['action' => 'roles']);
            }

            $this->Flash->error(__d('acl_manager', 'Unable to update role. Please try again.'));
        }

        $this->set(compact('role'));

        return $this->render('add_role');
    }

    /**
     * Delete a role
     *
     * @param int|null $id Role ID
     * @return \Cake\Http\Response|null
     */
    public function deleteRole(?int $id = null): ?Response
    {
        $this->request->allowMethod(['post', 'delete']);

        $rolesTable = $this->fetchTable('AclManager.Roles');
        $role = $rolesTable->get($id);

        if ($this->isProtectedRole((int)$role->id)) {
            $this->Flash->error(__d('acl_manager', 'Administrator roles cannot be deleted.'));

            return $this->redirect(['action' => 'roles']);
        }

        if ($rolesTable->delete($role)) {
            $this->Flash->success(__d('acl_manager', 'Role deleted successfully.'));
        } else {
            $this->Flash->error(__d('acl_manager', 'Unable to delete role. Please try again.'));
        }

        return $this->redirect(['action' => 'roles']);
    }

    /**
     * Copy permissions from one role to another
     *
     * @param int|null $sourceId Source role ID
     * @param int|null $targetId Target role ID
     * @return \Cake\Http\Response|null
     */
    public function copyPermissions(?int $sourceId = null, ?int $targetId = null): ?Response
    {
        $this->request->allowMethod(['post']);

        if (!$sourceId || !$targetId) {
            $this->Flash->error(__d('acl_manager', 'Invalid roles specified.'));

            return $this->redirect(['action' => 'index']);
        }

        if ($sourceId === $targetId) {
            $this->Flash->error(__d('acl_manager', 'Source and target roles must be different.'));

            return $this->redirect(['action' => 'index']);
        }

        try {
            $rolesTable = $this->fetchTable('AclManager.Roles');
            $sourceRole = $rolesTable->get($sourceId);
            $targetRole = $rolesTable->get($targetId);

            if ($this->permissionService->copyPermissions($sourceId, $targetId)) {
                $this->Flash->success(__d(
                    'acl_manager',
                    'Permissions copied successfully from "{0}" to "{1}".',
                    $sourceRole->name,
                    $targetRole->name,
                ));
            } else {
                $this->Flash->error(__d('acl_manager', 'Error copying permissions.'));
            }
        } catch (Exception $e) {
            $this->logFailure('copyPermissions', $e);
            $this->Flash->error(__d(
                'acl_manager',
                'The permissions could not be copied. Check the application log for details.',
            ));
        }

        return $this->redirect(['action' => 'index']);
    }

    /**
     * Clear all permissions for a role
     *
     * @param int|null $roleId Role ID
     * @return \Cake\Http\Response|null
     */
    public function clearPermissions(?int $roleId = null): ?Response
    {
        $this->request->allowMethod(['post']);

        if (!$roleId) {
            $this->Flash->error(__d('acl_manager', 'Invalid role specified.'));

            return $this->redirect(['action' => 'index']);
        }

        try {
            $role = $this->fetchTable('AclManager.Roles')->get($roleId);
            $count = $this->permissionService->revokeAll($roleId);

            $this->Flash->success(__d(
                'acl_manager',
                'All permissions cleared for role "{0}". {1} permissions removed.',
                $role->name,
                $count,
            ));
        } catch (Exception $e) {
            $this->logFailure('clearPermissions', $e);
            $this->Flash->error(__d(
                'acl_manager',
                'The permissions could not be cleared. Check the application log for details.',
            ));
        }

        return $this->redirect(['action' => 'manage', $roleId]);
    }

    /**
     * Log an unexpected failure with enough context to diagnose it; users only see a generic flash.
     *
     * @param string $action Controller action that failed
     * @param \Throwable $e The exception
     * @return void
     */
    private function logFailure(string $action, Throwable $e): void
    {
        Log::error(sprintf(
            'AclManager %s failed: %s: %s in %s:%d%s%s',
            $action,
            $e::class,
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            "\n",
            $e->getTraceAsString(),
        ));
    }

    /**
     * Whether the role is one of the configured administrator roles.
     *
     * @param int $roleId Role ID
     * @return bool
     */
    private function isProtectedRole(int $roleId): bool
    {
        return in_array($roleId, (array)Configure::read('AclManager.adminAccess.adminRoleIds', [1]));
    }
}

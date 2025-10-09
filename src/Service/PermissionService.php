<?php
declare(strict_types=1);

namespace AclManager\Service;

use AclManager\Model\Table\PermissionsTable;
use AclManager\Model\Table\RolesTable;
use Cake\ORM\TableRegistry;

/**
 * Permission Service
 *
 * Handles permission evaluation and management for the Authorization system
 */
class PermissionService
{
    private PermissionsTable $Permissions;
    private RolesTable $Roles;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->Permissions = TableRegistry::getTableLocator()->get('AclManager.Permissions');
        $this->Roles = TableRegistry::getTableLocator()->get('AclManager.Roles');
    }

    /**
     * Check if a role has permission for a specific resource
     *
     * @param int $roleId Role ID
     * @param string $controller Controller name
     * @param string $action Action name
     * @param string|null $plugin Plugin name
     * @return bool
     */
    public function isAuthorized(int $roleId, string $controller, string $action, ?string $plugin = null): bool
    {
        return $this->Permissions->isAuthorized($roleId, $controller, $action, $plugin);
    }

    /**
     * Get permission matrix for a role
     *
     * @param int $roleId Role ID
     * @return array Permission matrix grouped by plugin/controller
     */
    public function getPermissionMatrix(int $roleId): array
    {
        $permissions = $this->Permissions->findByRole($roleId)->all();

        $matrix = [];
        foreach ($permissions as $permission) {
            $pluginKey = $permission->plugin ?? 'App';
            $matrix[$pluginKey][$permission->controller][$permission->action] = $permission->allowed;
        }

        return $matrix;
    }

    /**
     * Grant permission to a role
     *
     * @param int $roleId Role ID
     * @param string $controller Controller name
     * @param string $action Action name
     * @param string|null $plugin Plugin name
     * @return bool Success
     */
    public function grant(int $roleId, string $controller, string $action, ?string $plugin = null): bool
    {
        return $this->setPermission($roleId, $controller, $action, $plugin, true);
    }

    /**
     * Deny permission to a role
     *
     * @param int $roleId Role ID
     * @param string $controller Controller name
     * @param string $action Action name
     * @param string|null $plugin Plugin name
     * @return bool Success
     */
    public function deny(int $roleId, string $controller, string $action, ?string $plugin = null): bool
    {
        return $this->setPermission($roleId, $controller, $action, $plugin, false);
    }

    /**
     * Set permission for a role
     *
     * @param int $roleId Role ID
     * @param string $controller Controller name
     * @param string $action Action name
     * @param string|null $plugin Plugin name
     * @param bool $allowed Allowed or denied
     * @return bool Success
     */
    protected function setPermission(int $roleId, string $controller, string $action, ?string $plugin, bool $allowed): bool
    {
        $existing = $this->Permissions->find()
            ->where([
                'role_id' => $roleId,
                'controller' => $controller,
                'action' => $action,
                'plugin IS' => $plugin,
            ])
            ->first();

        if ($existing) {
            $existing->allowed = $allowed;
            $permission = $existing;
        } else {
            $permission = $this->Permissions->newEntity([
                'role_id' => $roleId,
                'controller' => $controller,
                'action' => $action,
                'plugin' => $plugin,
                'allowed' => $allowed,
            ]);
        }

        return (bool)$this->Permissions->save($permission);
    }

    /**
     * Remove all permissions for a role
     *
     * @param int $roleId Role ID
     * @return int Number of permissions deleted
     */
    public function revokeAll(int $roleId): int
    {
        return $this->Permissions->deleteAll(['role_id' => $roleId]);
    }

    /**
     * Copy permissions from one role to another
     *
     * @param int $sourceRoleId Source role ID
     * @param int $targetRoleId Target role ID
     * @return bool Success
     */
    public function copyPermissions(int $sourceRoleId, int $targetRoleId): bool
    {
        $sourcePermissions = $this->Permissions->findByRole($sourceRoleId)->all();

        foreach ($sourcePermissions as $sourcePermission) {
            $newPermission = $this->Permissions->newEntity([
                'role_id' => $targetRoleId,
                'controller' => $sourcePermission->controller,
                'action' => $sourcePermission->action,
                'plugin' => $sourcePermission->plugin,
                'allowed' => $sourcePermission->allowed,
            ]);

            if (!$this->Permissions->save($newPermission)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get all roles with their permission counts
     *
     * @return array
     */
    public function getRolesWithPermissionCount(): array
    {
        $roles = $this->Roles->find()
            ->contain(['Permissions'])
            ->where(['Roles.active' => true])
            ->order(['Roles.priority' => 'DESC'])
            ->all();

        $result = [];
        foreach ($roles as $role) {
            $result[] = [
                'role' => $role,
                'permission_count' => count($role->permissions),
                'allowed_count' => count(array_filter($role->permissions, fn($p) => $p->allowed)),
                'denied_count' => count(array_filter($role->permissions, fn($p) => !$p->allowed)),
            ];
        }

        return $result;
    }
}

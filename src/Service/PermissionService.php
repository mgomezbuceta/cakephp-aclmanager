<?php
declare(strict_types=1);

namespace AclManager\Service;

use AclManager\Model\Table\PermissionsTable;
use AclManager\Model\Table\RolesTable;
use Cake\ORM\Exception\PersistenceFailedException;
use Cake\ORM\TableRegistry;

/**
 * Permission Service
 *
 * Handles permission evaluation and management for the Authorization system
 */
class PermissionService
{
    private const RESOURCE_CHUNK_SIZE = 500;

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
        // PermissionsTable::isAuthorized() normalizes the plugin.
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
            $pluginKey = PermissionsTable::normalizePlugin($permission->plugin);
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
        $plugin = PermissionsTable::normalizePlugin($plugin);
        $existing = $this->Permissions->find()
            ->where([
                'role_id' => $roleId,
                'controller' => $controller,
                'action' => $action,
                'plugin' => $plugin,
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
     * Replace all permissions of a role with grants for the given resource ids
     *
     * Only positive integer ids (int or digit-only string) are accepted; anything else is
     * ignored before the transaction starts. Unknown and inactive resources are ignored and
     * resources sharing a controller/action/plugin collapse into one permission.
     * Runs in a single transaction so a failure never leaves the role half-updated.
     *
     * @param int $roleId Role ID
     * @param array $resourceIds Resource ids to grant
     * @return int Number of permissions granted
     */
    public function replaceRolePermissions(int $roleId, array $resourceIds): int
    {
        $ids = [];
        foreach ($resourceIds as $value) {
            if ((is_string($value) || is_int($value)) && ctype_digit((string)$value) && (int)$value > 0) {
                $ids[(int)$value] = (int)$value;
            }
        }
        $ids = array_values($ids);

        return $this->Permissions->getConnection()->transactional(function () use ($roleId, $ids): int {
            $this->Permissions->deleteAll(['role_id' => $roleId]);

            $resourcesTable = TableRegistry::getTableLocator()->get('AclManager.Resources');
            $entities = [];
            foreach (array_chunk($ids, self::RESOURCE_CHUNK_SIZE) as $chunk) {
                $resources = $resourcesTable->find()
                    ->where(['id IN' => $chunk, 'active' => true])
                    ->all();

                foreach ($resources as $resource) {
                    $plugin = PermissionsTable::normalizePlugin($resource->plugin);
                    $key = $plugin . '|' . $resource->controller . '|' . $resource->action;
                    $entities[$key] ??= $this->Permissions->newEntity([
                        'role_id' => $roleId,
                        'controller' => $resource->controller,
                        'action' => $resource->action,
                        'plugin' => $plugin,
                        'allowed' => true,
                    ]);
                }
            }

            if ($entities) {
                $this->Permissions->saveManyOrFail(array_values($entities));
            }

            return count($entities);
        });
    }

    /**
     * Copy permissions from one role to another, replacing the target's permissions
     *
     * @param int $sourceRoleId Source role ID
     * @param int $targetRoleId Target role ID
     * @return bool False when both roles are the same or the copy failed (nothing is changed)
     */
    public function copyPermissions(int $sourceRoleId, int $targetRoleId): bool
    {
        if ($sourceRoleId === $targetRoleId) {
            return false;
        }

        try {
            $this->Permissions->getConnection()->transactional(function () use ($sourceRoleId, $targetRoleId): void {
                $copies = [];
                foreach ($this->Permissions->findByRole($sourceRoleId)->all() as $source) {
                    $copies[] = $this->Permissions->newEntity([
                        'role_id' => $targetRoleId,
                        'controller' => $source->controller,
                        'action' => $source->action,
                        'plugin' => PermissionsTable::normalizePlugin($source->plugin),
                        'allowed' => $source->allowed,
                    ]);
                }

                $this->Permissions->deleteAll(['role_id' => $targetRoleId]);
                if ($copies) {
                    $this->Permissions->saveManyOrFail($copies);
                }
            });
        } catch (PersistenceFailedException) {
            return false;
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
            ->orderBy(['Roles.priority' => 'DESC'])
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

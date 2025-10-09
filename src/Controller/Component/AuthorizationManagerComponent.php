<?php
declare(strict_types=1);

namespace AclManager\Controller\Component;

use AclManager\Service\PermissionService;
use Cake\Controller\Component;
use Cake\Controller\ComponentRegistry;

/**
 * AuthorizationManager Component
 *
 * Provides authorization checking functionality using the modern permission system
 *
 * @property \Cake\Controller\Component\FlashComponent $Flash
 */
class AuthorizationManagerComponent extends Component
{
    /**
     * Default configuration.
     *
     * @var array<string, mixed>
     */
    protected array $_defaultConfig = [
        'userModel' => 'Users',
        'roleField' => 'role_id',
        'unauthorizedRedirect' => ['controller' => 'Users', 'action' => 'login'],
        'cachePermissions' => true,
        'cacheConfig' => 'default',
        'cacheDuration' => '+1 hour',
    ];

    protected array $components = ['Flash'];
    private PermissionService $permissionService;
    private ?array $permissionCache = null;

    /**
     * Initialize component
     *
     * @param array $config Configuration options
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->permissionService = new PermissionService();
    }

    /**
     * Check if the current user is authorized for the request
     *
     * @param array|null $user User data
     * @return bool
     */
    public function isAuthorized(?array $user = null): bool
    {
        if (!$user) {
            return false;
        }

        $roleId = $this->getRoleId($user);
        if (!$roleId) {
            return false;
        }

        $controller = $this->getController()->getName();
        $action = $this->getController()->getRequest()->getParam('action');
        $plugin = $this->getController()->getRequest()->getParam('plugin');

        return $this->checkPermission($roleId, $controller, $action, $plugin);
    }

    /**
     * Check permission for a specific role and resource
     *
     * @param int $roleId Role ID
     * @param string $controller Controller name
     * @param string $action Action name
     * @param string|null $plugin Plugin name
     * @return bool
     */
    public function checkPermission(int $roleId, string $controller, string $action, ?string $plugin = null): bool
    {
        $cacheKey = $this->getPermissionCacheKey($roleId, $controller, $action, $plugin);

        if ($this->getConfig('cachePermissions')) {
            if ($this->permissionCache === null) {
                $this->permissionCache = [];
            }

            if (isset($this->permissionCache[$cacheKey])) {
                return $this->permissionCache[$cacheKey];
            }
        }

        $allowed = $this->permissionService->isAuthorized($roleId, $controller, $action, $plugin);

        if ($this->getConfig('cachePermissions')) {
            $this->permissionCache[$cacheKey] = $allowed;
        }

        return $allowed;
    }

    /**
     * Get role ID from user data
     *
     * @param array $user User data
     * @return int|null
     */
    protected function getRoleId(array $user): ?int
    {
        $roleField = $this->getConfig('roleField');

        return isset($user[$roleField]) ? (int)$user[$roleField] : null;
    }

    /**
     * Get permission cache key
     *
     * @param int $roleId Role ID
     * @param string $controller Controller name
     * @param string $action Action name
     * @param string|null $plugin Plugin name
     * @return string
     */
    protected function getPermissionCacheKey(int $roleId, string $controller, string $action, ?string $plugin): string
    {
        return sprintf('permission_%d_%s_%s_%s', $roleId, $plugin ?? 'app', $controller, $action);
    }

    /**
     * Clear permission cache
     *
     * @return void
     */
    public function clearCache(): void
    {
        $this->permissionCache = null;
    }

    /**
     * Get authorization error redirect
     *
     * @return array|string
     */
    public function getUnauthorizedRedirect(): array|string
    {
        return $this->getConfig('unauthorizedRedirect');
    }

    /**
     * Handle unauthorized access
     *
     * @param string|null $message Custom message
     * @return \Cake\Http\Response
     */
    public function handleUnauthorized(?string $message = null): \Cake\Http\Response
    {
        $message = $message ?? __('You are not authorized to access that location.');

        $this->Flash->error($message);

        return $this->getController()->redirect($this->getUnauthorizedRedirect());
    }
}

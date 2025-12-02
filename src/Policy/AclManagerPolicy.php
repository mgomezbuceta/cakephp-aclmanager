<?php

namespace AclManager\Policy;

use AclManager\Service\PermissionService;
use Authorization\IdentityInterface;
use Authorization\Policy\RequestPolicyInterface;
use Cake\Http\ServerRequest;

class AclManagerPolicy implements RequestPolicyInterface
{
    private $permissionService;

    public function __construct()
    {
        $this->permissionService = new PermissionService();
    }

    /**
     * Define si un usuario puede acceder a cualquier acción.
     *      
     * @param \Authorization\IdentityInterface $identity El usuario autenticado.
     * @param array $resource El controlador o recurso (no se usa aquí, pero es obligatorio).
     * @return bool
     */
    public function canAccess(?IdentityInterface $identity, ServerRequest $request): bool
    {
        // Si el usuario no está autenticado, denegar acceso a todo lo demás.
        if ($identity === null) {
            return false;
        }

        $user = $identity->getOriginalData();

        $roleId = $user->role_id;
        if (!$roleId) {
            return false;
        }

        $controller = $request->getParam('controller');
        $action = $request->getParam('action');
        $plugin = $request->getParam('plugin') ?? 'App';

        return $this->permissionService->isAuthorized($roleId, $controller, $action, $plugin);
        
    }

}
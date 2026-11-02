<?php
declare(strict_types=1);

namespace AclManager\Policy;

use AclManager\Service\PermissionService;
use Authorization\IdentityInterface;
use Authorization\Policy\RequestPolicyInterface;
use Cake\Http\ServerRequest;

class AclManagerPolicy implements RequestPolicyInterface
{
    private PermissionService $permissionService;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->permissionService = new PermissionService();
    }

    /**
     * Whether a user may access the requested action.
     *
     * @param \Authorization\IdentityInterface|null $identity The authenticated user.
     * @param \Cake\Http\ServerRequest $request The current request.
     * @return bool
     */
    public function canAccess(?IdentityInterface $identity, ServerRequest $request): bool
    {
        // Unauthenticated users are denied everything else.
        if ($identity === null) {
            return false;
        }

        $user = $identity->getOriginalData();

        $roleId = (int)($user['role_id'] ?? 0);
        if ($roleId === 0) {
            return false;
        }

        return $this->permissionService->isAuthorized(
            $roleId,
            (string)$request->getParam('controller'),
            (string)$request->getParam('action'),
            (string)($request->getParam('plugin') ?? 'App'),
        );
    }
}

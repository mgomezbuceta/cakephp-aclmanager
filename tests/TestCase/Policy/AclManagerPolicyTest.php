<?php
declare(strict_types=1);

namespace AclManager\Test\TestCase\Policy;

use AclManager\Policy\AclManagerPolicy;
use Authorization\AuthorizationService;
use Authorization\IdentityDecorator;
use Authorization\Policy\MapResolver;
use Cake\Http\ServerRequest;
use Cake\ORM\Entity;
use Cake\TestSuite\TestCase;

/**
 * Verifies the wiring documented in the README: MapResolver + AclManagerPolicy for ServerRequest.
 */
class AclManagerPolicyTest extends TestCase
{
    protected array $fixtures = [
        'plugin.AclManager.Roles',
        'plugin.AclManager.Resources',
        'plugin.AclManager.Permissions',
    ];

    private function can(?int $roleId, string $controller, string $action): bool
    {
        $service = new AuthorizationService(new MapResolver([ServerRequest::class => AclManagerPolicy::class]));
        $identity = $roleId === null ? null : new IdentityDecorator($service, new Entity(['id' => 1, 'role_id' => $roleId]));
        $request = new ServerRequest(['params' => ['controller' => $controller, 'action' => $action]]);

        return $service->can($identity, 'access', $request);
    }

    public function testGrantedActionIsAllowedAndOthersDenied(): void
    {
        $this->assertTrue($this->can(2, 'Articles', 'edit'));
        $this->assertFalse($this->can(2, 'Articles', 'delete'));
    }

    public function testAnonymousIsDenied(): void
    {
        $this->assertFalse($this->can(null, 'Articles', 'edit'));
    }

    public function testIdentityBackedByAPlainArrayIsSupported(): void
    {
        $service = new AuthorizationService(new MapResolver([ServerRequest::class => AclManagerPolicy::class]));
        $identity = new IdentityDecorator($service, ['id' => 1, 'role_id' => 2]);
        $request = new ServerRequest(['params' => ['controller' => 'Articles', 'action' => 'edit']]);

        $this->assertTrue($service->can($identity, 'access', $request));
    }

    public function testIdentityWithoutARoleIsDenied(): void
    {
        $service = new AuthorizationService(new MapResolver([ServerRequest::class => AclManagerPolicy::class]));
        $identity = new IdentityDecorator($service, new Entity(['id' => 1]));
        $request = new ServerRequest(['params' => ['controller' => 'Articles', 'action' => 'edit']]);

        $this->assertFalse($service->can($identity, 'access', $request));
    }
}

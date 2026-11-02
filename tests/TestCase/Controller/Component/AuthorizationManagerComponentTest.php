<?php
declare(strict_types=1);

namespace AclManager\Test\TestCase\Controller\Component;

use AclManager\Controller\Component\AuthorizationManagerComponent;
use AclManager\Service\PermissionService;
use Cake\Controller\Controller;
use Cake\Http\ServerRequest;
use Cake\Http\Session;
use Cake\I18n\I18n;
use Cake\TestSuite\TestCase;

class AuthorizationManagerComponentTest extends TestCase
{
    protected array $fixtures = [
        'plugin.AclManager.Roles',
        'plugin.AclManager.Resources',
        'plugin.AclManager.Permissions',
    ];

    private string $originalLocale;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalLocale = I18n::getLocale();
        $this->loadPlugins(['AclManager' => ['path' => ROOT . DS]]);
    }

    protected function tearDown(): void
    {
        I18n::setLocale($this->originalLocale);
        $this->removePlugins(['AclManager']);
        parent::tearDown();
    }

    public function testHandleUnauthorizedMessageUsesPluginDomain(): void
    {
        I18n::setLocale('es_ES');
        $session = new Session();
        $request = new ServerRequest(['session' => $session]);
        $controller = new Controller($request);
        $component = new AuthorizationManagerComponent(
            $controller->components(),
            ['unauthorizedRedirect' => '/users/login'],
        );

        $component->handleUnauthorized();

        $flash = $session->read('Flash.flash.0.message');
        $this->assertSame('No tienes autorización para acceder a esa ubicación.', $flash);
    }

    /**
     * @param array<string, mixed> $config Component config
     * @param array<string, mixed> $params Request routing params
     */
    private function component(array $config = [], array $params = []): AuthorizationManagerComponent
    {
        $request = new ServerRequest(['session' => new Session(), 'params' => $params]);
        $controller = new Controller($request, 'Articles');

        return new AuthorizationManagerComponent($controller->components(), $config);
    }

    public function testIsAuthorizedIsFalseWithoutUserOrRole(): void
    {
        $component = $this->component([], ['action' => 'index']);

        $this->assertFalse($component->isAuthorized(null));
        $this->assertFalse($component->isAuthorized([]));
        $this->assertFalse($component->isAuthorized(['name' => 'no role field']));
    }

    public function testIsAuthorizedResolvesControllerActionAndDefaultsPluginToApp(): void
    {
        $component = $this->component([], ['action' => 'index']);

        $this->assertTrue($component->isAuthorized(['role_id' => 1]));
        $this->assertFalse($component->isAuthorized(['role_id' => 2]));
    }

    public function testIsAuthorizedUsesThePluginFromTheRequest(): void
    {
        (new PermissionService())->grant(1, 'Articles', 'index', 'Blog');

        $app = $this->component([], ['action' => 'delete']);
        $blog = $this->component([], ['action' => 'index', 'plugin' => 'Blog']);

        $this->assertTrue($app->isAuthorized(['role_id' => 1]));
        $this->assertTrue($blog->isAuthorized(['role_id' => 1]));
        $this->assertFalse($this->component([], ['action' => 'index', 'plugin' => 'Other'])->isAuthorized(['role_id' => 1]));
    }

    public function testIsAuthorizedHonoursACustomRoleField(): void
    {
        $component = $this->component(['roleField' => 'group_id'], ['action' => 'index']);

        $this->assertFalse($component->isAuthorized(['role_id' => 1]));
        $this->assertTrue($component->isAuthorized(['group_id' => '1']));
    }

    public function testResultsAreCachedUntilClearCache(): void
    {
        $component = $this->component();
        $service = new PermissionService();

        $this->assertFalse($component->checkPermission(1, 'Articles', 'view'));
        $service->grant(1, 'Articles', 'view');
        $this->assertFalse($component->checkPermission(1, 'Articles', 'view'), 'served from cache');

        $component->clearCache();

        $this->assertTrue($component->checkPermission(1, 'Articles', 'view'));
    }

    public function testResultsAreNotCachedWhenCachingIsDisabled(): void
    {
        $component = $this->component(['cachePermissions' => false]);

        $this->assertFalse($component->checkPermission(1, 'Articles', 'view'));
        (new PermissionService())->grant(1, 'Articles', 'view');

        $this->assertTrue($component->checkPermission(1, 'Articles', 'view'));
    }

    public function testCacheDistinguishesRolePluginControllerAndAction(): void
    {
        $component = $this->component();

        $this->assertTrue($component->checkPermission(1, 'Articles', 'index'));
        $this->assertFalse($component->checkPermission(2, 'Articles', 'index'));
        $this->assertFalse($component->checkPermission(1, 'Articles', 'index', 'Blog'));
        $this->assertFalse($component->checkPermission(1, 'Users', 'index'));
        $this->assertFalse($component->checkPermission(1, 'Articles', 'view'));
    }

    public function testHandleUnauthorizedRedirectsToTheConfiguredLocationWithCustomMessage(): void
    {
        $session = new Session();
        $controller = new Controller(new ServerRequest(['session' => $session]), 'Articles');
        $component = new AuthorizationManagerComponent(
            $controller->components(),
            ['unauthorizedRedirect' => '/users/login'],
        );

        $response = $component->handleUnauthorized('Nope');

        $this->assertSame('Nope', $session->read('Flash.flash.0.message'));
        $this->assertStringEndsWith('/users/login', $response?->getHeaderLine('Location') ?? '');
        $this->assertSame('/users/login', $component->getUnauthorizedRedirect());
    }
}

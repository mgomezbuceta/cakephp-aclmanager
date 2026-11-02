<?php
declare(strict_types=1);

namespace AclManager\Test\TestCase;

use AclManager\Test\TestApp\Application;
use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Http\ServerRequest;
use Cake\Routing\Exception\MissingRouteException;
use Cake\Routing\Router;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Pins the plugin URLs for both mounts and the removal of the legacy /acl-manager mount.
 */
class RoutesTest extends TestCase
{
    use IntegrationTestTrait;

    protected function tearDown(): void
    {
        Configure::delete('AclManager.admin');
        Configure::delete('AclManager.adminAccess');
        Router::reload();
        Cache::clear('_cake_routes_');
        parent::tearDown();
    }

    /**
     * Boots the host app with the requested mount so the plugin routes are loaded.
     */
    private function bootRoutes(bool $admin): void
    {
        Configure::write('AclManager.admin', $admin);
        Cache::clear('_cake_routes_');
        Router::reload();
        $this->configApplication(Application::class, []);
        $this->disableErrorHandlerMiddleware();
        // Any request loads the routes through the RoutingMiddleware.
        Configure::write('AclManager.adminAccess.allowWithoutAuthentication', true);
        $this->get('/no-auth');
    }

    /**
     * @return array<string, array{0: string, 1: array<string, mixed>}>
     */
    public static function mountedRoutes(): array
    {
        return [
            'index' => ['/', ['action' => 'index', 'pass' => []]],
            'roles' => ['/roles', ['action' => 'roles', 'pass' => []]],
            'roles add' => ['/roles/add', ['action' => 'addRole', 'pass' => []]],
            'roles edit' => ['/roles/edit/3', ['action' => 'editRole', 'pass' => ['3']]],
            'roles delete' => ['/roles/delete/3', ['action' => 'deleteRole', 'pass' => ['3']]],
            'manage' => ['/manage/2', ['action' => 'manage', 'pass' => ['2']]],
            'sync resources' => ['/sync-resources', ['action' => 'syncResources', 'pass' => []]],
            'copy permissions' => ['/copy-permissions/1/2', ['action' => 'copyPermissions', 'pass' => ['1', '2']]],
            'clear permissions' => ['/clear-permissions/4', ['action' => 'clearPermissions', 'pass' => ['4']]],
        ];
    }

    /**
     * @dataProvider mountedRoutes
     * @param array<string, mixed> $expected
     */
    public function testStandardMountRoutes(string $path, array $expected): void
    {
        $this->bootRoutes(false);

        $params = Router::parseRequest($this->request('/authorization-manager' . rtrim($path, '/')));

        $this->assertSame('AclManager', $params['plugin']);
        $this->assertSame('Permissions', $params['controller']);
        $this->assertArrayNotHasKey('prefix', $params);
        $this->assertSame($expected['action'], $params['action']);
        $this->assertSame($expected['pass'], $params['pass']);
    }

    /**
     * @dataProvider mountedRoutes
     * @param array<string, mixed> $expected
     */
    public function testAdminMountRoutes(string $path, array $expected): void
    {
        $this->bootRoutes(true);

        // The prefix scope already adds /admin, so the plugin path nests as /admin/admin/authorization-manager.
        // Characterization of the current behavior; the refactor must not change it.
        $params = Router::parseRequest($this->request('/admin/admin/authorization-manager' . rtrim($path, '/')));

        $this->assertSame('AclManager', $params['plugin']);
        $this->assertSame('Admin', $params['prefix']);
        $this->assertSame('Permissions', $params['controller']);
        $this->assertSame($expected['action'], $params['action']);
        $this->assertSame($expected['pass'], $params['pass']);
    }

    public function testNonNumericIdsDoNotMatchTheNamedRoutes(): void
    {
        $this->bootRoutes(false);

        $params = Router::parseRequest($this->request('/authorization-manager/manage/abc'));

        // Only the plugin fallback matches (controller "manage"); the manage route requires a numeric id.
        $this->assertNotSame('Permissions', $params['controller']);
    }

    public function testStandardMountDoesNotExposeTheAdminMount(): void
    {
        $this->bootRoutes(false);

        $this->expectException(MissingRouteException::class);
        Router::parseRequest($this->request('/admin/admin/authorization-manager/roles'));
    }

    public function testLegacyAclManagerMountNoLongerRoutes(): void
    {
        $this->bootRoutes(false);

        $this->expectException(MissingRouteException::class);
        Router::parseRequest($this->request('/acl-manager/permissions/index'));
    }

    public function testReverseRoutingKeepsUrls(): void
    {
        $this->bootRoutes(false);

        $this->assertSame(
            '/authorization-manager/copy-permissions/1/2',
            Router::url([
                'plugin' => 'AclManager',
                'controller' => 'Permissions',
                'action' => 'copyPermissions',
                1,
                2,
            ]),
        );
        $this->assertSame(
            '/authorization-manager/roles/edit/5',
            Router::url(['plugin' => 'AclManager', 'controller' => 'Permissions', 'action' => 'editRole', 5]),
        );
    }

    private function request(string $url): ServerRequest
    {
        return new ServerRequest(['url' => $url, 'environment' => ['REQUEST_METHOD' => 'GET']]);
    }
}

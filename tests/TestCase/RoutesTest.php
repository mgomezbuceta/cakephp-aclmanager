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
 * Pins the plugin URLs, the configurable mount path and the removal of the legacy /acl-manager mount.
 */
class RoutesTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'plugin.AclManager.Roles',
        'plugin.AclManager.Resources',
        'plugin.AclManager.Permissions',
    ];

    protected function tearDown(): void
    {
        Configure::delete('AclManager.mountPath');
        Configure::delete('AclManager.adminAccess');
        Router::reload();
        Cache::clear('_cake_routes_');
        parent::tearDown();
    }

    /**
     * Boots the host app so the plugin routes are loaded with the current AclManager.mountPath.
     */
    private function bootRoutes(): void
    {
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
        $this->bootRoutes();

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
    public function testCustomMountPathRoutesEveryUrlAndDropsTheDefault(string $path, array $expected): void
    {
        Configure::write('AclManager.mountPath', '/admin/authorization-manager');
        $this->bootRoutes();

        $params = Router::parseRequest($this->request('/admin/authorization-manager' . rtrim($path, '/')));

        $this->assertSame('AclManager', $params['plugin']);
        $this->assertSame('Permissions', $params['controller']);
        $this->assertArrayNotHasKey('prefix', $params);
        $this->assertSame($expected['action'], $params['action']);
        $this->assertSame($expected['pass'], $params['pass']);

        $this->expectException(MissingRouteException::class);
        Router::parseRequest($this->request('/authorization-manager' . rtrim($path, '/')));
    }

    /**
     * @return array<string, array{0: mixed, 1: string}>
     */
    public static function mountPathValues(): array
    {
        return [
            'messy' => ['  admin/tools/ ', '/admin/tools'],
            'trailing slash' => ['/x/', '/x'],
            'multiple slashes' => ['//x//y//', '/x/y'],
            'empty' => ['', '/authorization-manager'],
            'null' => [null, '/authorization-manager'],
            'root' => ['/', '/authorization-manager'],
            'blank' => ['   ', '/authorization-manager'],
            'array' => [['/x'], '/authorization-manager'],
        ];
    }

    /**
     * @dataProvider mountPathValues
     */
    public function testMountPathIsNormalizedOrFallsBack(mixed $value, string $expectedMount): void
    {
        Configure::write('AclManager.mountPath', $value);
        $this->bootRoutes();

        $params = Router::parseRequest($this->request($expectedMount . '/roles'));

        $this->assertSame('roles', $params['action']);
        $this->assertSame($expectedMount . '/roles', Router::url([
            'plugin' => 'AclManager',
            'controller' => 'Permissions',
            'action' => 'roles',
        ]));
    }

    public function testAuthenticatedAdminReachesTheManagerAtACustomMountPath(): void
    {
        Configure::write('AclManager.mountPath', '/admin/authorization-manager');
        $this->bootRoutes();
        Configure::delete('AclManager.adminAccess');
        $this->session(['Auth' => ['id' => 10, 'role_id' => 1]]);

        $this->get('/admin/authorization-manager/roles');

        $this->assertResponseOk();
    }

    public function testNonNumericIdsDoNotMatchTheNamedRoutes(): void
    {
        $this->bootRoutes();

        $params = Router::parseRequest($this->request('/authorization-manager/manage/abc'));

        // Only the plugin fallback matches (controller "manage"); the manage route requires a numeric id.
        $this->assertNotSame('Permissions', $params['controller']);
    }

    public function testLegacyAclManagerMountNoLongerRoutes(): void
    {
        $this->bootRoutes();

        $this->expectException(MissingRouteException::class);
        Router::parseRequest($this->request('/acl-manager/permissions/index'));
    }

    public function testReverseRoutingKeepsUrls(): void
    {
        $this->bootRoutes();

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

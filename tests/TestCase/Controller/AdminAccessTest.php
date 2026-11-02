<?php
declare(strict_types=1);

namespace AclManager\Test\TestCase\Controller;

use AclManager\Test\TestApp\Application;
use Cake\Core\Configure;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\MethodNotAllowedException;
use Cake\ORM\Table;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * The plugin must only be usable by administrators, fail closed and refuse unsafe methods.
 */
class AdminAccessTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'plugin.AclManager.Roles',
        'plugin.AclManager.Resources',
        'plugin.AclManager.Permissions',
    ];

    private const ADMIN = ['id' => 10, 'role_id' => 1];
    private const EDITOR = ['id' => 11, 'role_id' => 2];

    protected function setUp(): void
    {
        parent::setUp();
        Configure::write('AclManager.redirects.login', '/users/login');
        Configure::write('AclManager.redirects.unauthorized', '/unauthorized');
        $this->configApplication(Application::class, []);
        $this->disableErrorHandlerMiddleware();
    }

    protected function tearDown(): void
    {
        Configure::delete('AclManager.redirects');
        Configure::delete('AclManager.adminAccess');
        parent::tearDown();
    }

    private function roles(): Table
    {
        return $this->getTableLocator()->get('AclManager.Roles');
    }

    private function loginAs(array $identity): void
    {
        $this->session(['Auth' => $identity]);
    }

    public function testAnonymousIndexRedirectsToLogin(): void
    {
        $this->get('/authorization-manager');

        $this->assertRedirectContains('/users/login');
    }

    public function testAnonymousDeleteRoleRedirectsAndDoesNotDelete(): void
    {
        $this->post('/authorization-manager/roles/delete/2');

        $this->assertRedirectContains('/users/login');
        $this->assertTrue($this->roles()->exists(['id' => 2]));
    }

    public function testNonAdminDeleteRoleRedirectsAndDoesNotDelete(): void
    {
        $this->loginAs(self::EDITOR);

        $this->post('/authorization-manager/roles/delete/2');

        $this->assertRedirectContains('/unauthorized');
        $this->assertTrue($this->roles()->exists(['id' => 2]));
    }

    public function testAdminCanViewIndex(): void
    {
        $this->loginAs(self::ADMIN);

        $this->get('/authorization-manager');

        $this->assertResponseOk();
    }

    public function testAdminCanDeleteNonAdminRole(): void
    {
        $this->loginAs(self::ADMIN);

        $this->post('/authorization-manager/roles/delete/2');

        $this->assertFlashElement('flash/success');
        $this->assertFalse($this->roles()->exists(['id' => 2]));
    }

    public function testWithoutAuthenticationComponentAccessIsDenied(): void
    {
        $this->expectException(ForbiddenException::class);

        $this->get('/no-auth');
    }

    public function testWithoutAuthenticationComponentAccessAllowedWhenOptedIn(): void
    {
        Configure::write('AclManager.adminAccess.allowWithoutAuthentication', true);

        $this->get('/no-auth');

        $this->assertResponseOk();
    }

    public function testClearPermissionsRejectsGet(): void
    {
        $this->loginAs(self::ADMIN);
        $before = $this->getTableLocator()->get('AclManager.Permissions')->find()->count();
        $this->expectException(MethodNotAllowedException::class);

        try {
            $this->get('/authorization-manager/clear-permissions/1');
        } finally {
            $this->assertSame($before, $this->getTableLocator()->get('AclManager.Permissions')->find()->count());
        }
    }

    public function testClearPermissionsAcceptsPost(): void
    {
        $this->loginAs(self::ADMIN);

        $this->post('/authorization-manager/clear-permissions/1');

        $this->assertFlashElement('flash/success');
    }

    public function testSyncResourcesRejectsGet(): void
    {
        $this->loginAs(self::ADMIN);
        $before = $this->getTableLocator()->get('AclManager.Resources')->find()->count();
        $this->expectException(MethodNotAllowedException::class);

        try {
            $this->get('/authorization-manager/sync-resources');
        } finally {
            $this->assertSame($before, $this->getTableLocator()->get('AclManager.Resources')->find()->count());
        }
    }

    public function testSyncResourcesAcceptsPost(): void
    {
        $this->loginAs(self::ADMIN);

        $this->post('/authorization-manager/sync-resources');

        $this->assertRedirectContains('/authorization-manager');
    }

    public function testAdminRoleCannotBeDeleted(): void
    {
        $this->loginAs(self::ADMIN);

        $this->post('/authorization-manager/roles/delete/1');

        $this->assertFlashElement('flash/error');
        $this->assertTrue($this->roles()->exists(['id' => 1]));
    }

    public function testAdminRoleCannotBeDeactivated(): void
    {
        $this->loginAs(self::ADMIN);

        $this->post('/authorization-manager/roles/edit/1', ['name' => 'Admin', 'active' => '0']);

        $this->assertFlashElement('flash/error');
        $this->assertTrue((bool)$this->roles()->get(1)->active);
    }

    public function testNonAdminRoleCanBeDeactivated(): void
    {
        $this->loginAs(self::ADMIN);

        $this->post('/authorization-manager/roles/edit/2', ['name' => 'Editor', 'active' => '0']);

        $this->assertFlashElement('flash/success');
        $this->assertFalse((bool)$this->roles()->get(2)->active);
    }

    public function testDestructiveLinksAreRenderedAsPostForms(): void
    {
        $this->loginAs(self::ADMIN);

        $this->get('/authorization-manager/manage/1');

        $this->assertResponseOk();
        $body = (string)$this->_response->getBody();
        $this->assertMatchesRegularExpression(
            '#<form\b[^>]*\bmethod="post"[^>]*\baction="/authorization-manager/clear-permissions/1"#i',
            $body,
        );
        $this->assertStringNotContainsString('href="/authorization-manager/clear-permissions/1"', $body);
    }
}

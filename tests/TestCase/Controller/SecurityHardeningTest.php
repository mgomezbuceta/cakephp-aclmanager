<?php
declare(strict_types=1);

namespace AclManager\Test\TestCase\Controller;

use AclManager\Model\Entity\Permission;
use AclManager\Model\Entity\Resource;
use AclManager\Model\Entity\Role;
use AclManager\Service\PermissionService;
use AclManager\Test\TestApp\Application;
use AclManager\Test\TestApp\Controller\FailingComponentController;
use AclManager\Test\TestApp\Model\Table\ThrowingPermissionsTable;
use AclManager\Test\TestApp\Model\Table\ThrowingResourcesTable;
use Cake\Datasource\ConnectionManager;
use Cake\Http\Exception\ForbiddenException;
use Cake\I18n\I18n;
use Cake\Log\Log;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Security hardening: mass assignment, error disclosure, silent failures and output escaping.
 */
class SecurityHardeningTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'plugin.AclManager.Roles',
        'plugin.AclManager.Resources',
        'plugin.AclManager.Permissions',
    ];

    private const SCRIPT = '<script>alert(1)</script>';
    private const BREAKOUT = '"><img src=x onerror=alert(2)>';

    private string $originalLocale;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalLocale = I18n::getLocale();
        I18n::setLocale('en_US');
        $this->configApplication(Application::class, []);
        $this->disableErrorHandlerMiddleware();
        $this->session(['Auth' => ['id' => 10, 'role_id' => 1]]);
        Log::setConfig('hardening_capture', ['className' => 'Array', 'levels' => ['warning', 'error']]);
    }

    protected function tearDown(): void
    {
        Log::drop('hardening_capture');
        FailingComponentController::$failing = [];
        I18n::setLocale($this->originalLocale);
        parent::tearDown();
    }

    /**
     * @return array<int, string>
     */
    private function loggedMessages(): array
    {
        return array_map('strval', Log::engine('hardening_capture')->read());
    }

    private function flashDump(): string
    {
        return (string)json_encode($this->_requestSession->read('Flash'));
    }

    // H2: mass assignment

    public function testEntitiesDoNotExposeManagedFieldsToMassAssignment(): void
    {
        $role = new Role();
        $permission = new Permission();
        $resource = new Resource();

        foreach (['id', 'created', 'modified', 'permissions'] as $field) {
            $this->assertFalse($role->isAccessible($field), "Role.$field");
        }
        foreach (['id', 'created', 'modified', 'role'] as $field) {
            $this->assertFalse($permission->isAccessible($field), "Permission.$field");
        }
        foreach (['id', 'created', 'modified'] as $field) {
            $this->assertFalse($resource->isAccessible($field), "Resource.$field");
        }
        foreach (['name', 'description', 'priority', 'active'] as $field) {
            $this->assertTrue($role->isAccessible($field), "Role.$field");
        }
    }

    public function testEditRoleIgnoresManagedFields(): void
    {
        $roles = $this->getTableLocator()->get('AclManager.Roles');
        $permissionsBefore = $this->getTableLocator()->get('AclManager.Permissions')->find()->where(['role_id' => 2])->count();

        $this->post('/authorization-manager/roles/edit/2', [
            'id' => 99,
            'name' => 'Editors',
            'priority' => 5,
            'active' => '1',
            'created' => '2000-01-01 00:00:00',
            'modified' => '2000-01-01 00:00:00',
            'permissions' => [['role_id' => 1, 'controller' => 'X', 'action' => 'y', 'plugin' => 'App', 'allowed' => 1]],
        ]);

        $this->assertFlashElement('flash/success');
        $role = $roles->get(2);
        $this->assertSame('Editors', $role->name);
        $this->assertFalse($roles->exists(['id' => 99]));
        $this->assertSame('2025-01-01 00:00:00', $role->created->format('Y-m-d H:i:s'));
        $this->assertNotSame('2000-01-01 00:00:00', $role->modified->format('Y-m-d H:i:s'));
        $this->assertSame(
            $permissionsBefore,
            $this->getTableLocator()->get('AclManager.Permissions')->find()->where(['role_id' => 2])->count(),
        );
    }

    public function testAddRoleIgnoresManagedFields(): void
    {
        $this->post('/authorization-manager/roles/add', [
            'id' => 77,
            'name' => 'Auditor',
            'priority' => 3,
            'active' => '1',
            'created' => '2000-01-01 00:00:00',
        ]);

        $this->assertFlashElement('flash/success');
        $roles = $this->getTableLocator()->get('AclManager.Roles');
        $this->assertFalse($roles->exists(['id' => 77]));
        $role = $roles->find()->where(['name' => 'Auditor'])->firstOrFail();
        $this->assertNotSame(77, $role->id);
        $this->assertNotSame('2000-01-01', $role->created->format('Y-m-d'));
    }

    // H3: no exception text in flash messages, failures are logged

    private function assertGenericFailure(string $expectedFlash, string $logNeedle): void
    {
        $this->assertFlashElement('flash/error');
        $this->assertFlashMessage($expectedFlash);
        $this->assertStringNotContainsString('hunter2', $this->flashDump());
        $this->assertStringNotContainsString('SQLSTATE', $this->flashDump());

        $logged = implode("\n", $this->loggedMessages());
        $this->assertStringContainsString($logNeedle, $logged);
        $this->assertStringContainsString('RuntimeException', $logged);
        $this->assertStringContainsString('hunter2', $logged);
    }

    public function testSaveFailureFlashesGenericMessageAndLogsTheException(): void
    {
        $this->getTableLocator()->set('AclManager.Permissions', new ThrowingPermissionsTable([
            'alias' => 'Permissions',
            'connection' => ConnectionManager::get('test'),
        ]));
        $version = (new PermissionService())->getPermissionsVersion(1);

        $this->post('/authorization-manager/manage/1', [
            'permissions_version' => $version,
            'resource_ids' => [1],
            'permissions_submitted' => '1',
        ]);

        $this->assertGenericFailure(
            __d('acl_manager', 'The permissions could not be saved. Check the application log for details.'),
            'savePermissions',
        );
    }

    public function testSyncFailureFlashesGenericMessageAndLogsTheException(): void
    {
        $this->getTableLocator()->set('AclManager.Resources', new ThrowingResourcesTable([
            'alias' => 'Resources',
            'connection' => ConnectionManager::get('test'),
        ]));

        $this->post('/authorization-manager/sync-resources');

        $this->assertGenericFailure(
            __d('acl_manager', 'The resources could not be synchronized. Check the application log for details.'),
            'syncResources',
        );
    }

    public function testCopyFailureFlashesGenericMessageAndLogsTheException(): void
    {
        $this->getTableLocator()->set('AclManager.Permissions', new ThrowingPermissionsTable([
            'alias' => 'Permissions',
            'connection' => ConnectionManager::get('test'),
        ]));

        $this->post('/authorization-manager/copy-permissions/1/2');

        $this->assertGenericFailure(
            __d('acl_manager', 'The permissions could not be copied. Check the application log for details.'),
            'copyPermissions',
        );
    }

    public function testClearFailureFlashesGenericMessageAndLogsTheException(): void
    {
        $this->getTableLocator()->set('AclManager.Permissions', new ThrowingPermissionsTable([
            'alias' => 'Permissions',
            'connection' => ConnectionManager::get('test'),
        ]));

        $this->post('/authorization-manager/clear-permissions/1');

        $this->assertGenericFailure(
            __d('acl_manager', 'The permissions could not be cleared. Check the application log for details.'),
            'clearPermissions',
        );
    }

    // H4: component load failures are logged, access stays fail-closed

    public function testAuthorizationComponentLoadFailureIsLoggedAndRequestContinues(): void
    {
        FailingComponentController::$failing = ['Authorization.Authorization'];

        $this->get('/failing-component');

        $this->assertResponseOk();
        $logged = implode("\n", $this->loggedMessages());
        $this->assertStringContainsString('Authorization.Authorization', $logged);
        $this->assertStringContainsString('cannot load Authorization.Authorization', $logged);
    }

    public function testAuthenticationComponentLoadFailureIsLoggedAndAccessIsDenied(): void
    {
        FailingComponentController::$failing = ['Authentication.Authentication'];

        try {
            $this->get('/failing-component');
            $this->fail('Access must stay closed when the Authentication component cannot be loaded.');
        } catch (ForbiddenException) {
            $logged = implode("\n", $this->loggedMessages());
            $this->assertStringContainsString('Authentication.Authentication', $logged);
            $this->assertStringContainsString('cannot load Authentication.Authentication', $logged);
        }
    }

    // H5: template output escaping

    private function insertHostileFixtures(): void
    {
        $this->getTableLocator()->get('AclManager.Roles')->updateAll(
            ['name' => self::SCRIPT . self::BREAKOUT, 'description' => self::SCRIPT],
            ['id' => 2],
        );
        $resources = $this->getTableLocator()->get('AclManager.Resources');
        $resources->saveOrFail($resources->newEntity([
            'plugin' => 'P' . self::BREAKOUT,
            'controller' => 'C<em>ct</em>',
            'action' => 'a<mark>ac</mark>' . self::BREAKOUT,
            'active' => true,
        ]));
    }

    private function assertNoRawMarkup(string $body): void
    {
        foreach ([self::SCRIPT, self::BREAKOUT, '<em>ct</em>', '<mark>ac</mark>', 'onerror=alert(2)>'] as $needle) {
            $this->assertStringNotContainsString($needle, $body, 'Unescaped user value in output: ' . $needle);
        }
        $this->assertStringNotContainsString('permission-P"', $body);
    }

    public function testRolesPageEscapesRoleValuesIncludingConfirmMessage(): void
    {
        $this->insertHostileFixtures();

        $this->get('/authorization-manager/roles');

        $this->assertResponseOk();
        $body = (string)$this->_response->getBody();
        $this->assertNoRawMarkup($body);
        $this->assertStringContainsString('&lt;script&gt;', $body);
        // The delete icon is still rendered as HTML, not escaped text.
        $this->assertMatchesRegularExpression('/data-confirm-message="[^"]*"[^>]*><i class="fas fa-trash"><\/i><\/a>/', $body);
    }

    public function testDashboardEscapesRoleValuesIncludingConfirmMessage(): void
    {
        $this->insertHostileFixtures();

        $this->get('/authorization-manager');

        $this->assertResponseOk();
        $body = (string)$this->_response->getBody();
        $this->assertNoRawMarkup($body);
        $this->assertStringContainsString('&lt;script&gt;', $body);
    }

    public function testManagePageEscapesResourceNames(): void
    {
        $this->insertHostileFixtures();

        $this->get('/authorization-manager/manage/2');

        $this->assertResponseOk();
        $body = (string)$this->_response->getBody();
        $this->assertNoRawMarkup($body);
        $this->assertStringContainsString('&lt;em&gt;ct&lt;/em&gt;', $body);
    }

    public function testEditRolePageEscapesRoleName(): void
    {
        $this->insertHostileFixtures();

        $this->get('/authorization-manager/roles/edit/2');

        $this->assertResponseOk();
        $this->assertNoRawMarkup((string)$this->_response->getBody());
    }

    public function testCopyPermissionsFlashEscapesRoleNames(): void
    {
        $this->insertHostileFixtures();

        $this->post('/authorization-manager/copy-permissions/1/2');
        $this->get('/authorization-manager');

        $this->assertNoRawMarkup((string)$this->_response->getBody());
    }
}

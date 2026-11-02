<?php
declare(strict_types=1);

namespace AclManager\Test\TestCase\Controller;

use AclManager\Test\TestApp\Application;
use Cake\Event\EventInterface;
use Cake\I18n\I18n;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

/**
 * Failure and edge branches of the role and permission operations, as an administrator sees them.
 */
class RoleManagementTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'plugin.AclManager.Roles',
        'plugin.AclManager.Resources',
        'plugin.AclManager.Permissions',
    ];

    private const BASE = '/authorization-manager';

    private string $originalLocale;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configApplication(Application::class, []);
        $this->disableErrorHandlerMiddleware();
        $this->session(['Auth' => ['id' => 10, 'role_id' => 1]]);
        $this->originalLocale = I18n::getLocale();
        I18n::setLocale('en_US');
    }

    protected function tearDown(): void
    {
        I18n::setLocale($this->originalLocale);
        parent::tearDown();
    }

    private function permissionCount(): int
    {
        return $this->getTableLocator()->get('AclManager.Permissions')->find()->count();
    }

    public function testManageWithoutRoleRedirectsToDashboardWithError(): void
    {
        $this->get(self::BASE . '/Permissions/manage');

        $this->assertRedirectContains(self::BASE);
        $this->assertFlashElement('flash/error');
    }

    public function testAddRoleWithInvalidDataKeepsTheFormAndCreatesNothing(): void
    {
        $roles = $this->getTableLocator()->get('AclManager.Roles');
        $before = $roles->find()->count();

        $this->post(self::BASE . '/roles/add', ['name' => '', 'priority' => 1, 'active' => 1]);

        $this->assertResponseOk();
        $this->assertResponseContains('Unable to create role');
        $this->assertSame($before, $roles->find()->count());
    }

    public function testAddRoleRejectsADuplicateName(): void
    {
        $roles = $this->getTableLocator()->get('AclManager.Roles');
        $before = $roles->find()->count();

        $this->post(self::BASE . '/roles/add', ['name' => 'Editor', 'priority' => 1, 'active' => 1]);

        $this->assertResponseContains('Unable to create role');
        $this->assertSame($before, $roles->find()->count());
    }

    public function testEditRoleWithInvalidDataKeepsTheFormAndTheOldName(): void
    {
        $this->post(self::BASE . '/roles/edit/2', ['name' => '', 'priority' => 5, 'active' => 1]);

        $this->assertResponseOk();
        $this->assertResponseContains('Unable to update role');
        $this->assertSame('Editor', $this->getTableLocator()->get('AclManager.Roles')->get(2)->name);
    }

    public function testDeleteRoleReportsAnErrorWhenTheRoleCannotBeDeleted(): void
    {
        $roles = $this->getTableLocator()->get('AclManager.Roles');
        $roles->getEventManager()->on('Model.beforeDelete', function (EventInterface $event): void {
            $event->setResult(false);
            $event->stopPropagation();
        });

        $this->post(self::BASE . '/roles/delete/2');

        $this->assertRedirectContains(self::BASE . '/roles');
        $this->assertFlashElement('flash/error');
        $this->assertTrue($roles->exists(['id' => 2]));
    }

    public function testCopyPermissionsWithAnInvalidRoleIdChangesNothing(): void
    {
        $before = $this->permissionCount();

        $this->post(self::BASE . '/copy-permissions/0/2');

        $this->assertRedirectContains(self::BASE);
        $this->assertFlashElement('flash/error');
        $this->assertSame($before, $this->permissionCount());
    }

    public function testCopyPermissionsFromARoleToItselfChangesNothing(): void
    {
        $before = $this->permissionCount();

        $this->post(self::BASE . '/copy-permissions/1/1');

        $this->assertRedirectContains(self::BASE);
        $this->assertFlashElement('flash/error');
        $this->assertSame($before, $this->permissionCount());
    }

    public function testClearPermissionsWithAnInvalidRoleIdChangesNothing(): void
    {
        $before = $this->permissionCount();

        $this->post(self::BASE . '/clear-permissions/0');

        $this->assertRedirectContains(self::BASE);
        $this->assertFlashElement('flash/error');
        $this->assertSame($before, $this->permissionCount());
    }
}

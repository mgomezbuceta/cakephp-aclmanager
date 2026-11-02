<?php
declare(strict_types=1);

namespace AclManager\Test\TestCase\Controller;

use AclManager\Test\TestApp\Application;
use Cake\TestSuite\IntegrationTestTrait;
use Cake\TestSuite\TestCase;

class PermissionsControllerTest extends TestCase
{
    use IntegrationTestTrait;

    protected array $fixtures = [
        'plugin.AclManager.Roles',
        'plugin.AclManager.Resources',
        'plugin.AclManager.Permissions',
    ];

    private const MANAGE_URL = '/authorization-manager/manage/1';

    protected function setUp(): void
    {
        parent::setUp();
        $this->configApplication(Application::class, []);
        $this->disableErrorHandlerMiddleware();
        $this->session(['Auth' => ['id' => 10, 'role_id' => 1]]);
    }

    /**
     * @return array<int, string> Sorted "role:plugin:controller:action:allowed" rows
     */
    private function permissionRows(?int $roleId = null): array
    {
        $query = $this->getTableLocator()->get('AclManager.Permissions')->find();
        if ($roleId !== null) {
            $query->where(['role_id' => $roleId]);
        }
        $rows = [];
        foreach ($query->all() as $p) {
            $rows[] = sprintf('%d:%s:%s:%s:%d', $p->role_id, $p->plugin, $p->controller, $p->action, $p->allowed);
        }
        sort($rows);

        return $rows;
    }

    public function testSaveReplacesRolePermissionsWithSubmittedResourceIds(): void
    {
        $this->post(self::MANAGE_URL, ['resource_ids' => [1, 3], 'permissions_submitted' => '1']);

        $this->assertRedirectContains(self::MANAGE_URL);
        $this->assertFlashElement('flash/success');
        $this->assertSame(
            ['1:App:Articles:index:1', '1:Blog:Comments:view:1'],
            $this->permissionRows(1),
        );
    }

    public function testSaveDoesNotTouchOtherRoles(): void
    {
        $this->post(self::MANAGE_URL, ['resource_ids' => [1], 'permissions_submitted' => '1']);

        $this->assertSame(['2:App:Articles:edit:1'], $this->permissionRows(2));
    }

    public function testSaveIgnoresUnknownAndInactiveResourceIds(): void
    {
        // 4 is inactive, 999 does not exist, "abc" is junk.
        $this->post(self::MANAGE_URL, ['resource_ids' => [2, 4, 999, 'abc'], 'permissions_submitted' => '1']);

        $this->assertSame(['1:App:Articles:edit:1'], $this->permissionRows(1));
    }

    public function testSaveWithoutSentinelLeavesPermissionsUntouched(): void
    {
        $before = $this->permissionRows();

        $this->post(self::MANAGE_URL, ['resource_ids' => [2]]);

        $this->assertRedirectContains(self::MANAGE_URL);
        $this->assertFlashElement('flash/error');
        $this->assertSame($before, $this->permissionRows());
    }

    public function testSaveWithSentinelAndNoIdsClearsRolePermissions(): void
    {
        $this->post(self::MANAGE_URL, ['permissions_submitted' => '1']);

        $this->assertFlashElement('flash/success');
        $this->assertSame([], $this->permissionRows(1));
        $this->assertSame(['2:App:Articles:edit:1'], $this->permissionRows(2));
    }

    public function testManageRendersResourceIdCheckboxes(): void
    {
        $this->get(self::MANAGE_URL);

        $this->assertResponseOk();
        $body = (string)$this->_response->getBody();

        preg_match_all('/<input[^>]*name="resource_ids\[\]"[^>]*>/', $body, $m);
        $checked = [];
        foreach ($m[0] as $tag) {
            preg_match('/value="(\d+)"/', $tag, $v);
            $checked[(int)$v[1]] = str_contains($tag, 'checked');
        }
        ksort($checked);

        // Role 1 has Articles::index granted (resource 1); inactive resource 4 is not rendered.
        $this->assertSame([1 => true, 2 => false, 3 => false], $checked);
        $this->assertStringContainsString('name="permissions_submitted"', $body);
        $this->assertStringNotContainsString('[controller]', $body);
        $this->assertStringNotContainsString('[plugin]', $body);
    }
}

<?php
declare(strict_types=1);

namespace AclManager\Test\TestCase\Controller;

use AclManager\Service\PermissionService;
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

    /**
     * Form payload as the browser would send it: current version first, sentinel last.
     *
     * @param array<string, mixed> $fields
     * @return array<string, mixed>
     */
    private function payload(array $fields, ?string $version = null): array
    {
        return ['permissions_version' => $version ?? (new PermissionService())->getPermissionsVersion(1)]
            + $fields
            + ['permissions_submitted' => '1'];
    }

    public function testSaveReplacesRolePermissionsWithSubmittedResourceIds(): void
    {
        $this->post(self::MANAGE_URL, $this->payload(['resource_ids' => [1, 3]]));

        $this->assertRedirectContains(self::MANAGE_URL);
        $this->assertFlashElement('flash/success');
        $this->assertSame(
            ['1:App:Articles:index:1', '1:Blog:Comments:view:1'],
            $this->permissionRows(1),
        );
    }

    public function testSaveDoesNotTouchOtherRoles(): void
    {
        $this->post(self::MANAGE_URL, $this->payload(['resource_ids' => [1]]));

        $this->assertSame(['2:App:Articles:edit:1'], $this->permissionRows(2));
        // The save really happened: role 1 has exactly the submitted grant.
        $this->assertSame(['1:App:Articles:index:1'], $this->permissionRows(1));
    }

    public function testSaveIgnoresUnknownAndInactiveResourceIds(): void
    {
        // 4 is inactive, 999 does not exist, "abc" is junk.
        $this->post(self::MANAGE_URL, $this->payload(['resource_ids' => [2, 4, 999, 'abc']]));

        $this->assertSame(['1:App:Articles:edit:1'], $this->permissionRows(1));
    }

    public function testSaveWithoutSentinelLeavesPermissionsUntouched(): void
    {
        $before = $this->permissionRows();

        $this->post(self::MANAGE_URL, ['permissions_version' => (new PermissionService())->getPermissionsVersion(1), 'resource_ids' => [2]]);

        $this->assertRedirectContains(self::MANAGE_URL);
        $this->assertFlashElement('flash/error');
        $this->assertSame($before, $this->permissionRows());
    }

    public function testSaveWithSentinelAndNoIdsClearsRolePermissions(): void
    {
        $this->post(self::MANAGE_URL, $this->payload([]));

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

    public function testSecondSaveFromSameLoadedFormIsRejectedAndKeepsFirstSave(): void
    {
        $version = (new PermissionService())->getPermissionsVersion(1);

        $this->post(self::MANAGE_URL, $this->payload(['resource_ids' => [2]], $version));
        $this->assertFlashElement('flash/success');
        $afterFirst = $this->permissionRows();

        $this->post(self::MANAGE_URL, $this->payload(['resource_ids' => [1, 3]], $version));

        $this->assertRedirectContains(self::MANAGE_URL);
        $this->assertFlashElement('flash/error');
        $this->assertFlashMessage(__d(
            'acl_manager',
            'Another administrator changed the permissions of this role while you were editing. '
            . 'The page has been reloaded with the current data; apply your changes again.',
        ));
        $this->assertSame($afterFirst, $this->permissionRows());
    }

    public function testSaveWithoutVersionIsRejected(): void
    {
        $before = $this->permissionRows();

        $this->post(self::MANAGE_URL, ['resource_ids' => [2], 'permissions_submitted' => '1']);
        $this->assertFlashElement('flash/error');
        $this->assertSame($before, $this->permissionRows());

        $this->post(self::MANAGE_URL, ['permissions_version' => '', 'resource_ids' => [2], 'permissions_submitted' => '1']);
        $this->assertFlashElement('flash/error');
        $this->assertSame($before, $this->permissionRows());
    }

    public function testCsvGrantsExactlyTheValidIds(): void
    {
        $this->post(self::MANAGE_URL, $this->payload(['resource_ids_csv' => ' 1, 3 ,5abc,0,-1,,4,999']));

        $this->assertFlashElement('flash/success');
        $this->assertSame(['1:App:Articles:index:1', '1:Blog:Comments:view:1'], $this->permissionRows(1));
    }

    public function testCsvWinsOverArrayWhenBothAreSent(): void
    {
        $this->post(self::MANAGE_URL, $this->payload(['resource_ids_csv' => '2', 'resource_ids' => [1, 3]]));

        $this->assertSame(['1:App:Articles:edit:1'], $this->permissionRows(1));
    }

    public function testEmptyCsvClearsGrantsEvenWithArrayPresent(): void
    {
        $this->post(self::MANAGE_URL, $this->payload(['resource_ids_csv' => '', 'resource_ids' => [1]]));

        $this->assertFlashElement('flash/success');
        $this->assertSame([], $this->permissionRows(1));
    }

    public function testArrayFallbackStillWorksWithoutCsv(): void
    {
        $this->post(self::MANAGE_URL, $this->payload(['resource_ids' => [2]]));

        $this->assertSame(['1:App:Articles:edit:1'], $this->permissionRows(1));
    }

    public function testCsvWithoutSentinelIsRejected(): void
    {
        $before = $this->permissionRows();

        $this->post(self::MANAGE_URL, [
            'permissions_version' => (new PermissionService())->getPermissionsVersion(1),
            'resource_ids_csv' => '2',
        ]);

        $this->assertFlashElement('flash/error');
        $this->assertSame($before, $this->permissionRows());
    }

    public function testManageRendersVersionAndCsvFieldsWithSentinelLast(): void
    {
        $this->get(self::MANAGE_URL);

        $body = (string)$this->_response->getBody();
        $version = (new PermissionService())->getPermissionsVersion(1);
        $this->assertMatchesRegularExpression('/<input[^>]*name="permissions_version"[^>]*value="' . $version . '"/', $body);
        $this->assertStringContainsString('name="resource_ids_csv"', $body);

        // Order of the named form fields in the HTML: the sentinel must be the last one.
        $form = substr($body, (int)strpos($body, '<form'), (int)strpos($body, '</form>'));
        preg_match_all('/<input[^>]*name="([^"]+)"/', $form, $m);
        $names = array_values(array_diff($m[1], ['resource_ids[]']));
        $this->assertSame('permissions_submitted', end($names));
        $this->assertLessThan(
            array_search('permissions_submitted', $names, true),
            array_search('permissions_version', $names, true),
        );
    }
}

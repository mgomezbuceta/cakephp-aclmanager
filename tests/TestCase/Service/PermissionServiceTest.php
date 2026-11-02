<?php
declare(strict_types=1);

namespace AclManager\Test\TestCase\Service;

use AclManager\Service\PermissionService;
use AclManager\Service\StalePermissionsException;
use Cake\TestSuite\TestCase;

class PermissionServiceTest extends TestCase
{
    protected array $fixtures = [
        'plugin.AclManager.Roles',
        'plugin.AclManager.Resources',
        'plugin.AclManager.Permissions',
    ];

    private PermissionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PermissionService();
    }

    /**
     * @return array<int, string> Sorted "plugin:controller:action:allowed" rows
     */
    private function rows(int $roleId): array
    {
        $rows = [];
        foreach ($this->getTableLocator()->get('AclManager.Permissions')->find()->where(['role_id' => $roleId])->all() as $p) {
            $rows[] = sprintf('%s:%s:%s:%d', $p->plugin, $p->controller, $p->action, $p->allowed);
        }
        sort($rows);

        return $rows;
    }

    public function testGrantWithoutPluginIsStoredAndFoundAsApp(): void
    {
        $this->assertTrue($this->service->grant(1, 'Articles', 'view'));

        $this->assertTrue($this->service->isAuthorized(1, 'Articles', 'view'));
        $this->assertTrue($this->service->isAuthorized(1, 'Articles', 'view', 'App'));
        $this->assertContains('App:Articles:view:1', $this->rows(1));
    }

    public function testGrantAppAndGrantNullDoNotDuplicate(): void
    {
        $this->service->grant(1, 'Articles', 'view', 'App');
        $this->service->deny(1, 'Articles', 'view');

        $this->assertContains('App:Articles:view:0', $this->rows(1));
        $this->assertNotContains('App:Articles:view:1', $this->rows(1));
        $this->assertFalse($this->service->isAuthorized(1, 'Articles', 'view'));
    }

    public function testReplaceIgnoresMalformedIds(): void
    {
        // '5abc' would become 5 with intval; [1] and ' 2' are not clean ids.
        $granted = $this->service->replaceRolePermissions(1, ['5abc', [1], '-1', '0', ' 2', 1.5, null, true, '2']);

        $this->assertSame(1, $granted);
        $this->assertSame(['App:Articles:edit:1'], $this->rows(1));
    }

    public function testReplaceCollapsesResourcesSharingAPermissionKey(): void
    {
        // A legacy duplicate of Articles::index (id 1), e.g. left over from NULL-plugin data.
        $resources = $this->getTableLocator()->get('AclManager.Resources');
        $resources->saveOrFail($resources->newEntity([
            'id' => 50,
            'controller' => 'Articles',
            'action' => 'index',
            'plugin' => null,
            'active' => true,
        ], ['accessibleFields' => ['id' => true]]));

        $granted = $this->service->replaceRolePermissions(1, [1, 50]);

        $this->assertSame(1, $granted);
        $this->assertSame(['App:Articles:index:1'], $this->rows(1));
    }

    public function testReplaceWorksWithMoreIdsThanOneChunk(): void
    {
        $ids = range(1, 1200);
        $granted = $this->service->replaceRolePermissions(1, $ids);

        // Resources 1-3 are active; 4 is inactive; the rest do not exist.
        $this->assertSame(3, $granted);
        $this->assertCount(3, $this->rows(1));
    }

    public function testReplaceInvalidInputOnlyClearsRole(): void
    {
        $this->assertSame(0, $this->service->replaceRolePermissions(1, ['abc']));
        $this->assertSame([], $this->rows(1));
        $this->assertSame(['App:Articles:edit:1'], $this->rows(2));
    }

    public function testCopyReplacesTargetPermissions(): void
    {
        // Role 1 has index+delete; role 2 has edit (must disappear).
        $this->assertTrue($this->service->copyPermissions(1, 2));

        $this->assertSame(['App:Articles:delete:1', 'App:Articles:index:1'], $this->rows(2));
        $this->assertSame(['App:Articles:delete:1', 'App:Articles:index:1'], $this->rows(1));
    }

    public function testCopyWithEmptySourceClearsTarget(): void
    {
        $this->service->revokeAll(1);

        $this->assertTrue($this->service->copyPermissions(1, 2));
        $this->assertSame([], $this->rows(2));
    }

    public function testCopyRejectsSameRoleAndKeepsPermissions(): void
    {
        $this->assertFalse($this->service->copyPermissions(1, 1));

        $this->assertSame(['App:Articles:delete:1', 'App:Articles:index:1'], $this->rows(1));
    }

    public function testVersionIsStableAndIgnoresInsertionOrder(): void
    {
        $first = $this->service->getPermissionsVersion(1);

        $this->assertSame($first, $this->service->getPermissionsVersion(1));
        $this->assertMatchesRegularExpression('/^[0-9a-f]{40}$/', $first);
        // Same content for another role gives the same token (content based, not id based).
        $this->service->copyPermissions(1, 2);
        $this->assertSame($first, $this->service->getPermissionsVersion(2));
    }

    public function testVersionChangesWhenGrantIsAddedOrRemoved(): void
    {
        $before = $this->service->getPermissionsVersion(1);

        $this->service->grant(1, 'Articles', 'view');
        $added = $this->service->getPermissionsVersion(1);
        $this->assertNotSame($before, $added);

        $this->service->deny(1, 'Articles', 'view');
        $this->assertSame($before, $this->service->getPermissionsVersion(1));
    }

    public function testVersionOfRoleWithoutGrantsIsStable(): void
    {
        $this->service->revokeAll(1);

        $this->assertSame($this->service->getPermissionsVersion(1), $this->service->getPermissionsVersion(1));
        $this->assertNotSame($this->service->getPermissionsVersion(2), $this->service->getPermissionsVersion(1));
    }

    public function testReplaceWithStaleVersionThrowsAndLeavesRowsUntouched(): void
    {
        $stale = $this->service->getPermissionsVersion(1);
        $this->service->grant(1, 'Articles', 'view'); // another administrator saves meanwhile
        $before = $this->rows(1);

        try {
            $this->service->replaceRolePermissions(1, [2], $stale);
            $this->fail('Expected StalePermissionsException');
        } catch (StalePermissionsException) {
            $this->assertSame($before, $this->rows(1));
        }
    }

    public function testReplaceWithCurrentVersionSucceeds(): void
    {
        $version = $this->service->getPermissionsVersion(1);

        $this->assertSame(1, $this->service->replaceRolePermissions(1, [2], $version));
        $this->assertSame(['App:Articles:edit:1'], $this->rows(1));
    }

    public function testMatrixKeysAppPermissionsUnderApp(): void
    {
        $matrix = $this->service->getPermissionMatrix(1);

        $this->assertArrayHasKey('App', $matrix);
        $this->assertTrue($matrix['App']['Articles']['index']);
    }
}

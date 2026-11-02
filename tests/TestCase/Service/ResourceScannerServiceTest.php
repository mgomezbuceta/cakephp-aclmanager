<?php
declare(strict_types=1);

namespace AclManager\Test\TestCase\Service;

use Cake\TestSuite\TestCase;

class ResourceScannerServiceTest extends TestCase
{
    protected array $fixtures = [
        'plugin.AclManager.Resources',
    ];

    private FakeScanner $scanner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->getTableLocator()->get('AclManager.Resources')->deleteAll('1 = 1');
        $this->scanner = new FakeScanner();
    }

    private function res(string $controller, string $action, string $plugin = 'App'): array
    {
        return ['controller' => $controller, 'action' => $action, 'plugin' => $plugin, 'description' => 'x', 'active' => true];
    }

    /**
     * @return array<string, bool> "plugin:controller:action" => active
     */
    private function stored(): array
    {
        $rows = [];
        foreach ($this->getTableLocator()->get('AclManager.Resources')->find()->all() as $r) {
            $rows[sprintf('%s:%s:%s', $r->plugin, $r->controller, $r->action)] = (bool)$r->active;
        }
        ksort($rows);

        return $rows;
    }

    public function testRealScanTagsApplicationControllersWithAppPlugin(): void
    {
        $found = $this->scanner->scanTestApp();

        $this->assertNotEmpty($found);
        foreach ($found as $resource) {
            $this->assertSame('App', $resource['plugin']);
        }
    }

    public function testFirstSyncCreatesAppResourcesAndSecondCreatesNothing(): void
    {
        $this->scanner->found = [$this->res('Articles', 'index'), $this->res('Articles', 'edit')];

        $first = $this->scanner->scanAndSync();
        $second = $this->scanner->scanAndSync();

        $this->assertSame(['found' => 2, 'created' => 2, 'updated' => 0, 'deactivated' => 0], $first);
        $this->assertSame(['found' => 2, 'created' => 0, 'updated' => 0, 'deactivated' => 0], $second);
        $this->assertSame(['App:Articles:edit' => true, 'App:Articles:index' => true], $this->stored());
    }

    public function testSyncDeactivatesRemovedAndReactivatesReturned(): void
    {
        $this->scanner->found = [$this->res('Articles', 'index'), $this->res('Articles', 'edit')];
        $this->scanner->scanAndSync();

        $this->scanner->found = [$this->res('Articles', 'index')];
        $stats = $this->scanner->scanAndSync();
        $this->assertSame(['found' => 1, 'created' => 0, 'updated' => 0, 'deactivated' => 1], $stats);
        $this->assertSame(['App:Articles:edit' => false, 'App:Articles:index' => true], $this->stored());

        $this->scanner->found = [$this->res('Articles', 'index'), $this->res('Articles', 'edit')];
        $stats = $this->scanner->scanAndSync();
        $this->assertSame(['found' => 2, 'created' => 0, 'updated' => 1, 'deactivated' => 0], $stats);
        $this->assertSame(['App:Articles:edit' => true, 'App:Articles:index' => true], $this->stored());
    }

    public function testSyncKeepsPluginResourcesSeparateFromApp(): void
    {
        $this->scanner->found = [$this->res('Comments', 'view', 'Blog'), $this->res('Comments', 'view')];

        $stats = $this->scanner->scanAndSync();

        $this->assertSame(2, $stats['created']);
        $this->assertSame(['App:Comments:view' => true, 'Blog:Comments:view' => true], $this->stored());
    }

    private function legacy(int $id, ?string $plugin, bool $active): void
    {
        $this->getTableLocator()->get('AclManager.Resources')->getConnection()->insert('resources', [
            'id' => $id, 'controller' => 'Articles', 'action' => 'index', 'plugin' => $plugin,
            'description' => 'x', 'active' => $active, 'created' => '2025-01-01 00:00:00', 'modified' => '2025-01-01 00:00:00',
        ]);
    }

    /**
     * @return array<int, int> ids of active rows
     */
    private function activeIds(): array
    {
        $ids = $this->getTableLocator()->get('AclManager.Resources')->find()->where(['active' => true])->all()->extract('id')->toList();
        sort($ids);

        return $ids;
    }

    public function testDuplicateFoundEntriesAreCreatedOnlyOnce(): void
    {
        $this->scanner->found = [$this->res('Articles', 'index'), $this->res('Articles', 'index')];

        $stats = $this->scanner->scanAndSync();

        $this->assertSame(1, $stats['created']);
        $this->assertSame(['App:Articles:index' => true], $this->stored());
        $this->assertSame(1, $this->getTableLocator()->get('AclManager.Resources')->find()->count());
    }

    public function testLegacyNullAndAppRowsAreReactivatedAsOne(): void
    {
        $this->legacy(1, null, false);
        $this->legacy(2, 'App', false);
        $this->scanner->found = [$this->res('Articles', 'index')];

        $stats = $this->scanner->scanAndSync();

        $this->assertSame(['found' => 1, 'created' => 0, 'updated' => 1, 'deactivated' => 0], $stats);
        $this->assertCount(1, $this->activeIds());
        $this->assertSame(2, $this->getTableLocator()->get('AclManager.Resources')->find()->count());
    }

    public function testLegacyRowsAlreadyActiveAreLeftAlone(): void
    {
        $this->legacy(1, null, true);
        $this->legacy(2, 'App', false);
        $this->scanner->found = [$this->res('Articles', 'index')];

        $stats = $this->scanner->scanAndSync();

        $this->assertSame(['found' => 1, 'created' => 0, 'updated' => 0, 'deactivated' => 0], $stats);
        $this->assertSame([1], $this->activeIds());
    }

    public function testEveryActiveLegacyRowIsDeactivatedWhenNotFound(): void
    {
        $this->legacy(1, null, true);
        $this->legacy(2, 'App', true);
        $this->scanner->found = [];

        $stats = $this->scanner->scanAndSync();

        $this->assertSame(['found' => 0, 'created' => 0, 'updated' => 0, 'deactivated' => 2], $stats);
        $this->assertSame([], $this->activeIds());
    }
}

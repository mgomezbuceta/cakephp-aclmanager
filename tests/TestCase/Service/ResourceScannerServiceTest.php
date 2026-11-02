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
}

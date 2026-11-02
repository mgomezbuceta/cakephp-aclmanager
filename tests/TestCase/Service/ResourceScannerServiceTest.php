<?php
declare(strict_types=1);

namespace AclManager\Test\TestCase\Service;

use AclManager\Service\ResourceScannerService;
use AclManager\Test\TestApp\Model\Table\ThrowingResourcesTable;
use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\Locator\TableLocator;
use Cake\TestSuite\TestCase;
use RuntimeException;

class ResourceScannerServiceTest extends TestCase
{
    protected array $fixtures = [
        'plugin.AclManager.Resources',
    ];

    private FakeScanner $scanner;

    /**
     * @var mixed
     */
    private mixed $originalIgnore;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalIgnore = Configure::read('AclManager.ignoreActions');
        $this->getTableLocator()->get('AclManager.Resources')->deleteAll('1 = 1');
        $this->scanner = new FakeScanner();
    }

    protected function tearDown(): void
    {
        Configure::write('AclManager.ignoreActions', $this->originalIgnore);
        $this->removePlugins(['TestPlug']);
        parent::tearDown();
    }

    /**
     * Loads the test-only plugin and returns a real scanner built with the given ignore rules.
     *
     * @param array<int, string> $ignore Value of AclManager.ignoreActions
     */
    private function pluginScanner(array $ignore): PluginProbeScanner
    {
        $dir = TESTS . 'TestApp' . DS . 'Plugin' . DS . 'TestPlug' . DS;
        require_once $dir . 'src' . DS . 'TestPlugPlugin.php';
        require_once $dir . 'src' . DS . 'Controller' . DS . 'WidgetsController.php';
        require_once $dir . 'src' . DS . 'Controller' . DS . 'GadgetsController.php';
        $this->loadPlugins(['TestPlug' => ['path' => $dir]]);
        Configure::write('AclManager.ignoreActions', $ignore);

        return new PluginProbeScanner();
    }

    /**
     * @param array<int, array<string, mixed>> $found Scanner output
     * @return array<int, string> Sorted "controller:action" of the TestPlug plugin
     */
    private function testPlugEntries(array $found): array
    {
        $keys = [];
        foreach ($found as $r) {
            if ($r['plugin'] === 'TestPlug') {
                $keys[] = $r['controller'] . ':' . $r['action'];
            }
        }
        sort($keys);

        return $keys;
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

    public function testScannerUsesTheInjectedTableLocator(): void
    {
        $locator = new TableLocator();
        $locator->set('AclManager.Resources', new ThrowingResourcesTable([
            'alias' => 'Resources',
            'connection' => ConnectionManager::get('test'),
        ]));
        $scanner = new ResourceScannerService($locator);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(ThrowingResourcesTable::SECRET);
        $scanner->getGroupedResources();
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

    public function testScanFindsPublicActionsOfLoadedPluginControllers(): void
    {
        $found = $this->pluginScanner(['beforeFilter'])->scanLoaded();

        $this->assertSame(['Gadgets:view', 'Widgets:archive', 'Widgets:index'], $this->testPlugEntries($found));
        $descriptions = array_column(
            array_filter($found, fn($r) => $r['plugin'] === 'TestPlug' && $r['action'] === 'archive'),
            'description',
        );
        $this->assertSame(['TestPlug - Widgets - Archive'], array_values($descriptions));
    }

    public function testScanSkipsAnIgnoredPlugin(): void
    {
        $found = $this->pluginScanner(['TestPlug.*'])->scanLoaded();

        $this->assertSame([], $this->testPlugEntries($found));
    }

    public function testScanSkipsAnIgnoredPluginController(): void
    {
        $found = $this->pluginScanner(['TestPlug.Widgets/*'])->scanLoaded();

        $this->assertSame(['Gadgets:view'], $this->testPlugEntries($found));
    }

    public function testScanSkipsIgnoredActionsByNameAndByFullPattern(): void
    {
        $found = $this->pluginScanner(['index', 'TestPlug.Gadgets/view'])->scanLoaded();

        $this->assertSame(['Widgets:archive'], $this->testPlugEntries($found));
    }

    public function testUnreadableControllerDirectoryYieldsNoFiles(): void
    {
        if (function_exists('posix_geteuid') && posix_geteuid() === 0) {
            $this->markTestSkipped('Directory permissions are not enforced for root.');
        }
        $dir = TMP . 'unreadable_' . uniqid();
        mkdir($dir);
        chmod($dir, 0000);

        try {
            $this->assertSame([], (new PluginProbeScanner())->filesIn($dir));
        } finally {
            chmod($dir, 0755);
            rmdir($dir);
        }
    }

    public function testScanOfAMissingDirectoryFindsNothing(): void
    {
        $this->assertSame([], (new PluginProbeScanner())->scanPath(TMP . 'does_not_exist'));
    }

    public function testGroupedResourcesDropIgnoredPluginsControllersAndActions(): void
    {
        Configure::write('AclManager.ignoreActions', ['Hidden.*', 'Blog.Secrets/*', 'purge', 'Blog.Posts/remove']);
        $rows = [
            ['Pages', 'view', 'App'], ['Pages', 'purge', 'App'], ['Posts', 'index', 'Blog'], ['Posts', 'remove', 'Blog'],
            ['Secrets', 'index', 'Blog'], ['Anything', 'index', 'Hidden'],
        ];
        $table = $this->getTableLocator()->get('AclManager.Resources');
        foreach ($rows as [$controller, $action, $plugin]) {
            $table->saveOrFail($table->newEntity(
                ['controller' => $controller, 'action' => $action, 'plugin' => $plugin, 'active' => true],
                ['accessibleFields' => ['controller' => true, 'action' => true, 'plugin' => true, 'active' => true]],
            ));
        }

        $grouped = (new ResourceScannerService())->getGroupedResources();

        $summary = [];
        foreach ($grouped as $plugin => $controllers) {
            foreach ($controllers as $controller => $actions) {
                $summary[$plugin . '.' . $controller] = array_map(fn($r) => $r->action, $actions);
            }
        }
        ksort($summary);
        $this->assertSame(['App.Pages' => ['view'], 'Blog.Posts' => ['index']], $summary);
    }
}

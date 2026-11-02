<?php
declare(strict_types=1);

namespace AclManager\Test\TestCase\Migration;

use AclManager\Migration\AppPluginNormalization;
use Cake\Database\Connection;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase;
use RuntimeException;

class AppPluginNormalizationTest extends TestCase
{
    protected array $fixtures = [
        'plugin.AclManager.Roles',
        'plugin.AclManager.Resources',
        'plugin.AclManager.Permissions',
    ];

    private function db(): Connection
    {
        /** @var \Cake\Database\Connection $connection */
        $connection = ConnectionManager::get('test');

        return $connection;
    }

    /**
     * Adapts the SQLite connection to the callables a migration passes (fetchAll/execute).
     *
     * @return array{0: callable, 1: callable}
     */
    private function runners(): array
    {
        $db = $this->db();

        return [
            fn(string $sql): array => $db->execute($sql)->fetchAll('assoc') ?: [],
            fn(string $sql): mixed => $db->execute($sql),
        ];
    }

    private function column(string $sql): array
    {
        $rows = $this->db()->execute($sql)->fetchAll('num') ?: [];
        $values = array_map(fn(array $r): string => (string)$r[0], $rows);
        sort($values);

        return $values;
    }

    private function insertResource(int $id, string $action, ?string $plugin): void
    {
        $now = '2025-01-01 00:00:00';
        $this->db()->insert('resources', [
            'id' => $id, 'controller' => 'C', 'action' => $action, 'plugin' => $plugin,
            'active' => 1, 'created' => $now, 'modified' => $now,
        ]);
    }

    private function insertPermission(int $id, int $role, string $action, ?string $plugin): void
    {
        $now = '2025-01-01 00:00:00';
        $this->db()->insert('permissions', [
            'id' => $id, 'role_id' => $role, 'controller' => 'C', 'action' => $action, 'plugin' => $plugin,
            'allowed' => 1, 'created' => $now, 'modified' => $now,
        ]);
    }

    private function emptyTables(): void
    {
        $this->db()->execute('DELETE FROM resources');
        $this->db()->execute('DELETE FROM permissions');
    }

    public function testDuplicatesAbortBeforeAnyChange(): void
    {
        $this->emptyTables();
        $this->insertResource(1, 'dupNull', null);
        $this->insertResource(2, 'dupNull', null);
        $this->insertResource(3, 'hasApp', null);
        $this->insertResource(4, 'hasApp', 'App');
        $this->insertResource(5, 'solo', null);
        $this->insertPermission(1, 1, 'dupNull', null);
        $this->insertPermission(2, 1, 'dupNull', null);
        $this->insertPermission(3, 1, 'hasApp', null);
        $this->insertPermission(4, 1, 'hasApp', 'App');
        $this->insertPermission(5, 2, 'hasApp', null);

        $this->assertSame(
            ['resources' => 2, 'permissions' => 2],
            AppPluginNormalization::findCollisions($this->runners()[0]),
        );

        try {
            AppPluginNormalization::up(...$this->runners());
            $this->fail('Expected a RuntimeException');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('resources: 2', $e->getMessage());
            $this->assertStringContainsString('permissions: 2', $e->getMessage());
            $this->assertStringContainsString('docs/sql/cleanup-duplicates.sql', $e->getMessage());
            $this->assertStringContainsString('CHANGELOG', $e->getMessage());
        }

        $this->assertSame(['1', '2', '3', '4', '5'], $this->column('SELECT id FROM resources'));
        $this->assertSame(['1', '2', '3', '4', '5'], $this->column('SELECT id FROM permissions'));
        $this->assertSame(['1', '2', '3', '5'], $this->column('SELECT id FROM resources WHERE plugin IS NULL'));
        $this->assertSame(['1', '2', '3', '5'], $this->column('SELECT id FROM permissions WHERE plugin IS NULL'));
    }

    public function testCleanDataConvertsNullToApp(): void
    {
        $this->emptyTables();
        $this->insertResource(1, 'a', null);
        $this->insertResource(2, 'b', 'App');
        $this->insertResource(3, 'blog', 'Blog');
        $this->insertPermission(1, 1, 'a', null);
        $this->insertPermission(2, 2, 'a', null);
        $this->insertPermission(3, 1, 'blog', 'Blog');

        $this->assertSame(['resources' => 0, 'permissions' => 0], AppPluginNormalization::findCollisions($this->runners()[0]));
        AppPluginNormalization::up(...$this->runners());

        $this->assertSame(['1', '2', '3'], $this->column('SELECT id FROM resources'));
        $this->assertSame(['App', 'App', 'Blog'], $this->column('SELECT plugin FROM resources'));
        $this->assertSame(['1', '2', '3'], $this->column('SELECT id FROM permissions'));
        $this->assertSame([], $this->column('SELECT id FROM permissions WHERE plugin IS NULL'));
    }

    public function testDownRevertsResourcesOnly(): void
    {
        $this->emptyTables();
        $this->insertResource(1, 'a', 'App');
        $this->insertPermission(1, 1, 'a', 'App');

        foreach (AppPluginNormalization::down() as $sql) {
            $this->db()->execute($sql);
        }

        $this->assertSame([], $this->column("SELECT id FROM resources WHERE plugin = 'App'"));
        $this->assertSame(['1'], $this->column("SELECT id FROM permissions WHERE plugin = 'App'"));
    }

    public function testClassNeverContainsDeleteStatements(): void
    {
        $source = (string)file_get_contents(dirname(__DIR__, 3) . '/src/Migration/AppPluginNormalization.php');
        $this->assertDoesNotMatchRegularExpression('/DELETE\s+FROM/i', $source);
        foreach (array_merge(AppPluginNormalization::statements(), AppPluginNormalization::down()) as $sql) {
            $this->assertStringNotContainsStringIgnoringCase('DELETE', $sql);
        }
    }
}

<?php
declare(strict_types=1);

namespace AclManager\Test\TestCase\Migration;

use AclManager\Migration\AppPluginNormalization;
use AclManager\Migration\SchemaCompatibility;
use Cake\Database\Connection;
use Cake\Database\Driver\Sqlite;
use Cake\TestSuite\TestCase;
use PDOException;

/**
 * Runs the documented SQLite schema on a fresh database. MySQL and PostgreSQL scripts are not executed in CI.
 */
class SqlDocsTest extends TestCase
{
    private Connection $db;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = new Connection(['driver' => Sqlite::class, 'database' => ':memory:']);
        $sql = (string)file_get_contents(dirname(__DIR__, 3) . '/docs/sql/sqlite.sql');
        $this->assertNotSame('', $sql);
        $this->runScript($sql);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchAll(string $sql): array
    {
        return $this->db->execute($sql)->fetchAll('assoc') ?: [];
    }

    private function runScript(string $sql): void
    {
        $sql = (string)preg_replace('/^\s*--.*$/m', '', $sql);
        foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
            $this->db->execute($statement);
        }
    }

    public function testTablesAndRequiredColumnsExist(): void
    {
        foreach (SchemaCompatibility::REQUIRED as $table => $required) {
            $columns = array_column($this->db->execute("PRAGMA table_info($table)")->fetchAll('assoc'), 'name');
            $this->assertContains('id', $columns, $table);
            $this->assertSame([], SchemaCompatibility::missingColumns($table, $columns), $table);
        }
    }

    public function testUniqueIndexesExist(): void
    {
        $rows = $this->db->execute("SELECT name, sql FROM sqlite_master WHERE type = 'index'")->fetchAll('assoc');
        $indexes = array_column($rows, 'sql', 'name');
        foreach (['idx_roles_name_unique', 'idx_permissions_unique', 'idx_resources_unique'] as $name) {
            $this->assertArrayHasKey($name, $indexes);
            $this->assertStringContainsStringIgnoringCase('UNIQUE', (string)$indexes[$name]);
        }
    }

    public function testCleanupScriptRemovesEveryCollision(): void
    {
        $now = '2025-01-01 00:00:00';
        $res = fn(string $action, ?string $plugin) => $this->db->insert('resources', [
            'controller' => 'C', 'action' => $action, 'plugin' => $plugin, 'created' => $now, 'modified' => $now,
        ]);
        $res('dup', null);
        $res('dup', null);
        $res('both', null);
        $res('both', 'App');
        $res('solo', null);
        $res('empty', '');
        $res('empty', 'App');
        $this->db->insert('roles', ['name' => 'r', 'created' => $now, 'modified' => $now]);
        foreach ([null, null] as $plugin) {
            $this->db->insert('permissions', [
                'role_id' => 1, 'controller' => 'C', 'action' => 'dup', 'plugin' => $plugin, 'created' => $now, 'modified' => $now,
            ]);
        }
        $this->assertSame(['resources' => 3, 'permissions' => 1], AppPluginNormalization::findCollisions($this->fetchAll(...)));

        $this->runScript((string)file_get_contents(dirname(__DIR__, 3) . '/docs/sql/cleanup-duplicates.sql'));

        $this->assertSame(['resources' => 0, 'permissions' => 0], AppPluginNormalization::findCollisions($this->fetchAll(...)));
        $this->assertSame(
            2,
            (int)$this->db->execute('SELECT COUNT(*) FROM resources WHERE plugin IS NULL')->fetchColumn(0),
        );
    }

    public function testUniqueIndexRejectsDuplicateAppResource(): void
    {
        $now = '2025-01-01 00:00:00';
        $row = ['controller' => 'C', 'action' => 'a', 'plugin' => 'App', 'created' => $now, 'modified' => $now];
        $this->db->insert('resources', $row);
        $this->expectException(PDOException::class);
        $this->db->insert('resources', $row);
    }
}

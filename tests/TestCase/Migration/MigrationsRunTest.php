<?php
declare(strict_types=1);

namespace AclManager\Test\TestCase\Migration;

use AclManager\AclManagerPlugin;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Database\Connection;
use Cake\Database\Driver\Sqlite;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase;
use Migrations\Migrations;
use RuntimeException;

/**
 * Runs the real plugin migrations in-process against a temporary SQLite file.
 */
class MigrationsRunTest extends TestCase
{
    private const CONNECTION = 'aclmanager_migrations';
    private const LAST = 20250101000004;

    private string $dbFile;
    private bool $addedPlugin = false;

    protected function setUp(): void
    {
        parent::setUp();
        if (!class_exists(Migrations::class)) {
            $this->markTestSkipped('cakephp/migrations is not installed.');
        }
        if (!Plugin::isLoaded('AclManager')) {
            Plugin::getCollection()->add(new AclManagerPlugin(['path' => ROOT . DS]));
            $this->addedPlugin = true;
        }
        $this->dbFile = sys_get_temp_dir() . '/aclmanager_migrations_' . uniqid() . '.sqlite';
        ConnectionManager::setConfig(self::CONNECTION, [
            'className' => Connection::class,
            'driver' => Sqlite::class,
            'database' => $this->dbFile,
        ]);
        Configure::delete('AclManager.migrations.adoptExistingTables');
    }

    protected function tearDown(): void
    {
        if (ConnectionManager::getConfig(self::CONNECTION)) {
            ConnectionManager::get(self::CONNECTION)->getDriver()->disconnect();
            ConnectionManager::drop(self::CONNECTION);
        }
        if (isset($this->dbFile) && is_file($this->dbFile)) {
            unlink($this->dbFile);
        }
        Configure::delete('AclManager.migrations.adoptExistingTables');
        if ($this->addedPlugin) {
            Plugin::getCollection()->remove('AclManager');
        }
        parent::tearDown();
    }

    private function migrations(): Migrations
    {
        return new Migrations(['connection' => self::CONNECTION, 'plugin' => 'AclManager']);
    }

    private function db(): Connection
    {
        /** @var \Cake\Database\Connection $connection */
        $connection = ConnectionManager::get(self::CONNECTION);

        return $connection;
    }

    /**
     * @return array<int, string>
     */
    private function tables(): array
    {
        $rows = $this->db()->execute(
            "SELECT name FROM sqlite_master WHERE type = 'table' AND name IN ('roles', 'permissions', 'resources') ORDER BY name",
        )->fetchAll('num') ?: [];

        return array_map(fn(array $r): string => $r[0], $rows);
    }

    /**
     * @return array<int, string>
     */
    private function columns(string $table): array
    {
        return array_column($this->db()->execute("PRAGMA table_info($table)")->fetchAll('assoc'), 'name');
    }

    private function createHostRoles(): void
    {
        $this->db()->execute('CREATE TABLE roles (id INTEGER PRIMARY KEY AUTOINCREMENT, name VARCHAR(100) NOT NULL)');
        $this->db()->execute("INSERT INTO roles (name) VALUES ('host-role')");
    }

    public function testFreshMigrateCreatesTablesAndFullRollbackRemovesThem(): void
    {
        $this->migrations()->migrate();
        $this->assertSame(['permissions', 'resources', 'roles'], $this->tables());

        $this->migrations()->rollback(['target' => 0]);
        $this->assertSame([], $this->tables());
    }

    public function testIncompatibleHostRolesWithoutFlagThrowsAndStaysUntouched(): void
    {
        $this->createHostRoles();

        try {
            $this->migrations()->migrate();
            $this->fail('Expected a RuntimeException');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('description, priority, active, created, modified', $e->getMessage());
            $this->assertStringContainsString('adoptExistingTables', $e->getMessage());
        }

        $this->assertSame(['id', 'name'], $this->columns('roles'));
        $this->assertSame(['roles'], $this->tables());
    }

    public function testAdoptModeAddsColumnsKeepsRowsAndRollbackKeepsHostTable(): void
    {
        $this->createHostRoles();
        Configure::write('AclManager.migrations.adoptExistingTables', true);

        $this->migrations()->migrate();
        $this->assertSame(
            ['id', 'name', 'description', 'priority', 'active', 'created', 'modified'],
            $this->columns('roles'),
        );
        $this->assertSame(['permissions', 'resources', 'roles'], $this->tables());

        $this->migrations()->rollback(['target' => 0]);

        $this->assertSame(['roles'], $this->tables());
        $names = $this->db()->execute('SELECT name FROM roles')->fetchAll('num') ?: [];
        $this->assertSame([['host-role']], $names);
    }

    public function testNormalizationAbortsOnCollisionsIncludingEmptyStringPlugin(): void
    {
        $this->migrations()->migrate(['target' => self::LAST - 1]);
        $now = '2025-01-01 00:00:00';
        $this->db()->execute(
            "INSERT INTO resources (controller, action, plugin, active, created, modified) VALUES
             ('C', 'a', NULL, 1, '$now', '$now'), ('C', 'a', 'App', 1, '$now', '$now'),
             ('C', 'b', '', 1, '$now', '$now'), ('C', 'b', 'App', 1, '$now', '$now')",
        );

        try {
            $this->migrations()->migrate();
            $this->fail('Expected a RuntimeException');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('resources: 2', $e->getMessage());
        }

        $plugins = $this->db()->execute('SELECT action, plugin FROM resources ORDER BY id')->fetchAll('num') ?: [];
        $this->assertSame([['a', null], ['a', 'App'], ['b', ''], ['b', 'App']], $plugins);
    }
}

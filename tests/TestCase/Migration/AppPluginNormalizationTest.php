<?php
declare(strict_types=1);

namespace AclManager\Test\TestCase\Migration;

use AclManager\Migration\AppPluginNormalization;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\TestCase;

class AppPluginNormalizationTest extends TestCase
{
    protected array $fixtures = [
        'plugin.AclManager.Roles',
        'plugin.AclManager.Resources',
        'plugin.AclManager.Permissions',
    ];

    private function execAll(array $statements): void
    {
        $connection = ConnectionManager::get('test');
        foreach ($statements as $sql) {
            $connection->execute($sql);
        }
    }

    private function column(string $sql): array
    {
        $rows = ConnectionManager::get('test')->execute($sql)->fetchAll('num') ?: [];
        $values = array_map(fn(array $r): string => (string)$r[0], $rows);
        sort($values);

        return $values;
    }

    public function testUpDedupesThenConvertsNullToApp(): void
    {
        $connection = ConnectionManager::get('test');
        $connection->execute('DELETE FROM resources');
        $connection->execute('DELETE FROM permissions');
        $now = "'2025-01-01 00:00:00'";
        $res = fn(int $id, string $a, ?string $plugin): string => sprintf(
            "INSERT INTO resources (id, controller, action, plugin, active, created, modified) VALUES (%d, 'C', '%s', %s, 1, %s, %s)",
            $id,
            $a,
            $plugin === null ? 'NULL' : "'$plugin'",
            $now,
            $now,
        );
        $perm = fn(int $id, int $role, string $a, ?string $plugin): string => sprintf(
            "INSERT INTO permissions (id, role_id, controller, action, plugin, allowed, created, modified) VALUES (%d, %d, 'C', '%s', %s, 1, %s, %s)",
            $id,
            $role,
            $a,
            $plugin === null ? 'NULL' : "'$plugin'",
            $now,
            $now,
        );
        $this->execAll([
            $res(1, 'dupNull', null),
            $res(2, 'dupNull', null),
            $res(3, 'hasApp', null),
            $res(4, 'hasApp', 'App'),
            $res(5, 'solo', null),
            $res(6, 'blog', 'Blog'),
            $perm(1, 1, 'dupNull', null),
            $perm(2, 1, 'dupNull', null),
            $perm(3, 1, 'hasApp', null),
            $perm(4, 1, 'hasApp', 'App'),
            $perm(5, 2, 'hasApp', null),
            $perm(6, 1, 'blog', 'Blog'),
        ]);

        $this->execAll(AppPluginNormalization::up());

        $this->assertSame(['1', '4', '5', '6'], $this->column('SELECT id FROM resources'));
        $this->assertSame(
            ['App', 'App', 'App', 'Blog'],
            $this->column('SELECT plugin FROM resources'),
        );
        $this->assertSame(['1', '4', '5', '6'], $this->column('SELECT id FROM permissions'));
        $this->assertSame([], $this->column('SELECT id FROM permissions WHERE plugin IS NULL'));
    }

    public function testDownRevertsResourcesOnly(): void
    {
        $this->execAll(AppPluginNormalization::down());

        $this->assertSame([], $this->column("SELECT id FROM resources WHERE plugin = 'App'"));
        $this->assertNotSame([], $this->column("SELECT id FROM permissions WHERE plugin = 'App'"));
    }
}

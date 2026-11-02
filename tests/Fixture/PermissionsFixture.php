<?php
declare(strict_types=1);

namespace AclManager\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class PermissionsFixture extends TestFixture
{
    public array $records = [
        // Role 1 (Admin): stale grants that a save must replace.
        ['id' => 1, 'role_id' => 1, 'controller' => 'Articles', 'action' => 'index', 'plugin' => 'App', 'allowed' => true, 'created' => '2025-01-01 00:00:00', 'modified' => '2025-01-01 00:00:00'],
        ['id' => 2, 'role_id' => 1, 'controller' => 'Articles', 'action' => 'delete', 'plugin' => 'App', 'allowed' => true, 'created' => '2025-01-01 00:00:00', 'modified' => '2025-01-01 00:00:00'],
        // Role 2 (Editor): must never be touched by saves for role 1.
        ['id' => 3, 'role_id' => 2, 'controller' => 'Articles', 'action' => 'edit', 'plugin' => 'App', 'allowed' => true, 'created' => '2025-01-01 00:00:00', 'modified' => '2025-01-01 00:00:00'],
    ];
}

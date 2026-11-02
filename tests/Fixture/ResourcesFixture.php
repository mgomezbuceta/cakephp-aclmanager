<?php
declare(strict_types=1);

namespace AclManager\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class ResourcesFixture extends TestFixture
{
    public array $records = [
        ['id' => 1, 'controller' => 'Articles', 'action' => 'index', 'plugin' => 'App', 'description' => null, 'active' => true, 'created' => '2025-01-01 00:00:00', 'modified' => '2025-01-01 00:00:00'],
        ['id' => 2, 'controller' => 'Articles', 'action' => 'edit', 'plugin' => 'App', 'description' => null, 'active' => true, 'created' => '2025-01-01 00:00:00', 'modified' => '2025-01-01 00:00:00'],
        ['id' => 3, 'controller' => 'Comments', 'action' => 'view', 'plugin' => 'Blog', 'description' => null, 'active' => true, 'created' => '2025-01-01 00:00:00', 'modified' => '2025-01-01 00:00:00'],
        ['id' => 4, 'controller' => 'Articles', 'action' => 'delete', 'plugin' => 'App', 'description' => null, 'active' => false, 'created' => '2025-01-01 00:00:00', 'modified' => '2025-01-01 00:00:00'],
    ];
}

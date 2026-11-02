<?php
declare(strict_types=1);

namespace AclManager\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class RolesFixture extends TestFixture
{
    public array $records = [
        ['id' => 1, 'name' => 'Admin', 'description' => null, 'priority' => 10, 'active' => true, 'created' => '2025-01-01 00:00:00', 'modified' => '2025-01-01 00:00:00'],
        ['id' => 2, 'name' => 'Editor', 'description' => null, 'priority' => 5, 'active' => true, 'created' => '2025-01-01 00:00:00', 'modified' => '2025-01-01 00:00:00'],
    ];
}

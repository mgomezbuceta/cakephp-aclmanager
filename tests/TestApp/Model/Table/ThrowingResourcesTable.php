<?php
declare(strict_types=1);

namespace AclManager\Test\TestApp\Model\Table;

use AclManager\Model\Table\ResourcesTable;
use Cake\ORM\Query\SelectQuery;
use RuntimeException;

/**
 * Resources table whose reads fail with a message that must never reach the user.
 */
class ThrowingResourcesTable extends ResourcesTable
{
    public const SECRET = 'SQLSTATE[HY000] password=hunter2';

    public function find(string $type = 'all', mixed ...$args): SelectQuery
    {
        throw new RuntimeException(self::SECRET);
    }
}

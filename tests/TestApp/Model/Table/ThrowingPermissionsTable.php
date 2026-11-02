<?php
declare(strict_types=1);

namespace AclManager\Test\TestApp\Model\Table;

use AclManager\Model\Table\PermissionsTable;
use RuntimeException;

/**
 * Permissions table whose writes fail with a message that must never reach the user.
 */
class ThrowingPermissionsTable extends PermissionsTable
{
    public const SECRET = 'SQLSTATE[HY000] password=hunter2';

    public function deleteAll(mixed $conditions): int
    {
        throw new RuntimeException(self::SECRET);
    }
}

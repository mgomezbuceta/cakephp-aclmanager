<?php
declare(strict_types=1);

namespace AclManager\Service;

use RuntimeException;

/**
 * Thrown when a role's permissions changed after the form that is being saved was loaded.
 */
class StalePermissionsException extends RuntimeException
{
}

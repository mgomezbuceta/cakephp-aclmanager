<?php
declare(strict_types=1);

namespace AclManager\Migration;

use InvalidArgumentException;
use RuntimeException;

/**
 * Decides what migrations 0001-0003 may do with a table that may already exist
 *
 * Existing host tables are never altered unless `AclManager.migrations.adoptExistingTables`
 * is true. Required column lists mirror migration 20250101000001.
 */
final class SchemaCompatibility
{
    public const CREATE = 'create';
    public const SKIP = 'skip';
    public const ADOPT = 'adopt';

    /**
     * Columns (besides `id`) each table needs
     *
     * @var array<string, array<int, string>>
     */
    public const REQUIRED = [
        'roles' => ['name', 'description', 'priority', 'active', 'created', 'modified'],
        'permissions' => ['role_id', 'controller', 'action', 'plugin', 'allowed', 'created', 'modified'],
        'resources' => ['controller', 'action', 'plugin', 'description', 'active', 'created', 'modified'],
    ];

    /**
     * @param string $table One of roles, permissions, resources
     * @param array<int, string> $existingColumns Column names currently in the table
     * @return array<int, string> Required columns that are absent
     */
    public static function missingColumns(string $table, array $existingColumns): array
    {
        if (!isset(self::REQUIRED[$table])) {
            throw new InvalidArgumentException("Unknown AclManager table '{$table}'.");
        }

        return array_values(array_diff(self::REQUIRED[$table], $existingColumns));
    }

    /**
     * @param string $table One of roles, permissions, resources
     * @param bool $exists Whether the table already exists
     * @param array<int, string> $existingColumns Column names currently in the table
     * @param bool $adopt Value of AclManager.migrations.adoptExistingTables
     * @return string CREATE (table absent), SKIP (leave untouched) or ADOPT (add missing columns)
     * @throws \RuntimeException When the table exists, is incompatible and adoption is not enabled
     */
    public static function decide(string $table, bool $exists, array $existingColumns, bool $adopt): string
    {
        if (!$exists) {
            return self::CREATE;
        }
        if ($adopt) {
            return self::ADOPT;
        }

        $missing = self::missingColumns($table, $existingColumns);
        if ($missing === []) {
            return self::SKIP;
        }

        throw new RuntimeException(sprintf(
            "Table '%s' already exists and is missing required columns: %s. "
            . 'Migrations do not alter existing tables by default. Either set '
            . "Configure::write('AclManager.migrations.adoptExistingTables', true) to let the "
            . 'migrations add them, or apply the SQL documented in docs/sql/ manually.',
            $table,
            implode(', ', $missing),
        ));
    }
}

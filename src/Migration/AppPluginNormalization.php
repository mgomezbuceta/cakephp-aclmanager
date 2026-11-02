<?php
declare(strict_types=1);

namespace AclManager\Migration;

use RuntimeException;

/**
 * Logic used by migration 20250101000004_NormalizeAppPlugin
 *
 * Kept in a class so it can be run against the test database. This class never removes
 * rows: when converting NULL plugin rows to 'App' would collide with another row, it
 * aborts before touching anything and points to the documented cleanup SQL.
 */
final class AppPluginNormalization
{
    /**
     * Count rows with an empty plugin (NULL or '') that would collide once mapped to 'App'
     *
     * A row collides when an 'App' row has the same key, or another empty-plugin row with a lower id does.
     *
     * @param callable(string): array<int, array<int|string, mixed>> $fetchAll Returns rows readable by column alias
     * @return array{resources: int, permissions: int}
     */
    public static function findCollisions(callable $fetchAll): array
    {
        $count = static function (string $table, array $keys) use ($fetchAll): int {
            $same = implode(' AND ', array_map(fn(string $k): string => "x.$k = r.$k", $keys));
            $sql = "SELECT COUNT(*) AS n FROM $table r WHERE (r.plugin IS NULL OR r.plugin = '') AND ("
                . "EXISTS (SELECT 1 FROM $table x WHERE x.plugin = 'App' AND $same)"
                . " OR EXISTS (SELECT 1 FROM $table x WHERE (x.plugin IS NULL OR x.plugin = '') AND x.id < r.id AND $same))";

            $row = $fetchAll($sql)[0] ?? [];

            return (int)($row['n'] ?? $row[0] ?? 0);
        };

        return [
            'resources' => $count('resources', ['controller', 'action']),
            'permissions' => $count('permissions', ['role_id', 'controller', 'action']),
        ];
    }

    /**
     * Convert empty-plugin rows (NULL or '') to 'App', or throw before any change when rows would collide
     *
     * @param callable(string): array<int, array<int|string, mixed>> $fetchAll Returns rows readable by column alias
     * @param callable(string): mixed $execute Runs a statement
     * @return void
     * @throws \RuntimeException When duplicates must be cleaned up manually first
     */
    public static function up(callable $fetchAll, callable $execute): void
    {
        $collisions = self::findCollisions($fetchAll);
        if (array_sum($collisions) > 0) {
            throw new RuntimeException(sprintf(
                'Migration NormalizeAppPlugin aborted, nothing was changed. '
                . "Converting an empty plugin (NULL or '') to 'App' would collide with existing rows "
                . '(resources: %d, permissions: %d). '
                . 'Review and clean the duplicates with docs/sql/cleanup-duplicates.sql '
                . '(back up first), then run the migration again. See the CHANGELOG for details.',
                $collisions['resources'],
                $collisions['permissions'],
            ));
        }

        foreach (self::statements() as $sql) {
            $execute($sql);
        }
    }

    /**
     * Statements that turn NULL plugin rows into 'App'
     *
     * @return array<int, string>
     */
    public static function statements(): array
    {
        return [
            "UPDATE resources SET plugin = 'App' WHERE plugin IS NULL OR plugin = ''",
            "UPDATE permissions SET plugin = 'App' WHERE plugin IS NULL OR plugin = ''",
        ];
    }

    /**
     * Statements that revert resources to the previous NULL representation
     *
     * Permissions stay 'App': that is what the application used before this migration.
     *
     * @return array<int, string>
     */
    public static function down(): array
    {
        return ["UPDATE resources SET plugin = NULL WHERE plugin = 'App'"];
    }
}

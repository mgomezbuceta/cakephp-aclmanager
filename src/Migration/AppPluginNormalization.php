<?php
declare(strict_types=1);

namespace AclManager\Migration;

/**
 * SQL used by migration 20250101000004_NormalizeAppPlugin
 *
 * Kept in a class so the exact statements can be run against the test database.
 * Subqueries use derived tables with GROUP BY/DISTINCT so MySQL does not reject
 * them with "can't specify target table for update in FROM clause" (error 1093).
 */
final class AppPluginNormalization
{
    /**
     * Statements that turn NULL plugin rows into 'App'
     *
     * NULL rows that would collide with another row once mapped to 'App' are deleted first
     * (lowest id wins), otherwise the unique indexes would abort the conversion.
     *
     * @return array<int, string>
     */
    public static function up(): array
    {
        return [
            // resources: NULL row duplicated by an existing 'App' row
            "DELETE FROM resources WHERE plugin IS NULL AND EXISTS (
                SELECT 1 FROM (SELECT DISTINCT controller, action FROM resources WHERE plugin = 'App') AS a
                WHERE a.controller = resources.controller AND a.action = resources.action
            )",
            // resources: several NULL rows for the same controller/action, keep the lowest id
            'DELETE FROM resources WHERE plugin IS NULL AND id NOT IN (
                SELECT keep_id FROM (SELECT MIN(id) AS keep_id FROM resources WHERE plugin IS NULL GROUP BY controller, action) AS k
            )',
            "UPDATE resources SET plugin = 'App' WHERE plugin IS NULL",
            // permissions: same, per role
            "DELETE FROM permissions WHERE plugin IS NULL AND EXISTS (
                SELECT 1 FROM (SELECT DISTINCT role_id, controller, action FROM permissions WHERE plugin = 'App') AS a
                WHERE a.role_id = permissions.role_id AND a.controller = permissions.controller AND a.action = permissions.action
            )",
            'DELETE FROM permissions WHERE plugin IS NULL AND id NOT IN (
                SELECT keep_id FROM (SELECT MIN(id) AS keep_id FROM permissions WHERE plugin IS NULL GROUP BY role_id, controller, action) AS k
            )',
            "UPDATE permissions SET plugin = 'App' WHERE plugin IS NULL",
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

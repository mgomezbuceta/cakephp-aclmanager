<?php
declare(strict_types=1);

use AclManager\Migration\AppPluginNormalization;
use Migrations\BaseMigration;

/**
 * NormalizeAppPlugin migration
 *
 * Application-level resources used a NULL plugin while permissions used 'App', so
 * lookups by the policy never matched resource rows and the nullable column let
 * duplicates slip past the unique indexes. 'App' is now the single canonical value.
 *
 * This migration never deletes data: if converting would create duplicates it aborts
 * before changing anything (see docs/sql/cleanup-duplicates.sql).
 */
class NormalizeAppPlugin extends BaseMigration
{
    /**
     * @return void
     */
    public function up(): void
    {
        AppPluginNormalization::up(
            fn(string $sql): array => $this->fetchAll($sql),
            fn(string $sql): mixed => $this->execute($sql),
        );
    }

    /**
     * @return void
     */
    public function down(): void
    {
        foreach (AppPluginNormalization::down() as $sql) {
            $this->execute($sql);
        }
    }
}

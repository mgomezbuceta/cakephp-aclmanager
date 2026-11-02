<?php
declare(strict_types=1);

use AclManager\Migration\SchemaCompatibility;
use Migrations\BaseMigration;

/**
 * AddMissingColumnsToRoles migration
 *
 * Adds missing columns to existing roles table without losing data
 */
class AddMissingColumnsToRoles extends BaseMigration
{
    /**
     * Adds what is missing when adoption is enabled.
     *
     * @return void
     */
    public function up(): void
    {
        // Migration 0002 already adds these columns when adoption is enabled and 0001 creates
        // them on fresh installs, so this is a no-op unless the table is still missing some.
        // Existing tables are only altered when AclManager.migrations.adoptExistingTables is true.
        $decision = SchemaCompatibility::decideForMigration($this, 'roles');
        if ($decision !== SchemaCompatibility::ADOPT) {
            return;
        }

        $table = $this->table('roles');

        // Add description column if it doesn't exist
        if (!$table->hasColumn('description')) {
            $table->addColumn('description', 'text', [
                'null' => true,
                'after' => 'name',
            ]);
        }

        // Add priority column if it doesn't exist
        if (!$table->hasColumn('priority')) {
            $table->addColumn('priority', 'integer', [
                'default' => 0,
                'null' => false,
                'comment' => 'Higher priority = more permissions',
            ]);
        }

        // Add active column if it doesn't exist
        if (!$table->hasColumn('active')) {
            $table->addColumn('active', 'boolean', [
                'default' => true,
                'null' => false,
            ]);
        }

        $table->update();
    }

    /**
     * Intentionally a no-op.
     *
     * Only columns/indexes missing from a host table are added, and it is not recorded which
     * ones, so removing anything here could destroy host data. Migration 0001 owns the tables.
     *
     * @return void
     */
    public function down(): void
    {
    }
}

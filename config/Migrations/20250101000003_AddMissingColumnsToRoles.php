<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * AddMissingColumnsToRoles migration
 *
 * Adds missing columns to existing roles table without losing data
 */
class AddMissingColumnsToRoles extends AbstractMigration
{
    /**
     * Change Method.
     *
     * @return void
     */
    public function change(): void
    {
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
}

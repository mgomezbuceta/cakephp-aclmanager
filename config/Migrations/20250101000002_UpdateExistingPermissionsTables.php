<?php
declare(strict_types=1);

use AclManager\Migration\SchemaCompatibility;
use Migrations\BaseMigration;

/**
 * UpdateExistingPermissionsTables migration
 *
 * Updates existing tables to match the Authorization Manager schema without losing data.
 * Only runs when AclManager.migrations.adoptExistingTables is true; otherwise an existing
 * incompatible table makes it throw and compatible tables are left untouched.
 *
 * Adoption adds missing COLUMNS only. Indexes and foreign keys are never added to a host table:
 * they could fail on host data, and migration 0001 treats its own unique indexes as the marker
 * that it created a table (so rollback never drops an adopted one).
 */
class UpdateExistingPermissionsTables extends BaseMigration
{
    /**
     * Adds what is missing when adoption is enabled.
     *
     * @return void
     */
    public function up(): void
    {
        // Check and update roles table
        if (SchemaCompatibility::decideForMigration($this, 'roles') === SchemaCompatibility::ADOPT) {
            $roles = $this->table('roles');

            // Add missing columns if they don't exist
            if (!$roles->hasColumn('name')) {
                $roles->addColumn('name', 'string', [
                    'limit' => 100,
                    'null' => false,
                    'after' => 'id',
                ])->update();
            }

            if (!$roles->hasColumn('description')) {
                $roles->addColumn('description', 'text', [
                    'null' => true,
                    'after' => 'name',
                ])->update();
            }

            if (!$roles->hasColumn('priority')) {
                $roles->addColumn('priority', 'integer', [
                    'default' => 0,
                    'null' => false,
                    'comment' => 'Higher priority = more permissions',
                    'after' => 'description',
                ])->update();
            }

            if (!$roles->hasColumn('active')) {
                $roles->addColumn('active', 'boolean', [
                    'default' => true,
                    'null' => false,
                    'after' => 'priority',
                ])->update();
            }

            if (!$roles->hasColumn('created')) {
                $roles->addColumn('created', 'datetime', [
                    'null' => false,
                    'default' => 'CURRENT_TIMESTAMP',
                ])->update();
            }

            if (!$roles->hasColumn('modified')) {
                $roles->addColumn('modified', 'datetime', [
                    'null' => false,
                    'default' => 'CURRENT_TIMESTAMP',
                    'update' => 'CURRENT_TIMESTAMP',
                ])->update();
            }
        }

        // Check and update permissions table
        if (SchemaCompatibility::decideForMigration($this, 'permissions') === SchemaCompatibility::ADOPT) {
            $permissions = $this->table('permissions');

            if (!$permissions->hasColumn('role_id')) {
                $permissions->addColumn('role_id', 'integer', [
                    'null' => false,
                    'after' => 'id',
                ])->update();
            }

            if (!$permissions->hasColumn('controller')) {
                $permissions->addColumn('controller', 'string', [
                    'limit' => 100,
                    'null' => false,
                    'after' => 'role_id',
                ])->update();
            }

            if (!$permissions->hasColumn('action')) {
                $permissions->addColumn('action', 'string', [
                    'limit' => 100,
                    'null' => false,
                    'after' => 'controller',
                ])->update();
            }

            if (!$permissions->hasColumn('plugin')) {
                $permissions->addColumn('plugin', 'string', [
                    'limit' => 100,
                    'null' => true,
                    'after' => 'action',
                ])->update();
            }

            if (!$permissions->hasColumn('allowed')) {
                $permissions->addColumn('allowed', 'boolean', [
                    'default' => true,
                    'null' => false,
                    'after' => 'plugin',
                ])->update();
            }

            if (!$permissions->hasColumn('created')) {
                $permissions->addColumn('created', 'datetime', [
                    'null' => false,
                    'default' => 'CURRENT_TIMESTAMP',
                ])->update();
            }

            if (!$permissions->hasColumn('modified')) {
                $permissions->addColumn('modified', 'datetime', [
                    'null' => false,
                    'default' => 'CURRENT_TIMESTAMP',
                    'update' => 'CURRENT_TIMESTAMP',
                ])->update();
            }
        }

        // Check and update resources table
        if (SchemaCompatibility::decideForMigration($this, 'resources') === SchemaCompatibility::ADOPT) {
            $resources = $this->table('resources');

            if (!$resources->hasColumn('controller')) {
                $resources->addColumn('controller', 'string', [
                    'limit' => 100,
                    'null' => false,
                    'after' => 'id',
                ])->update();
            }

            if (!$resources->hasColumn('action')) {
                $resources->addColumn('action', 'string', [
                    'limit' => 100,
                    'null' => false,
                    'after' => 'controller',
                ])->update();
            }

            if (!$resources->hasColumn('plugin')) {
                $resources->addColumn('plugin', 'string', [
                    'limit' => 100,
                    'null' => true,
                    'after' => 'action',
                ])->update();
            }

            if (!$resources->hasColumn('description')) {
                $resources->addColumn('description', 'text', [
                    'null' => true,
                    'after' => 'plugin',
                ])->update();
            }

            if (!$resources->hasColumn('active')) {
                $resources->addColumn('active', 'boolean', [
                    'default' => true,
                    'null' => false,
                    'after' => 'description',
                ])->update();
            }

            if (!$resources->hasColumn('created')) {
                $resources->addColumn('created', 'datetime', [
                    'null' => false,
                    'default' => 'CURRENT_TIMESTAMP',
                ])->update();
            }

            if (!$resources->hasColumn('modified')) {
                $resources->addColumn('modified', 'datetime', [
                    'null' => false,
                    'default' => 'CURRENT_TIMESTAMP',
                    'update' => 'CURRENT_TIMESTAMP',
                ])->update();
            }
        }
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

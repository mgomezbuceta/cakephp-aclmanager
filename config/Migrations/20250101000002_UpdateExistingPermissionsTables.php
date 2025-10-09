<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * UpdateExistingPermissionsTables migration
 *
 * Updates existing tables to match the Authorization Manager schema
 * without losing existing data
 */
class UpdateExistingPermissionsTables extends AbstractMigration
{
    /**
     * Change Method.
     *
     * @return void
     */
    public function change(): void
    {
        // Check and update roles table
        if ($this->hasTable('roles')) {
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

            // Add index if it doesn't exist
            if (!$roles->hasIndex(['name'])) {
                $roles->addIndex(['name'], [
                    'unique' => true,
                    'name' => 'idx_roles_name_unique'
                ])->update();
            }
        }

        // Check and update permissions table
        if ($this->hasTable('permissions')) {
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

            // Add indexes if they don't exist
            if (!$permissions->hasIndex(['role_id'])) {
                $permissions->addIndex(['role_id'], [
                    'name' => 'idx_permissions_role_id'
                ])->update();
            }

            if (!$permissions->hasIndex(['controller', 'action'])) {
                $permissions->addIndex(['controller', 'action'], [
                    'name' => 'idx_permissions_controller_action'
                ])->update();
            }

            if (!$permissions->hasIndex(['role_id', 'controller', 'action', 'plugin'])) {
                $permissions->addIndex(['role_id', 'controller', 'action', 'plugin'], [
                    'unique' => true,
                    'name' => 'idx_permissions_unique'
                ])->update();
            }

            // Add foreign key if it doesn't exist
            if (!$permissions->hasForeignKey('role_id')) {
                $permissions->addForeignKey('role_id', 'roles', 'id', [
                    'delete' => 'CASCADE',
                    'update' => 'NO_ACTION'
                ])->update();
            }
        }

        // Check and update resources table
        if ($this->hasTable('resources')) {
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

            // Add index if it doesn't exist
            if (!$resources->hasIndex(['controller', 'action', 'plugin'])) {
                $resources->addIndex(['controller', 'action', 'plugin'], [
                    'unique' => true,
                    'name' => 'idx_resources_unique'
                ])->update();
            }
        }
    }
}

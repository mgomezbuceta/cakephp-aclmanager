<?php
declare(strict_types=1);

use Migrations\AbstractMigration;

/**
 * CreatePermissionsTables migration
 *
 * Creates the necessary tables for the Authorization Manager system
 */
class CreatePermissionsTables extends AbstractMigration
{
    /**
     * Change Method.
     *
     * @return void
     */
    public function change(): void
    {
        // Roles table
        $roles = $this->table('roles');
        $roles->addColumn('name', 'string', [
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('description', 'text', [
                'null' => true,
            ])
            ->addColumn('priority', 'integer', [
                'default' => 0,
                'null' => false,
                'comment' => 'Higher priority = more permissions'
            ])
            ->addColumn('active', 'boolean', [
                'default' => true,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'null' => false,
            ])
            ->addColumn('modified', 'datetime', [
                'null' => false,
            ])
            ->addIndex(['name'], [
                'unique' => true,
                'name' => 'idx_roles_name_unique'
            ])
            ->create();

        // Permissions table
        $permissions = $this->table('permissions');
        $permissions->addColumn('role_id', 'integer', [
                'null' => false,
            ])
            ->addColumn('controller', 'string', [
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('action', 'string', [
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('plugin', 'string', [
                'limit' => 100,
                'null' => true,
            ])
            ->addColumn('allowed', 'boolean', [
                'default' => true,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'null' => false,
            ])
            ->addColumn('modified', 'datetime', [
                'null' => false,
            ])
            ->addIndex(['role_id'], [
                'name' => 'idx_permissions_role_id'
            ])
            ->addIndex(['controller', 'action'], [
                'name' => 'idx_permissions_controller_action'
            ])
            ->addIndex(['role_id', 'controller', 'action', 'plugin'], [
                'unique' => true,
                'name' => 'idx_permissions_unique'
            ])
            ->addForeignKey('role_id', 'roles', 'id', [
                'delete' => 'CASCADE',
                'update' => 'NO_ACTION'
            ])
            ->create();

        // Resources table (optional - to track available controllers/actions)
        $resources = $this->table('resources');
        $resources->addColumn('controller', 'string', [
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('action', 'string', [
                'limit' => 100,
                'null' => false,
            ])
            ->addColumn('plugin', 'string', [
                'limit' => 100,
                'null' => true,
            ])
            ->addColumn('description', 'text', [
                'null' => true,
            ])
            ->addColumn('active', 'boolean', [
                'default' => true,
                'null' => false,
            ])
            ->addColumn('created', 'datetime', [
                'null' => false,
            ])
            ->addColumn('modified', 'datetime', [
                'null' => false,
            ])
            ->addIndex(['controller', 'action', 'plugin'], [
                'unique' => true,
                'name' => 'idx_resources_unique'
            ])
            ->create();
    }
}

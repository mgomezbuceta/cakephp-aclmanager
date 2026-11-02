<?php
declare(strict_types=1);

use AclManager\Migration\SchemaCompatibility;
use Cake\Core\Configure;
use Migrations\BaseMigration;

/**
 * CreatePermissionsTables migration
 *
 * Creates the necessary tables for the Authorization Manager system
 */
class CreatePermissionsTables extends BaseMigration
{
    /**
     * Change Method.
     *
     * @return void
     */
    public function change(): void
    {
        // Roles table
        // Existing tables are never altered here; decide() throws if one is incompatible.
        if ($this->decide('roles') === SchemaCompatibility::CREATE) {
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
        }

        // Permissions table
        // Existing tables are never altered here; decide() throws if one is incompatible.
        if ($this->decide('permissions') === SchemaCompatibility::CREATE) {
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
        }

        // Resources table (optional - to track available controllers/actions)
        // Existing tables are never altered here; decide() throws if one is incompatible.
        if ($this->decide('resources') === SchemaCompatibility::CREATE) {
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

    /**
     * @param string $table Table name
     * @return string One of the SchemaCompatibility decisions
     */
    private function decide(string $table): string
    {
        $exists = $this->hasTable($table);
        $columns = $exists ? array_map(fn($c) => $c->getName(), $this->table($table)->getColumns()) : [];

        return SchemaCompatibility::decide(
            $table,
            $exists,
            $columns,
            (bool)Configure::read('AclManager.migrations.adoptExistingTables', false),
        );
    }
}

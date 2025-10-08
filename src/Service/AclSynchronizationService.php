<?php

declare(strict_types=1);

/**
 * ACL Synchronization Service
 *
 * Handles ACL synchronization operations, ACO/ARO updates and database operations
 *
 * @author Ivan Amat <dev@ivanamat.es>
 * @copyright Copyright 2024, Iván Amat
 * @license MIT http://opensource.org/licenses/MIT
 */

namespace AclManager\Service;

use Acl\Controller\Component\AclComponent;
use AclManager\Controller\Component\AclManagerComponent;
use AclManager\AclExtras;
use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\Log\Log;

class AclSynchronizationService
{
    private AclComponent $acl;
    private AclManagerComponent $aclManager;
    private AclExtras $aclExtras;

    public function __construct(AclComponent $acl, AclManagerComponent $aclManager)
    {
        $this->acl = $acl;
        $this->aclManager = $aclManager;
        $this->aclExtras = new AclExtras();
    }

    /**
     * Update ACOs (Access Control Objects)
     */
    public function updateAcos(): void
    {
        $this->aclExtras->acoUpdate();
    }

    /**
     * Update AROs (Access Request Objects)
     */
    public function updateAros(): int
    {
        return $this->aclManager->arosBuilder();
    }

    /**
     * Revoke all permissions
     */
    public function revokeAllPermissions(): void
    {
        $this->truncateTable('aros_acos');
    }

    /**
     * Set default permissions for the last ARO model
     */
    public function setDefaultPermissions(): void
    {
        $models = Configure::read('AclManager.aros', []);
        if (empty($models)) {
            return;
        }

        $lastModel = end($models);
        $firstEntity = $this->getFirstEntityOfModel($lastModel);

        if ($firstEntity) {
            $this->acl->allow([$lastModel => ['id' => $firstEntity->id]], 'controllers');
            Log::info("Granted permissions to {$lastModel} with id {$firstEntity->id}");
        }
    }

    /**
     * Drop all ACL data (ACOs, AROs, and permissions)
     */
    public function dropAllAclData(): void
    {
        $this->truncateTable('aros_acos');
        $this->truncateTable('acos');
        $this->truncateTable('aros');
    }

    /**
     * Reset everything to defaults
     */
    public function resetToDefaults(): void
    {
        // Drop all data
        $this->dropAllAclData();

        // Rebuild AROs
        $arosCount = $this->updateAros();
        Log::info("Created/updated {$arosCount} AROs");

        // Rebuild ACOs
        $this->updateAcos();
        Log::info('ACOs updated');

        // Set default permissions
        $this->setDefaultPermissions();
        Log::info('Default permissions set');
    }

    /**
     * Truncate a database table safely
     */
    private function truncateTable(string $tableName): void
    {
        $connection = ConnectionManager::get('default');

        try {
            $statement = $connection->execute("TRUNCATE TABLE {$tableName}");
            $errorInfo = $statement->errorInfo();

            if ($errorInfo[1] !== null) {
                throw new \RuntimeException("Database error truncating {$tableName}: " . implode(', ', $errorInfo));
            }
        } catch (\Exception $e) {
            Log::error("Failed to truncate table {$tableName}: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get the first entity of a specific model
     */
    private function getFirstEntityOfModel(string $modelName): ?\Cake\ORM\Entity
    {
        try {
            $table = \Cake\ORM\TableRegistry::getTableLocator()->get($modelName);
            return $table->find('all')
                ->orderBy(["{$modelName}.id" => 'ASC'])
                ->first();
        } catch (\Exception $e) {
            Log::error("Failed to get first entity of model {$modelName}: " . $e->getMessage());
            return null;
        }
    }
}
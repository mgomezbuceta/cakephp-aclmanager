<?php

declare(strict_types=1);

/**
 * CakePHP 5.x - ACL Manager Component
 *
 * Enhanced ACL Manager component for CakePHP 5.x with improved architecture
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @author Ivan Amat <dev@ivanamat.es>
 * @copyright Copyright 2024, Iván Amat
 * @license MIT http://opensource.org/licenses/MIT
 * @link https://github.com/ivanamat/cakephp-aclmanager
 */

namespace AclManager\Controller\Component;

use Acl\Controller\Component\AclComponent;
use Acl\Model\Entity\Aco;
use Acl\Model\Entity\Aro;
use Cake\Controller\Component;
use Cake\Core\Configure;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\Table;
use Cake\Utility\Inflector;

class AclManagerComponent extends Component
{
    use LocatorAwareTrait;

    private AclComponent $acl;
    private Table $acoTable;
    private Table $aroTable;

    /**
     * Initialize component
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->initializeAclComponent();
    }

    /**
     * Initialize ACL component and related tables
     */
    private function initializeAclComponent(): void
    {
        $this->acl = $this->getController()->loadComponent('Acl', [
            'className' => 'Acl.Acl'
        ]);

        $this->acoTable = $this->acl->Aco;
        $this->aroTable = $this->acl->Aro;
    }

    /**
     * Build AROs from configured models
     */
    public function arosBuilder(): int
    {
        $models = Configure::read('AclManager.aros', []);
        $counter = 0;

        foreach ($models as $index => $modelName) {
            $counter += $this->buildArosForModel($modelName, $index, $models);
        }

        return $counter;
    }

    /**
     * Build AROs for a specific model
     */
    private function buildArosForModel(string $modelName, int $index, array $models): int
    {
        $table = $this->fetchTable($modelName);
        $entities = $table->find('all');
        $counter = 0;

        foreach ($entities as $entity) {
            $parentAro = $this->findParentAro($entity, $index, $models);
            $alias = $this->determineAroAlias($entity);

            $aroData = [
                'alias' => $alias,
                'foreign_key' => $entity->id,
                'model' => $modelName,
                'parent_id' => $parentAro?->id
            ];

            if ($this->createAroIfNotExists($aroData)) {
                $counter++;
            }
        }

        return $counter;
    }

    /**
     * Find parent ARO for hierarchical structure
     */
    private function findParentAro($entity, int $modelIndex, array $models): ?Aro
    {
        if ($modelIndex === 0 || !isset($models[$modelIndex - 1])) {
            return null;
        }

        $parentModel = $models[$modelIndex - 1];
        $foreignKey = strtolower(Inflector::singularize($parentModel)) . '_id';

        if (!property_exists($entity, $foreignKey) || !$entity->{$foreignKey}) {
            return null;
        }

        return $this->aroTable->find('all')
            ->where([
                'model' => $parentModel,
                'foreign_key' => $entity->{$foreignKey}
            ])
            ->first();
    }

    /**
     * Determine ARO alias from entity properties
     */
    private function determineAroAlias($entity): ?string
    {
        $aliasFields = ['name', 'username', 'title', 'alias'];

        foreach ($aliasFields as $field) {
            if (property_exists($entity, $field) && !empty($entity->{$field})) {
                return (string)$entity->{$field};
            }
        }

        return null;
    }

    /**
     * Create ARO if it doesn't exist
     */
    private function createAroIfNotExists(array $aroData): bool
    {
        if ($this->aroExists($aroData)) {
            return false;
        }

        $aro = $this->aroTable->newEntity($aroData);
        return (bool)$this->aroTable->save($aro);
    }

    /**
     * Check if ARO already exists
     */
    private function aroExists(array $aroData): bool
    {
        $conditions = [
            'foreign_key' => $aroData['foreign_key'],
            'model' => $aroData['model']
        ];

        if (isset($aroData['parent_id'])) {
            $conditions['parent_id'] = $aroData['parent_id'];
        } else {
            $conditions['parent_id IS'] = null;
        }

        return $this->aroTable->exists($conditions);
    }

    /**
     * Check if ACO exists and create it if not
     */
    public function checkNodeOrSave(string $path, string $alias, ?int $parentId = null): ?Aco
    {
        $node = $this->acoTable->node($path);

        if ($node === false) {
            $acoData = [
                'parent_id' => $parentId,
                'model' => null,
                'alias' => $alias,
            ];

            $aco = $this->acoTable->newEntity($acoData);
            return $this->acoTable->save($aco) ?: null;
        }

        return $node->first();
    }
}
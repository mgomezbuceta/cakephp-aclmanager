<?php

declare(strict_types=1);

/**
 * ACL Permission Service
 *
 * Handles ACL permission operations and matrix building
 *
 * @author Ivan Amat <dev@ivanamat.es>
 * @copyright Copyright 2024, Iván Amat
 * @license MIT http://opensource.org/licenses/MIT
 */

namespace AclManager\Service;

use Acl\Controller\Component\AclComponent;
use Cake\ORM\Table;

class AclPermissionService
{
    private AclComponent $acl;

    public function __construct(AclComponent $acl)
    {
        $this->acl = $acl;
    }

    /**
     * Get ACOs with their permissions
     */
    public function getAcosWithPermissions(): array
    {
        $acosResult = $this->acl->Aco->find('all')
            ->orderBy(['lft' => 'ASC'])
            ->contain(['Aros'])
            ->toArray();

        return $this->parseAcosForView($acosResult);
    }

    /**
     * Build permissions matrix for AROs and ACOs
     */
    public function buildPermissionsMatrix(array $acos, array $aros, Table $aroTable): array
    {
        $permissions = [];
        $parents = [];

        foreach ($acos as $key => $acoData) {
            $aco = &$acos[$key];
            $aco = [
                'Aco' => $acoData['Aco'],
                'Aro' => $acoData['Aro'],
                'Action' => []
            ];

            $id = $aco['Aco']['id'];

            // Generate hierarchical path
            if ($aco['Aco']['parent_id'] && isset($parents[$aco['Aco']['parent_id']])) {
                $parents[$id] = $parents[$aco['Aco']['parent_id']] . '/' . $aco['Aco']['alias'];
            } else {
                $parents[$id] = $aco['Aco']['alias'];
            }

            $aco['Action'] = $parents[$id];
            $acoNode = $aco['Action'];

            // Build permissions for each ARO
            foreach ($aros as $aro) {
                $aroId = $aro[$aroTable->getAlias()]['id'];
                $permissionResult = $this->evaluatePermission($aroId, $aroTable->getAlias(), $aco);

                $nodeKey = str_replace('/', ':', $acoNode);
                $permissions[$nodeKey][$aroTable->getAlias() . ':' . $aroId . '-inherit'] = $permissionResult['inherited'];
                $permissions[$nodeKey][$aroTable->getAlias() . ':' . $aroId] = $permissionResult['allowed'];
            }
        }

        return $permissions;
    }

    /**
     * Evaluate permission for a specific ARO and ACO
     */
    private function evaluatePermission(int $aroId, string $aroAlias, array $aco): array
    {
        $acoId = $aco['Aco']['id'];

        $result = $this->acl->Aro->find('all')
            ->contain([
                'Permissions' => function ($q) use ($acoId) {
                    return $q->where(['aco_id' => $acoId]);
                }
            ])
            ->where([
                'model' => $aroAlias,
                'foreign_key' => $aroId
            ])
            ->toArray();

        if (empty($result)) {
            return ['allowed' => false, 'inherited' => true];
        }

        $permissions = $result[0]->permissions[0] ?? null;
        if (!$permissions) {
            return ['allowed' => false, 'inherited' => true];
        }

        return $this->analyzePermissionFlags($permissions);
    }

    /**
     * Analyze permission flags to determine access level
     */
    private function analyzePermissionFlags($permissions): array
    {
        $permissionKeys = ['_create', '_read', '_update', '_delete'];
        $allowed = true;
        $inherited = false;

        foreach ($permissionKeys as $key) {
            $value = $permissions->{$key} ?? 0;

            if ($value == -1) {
                return ['allowed' => false, 'inherited' => false];
            } elseif ($value == 0) {
                $inherited = true;
            }
        }

        if ($inherited) {
            // Check parent permissions or use ACL component check
            $aroNode = ['model' => $permissions->aro->model, 'foreign_key' => $permissions->aro->foreign_key];
            $acoNode = $permissions->aco->alias;
            $allowed = $this->acl->check($aroNode, $acoNode);
        }

        return ['allowed' => $allowed, 'inherited' => $inherited];
    }

    /**
     * Parse ACOs for view display
     */
    private function parseAcosForView(array $acos): array
    {
        $parsed = [];

        foreach ($acos as $aco) {
            $data['Aco'] = [
                'id' => $aco->id,
                'parent_id' => $aco->parent_id,
                'foreign_key' => $aco->foreign_key,
                'alias' => $aco->alias,
                'lft' => $aco->lft,
                'rght' => $aco->rght,
            ];

            if (isset($aco->model)) {
                $data['Aco']['model'] = $aco->model;
            }

            $aroData = [];
            foreach ($aco->aros as $aro) {
                $aroData[] = [
                    'id' => $aro->id,
                    'parent_id' => $aro->parent_id,
                    'model' => $aro->model,
                    'foreign_key' => $aro->foreign_key,
                    'alias' => $aro->alias,
                    'lft' => $aro->lft,
                    'rght' => $aro->rght,
                    'Permission' => [
                        'aro_id' => $aro->_joinData->aro_id,
                        'id' => $aro->_joinData->id,
                        'aco_id' => $aro->_joinData->aco_id,
                        '_create' => $aro->_joinData->_create,
                        '_read' => $aro->_joinData->_read,
                        '_update' => $aro->_joinData->_update,
                        '_delete' => $aro->_joinData->_delete,
                    ]
                ];
            }

            $data['Aro'] = $aroData;
            $parsed[] = $data;
        }

        return $parsed;
    }
}
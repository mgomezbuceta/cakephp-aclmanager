<?php

declare(strict_types=1);

/**
 * CakePHP 5.x - ACL Manager
 *
 * Enhanced ACL permissions management controller for CakePHP 5.x
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @author Ivan Amat <dev@ivanamat.es>
 * @copyright Copyright 2024, Iván Amat
 * @license MIT http://opensource.org/licenses/MIT
 * @link https://github.com/ivanamat/cakephp-aclmanager
 */

namespace AclManager\Controller;

use Acl\Controller\Component\AclComponent;
use AclManager\Controller\AppController;
use AclManager\Service\AclPermissionService;
use AclManager\Service\AclSynchronizationService;
use Cake\Core\Configure;
use Cake\Datasource\ConnectionManager;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\ForbiddenException;

class AclController extends AppController
{
    private AclPermissionService $permissionService;
    private AclSynchronizationService $synchronizationService;
    private ?string $currentModel = null;

    /**
     * Initialize controller
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->loadComponent('Acl', ['className' => 'Acl.Acl']);
        $this->loadComponent('AclManager', ['className' => 'AclManager.AclManager']);

        $this->initializeServices();
        $this->loadRequiredModels();
        $this->configurePagination();
    }

    /**
     * Initialize ACL services
     */
    private function initializeServices(): void
    {
        $this->permissionService = new AclPermissionService($this->Acl);
        $this->synchronizationService = new AclSynchronizationService($this->Acl, $this->AclManager);
    }

    /**
     * Load required models for ACL management
     */
    private function loadRequiredModels(): void
    {
        $models = Configure::read('AclManager.models', []);
        foreach ($models as $model) {
            $this->loadModel($model);
        }

        $this->loadModel('Acl.Permissions');
        $this->loadModel('Acos');
    }

    /**
     * Configure pagination for ARO models
     */
    private function configurePagination(): void
    {
        $aros = Configure::read('AclManager.aros', []);
        foreach ($aros as $aro) {
            $limit = Configure::read("AclManager.{$aro}.limit", 4);
            $this->paginate[$this->{$aro}->getAlias()] = [
                'limit' => $limit
            ];
        }
    }
    /**
     * ACL Manager main page
     */
    public function index(): void
    {
        $managedEntities = Configure::read('AclManager.aros', []);
        $this->set('manage', $managedEntities);
    }

    /**
     * Manage permissions for ARO entities
     */
    public function permissions(?string $model = null): void
    {
        $this->ensureUserAuthenticated();

        $model = $this->validateAndNormalizeModel($model);
        $this->currentModel = $model;

        if ($this->request->is(['post', 'put'])) {
            $this->processPermissionUpdates();
        }

        $this->renderPermissionsView($model);
    }

    /**
     * Ensure user is authenticated
     */
    private function ensureUserAuthenticated(): void
    {
        if (!$this->Authentication->getIdentity()) {
            $this->Flash->error(__('Please sign in'));
            $this->redirect(['action' => 'index']);
        }
    }

    /**
     * Validate and normalize the model parameter
     */
    private function validateAndNormalizeModel(?string $model): string
    {
        $availableModels = Configure::read('AclManager.aros', []);

        if (empty($model) || !in_array($model, $availableModels)) {
            return $availableModels[0] ?? throw new BadRequestException('No ARO models configured');
        }

        return $model;
    }

    /**
     * Process permission updates from form submission
     */
    private function processPermissionUpdates(): void
    {
        $permissions = $this->request->getData('Perms', []);

        foreach ($permissions as $aco => $aros) {
            $action = str_replace(':', '/', $aco);

            foreach ($aros as $node => $permission) {
                $this->updateSinglePermission($node, $action, $permission);
            }
        }
    }

    /**
     * Update a single permission
     */
    private function updateSinglePermission(string $node, string $action, string $permission): void
    {
        [$model, $id] = explode(':', $node);
        $nodeArray = ['model' => $model, 'foreign_key' => (int)$id];

        match($permission) {
            'allow' => $this->Acl->allow($nodeArray, $action),
            'inherit' => $this->Acl->inherit($nodeArray, $action),
            'deny' => $this->Acl->deny($nodeArray, $action),
            default => null
        };
    }

    /**
     * Render the permissions view with necessary data
     */
    private function renderPermissionsView(string $model): void
    {
        $aroTable = $this->{$model};
        $aros = $this->paginate($aroTable->getAlias());
        $parsedAros = $this->parseArosForView($aros);

        $acos = $this->permissionService->getAcosWithPermissions();
        $permissions = $this->permissionService->buildPermissionsMatrix($acos, $parsedAros, $aroTable);

        $this->set([
            'model' => $model,
            'manage' => Configure::read('AclManager.aros'),
            'hideDenied' => Configure::read('AclManager.hideDenied'),
            'aroAlias' => $aroTable->getAlias(),
            'aroDisplayField' => $aroTable->getDisplayField(),
            'acos' => $acos,
            'aros' => $parsedAros,
            'permissions' => $permissions
        ]);
    }


    /**
     * Update ACOs (Access Control Objects)
     */
    public function updateAcos(): void
    {
        $this->synchronizationService->updateAcos();
        $this->Flash->success(__('ACOs have been updated successfully'));
        $this->redirectToReferrerOrIndex();
    }

    /**
     * Update AROs (Access Request Objects)
     */
    public function updateAros(): void
    {
        $arosCounter = $this->synchronizationService->updateAros();
        $this->Flash->success(sprintf(__('%d AROs have been created, updated or deleted'), $arosCounter));
        $this->redirectToReferrerOrIndex();
    }

    /**
     * Revoke all permissions and set defaults
     */
    public function revokePerms(): void
    {
        try {
            $this->synchronizationService->revokeAllPermissions();
            $this->synchronizationService->setDefaultPermissions();

            $this->Flash->success(__('All permissions revoked and defaults set successfully'));
        } catch (\Exception $e) {
            $this->Flash->error(__('Error while revoking permissions: {0}', $e->getMessage()));
        }

        $this->redirect(['action' => 'permissions']);
    }
    
    /**
     * Drop all ACL data (ACOs and AROs)
     */
    public function drop(): void
    {
        try {
            $this->synchronizationService->dropAllAclData();
            $this->Flash->success(__('ACOs and AROs have been dropped successfully'));
        } catch (\Exception $e) {
            $this->Flash->error(__('Error while dropping ACL data: {0}', $e->getMessage()));
        }

        $this->redirect(['action' => 'index']);
    }
    
    /**
     * Reset to defaults - drop everything and recreate with default permissions
     */
    public function defaults(): void
    {
        try {
            $this->synchronizationService->resetToDefaults();
            $this->Flash->success(__('Everything has been restored to defaults successfully!'));
        } catch (\Exception $e) {
            $this->Flash->error(__('Error while resetting to defaults: {0}', $e->getMessage()));
        }

        $this->redirect(['action' => 'index']);
    }

    /**
     * Helper method to redirect to referrer or index
     */
    private function redirectToReferrerOrIndex(): void
    {
        $referrer = $this->request->getHeaderLine('Referer');
        $defaultUrl = ['plugin' => 'AclManager', 'controller' => 'Acl', 'action' => 'index'];

        $url = (!empty($referrer) && $referrer !== '/') ? $referrer : $defaultUrl;
        $this->redirect($url);
    }

    /**
     * Parse AROs for view display
     */
    private function parseArosForView(iterable $aros): array
    {
        $parsed = [];
        foreach ($aros as $aro) {
            $parsed[] = [$this->currentModel => $aro];
        }

        return $parsed;
    }
}
<?php
declare(strict_types=1);

/**
 * CakePHP Authorization Manager
 *
 * Base controller for the Authorization Manager plugin
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @package AclManager\Controller
 *
 * @author Marcos Gómez Buceta <mgomezbuceta@gmail.com>
 * @author Ivan Amat <dev@ivanamat.es>
 * @copyright Copyright 2025, Marcos Gómez Buceta
 * @copyright Copyright 2016, Iván Amat
 * @license MIT http://opensource.org/licenses/MIT
 * @link https://github.com/mgomezbuceta/cakephp-aclmanager
 */

namespace AclManager\Controller;

use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Event\EventInterface;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Response;
use Cake\I18n\I18n;

/**
 * AppController
 *
 * Base controller for all Authorization Manager controllers
 */
class AppController extends Controller
{
    /**
     * Initialization hook method.
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->loadComponent('Flash');

        // Try to load Authorization component if available
        try {
            $this->loadComponent('Authorization.Authorization');
        } catch (\Exception $e) {
            // Authorization component not available, continue without it
        }

        // Try to load Authentication component if available
        try {
            $this->loadComponent('Authentication.Authentication');
        } catch (\Exception $e) {
            // Authentication component not available, continue without it
        }
    }

    /**
     * Before filter callback
     *
     * @param \Cake\Event\EventInterface $event The event object
     * @return void|\Cake\Http\Response
     */
    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);

        // Optional per-request locale; set before the admin check so denied-access flashes are translated.
        $locale = Configure::read('AclManager.locale');
        if (is_string($locale) && $locale !== '') {
            I18n::setLocale($locale);
        }

        // Skip authorization checks if Authorization component is loaded
        if ($this->components()->has('Authorization')) {
            $this->Authorization->skipAuthorization();
        }

        // Restrict access to administrators only. Setting a response as the event result
        // stops the request here, so the action never runs for denied users.
        $response = $this->checkAdminAccess();
        if ($response !== null) {
            $event->setResult($response);
            $event->stopPropagation();
        }
    }

    /**
     * Check if current user has admin access
     *
     * @return \Cake\Http\Response|null Redirect response when access is denied, null when allowed
     * @throws \Cake\Http\Exception\ForbiddenException When there is no Authentication and no opt-in
     */
    protected function checkAdminAccess(): ?Response
    {
        // Fail closed: without the Authentication component nobody is identified, so access
        // requires an explicit opt-in or a customCheck callback that grants it.
        if (!$this->components()->has('Authentication')) {
            $customCheck = Configure::read('AclManager.adminAccess.customCheck');
            if (
                Configure::read('AclManager.adminAccess.allowWithoutAuthentication', false) === true
                || (is_callable($customCheck) && $customCheck(null))
            ) {
                return null;
            }

            throw new ForbiddenException(__d(
                'acl_manager',
                'The Authentication component is not available. '
                . 'Configure cakephp/authentication or set AclManager.adminAccess.allowWithoutAuthentication.',
            ));
        }

        // Get the current authenticated user
        $identity = $this->Authentication->getIdentity();

        if (!$identity) {
            // No authenticated user - redirect to login with return URL
            $loginUrl = \Cake\Core\Configure::read('AclManager.redirects.login', ['plugin' => false, 'controller' => 'Users', 'action' => 'login']);

            // Store current URL to redirect back after login
            $currentUrl = $this->request->getUri()->getPath();
            if ($this->request->getQuery()) {
                $currentUrl .= '?' . http_build_query($this->request->getQueryParams());
            }

            // Add redirect parameter to login URL
            if (is_array($loginUrl)) {
                $loginUrl['?'] = ['redirect' => $currentUrl];
            } else {
                $loginUrl .= (strpos($loginUrl, '?') === false ? '?' : '&') . 'redirect=' . urlencode($currentUrl);
            }

            $this->Flash->error(__d('acl_manager', 'You must be logged in to access the Authorization Manager.'));

            return $this->redirect($loginUrl);
        }

        // Check if user is admin
        $isAdmin = $this->isUserAdmin($identity);

        if (!$isAdmin) {
            $unauthorizedUrl = \Cake\Core\Configure::read('AclManager.redirects.unauthorized', ['plugin' => false, 'controller' => 'Pages', 'action' => 'display', 'home']);
            $this->Flash->error(__d('acl_manager', 'You do not have permission to access the Authorization Manager.'));

            return $this->redirect($unauthorizedUrl);
        }

        return null;
    }

    /**
     * Determine if user is an administrator
     *
     * Override this method in your application if you need custom logic
     *
     * @param \Authentication\IdentityInterface $identity User identity
     * @return bool
     */
    protected function isUserAdmin($identity): bool
    {
        // Load configuration from config/app.php
        $adminRoleIds = \Cake\Core\Configure::read('AclManager.adminAccess.adminRoleIds', [1]);
        $adminRoleNames = \Cake\Core\Configure::read('AclManager.adminAccess.adminRoleNames', ['admin', 'administrator', 'superadmin']);
        $adminEmails = \Cake\Core\Configure::read('AclManager.adminAccess.adminEmails', []);
        $customCheck = \Cake\Core\Configure::read('AclManager.adminAccess.customCheck');

        // Custom callback function (highest priority)
        if (is_callable($customCheck)) {
            return $customCheck($identity);
        }

        // Method 1: Check by role name (if you have a role_name field)
        if (isset($identity->role_name)) {
            return in_array(strtolower($identity->role_name), array_map('strtolower', $adminRoleNames));
        }

        // Method 2: Check by role_id (common pattern)
        if (isset($identity->role_id)) {
            return in_array($identity->role_id, $adminRoleIds);
        }

        // Method 3: Check by is_admin flag
        if (isset($identity->is_admin)) {
            return (bool)$identity->is_admin;
        }

        // Method 4: Check by email (useful for initial setup)
        if (isset($identity->email) && !empty($adminEmails)) {
            return in_array($identity->email, $adminEmails);
        }

        // Default: deny access if we can't determine admin status
        return false;
    }
}

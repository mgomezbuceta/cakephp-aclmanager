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
use Cake\Event\EventInterface;
use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * AppController
 *
 * Base controller for all Authorization Manager controllers
 */
class AppController extends Controller
{
    use LocatorAwareTrait;
    /**
     * Initialization hook method.
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->loadComponent('Flash');
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

        // Skip authorization checks (plugin manages authorization)
        $this->Authorization->skipAuthorization();

        // Restrict access to administrators only
        $this->checkAdminAccess();
    }

    /**
     * Check if current user has admin access
     *
     * @return void
     * @throws \Cake\Http\Exception\ForbiddenException
     */
    protected function checkAdminAccess(): void
    {
        // Get the current authenticated user
        $identity = $this->Authentication->getIdentity();

        if (!$identity) {
            // No authenticated user - redirect to login
            $this->Flash->error(__('You must be logged in to access the Authorization Manager.'));
            $this->redirect(['plugin' => false, 'controller' => 'Users', 'action' => 'login']);
            return;
        }

        // Check if user is admin
        // Customize this based on your application's structure
        $isAdmin = $this->isUserAdmin($identity);

        if (!$isAdmin) {
            $this->Flash->error(__('You do not have permission to access the Authorization Manager.'));
            $this->redirect(['plugin' => false, 'controller' => 'Pages', 'action' => 'display', 'home']);
            return;
        }
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
        // Method 1: Check by role name (if you have a role_name field)
        if (isset($identity->role_name)) {
            return in_array(strtolower($identity->role_name), ['admin', 'administrator', 'superadmin']);
        }

        // Method 2: Check by role_id (common pattern)
        if (isset($identity->role_id)) {
            // Role ID 1 is typically admin
            // Adjust this based on your application's role structure
            return $identity->role_id == 1;
        }

        // Method 3: Check by is_admin flag
        if (isset($identity->is_admin)) {
            return (bool)$identity->is_admin;
        }

        // Method 4: Check by email domain (for initial setup)
        if (isset($identity->email)) {
            // Allow access for specific admin emails during setup
            // Remove or comment this in production
            $adminEmails = [
                // 'admin@example.com',
            ];
            return in_array($identity->email, $adminEmails);
        }

        // Default: deny access if we can't determine admin status
        return false;
    }
}

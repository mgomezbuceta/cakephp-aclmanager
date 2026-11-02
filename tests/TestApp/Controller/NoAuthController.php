<?php
declare(strict_types=1);

namespace AclManager\Test\TestApp\Controller;

use AclManager\Controller\AppController;
use Cake\Http\Response;

/**
 * Plugin controller as seen by a host app where the Authentication component is not available.
 */
class NoAuthController extends AppController
{
    public function initialize(): void
    {
        // Skip AppController::initialize(): no Authentication/Authorization components.
        $this->loadComponent('Flash');
    }

    public function index(): Response
    {
        return $this->response->withStringBody('ok');
    }
}

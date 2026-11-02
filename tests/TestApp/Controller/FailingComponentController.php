<?php
declare(strict_types=1);

namespace AclManager\Test\TestApp\Controller;

use AclManager\Controller\AppController;
use Cake\Controller\Component;
use Cake\Http\Response;
use RuntimeException;

/**
 * Plugin controller where loading a configurable component fails (e.g. a broken host integration).
 */
class FailingComponentController extends AppController
{
    /**
     * @var array<int, string>
     */
    public static array $failing = [];

    public function loadComponent(string $name, array $config = []): Component
    {
        if (in_array($name, self::$failing, true)) {
            throw new RuntimeException('cannot load ' . $name);
        }

        return parent::loadComponent($name, $config);
    }

    public function index(): Response
    {
        return $this->response->withStringBody('ok');
    }
}

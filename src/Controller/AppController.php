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
     * @return void
     */
    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);
    }
}

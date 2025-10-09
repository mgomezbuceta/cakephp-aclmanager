<?php
declare(strict_types=1);

/**
 * CakePHP 5.x - Authorization Manager
 *
 * PHP version 8.1
 *
 * Class AclManagerHelper
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @package  AclManager\View\Helper
 *
 * @author Marcos Gómez Buceta <mgomezbuceta@gmail.com>
 * @author Ivan Amat <dev@ivanamat.es>
 * @copyright Copyright 2025, Marcos Gómez Buceta
 * @copyright Copyright 2016, Iván Amat
 * @license MIT http://opensource.org/licenses/MIT
 * @link https://github.com/mgomezbuceta/cakephp-aclmanager
 */

namespace AclManager\View\Helper;

use Acl\Controller\Component\AclComponent;
use Cake\Controller\ComponentRegistry;
use Cake\Core\Configure;
use Cake\ORM\TableRegistry;
use Cake\View\Helper;
use Cake\View\View;

/**
 * AclManagerHelper
 *
 * Helper for ACL-related functionality in views
 */
class AclManagerHelper extends Helper
{
    /**
     * Helpers used.
     *
     * @var array<string>
     */
    protected array $helpers = ['Html'];

    /**
     * Acl Instance.
     *
     * @var \Acl\Controller\Component\AclComponent
     */
    protected AclComponent $Acl;

    /**
     * Constructor
     *
     * @param \Cake\View\View $View The View this helper is being attached to
     * @param array $config Configuration settings for the helper
     */
    public function __construct(View $View, array $config = [])
    {
        parent::__construct($View, $config);

        $collection = new ComponentRegistry();
        $this->Acl = new AclComponent($collection, Configure::read('Acl'));
    }

    /**
     * Check if the User have access to the aco
     *
     * @param mixed $aro The Aro of the user you want to check
     * @param string $aco The path of the Aco like App/Blog/add
     * @return bool
     */
    public function check(mixed $aro, string $aco): bool
    {
        if (empty($aro) || empty($aco)) {
            return false;
        }

        return $this->Acl->check($aro, $aco);
    }

    /**
     * Get name for ARO by ID
     *
     * @param string $aro The ARO model name
     * @param int $id The ARO ID
     * @return mixed
     */
    public function getName(string $aro, int $id): mixed
    {
        return $this->__getName($aro, $id);
    }

    /**
     * Return value from permissions input
     *
     * @param mixed $value The value to check
     * @return mixed
     */
    public function value(mixed $value = null): mixed
    {
        if ($value === null) {
            return false;
        }

        $o = explode('.', $value);
        $data = $this->getView()->getRequest()->getData();
        return $data[$o[0]][$o[1]][$o[2]] ?? false;
    }

    /**
     * Get name for ARO by ID (internal method)
     *
     * @param string $aro The ARO model name
     * @param int $id The ARO ID
     * @return mixed
     */
    protected function __getName(string $aro, int $id): mixed
    {
        $model = TableRegistry::getTableLocator()->get($aro);
        $data = $model->get($id);

        return $data;
    }
}

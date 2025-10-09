<?php
declare(strict_types=1);

namespace AclManager\Model\Entity;

use Cake\ORM\Entity;

/**
 * Permission Entity
 *
 * @property int $id
 * @property int $role_id
 * @property string $controller
 * @property string $action
 * @property string|null $plugin
 * @property bool $allowed
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 *
 * @property \AclManager\Model\Entity\Role $role
 */
class Permission extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'role_id' => true,
        'controller' => true,
        'action' => true,
        'plugin' => true,
        'allowed' => true,
        'created' => true,
        'modified' => true,
        'role' => true,
    ];
}

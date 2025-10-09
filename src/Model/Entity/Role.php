<?php
declare(strict_types=1);

namespace AclManager\Model\Entity;

use Cake\ORM\Entity;

/**
 * Role Entity
 *
 * @property int $id
 * @property string $name
 * @property string|null $description
 * @property int $priority
 * @property bool $active
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 *
 * @property \AclManager\Model\Entity\Permission[] $permissions
 */
class Role extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'name' => true,
        'description' => true,
        'priority' => true,
        'active' => true,
        'created' => true,
        'modified' => true,
        'permissions' => true,
    ];
}

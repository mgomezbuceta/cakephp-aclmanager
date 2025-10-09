<?php
declare(strict_types=1);

namespace AclManager\Model\Entity;

use Cake\ORM\Entity;

/**
 * Resource Entity
 *
 * @property int $id
 * @property string $controller
 * @property string $action
 * @property string|null $plugin
 * @property string|null $description
 * @property bool $active
 * @property \Cake\I18n\DateTime $created
 * @property \Cake\I18n\DateTime $modified
 */
class Resource extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * @var array<string, bool>
     */
    protected array $_accessible = [
        'controller' => true,
        'action' => true,
        'plugin' => true,
        'description' => true,
        'active' => true,
        'created' => true,
        'modified' => true,
    ];

    /**
     * Get the full resource identifier
     *
     * @return string
     */
    protected function _getFullIdentifier(): string
    {
        $parts = [];
        if ($this->plugin) {
            $parts[] = $this->plugin;
        }
        $parts[] = $this->controller;
        $parts[] = $this->action;

        return implode('.', $parts);
    }
}

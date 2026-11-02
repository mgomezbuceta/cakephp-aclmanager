<?php
declare(strict_types=1);

$timestamps = [
    'created' => ['type' => 'datetime', 'null' => false],
    'modified' => ['type' => 'datetime', 'null' => false],
];
$primary = ['primary' => ['type' => 'primary', 'columns' => ['id']]];

return [
    [
        'table' => 'roles',
        'columns' => [
            'id' => ['type' => 'integer', 'autoIncrement' => true],
            'name' => ['type' => 'string', 'length' => 100, 'null' => false],
            'description' => ['type' => 'text', 'null' => true],
            'priority' => ['type' => 'integer', 'default' => 0, 'null' => false],
            'active' => ['type' => 'boolean', 'default' => true, 'null' => false],
        ] + $timestamps,
        'constraints' => $primary,
    ],
    [
        'table' => 'permissions',
        'columns' => [
            'id' => ['type' => 'integer', 'autoIncrement' => true],
            'role_id' => ['type' => 'integer', 'null' => false],
            'controller' => ['type' => 'string', 'length' => 100, 'null' => false],
            'action' => ['type' => 'string', 'length' => 100, 'null' => false],
            'plugin' => ['type' => 'string', 'length' => 100, 'null' => true],
            'allowed' => ['type' => 'boolean', 'default' => true, 'null' => false],
        ] + $timestamps,
        'constraints' => $primary,
    ],
    [
        'table' => 'resources',
        'columns' => [
            'id' => ['type' => 'integer', 'autoIncrement' => true],
            'controller' => ['type' => 'string', 'length' => 100, 'null' => false],
            'action' => ['type' => 'string', 'length' => 100, 'null' => false],
            'plugin' => ['type' => 'string', 'length' => 100, 'null' => true],
            'description' => ['type' => 'text', 'null' => true],
            'active' => ['type' => 'boolean', 'default' => true, 'null' => false],
        ] + $timestamps,
        'constraints' => $primary,
    ],
];

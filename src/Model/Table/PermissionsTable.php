<?php
declare(strict_types=1);

namespace AclManager\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Permissions Model
 *
 * @property \AclManager\Model\Table\RolesTable&\Cake\ORM\Association\BelongsTo $Roles
 * @method \AclManager\Model\Entity\Permission newEmptyEntity()
 * @method \AclManager\Model\Entity\Permission newEntity(array<string, mixed> $data, array<string, mixed> $options = [])
 * @method \AclManager\Model\Entity\Permission[] newEntities(array<string, mixed> $data, array<string, mixed> $options = [])
 * @method \AclManager\Model\Entity\Permission get(mixed $primaryKey, array<string, mixed>|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \AclManager\Model\Entity\Permission findOrCreate($search, ?callable $callback = null, array<string, mixed> $options = [])
 * @method \AclManager\Model\Entity\Permission patchEntity(\Cake\Datasource\EntityInterface $entity, array<string, mixed> $data, array<string, mixed> $options = [])
 * @method \AclManager\Model\Entity\Permission[] patchEntities(iterable<\Cake\Datasource\EntityInterface> $entities, array<string, mixed> $data, array<string, mixed> $options = [])
 * @method \AclManager\Model\Entity\Permission|false save(\Cake\Datasource\EntityInterface $entity, array<string, mixed> $options = [])
 * @method \AclManager\Model\Entity\Permission saveOrFail(\Cake\Datasource\EntityInterface $entity, array<string, mixed> $options = [])
 * @method iterable<\AclManager\Model\Entity\Permission>|\Cake\Datasource\ResultSetInterface<\AclManager\Model\Entity\Permission>|false saveMany(iterable<\Cake\Datasource\EntityInterface> $entities, array<string, mixed> $options = [])
 * @method iterable<\AclManager\Model\Entity\Permission>|\Cake\Datasource\ResultSetInterface<\AclManager\Model\Entity\Permission> saveManyOrFail(iterable<\Cake\Datasource\EntityInterface> $entities, array<string, mixed> $options = [])
 * @method iterable<\AclManager\Model\Entity\Permission>|\Cake\Datasource\ResultSetInterface<\AclManager\Model\Entity\Permission>|false deleteMany(iterable<\Cake\Datasource\EntityInterface> $entities, array<string, mixed> $options = [])
 * @method iterable<\AclManager\Model\Entity\Permission>|\Cake\Datasource\ResultSetInterface<\AclManager\Model\Entity\Permission> deleteManyOrFail(iterable<\Cake\Datasource\EntityInterface> $entities, array<string, mixed> $options = [])
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class PermissionsTable extends Table
{
    /**
     * Canonical plugin value for application-level (non-plugin) controllers.
     */
    public const APP_PLUGIN = 'App';

    /**
     * Map the "no plugin" forms (null, empty string) to the canonical 'App' value
     *
     * @param string|null $plugin Plugin name
     * @return string
     */
    public static function normalizePlugin(?string $plugin): string
    {
        return $plugin === null || $plugin === '' ? self::APP_PLUGIN : $plugin;
    }

    /**
     * Initialize method
     *
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('permissions');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->getSchema()->setColumnType('allowed', 'boolean');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Roles', [
            'foreignKey' => 'role_id',
            'joinType' => 'INNER',
            'className' => 'AclManager.Roles',
        ]);
    }

    /**
     * Default validation rules.
     *
     * @param \Cake\Validation\Validator $validator Validator instance.
     * @return \Cake\Validation\Validator
     */
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->integer('id')
            ->allowEmptyString('id', null, 'create');

        $validator
            ->integer('role_id')
            ->requirePresence('role_id', 'create')
            ->notEmptyString('role_id');

        $validator
            ->scalar('controller')
            ->maxLength('controller', 100)
            ->requirePresence('controller', 'create')
            ->notEmptyString('controller');

        $validator
            ->scalar('action')
            ->maxLength('action', 100)
            ->requirePresence('action', 'create')
            ->notEmptyString('action');

        $validator
            ->scalar('plugin')
            ->maxLength('plugin', 100)
            ->allowEmptyString('plugin');

        $validator
            ->boolean('allowed')
            ->notEmptyString('allowed');

        return $validator;
    }

    /**
     * Check if a role has permission for a specific resource
     *
     * @param int $roleId Role ID
     * @param string $controller Controller name
     * @param string $action Action name
     * @param string|null $plugin Plugin name
     * @return bool
     */
    public function isAuthorized(int $roleId, string $controller, string $action, ?string $plugin = null): bool
    {
        $permission = $this->find()
            ->where([
                'role_id' => $roleId,
                'controller' => $controller,
                'action' => $action,
                'plugin' => self::normalizePlugin($plugin),
            ])
            ->first();

        return $permission ? (bool)$permission->allowed : false;
    }

    /**
     * Get all permissions for a role
     *
     * @param int $roleId Role ID
     * @return \Cake\ORM\Query\SelectQuery<\Cake\Datasource\EntityInterface>
     */
    public function findByRole(int $roleId): Query
    {
        return $this->find()
            ->where(['role_id' => $roleId])
            ->orderBy(['plugin' => 'ASC', 'controller' => 'ASC', 'action' => 'ASC']);
    }
}

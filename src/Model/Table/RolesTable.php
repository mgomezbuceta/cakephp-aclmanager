<?php
declare(strict_types=1);

namespace AclManager\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Roles Model
 *
 * @property \AclManager\Model\Table\PermissionsTable&\Cake\ORM\Association\HasMany $Permissions
 *
 * @method \AclManager\Model\Entity\Role newEmptyEntity()
 * @method \AclManager\Model\Entity\Role newEntity(array $data, array $options = [])
 * @method \AclManager\Model\Entity\Role[] newEntities(array $data, array $options = [])
 * @method \AclManager\Model\Entity\Role get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \AclManager\Model\Entity\Role findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \AclManager\Model\Entity\Role patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \AclManager\Model\Entity\Role[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \AclManager\Model\Entity\Role|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \AclManager\Model\Entity\Role saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\AclManager\Model\Entity\Role>|\Cake\Datasource\ResultSetInterface<\AclManager\Model\Entity\Role>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\AclManager\Model\Entity\Role>|\Cake\Datasource\ResultSetInterface<\AclManager\Model\Entity\Role> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\AclManager\Model\Entity\Role>|\Cake\Datasource\ResultSetInterface<\AclManager\Model\Entity\Role>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\AclManager\Model\Entity\Role>|\Cake\Datasource\ResultSetInterface<\AclManager\Model\Entity\Role> deleteManyOrFail(iterable $entities, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class RolesTable extends Table
{
    /**
     * Initialize method
     *
     * @param array<string, mixed> $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('roles');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->hasMany('Permissions', [
            'foreignKey' => 'role_id',
            'className' => 'AclManager.Permissions',
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
            ->scalar('name')
            ->maxLength('name', 100)
            ->requirePresence('name', 'create')
            ->notEmptyString('name')
            ->add('name', 'unique', ['rule' => 'validateUnique', 'provider' => 'table']);

        $validator
            ->scalar('description')
            ->allowEmptyString('description');

        $validator
            ->integer('priority')
            ->notEmptyString('priority');

        $validator
            ->boolean('active')
            ->notEmptyString('active');

        return $validator;
    }
}

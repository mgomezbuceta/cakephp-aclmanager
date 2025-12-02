<?php
declare(strict_types=1);

namespace AclManager\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Resources Model
 *
 * @method \AclManager\Model\Entity\Resource newEmptyEntity()
 * @method \AclManager\Model\Entity\Resource newEntity(array $data, array $options = [])
 * @method \AclManager\Model\Entity\Resource[] newEntities(array $data, array $options = [])
 * @method \AclManager\Model\Entity\Resource get(mixed $primaryKey, array|string $finder = 'all', \Psr\SimpleCache\CacheInterface|string|null $cache = null, \Closure|string|null $cacheKey = null, mixed ...$args)
 * @method \AclManager\Model\Entity\Resource findOrCreate($search, ?callable $callback = null, array $options = [])
 * @method \AclManager\Model\Entity\Resource patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \AclManager\Model\Entity\Resource[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \AclManager\Model\Entity\Resource|false save(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method \AclManager\Model\Entity\Resource saveOrFail(\Cake\Datasource\EntityInterface $entity, array $options = [])
 * @method iterable<\AclManager\Model\Entity\Resource>|\Cake\Datasource\ResultSetInterface<\AclManager\Model\Entity\Resource>|false saveMany(iterable $entities, array $options = [])
 * @method iterable<\AclManager\Model\Entity\Resource>|\Cake\Datasource\ResultSetInterface<\AclManager\Model\Entity\Resource> saveManyOrFail(iterable $entities, array $options = [])
 * @method iterable<\AclManager\Model\Entity\Resource>|\Cake\Datasource\ResultSetInterface<\AclManager\Model\Entity\Resource>|false deleteMany(iterable $entities, array $options = [])
 * @method iterable<\AclManager\Model\Entity\Resource>|\Cake\Datasource\ResultSetInterface<\AclManager\Model\Entity\Resource> deleteManyOrFail(iterable $entities, array $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class ResourcesTable extends Table
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

        $this->setTable('resources');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');
        
        $this->getSchema()->setColumnType('active', 'boolean');

        $this->addBehavior('Timestamp');
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
            ->scalar('description')
            ->allowEmptyString('description');

        $validator
            ->boolean('active')
            ->notEmptyString('active');

        return $validator;
    }

    /**
     * Get all active resources grouped by plugin and controller
     *
     * @return array
     */
    public function getGroupedResources(): array
    {
        $resources = $this->find()
            ->where(['active' => true])
            ->order(['plugin' => 'ASC', 'controller' => 'ASC', 'action' => 'ASC'])
            ->all();

        $grouped = [];
        foreach ($resources as $resource) {
            $pluginKey = $resource->plugin ?? 'App';
            $grouped[$pluginKey][$resource->controller][] = $resource;
        }

        return $grouped;
    }
}

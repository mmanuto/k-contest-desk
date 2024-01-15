<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Federations Model
 *
 * @property \App\Model\Table\AthletesTable&\Cake\ORM\Association\HasMany $Athletes
 *
 * @method \App\Model\Entity\Federation newEmptyEntity()
 * @method \App\Model\Entity\Federation newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\Federation[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Federation get($primaryKey, $options = [])
 * @method \App\Model\Entity\Federation findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\Federation patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Federation[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Federation|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Federation saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Federation[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Federation[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\Federation[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Federation[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 */
class FederationsTable extends Table
{
    /**
     * Initialize method
     *
     * @param array $config The configuration for the Table.
     * @return void
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('federations');
        $this->setDisplayField('name');
        $this->setPrimaryKey('id');

        $this->hasMany('Athletes', [
            'foreignKey' => 'federation_id',
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
            ->scalar('name')
            ->maxLength('name', 50)
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

        return $validator;
    }
}

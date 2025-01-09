<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Agecategories Model
 *
 * @property \App\Model\Table\CategorycodesTable&\Cake\ORM\Association\HasMany $Categorycodes
 *
 * @method \App\Model\Entity\Agecategory newEmptyEntity()
 * @method \App\Model\Entity\Agecategory newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\Agecategory[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Agecategory get($primaryKey, $options = [])
 * @method \App\Model\Entity\Agecategory findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\Agecategory patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Agecategory[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Agecategory|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Agecategory saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Agecategory[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Agecategory[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\Agecategory[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Agecategory[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 */
class AgecategoriesTable extends Table
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

        $this->setTable('agecategories');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

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
            ->scalar('description')
            ->maxLength('description', 30)
            ->requirePresence('description', 'create')
            ->notEmptyString('description');

        $validator
            ->integer('age_min')
            ->requirePresence('age_min', 'create')
            ->notEmptyString('age_min');

        $validator
            ->integer('age_max')
            ->requirePresence('age_max', 'create')
            ->notEmptyString('age_max');

        $validator
            ->boolean('agonist')
            ->requirePresence('agonist', 'create')
            ->notEmptyString('agonist');

        $validator
            ->boolean('out_category')
            ->notEmptyString('out_category');

        return $validator;
    }

    /**
     * Returns a rules checker object that will be used for validating
     * application integrity.
     *
     * @param \Cake\ORM\RulesChecker $rules The rules object to be modified.
     * @return \Cake\ORM\RulesChecker
     */
    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->isUnique(['id']), ['errorField' => 'id']);

        return $rules;
    }
}

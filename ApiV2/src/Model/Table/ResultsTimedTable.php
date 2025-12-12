<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * ResultsTimed Model
 *
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Users
 * @property \App\Model\Table\AthleteInscriptionsTable&\Cake\ORM\Association\BelongsTo $AthleteInscriptions
 * @property \App\Model\Table\CategorycodesTable&\Cake\ORM\Association\BelongsTo $Categorycodes
 *
 * @method \App\Model\Entity\ResultsTimed newEmptyEntity()
 * @method \App\Model\Entity\ResultsTimed newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\ResultsTimed[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\ResultsTimed get($primaryKey, $options = [])
 * @method \App\Model\Entity\ResultsTimed findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\ResultsTimed patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\ResultsTimed[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\ResultsTimed|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\ResultsTimed saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\ResultsTimed[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\ResultsTimed[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\ResultsTimed[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\ResultsTimed[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 */
class ResultsTimedTable extends Table
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

        $this->setTable('results_timed');
        $this->setDisplayField('user_id');
        $this->setPrimaryKey('id');

        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('AthleteInscriptions', [
            'foreignKey' => 'athlete_inscription_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Categorycodes', [
            'foreignKey' => 'categorycode_id',
            'joinType' => 'INNER',
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
            ->scalar('user_id')
            ->maxLength('user_id', 10)
            ->notEmptyString('user_id');

        $validator
            ->integer('athlete_inscription_id')
            ->notEmptyString('athlete_inscription_id');

        $validator
            ->scalar('categorycode_id')
            ->maxLength('categorycode_id', 10)
            ->notEmptyString('categorycode_id');

        $validator
            ->integer('minutes')
            ->allowEmptyString('minutes');

        $validator
            ->integer('seconds')
            ->allowEmptyString('seconds');

        $validator
            ->integer('milliseconds')
            ->allowEmptyString('milliseconds');

        $validator
            ->integer('penalties')
            ->notEmptyString('penalties');

        $validator
            ->integer('total_time')
            ->allowEmptyString('total_time');

        $validator
            ->integer('pool_ranking')
            ->allowEmptyString('pool_ranking');

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
        $rules->add($rules->existsIn('user_id', 'Users'), ['errorField' => 'user_id']);
        $rules->add($rules->existsIn('athlete_inscription_id', 'AthleteInscriptions'), ['errorField' => 'athlete_inscription_id']);
        $rules->add($rules->existsIn('categorycode_id', 'Categorycodes'), ['errorField' => 'categorycode_id']);

        return $rules;
    }
}

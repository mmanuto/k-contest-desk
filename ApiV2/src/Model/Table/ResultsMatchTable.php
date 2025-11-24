<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * ResultsMatch Model
 *
 * @property \App\Model\Table\CategorycodesTable&\Cake\ORM\Association\BelongsTo $Categorycodes
 *
 * @method \App\Model\Entity\ResultsMatch newEmptyEntity()
 * @method \App\Model\Entity\ResultsMatch newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\ResultsMatch[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\ResultsMatch get($primaryKey, $options = [])
 * @method \App\Model\Entity\ResultsMatch findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\ResultsMatch patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\ResultsMatch[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\ResultsMatch|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\ResultsMatch saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\ResultsMatch[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\ResultsMatch[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\ResultsMatch[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\ResultsMatch[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 */
class ResultsMatchTable extends Table
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

        $this->setTable('results_match');
        $this->setDisplayField('categorycode_id');
        $this->setPrimaryKey('id');

        $this->belongsTo('AthleteInscriptions', [
            'foreignKey' => 'athlete_aka_inscription_id',
            'joinType' => 'INNER',
        ]);

        $this->belongsTo('AthleteInscriptions', [
            'foreignKey' => 'athlete_ao_inscription_id',
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
            ->scalar('categorycode_id')
            ->maxLength('categorycode_id', 10)
            ->notEmptyString('categorycode_id');

        $validator
            ->integer('round')
            ->requirePresence('round', 'create')
            ->notEmptyString('round');

        $validator
            ->integer('match_number')
            ->requirePresence('match_number', 'create')
            ->notEmptyString('match_number');

        $validator
            ->integer('athlete_aka_inscription_id')
            ->allowEmptyString('athlete_aka_inscription_id');

        $validator
            ->integer('athlete_ao_inscription_id')
            ->allowEmptyString('athlete_ao_inscription_id');

        $validator
            ->integer('winner_inscription_id')
            ->allowEmptyString('winner_inscription_id');

        $validator
            ->integer('loser_inscription_id')
            ->allowEmptyString('loser_inscription_id');

        $validator
            ->scalar('method_of_win')
            ->allowEmptyString('method_of_win');

        $validator
            ->integer('score_aka')
            ->allowEmptyString('score_aka');

        $validator
            ->integer('score_ao')
            ->allowEmptyString('score_ao');

        $validator
            ->boolean('senshu_aka')
            ->allowEmptyString('senshu_aka');

        $validator
            ->boolean('senshu_ao')
            ->allowEmptyString('senshu_ao');

        $validator
            ->integer('penalties_aka')
            ->allowEmptyString('penalties_aka');

        $validator
            ->integer('penalties_ao')
            ->allowEmptyString('penalties_ao');

        $validator
            ->boolean('kiken')
            ->allowEmptyString('kiken');

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
        $rules->add($rules->existsIn('categorycode_id', 'Categorycodes'), ['errorField' => 'categorycode_id']);
        $rules->add($rules->existsIn('athlete_aka_inscription_id', 'AthleteInscriptions'), ['errorField' => 'athlete_inscription_id']);
        $rules->add($rules->existsIn('athlete_ao_inscription_id', 'AthleteInscriptions'), ['errorField' => 'athlete_inscription_id']);

        return $rules;
    }
}

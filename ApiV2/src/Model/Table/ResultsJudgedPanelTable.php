<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * ResultsJudgedPanel Model
 *
 * @property \App\Model\Table\AthleteInscriptionsTable&\Cake\ORM\Association\BelongsTo $AthleteInscriptions
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Users
 * @property \App\Model\Table\CategorycodesTable&\Cake\ORM\Association\BelongsTo $Categorycodes
 * @property \App\Model\Table\KatasTable&\Cake\ORM\Association\BelongsTo $Katas
 *
 * @method \App\Model\Entity\ResultsJudgedPanel newEmptyEntity()
 * @method \App\Model\Entity\ResultsJudgedPanel newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\ResultsJudgedPanel[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\ResultsJudgedPanel get($primaryKey, $options = [])
 * @method \App\Model\Entity\ResultsJudgedPanel findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\ResultsJudgedPanel patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\ResultsJudgedPanel[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\ResultsJudgedPanel|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\ResultsJudgedPanel saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\ResultsJudgedPanel[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\ResultsJudgedPanel[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\ResultsJudgedPanel[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\ResultsJudgedPanel[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 */
class ResultsJudgedPanelTable extends Table
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

        $this->setTable('results_judged_panel');
        $this->setDisplayField('user_id');
        $this->setPrimaryKey('id');

        $this->belongsTo('AthleteInscriptions', [
            'foreignKey' => 'athlete_inscription_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Categorycodes', [
            'foreignKey' => 'categorycode_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Katas', [
            'foreignKey' => 'kata_id',
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
            ->notEmptyString('athlete_inscription_id');

        $validator
            ->scalar('user_id')
            ->maxLength('user_id', 10)
            ->notEmptyString('user_id');

        $validator
            ->scalar('categorycode_id')
            ->maxLength('categorycode_id', 10)
            ->notEmptyString('categorycode_id');

        $validator
            ->notEmptyString('round');

        $validator
            ->integer('kata_id')
            ->allowEmptyString('kata_id');

        $validator
            ->numeric('referee_1')
            ->allowEmptyString('referee_1');

        $validator
            ->numeric('referee_2')
            ->allowEmptyString('referee_2');

        $validator
            ->numeric('referee_3')
            ->allowEmptyString('referee_3');

        $validator
            ->numeric('referee_4')
            ->allowEmptyString('referee_4');

        $validator
            ->numeric('referee_5')
            ->allowEmptyString('referee_5');

        $validator
            ->numeric('partial_score')
            ->allowEmptyString('partial_score');

        $validator
            ->numeric('penalties_points')
            ->allowEmptyString('penalties_points');

        $validator
            ->numeric('total_score')
            ->allowEmptyString('total_score');

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
        $rules->add($rules->existsIn('athlete_inscription_id', 'AthleteInscriptions'), ['errorField' => 'athlete_inscription_id']);
        $rules->add($rules->existsIn('user_id', 'Users'), ['errorField' => 'user_id']);
        $rules->add($rules->existsIn('categorycode_id', 'Categorycodes'), ['errorField' => 'categorycode_id']);
        $rules->add($rules->existsIn('kata_id', 'Katas'), ['errorField' => 'kata_id']);

        return $rules;
    }
}

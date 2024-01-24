<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Scores Model
 *
 * @property \App\Model\Table\AthleteInscriptionsTable&\Cake\ORM\Association\BelongsTo $AthleteInscriptions
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsTo $Users
 * @property \App\Model\Table\KatasTable&\Cake\ORM\Association\BelongsTo $Katas
 *
 * @method \App\Model\Entity\Score newEmptyEntity()
 * @method \App\Model\Entity\Score newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\Score[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Score get($primaryKey, $options = [])
 * @method \App\Model\Entity\Score findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\Score patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Score[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Score|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Score saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Score[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Score[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\Score[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Score[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 */
class ScoresTable extends Table
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

        $this->setTable('scores');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->belongsTo('AthleteInscriptions', [
            'foreignKey' => 'athlete_inscription_id',
        ]);
        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
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
            ->integer('athlete_inscription_id')
            ->allowEmptyString('athlete_inscription_id');

        $validator
            ->allowEmptyString('user_id');

        $validator
            ->scalar('category_code')
            ->maxLength('category_code', 10)
            ->allowEmptyString('category_code');

        $validator
            ->scalar('type')
            ->maxLength('type', 5)
            ->allowEmptyString('type');

        $validator
            ->integer('n_prova')
            ->allowEmptyString('n_prova');

        $validator
            ->integer('kata_id')
            ->allowEmptyString('kata_id');

        $validator
            ->decimal('opponentid')
            ->allowEmptyString('opponentid');

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
            ->numeric('min')
            ->allowEmptyString('min');

        $validator
            ->numeric('max')
            ->allowEmptyString('max');

        $validator
            ->numeric('valid_1')
            ->allowEmptyString('valid_1');

        $validator
            ->numeric('valid_2')
            ->allowEmptyString('valid_2');

        $validator
            ->numeric('valid_3')
            ->allowEmptyString('valid_3');

        $validator
            ->numeric('total_without_penalties')
            ->allowEmptyString('total_without_penalties');

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
            ->integer('penalty')
            ->allowEmptyString('penalty');

        $validator
            ->scalar('total_time')
            ->maxLength('total_time', 50)
            ->allowEmptyString('total_time');

        $validator
            ->numeric('total_time_seconds')
            ->allowEmptyString('total_time_seconds');

        $validator
            ->numeric('total')
            ->allowEmptyString('total');

        $validator
            ->allowEmptyString('senshu');

        $validator
            ->allowEmptyString('chui_1');

        $validator
            ->allowEmptyString('chui_2');

        $validator
            ->allowEmptyString('chui_3');

        $validator
            ->allowEmptyString('hans_chui');

        $validator
            ->allowEmptyString('hansoku');

        $validator
            ->allowEmptyString('shikkaku');

        $validator
            ->integer('ippon')
            ->allowEmptyString('ippon');

        $validator
            ->integer('yuko')
            ->allowEmptyString('yuko');

        $validator
            ->integer('wazaari')
            ->allowEmptyString('wazaari');

        $validator
            ->allowEmptyString('win');

        $validator
            ->scalar('color')
            ->maxLength('color', 10)
            ->allowEmptyString('color');

        $validator
            ->allowEmptyString('kiken');

        $validator
            ->integer('cl_position')
            ->allowEmptyString('cl_position');

        $validator
            ->notEmptyString('deleted');

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
        $rules->add($rules->existsIn('athlete_inscription_id', 'AthleteInscriptions'), ['errorField' => 'athlete_inscription_id']);
        $rules->add($rules->existsIn('user_id', 'Users'), ['errorField' => 'user_id']);
        $rules->add($rules->existsIn('kata_id', 'Katas'), ['errorField' => 'kata_id']);

        return $rules;
    }
}

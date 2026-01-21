<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Athletes Model
 *
 * @property \App\Model\Table\ClubsTable&\Cake\ORM\Association\BelongsTo $Clubs
 * @property \App\Model\Table\FederationsTable&\Cake\ORM\Association\BelongsTo $Federations
 * @property \App\Model\Table\AthleteInscriptionsTable&\Cake\ORM\Association\HasMany $AthleteInscriptions
 *
 * @method \App\Model\Entity\Athlete newEmptyEntity()
 * @method \App\Model\Entity\Athlete newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\Athlete[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Athlete get($primaryKey, $options = [])
 * @method \App\Model\Entity\Athlete findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\Athlete patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Athlete[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Athlete|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Athlete saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Athlete[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Athlete[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\Athlete[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Athlete[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 */
class AthletesTable extends Table
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

        $this->setTable('athletes');
        $this->setDisplayField('nome');
        $this->setPrimaryKey('id');

        $this->belongsTo('Clubs', [
            'foreignKey' => 'club_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Federations', [
            'foreignKey' => 'federation_id',
        ]);
        $this->hasMany('AthleteInscriptions', [
            'foreignKey' => 'athlete_id',
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
            ->scalar('club_id')
            ->maxLength('club_id', 21)
            ->notEmptyString('club_id');

        $validator
            ->scalar('nome')
            ->maxLength('nome', 50)
            ->requirePresence('nome', 'create')
            ->notEmptyString('nome');

        $validator
            ->scalar('cognome')
            ->maxLength('cognome', 50)
            ->allowEmptyString('cognome');

        $validator
            ->scalar('data_nascita')
            ->maxLength('data_nascita', 20)
            ->allowEmptyString('data_nascita');

        $validator
            ->scalar('sesso')
            ->maxLength('sesso', 1)
            ->allowEmptyString('sesso');

        $validator
            ->scalar('cod_fiscale')
            ->maxLength('cod_fiscale', 20)
            ->allowEmptyString('cod_fiscale');

        $validator
            ->allowEmptyString('federation_id');

        $validator
            ->scalar('n_tessera')
            ->maxLength('n_tessera', 100)
            ->allowEmptyString('n_tessera');

        $validator
            ->allowEmptyString('peso');

        $validator
            ->scalar('grado')
            ->maxLength('grado', 15)
            ->allowEmptyString('grado');

        $validator
            ->scalar('tesserino')
            ->maxLength('tesserino', 50)
            ->allowEmptyString('tesserino');

        $validator
            ->dateTime('created_date')
            ->notEmptyDateTime('created_date');

        $validator
            ->dateTime('modified_date')
            ->notEmptyDateTime('modified_date');

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
        $rules->add($rules->existsIn('club_id', 'Clubs'), ['errorField' => 'club_id']);
        $rules->add($rules->existsIn('federation_id', 'Federations'), ['errorField' => 'federation_id']);

        return $rules;
    }
}

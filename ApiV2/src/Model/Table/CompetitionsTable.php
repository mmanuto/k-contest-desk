<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Competitions Model
 *
 * @property \App\Model\Table\AthleteInscriptionsTable&\Cake\ORM\Association\HasMany $AthleteInscriptions
 * @property \App\Model\Table\ClubInscriptionsTable&\Cake\ORM\Association\HasMany $ClubInscriptions
 * @property \App\Model\Table\TeamsTable&\Cake\ORM\Association\HasMany $Teams
 *
 * @method \App\Model\Entity\Competition newEmptyEntity()
 * @method \App\Model\Entity\Competition newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\Competition[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Competition get($primaryKey, $options = [])
 * @method \App\Model\Entity\Competition findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\Competition patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Competition[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Competition|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Competition saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Competition[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Competition[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\Competition[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Competition[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 */
class CompetitionsTable extends Table
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

        $this->setTable('competitions');
        $this->setDisplayField('responsabile');
        $this->setPrimaryKey('id');

        $this->hasMany('AthleteInscriptions', [
            'foreignKey' => 'competition_id',
        ]);
        $this->hasMany('ClubInscriptions', [
            'foreignKey' => 'competition_id',
        ]);
        $this->hasMany('Teams', [
            'foreignKey' => 'competition_id',
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
            ->scalar('responsabile')
            ->maxLength('responsabile', 100)
            ->requirePresence('responsabile', 'create')
            ->notEmptyString('responsabile');

        $validator
            ->scalar('club_name')
            ->maxLength('club_name', 100)
            ->requirePresence('club_name', 'create')
            ->notEmptyString('club_name');

        $validator
            ->scalar('logo')
            ->maxLength('logo', 200)
            ->allowEmptyString('logo');

        $validator
            ->email('email')
            ->requirePresence('email', 'create')
            ->notEmptyString('email');

        $validator
            ->scalar('telephone')
            ->maxLength('telephone', 50)
            ->requirePresence('telephone', 'create')
            ->notEmptyString('telephone');

        $validator
            ->scalar('nome_gara')
            ->maxLength('nome_gara', 500)
            ->requirePresence('nome_gara', 'create')
            ->notEmptyString('nome_gara');

        $validator
            ->date('apertura_iscrizioni')
            ->allowEmptyDate('apertura_iscrizioni');

        $validator
            ->date('chiusura_iscrizioni')
            ->requirePresence('chiusura_iscrizioni', 'create')
            ->notEmptyDate('chiusura_iscrizioni');

        $validator
            ->date('data_gara')
            ->requirePresence('data_gara', 'create')
            ->notEmptyDate('data_gara');

        $validator
            ->scalar('locandina')
            ->maxLength('locandina', 100)
            ->allowEmptyString('locandina');

        $validator
            ->scalar('circolare')
            ->maxLength('circolare', 100)
            ->allowEmptyString('circolare');

        $validator
            ->integer('nr_atleti')
            ->requirePresence('nr_atleti', 'create')
            ->notEmptyString('nr_atleti');

        $validator
            ->scalar('richieste')
            ->maxLength('richieste', 500)
            ->requirePresence('richieste', 'create')
            ->notEmptyString('richieste');

        $validator
            ->allowEmptyString('codiceTipoCategorie');

        $validator
            ->scalar('comp_status')
            ->maxLength('comp_status', 30)
            ->notEmptyString('comp_status');

        $validator
            ->notEmptyString('flag_tabs');

        $validator
            ->notEmptyString('flag_classifications');

        $validator
            ->notEmptyString('flag_timetable');

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

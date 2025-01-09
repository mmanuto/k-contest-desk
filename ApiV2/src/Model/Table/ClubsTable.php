<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Clubs Model
 *
 * @property \App\Model\Table\AthletesTable&\Cake\ORM\Association\HasMany $Athletes
 * @property \App\Model\Table\ClubInscriptionsTable&\Cake\ORM\Association\HasMany $ClubInscriptions
 * @property \App\Model\Table\TeamsTable&\Cake\ORM\Association\HasMany $Teams
 *
 * @method \App\Model\Entity\Club newEmptyEntity()
 * @method \App\Model\Entity\Club newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\Club[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Club get($primaryKey, $options = [])
 * @method \App\Model\Entity\Club findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\Club patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Club[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Club|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Club saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Club[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Club[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\Club[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Club[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 */
class ClubsTable extends Table
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

        $this->setTable('clubs');
        $this->setDisplayField('nome_societa');
        $this->setPrimaryKey('id');

        $this->hasMany('Athletes', [
            'foreignKey' => 'club_id',
        ]);
        $this->hasMany('ClubInscriptions', [
            'foreignKey' => 'club_id',
        ]);
        $this->hasMany('Teams', [
            'foreignKey' => 'club_id',
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
            ->scalar('club_code')
            ->maxLength('club_code', 50)
            ->allowEmptyString('club_code');

        $validator
            ->scalar('club_name')
            ->maxLength('club_name', 500)
            ->requirePresence('club_name', 'create')
            ->notEmptyString('club_name');

        $validator
            ->scalar('fiscal_code')
            ->maxLength('fiscal_code', 20)
            ->requirePresence('fiscal_code', 'create')
            ->notEmptyString('fiscal_code');

        $validator
            ->scalar('short_name')
            ->maxLength('short_name', 20)
            ->allowEmptyString('short_name');

        $validator
            ->scalar('club_manager')
            ->maxLength('club_manager', 50)
            ->requirePresence('club_manager', 'create')
            ->notEmptyString('club_manager');

        $validator
            ->scalar('telephone_n')
            ->maxLength('telephone_n', 20)
            ->requirePresence('telephone_n', 'create')
            ->notEmptyString('telephone_n');

        $validator
            ->scalar('mail')
            ->maxLength('mail', 100)
            ->requirePresence('mail', 'create')
            ->notEmptyString('mail');

        $validator
            ->scalar('coach')
            ->maxLength('coach', 50)
            ->requirePresence('coach', 'create')
            ->notEmptyString('coach');

        $validator
            ->dateTime('last_login')
            ->allowEmptyDateTime('last_login');

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

        return $rules;
    }
}

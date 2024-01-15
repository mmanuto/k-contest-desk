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
            ->scalar('nome_societa')
            ->maxLength('nome_societa', 500)
            ->requirePresence('nome_societa', 'create')
            ->notEmptyString('nome_societa');

        $validator
            ->scalar('responsabile')
            ->maxLength('responsabile', 50)
            ->requirePresence('responsabile', 'create')
            ->notEmptyString('responsabile');

        $validator
            ->scalar('telefono')
            ->maxLength('telefono', 20)
            ->requirePresence('telefono', 'create')
            ->notEmptyString('telefono');

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
            ->scalar('CF')
            ->maxLength('CF', 20)
            ->requirePresence('CF', 'create')
            ->notEmptyString('CF');

        return $validator;
    }
}

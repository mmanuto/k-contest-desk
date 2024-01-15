<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Katas Model
 *
 * @property \App\Model\Table\ScoresTable&\Cake\ORM\Association\HasMany $Scores
 * @property \App\Model\Table\ScoresOldTable&\Cake\ORM\Association\HasMany $ScoresOld
 *
 * @method \App\Model\Entity\Kata newEmptyEntity()
 * @method \App\Model\Entity\Kata newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\Kata[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Kata get($primaryKey, $options = [])
 * @method \App\Model\Entity\Kata findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\Kata patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Kata[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Kata|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Kata saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Kata[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Kata[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\Kata[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Kata[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 */
class KatasTable extends Table
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

        $this->setTable('katas');
        $this->setDisplayField('id');
        $this->setPrimaryKey('id');

        $this->hasMany('Scores', [
            'foreignKey' => 'kata_id',
        ]);
        $this->hasMany('ScoresOld', [
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
            ->scalar('kata_name')
            ->maxLength('kata_name', 100)
            ->allowEmptyString('kata_name');

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

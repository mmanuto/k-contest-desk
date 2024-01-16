<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * AthleteInscriptions Model
 *
 * @property \App\Model\Table\AthletesTable&\Cake\ORM\Association\BelongsTo $Athletes
 * @property \App\Model\Table\CategorycodesTable&\Cake\ORM\Association\BelongsTo $Categorycodes
 * @property \App\Model\Table\CompetitionsTable&\Cake\ORM\Association\BelongsTo $Competitions
 * @property \App\Model\Table\ScoresTable&\Cake\ORM\Association\HasMany $Scores
 * @property \App\Model\Table\ScoresOldTable&\Cake\ORM\Association\HasMany $ScoresOld
 *
 * @method \App\Model\Entity\AthleteInscription newEmptyEntity()
 * @method \App\Model\Entity\AthleteInscription newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\AthleteInscription[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\AthleteInscription get($primaryKey, $options = [])
 * @method \App\Model\Entity\AthleteInscription findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\AthleteInscription patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\AthleteInscription[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\AthleteInscription|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\AthleteInscription saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\AthleteInscription[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\AthleteInscription[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\AthleteInscription[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\AthleteInscription[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 */
class AthleteInscriptionsTable extends Table
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

        $this->setTable('athlete_inscriptions');
        $this->setDisplayField('cintura');
        $this->setPrimaryKey('id');

        $this->belongsTo('Athletes', [
            'foreignKey' => 'athlete_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Categorycodes', [
            'foreignKey' => 'categorycode_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Competitions', [
            'foreignKey' => 'competition_id',
            'joinType' => 'INNER',
        ]);
        $this->hasMany('Scores', [
            'foreignKey' => 'athlete_inscription_id',
        ]);
        $this->hasMany('ScoresOld', [
            'foreignKey' => 'athlete_inscription_id',
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
            ->integer('athlete_id')
            ->notEmptyString('athlete_id');

        $validator
            ->notEmptyString('categorycode_id');

        $validator
            ->notEmptyString('competition_id');

        $validator
            ->scalar('cintura')
            ->maxLength('cintura', 50)
            ->requirePresence('cintura', 'create')
            ->notEmptyString('cintura');

        $validator
            ->allowEmptyString('n_iscrizione');

        $validator
            ->notEmptyString('inviato');

        $validator
            ->notEmptyString('modificato');

        $validator
            ->notEmptyString('accorpamento');

        $validator
            ->notEmptyString('deleted');

        $validator
            ->scalar('old_category')
            ->maxLength('old_category', 15)
            ->requirePresence('old_category', 'create')
            ->notEmptyString('old_category');

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
        $rules->add($rules->existsIn('athlete_id', 'Athletes'), ['errorField' => 'athlete_id']);
        $rules->add($rules->existsIn('categorycode_id', 'Categorycodes'), ['errorField' => 'categorycode_id']);
        $rules->add($rules->existsIn('competition_id', 'Competitions'), ['errorField' => 'competition_id']);

        return $rules;
    }
}

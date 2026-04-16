<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * CompetitionClubRankings Model
 *
 * @property \App\Model\Table\CompetitionsTable&\Cake\ORM\Association\BelongsTo $Competitions
 * @property \App\Model\Table\ClubsTable&\Cake\ORM\Association\BelongsTo $Clubs
 *
 * @method \App\Model\Entity\CompetitionClubRanking newEmptyEntity()
 * @method \App\Model\Entity\CompetitionClubRanking newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\CompetitionClubRanking[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\CompetitionClubRanking get($primaryKey, $options = [])
 * @method \App\Model\Entity\CompetitionClubRanking findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\CompetitionClubRanking patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\CompetitionClubRanking[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\CompetitionClubRanking|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\CompetitionClubRanking saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\CompetitionClubRanking[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\CompetitionClubRanking[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\CompetitionClubRanking[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\CompetitionClubRanking[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 *
 * @mixin \Cake\ORM\Behavior\TimestampBehavior
 */
class CompetitionClubRankingsTable extends Table
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

        $this->setTable('competition_club_rankings');
        $this->setDisplayField('competition_id');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');

        $this->belongsTo('Competitions', [
            'foreignKey' => 'competition_id',
            'joinType' => 'INNER',
        ]);
        $this->belongsTo('Clubs', [
            'foreignKey' => 'club_id',
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
            ->scalar('competition_id')
            ->maxLength('competition_id', 50)
            ->notEmptyString('competition_id');

        $validator
            ->scalar('club_id')
            ->maxLength('club_id', 50)
            ->notEmptyString('club_id');

        $validator
            ->integer('golds')
            ->allowEmptyString('golds');

        $validator
            ->integer('silvers')
            ->allowEmptyString('silvers');

        $validator
            ->integer('bronzes')
            ->allowEmptyString('bronzes');

        $validator
            ->integer('total_points')
            ->allowEmptyString('total_points');

        $validator
            ->integer('rank_position')
            ->allowEmptyString('rank_position');

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
        $rules->add($rules->existsIn('competition_id', 'Competitions'), ['errorField' => 'competition_id']);
        $rules->add($rules->existsIn('club_id', 'Clubs'), ['errorField' => 'club_id']);

        return $rules;
    }
}

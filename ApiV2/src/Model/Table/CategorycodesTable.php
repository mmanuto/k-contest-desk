<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

/**
 * Categorycodes Model
 *
 * @property \App\Model\Table\AthleteInscriptionsTable&\Cake\ORM\Association\HasMany $AthleteInscriptions
 * @property \App\Model\Table\UsersTable&\Cake\ORM\Association\BelongsToMany $Users
 *
 * @method \App\Model\Entity\Categorycode newEmptyEntity()
 * @method \App\Model\Entity\Categorycode newEntity(array $data, array $options = [])
 * @method \App\Model\Entity\Categorycode[] newEntities(array $data, array $options = [])
 * @method \App\Model\Entity\Categorycode get($primaryKey, $options = [])
 * @method \App\Model\Entity\Categorycode findOrCreate($search, ?callable $callback = null, $options = [])
 * @method \App\Model\Entity\Categorycode patchEntity(\Cake\Datasource\EntityInterface $entity, array $data, array $options = [])
 * @method \App\Model\Entity\Categorycode[] patchEntities(iterable $entities, array $data, array $options = [])
 * @method \App\Model\Entity\Categorycode|false save(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Categorycode saveOrFail(\Cake\Datasource\EntityInterface $entity, $options = [])
 * @method \App\Model\Entity\Categorycode[]|\Cake\Datasource\ResultSetInterface|false saveMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Categorycode[]|\Cake\Datasource\ResultSetInterface saveManyOrFail(iterable $entities, $options = [])
 * @method \App\Model\Entity\Categorycode[]|\Cake\Datasource\ResultSetInterface|false deleteMany(iterable $entities, $options = [])
 * @method \App\Model\Entity\Categorycode[]|\Cake\Datasource\ResultSetInterface deleteManyOrFail(iterable $entities, $options = [])
 */
class CategorycodesTable extends Table
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

        $this->setTable('categorycodes');
        $this->setDisplayField('codice');
        $this->setPrimaryKey('id');

        $this->hasMany('AthleteInscriptions', [
            'foreignKey' => 'categorycode_id',
        ]);
        $this->belongsToMany('Users', [
            'foreignKey' => 'categorycode_id',
            'targetForeignKey' => 'user_id',
            'joinTable' => 'users_categorycodes',
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
            ->scalar('codice')
            ->maxLength('codice', 10)
            ->requirePresence('codice', 'create')
            ->notEmptyString('codice');

        $validator
            ->scalar('categoria')
            ->maxLength('categoria', 50)
            ->requirePresence('categoria', 'create')
            ->notEmptyString('categoria');

        $validator
            ->integer('anno_min')
            ->requirePresence('anno_min', 'create')
            ->notEmptyString('anno_min');

        $validator
            ->integer('anno_max')
            ->requirePresence('anno_max', 'create')
            ->notEmptyString('anno_max');

        $validator
            ->scalar('sesso')
            ->requirePresence('sesso', 'create')
            ->notEmptyString('sesso');

        $validator
            ->scalar('grado')
            ->maxLength('grado', 300)
            ->requirePresence('grado', 'create')
            ->notEmptyString('grado');

        $validator
            ->scalar('cat_peso')
            ->maxLength('cat_peso', 10)
            ->allowEmptyString('cat_peso');

        $validator
            ->integer('peso_min')
            ->allowEmptyString('peso_min');

        $validator
            ->integer('peso_max')
            ->allowEmptyString('peso_max');

        $validator
            ->scalar('specialita')
            ->maxLength('specialita', 100)
            ->requirePresence('specialita', 'create')
            ->notEmptyString('specialita');

        $validator
            ->numeric('costo_intero')
            ->requirePresence('costo_intero', 'create')
            ->notEmptyString('costo_intero');

        $validator
            ->numeric('costo_scontato')
            ->requirePresence('costo_scontato', 'create')
            ->notEmptyString('costo_scontato');

        $validator
            ->requirePresence('codiceTipoCategorie', 'create')
            ->notEmptyString('codiceTipoCategorie');

        return $validator;
    }
}

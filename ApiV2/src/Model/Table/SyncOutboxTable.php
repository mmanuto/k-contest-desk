<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class SyncOutboxTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        // La tabella ha nome singolare, quindi va indicato esplicitamente.
        $this->setTable('sync_outbox');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp');
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('event_type')
            ->maxLength('event_type', 100)
            ->requirePresence('event_type', 'create')
            ->notEmptyString('event_type');

        $validator
            ->scalar('endpoint')
            ->maxLength('endpoint', 255)
            ->requirePresence('endpoint', 'create')
            ->notEmptyString('endpoint');

        $validator
            ->requirePresence('payload', 'create')
            ->notEmptyArray('payload');

        $validator
            ->scalar('status')
            ->inList('status', [
                'PENDING',
                'PROCESSING',
                'COMPLETED',
                'FAILED',
            ]);

        return $validator;
    }
}
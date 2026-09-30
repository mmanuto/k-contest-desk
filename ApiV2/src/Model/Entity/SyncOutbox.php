<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class SyncOutbox extends Entity
{
    protected $_accessible = [
        'event_type' => true,
        'endpoint' => true,
        'payload' => true,
        'status' => true,
        'attempts' => true,
        'next_attempt_at' => true,
        'last_error' => true,
        'processed_at' => true,
        'created' => true,
        'modified' => true,
    ];
}
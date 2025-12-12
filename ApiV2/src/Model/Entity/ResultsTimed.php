<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * ResultsTimed Entity
 *
 * @property int $id
 * @property string $user_id
 * @property int $athlete_inscription_id
 * @property string $categorycode_id
 * @property int|null $minutes
 * @property int|null $seconds
 * @property int|null $milliseconds
 * @property int $penalties
 * @property int|null $total_time
 * @property int|null $pool_ranking
 *
 * @property \App\Model\Entity\User $user
 * @property \App\Model\Entity\AthleteInscription $athlete_inscription
 * @property \App\Model\Entity\Categorycode $categorycode
 */
class ResultsTimed extends Entity
{
    /**
     * Fields that can be mass assigned using newEntity() or patchEntity().
     *
     * Note that when '*' is set to true, this allows all unspecified fields to
     * be mass assigned. For security purposes, it is advised to set '*' to false
     * (or remove it), and explicitly make individual fields accessible as needed.
     *
     * @var array<string, bool>
     */
    protected $_accessible = [
        'user_id' => true,
        'athlete_inscription_id' => true,
        'categorycode_id' => true,
        'minutes' => true,
        'seconds' => true,
        'milliseconds' => true,
        'penalties' => true,
        'total_time' => true,
        'pool_ranking' => true,
        'user' => true,
        'athlete_inscription' => true,
        'categorycode' => true,
    ];
}

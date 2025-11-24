<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * ResultsJudgedPanel Entity
 *
 * @property int $id
 * @property int $athlete_inscription_id
 * @property string $user_id
 * @property string $categorycode_id
 * @property int $round
 * @property int|null $kata_id
 * @property float|null $referee_1
 * @property float|null $referee_2
 * @property float|null $referee_3
 * @property float|null $referee_4
 * @property float|null $referee_5
 * @property float|null $partial_score
 * @property float|null $penalties_points
 * @property float|null $total_score
 * @property int|null $pool_ranking
 *
 * @property \App\Model\Entity\AthleteInscription $athlete_inscription
 * @property \App\Model\Entity\User $user
 * @property \App\Model\Entity\Categorycode $categorycode
 * @property \App\Model\Entity\Kata $kata
 */
class ResultsJudgedPanel extends Entity
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
        'athlete_inscription_id' => true,
        'user_id' => true,
        'categorycode_id' => true,
        'round' => true,
        'kata_id' => true,
        'referee_1' => true,
        'referee_2' => true,
        'referee_3' => true,
        'referee_4' => true,
        'referee_5' => true,
        'partial_score' => true,
        'penalties_points' => true,
        'total_score' => true,
        'pool_ranking' => true,
        'athlete_inscription' => true,
        'user' => true,
        'categorycode' => true,
        'kata' => true,
    ];
}

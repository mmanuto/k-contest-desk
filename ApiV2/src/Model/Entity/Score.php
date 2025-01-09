<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Score Entity
 *
 * @property int $id
 * @property int|null $athlete_inscription_id
 * @property string|null $user_id
 * @property string|null $categorycode_id
 * @property string|null $type
 * @property int|null $n_prova
 * @property int|null $kata_id
 * @property string|null $opponentid
 * @property float|null $referee_1
 * @property float|null $referee_2
 * @property float|null $referee_3
 * @property float|null $referee_4
 * @property float|null $referee_5
 * @property float|null $min
 * @property float|null $max
 * @property float|null $valid_1
 * @property float|null $valid_2
 * @property float|null $valid_3
 * @property float|null $total_without_penalties
 * @property int|null $minutes
 * @property int|null $seconds
 * @property int|null $milliseconds
 * @property int|null $penalty
 * @property string|null $total_time
 * @property float|null $total_time_seconds
 * @property float|null $total
 * @property int|null $senshu
 * @property int|null $chui_1
 * @property int|null $chui_2
 * @property int|null $chui_3
 * @property int|null $hans_chui
 * @property int|null $hansoku
 * @property int|null $shikkaku
 * @property int|null $ippon
 * @property int|null $yuko
 * @property int|null $wazaari
 * @property int|null $win
 * @property string|null $color
 * @property int|null $kiken
 * @property int|null $cl_position
 * @property int $deleted
 *
 * @property \App\Model\Entity\AthleteInscription $athlete_inscription
 * @property \App\Model\Entity\User $user
 * @property \App\Model\Entity\Categorycode $categorycode
 * @property \App\Model\Entity\Kata $kata
 */
class Score extends Entity
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
        'type' => true,
        'n_prova' => true,
        'kata_id' => true,
        'opponentid' => true,
        'referee_1' => true,
        'referee_2' => true,
        'referee_3' => true,
        'referee_4' => true,
        'referee_5' => true,
        'min' => true,
        'max' => true,
        'valid_1' => true,
        'valid_2' => true,
        'valid_3' => true,
        'total_without_penalties' => true,
        'minutes' => true,
        'seconds' => true,
        'milliseconds' => true,
        'penalty' => true,
        'total_time' => true,
        'total_time_seconds' => true,
        'total' => true,
        'senshu' => true,
        'chui_1' => true,
        'chui_2' => true,
        'chui_3' => true,
        'hans_chui' => true,
        'hansoku' => true,
        'shikkaku' => true,
        'ippon' => true,
        'yuko' => true,
        'wazaari' => true,
        'win' => true,
        'color' => true,
        'kiken' => true,
        'cl_position' => true,
        'deleted' => true,
        'athlete_inscription' => true,
        'user' => true,
        'categorycode' => true,
        'kata' => true,
    ];
}

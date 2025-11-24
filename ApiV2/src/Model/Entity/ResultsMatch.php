<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * ResultsMatch Entity
 *
 * @property int $id
 * @property string $categorycode_id
 * @property int $round
 * @property int $match_number
 * @property int|null $athlete_aka_inscription_id
 * @property int|null $athlete_ao_inscription_id
 * @property int|null $winner_inscription_id
 * @property int|null $loser_inscription_id
 * @property string|null $method_of_win
 * @property int|null $score_aka
 * @property int|null $score_ao
 * @property bool|null $senshu_aka
 * @property bool|null $senshu_ao
 * @property int|null $penalties_aka
 * @property int|null $penalties_ao
 * @property bool|null $kiken
 *
 * @property \App\Model\Entity\Categorycode $categorycode
 */
class ResultsMatch extends Entity
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
        'categorycode_id' => true,
        'round' => true,
        'match_number' => true,
        'athlete_aka_inscription_id' => true,
        'athlete_ao_inscription_id' => true,
        'winner_inscription_id' => true,
        'loser_inscription_id' => true,
        'method_of_win' => true,
        'score_aka' => true,
        'score_ao' => true,
        'senshu_aka' => true,
        'senshu_ao' => true,
        'penalties_aka' => true,
        'penalties_ao' => true,
        'kiken' => true,
        'categorycode' => true,
    ];
}

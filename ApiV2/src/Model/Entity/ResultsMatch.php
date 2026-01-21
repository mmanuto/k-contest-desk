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
 * @property int|null $kata_id_aka
 * @property int|null $kata_id_ao
 * @property int|null $score_aka
 * @property int|null $score_ao
 * @property bool|null $senshu_aka
 * @property bool|null $senshu_ao
 * @property int|null $yuko_aka
 * @property int|null $yuko_ao
 * @property int|null $wazaari_aka
 * @property int|null $wazaari_ao
 * @property int|null $ippon_aka
 * @property int|null $ippon_ao
 * @property bool|null $chui_1_aka
 * @property bool|null $chui_1_ao
 * @property bool|null $chui_2_aka
 * @property bool|null $chui_2_ao
 * @property bool|null $chui_3_aka
 * @property bool|null $chui_3_ao
 * @property bool|null $hans_chui_aka
 * @property bool|null $hans_chui_ao
 * @property bool|null $hans_aka
 * @property bool|null $hans_ao
 * @property string|null $method_of_win
 * @property bool $is_bronze_final
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
        'kata_id_aka' => true,
        'kata_id_ao' => true,
        'score_aka' => true,
        'score_ao' => true,
        'senshu_aka' => true,
        'senshu_ao' => true,
        'yuko_aka' => true,
        'yuko_ao' => true,
        'wazaari_aka' => true,
        'wazaari_ao' => true,
        'ippon_aka' => true,
        'ippon_ao' => true,
        'chui_1_aka' => true,
        'chui_1_ao' => true,
        'chui_2_aka' => true,
        'chui_2_ao' => true,
        'chui_3_aka' => true,
        'chui_3_ao' => true,
        'hans_chui_aka' => true,
        'hans_chui_ao' => true,
        'hans_aka' => true,
        'hans_ao' => true,
        'method_of_win' => true,
        'is_bronze_final' => true,
        'categorycode' => true,
    ];
}

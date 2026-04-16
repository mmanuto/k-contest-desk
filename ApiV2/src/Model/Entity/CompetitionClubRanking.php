<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * CompetitionClubRanking Entity
 *
 * @property int $id
 * @property string $competition_id
 * @property string $club_id
 * @property int|null $golds
 * @property int|null $silvers
 * @property int|null $bronzes
 * @property int|null $total_points
 * @property int|null $rank_position
 * @property \Cake\I18n\FrozenTime|null $created
 *
 * @property \App\Model\Entity\Competition $competition
 * @property \App\Model\Entity\Club $club
 */
class CompetitionClubRanking extends Entity
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
        'competition_id' => true,
        'club_id' => true,
        'golds' => true,
        'silvers' => true,
        'bronzes' => true,
        'total_points' => true,
        'rank_position' => true,
        'created' => true,
        'competition' => true,
        'club' => true,
    ];
}

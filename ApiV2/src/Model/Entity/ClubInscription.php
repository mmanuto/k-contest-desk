<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * ClubInscription Entity
 *
 * @property int $id
 * @property int $club_id
 * @property int $competition_id
 *
 * @property \App\Model\Entity\Club $club
 * @property \App\Model\Entity\Competition $competition
 */
class ClubInscription extends Entity
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
        'club_id' => true,
        'competition_id' => true,
        'club' => true,
        'competition' => true,
    ];
}

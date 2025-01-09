<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Team Entity
 *
 * @property int $id
 * @property string $club_id
 * @property string $competition_id
 * @property string $componenti
 * @property string $categoria
 * @property string $grado
 * @property \Cake\I18n\FrozenTime $created_date
 * @property \Cake\I18n\FrozenTime $modified_date
 *
 * @property \App\Model\Entity\Club $club
 * @property \App\Model\Entity\Competition $competition
 */
class Team extends Entity
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
        'componenti' => true,
        'categoria' => true,
        'grado' => true,
        'created_date' => true,
        'modified_date' => true,
        'club' => true,
        'competition' => true,
    ];
}

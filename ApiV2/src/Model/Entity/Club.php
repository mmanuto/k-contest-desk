<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Club Entity
 *
 * @property int $id
 * @property string $nome_societa
 * @property string $responsabile
 * @property string $telefono
 * @property string $mail
 * @property string $coach
 * @property string $CF
 *
 * @property \App\Model\Entity\Athlete[] $athletes
 * @property \App\Model\Entity\ClubInscription[] $club_inscriptions
 * @property \App\Model\Entity\Team[] $teams
 */
class Club extends Entity
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
        'nome_societa' => true,
        'responsabile' => true,
        'telefono' => true,
        'mail' => true,
        'coach' => true,
        'CF' => true,
        'athletes' => true,
        'club_inscriptions' => true,
        'teams' => true,
    ];
}

<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Club Entity
 *
 * @property string $id
 * @property string|null $club_code
 * @property string $club_name
 * @property string $fiscal_code
 * @property string|null $short_name
 * @property string $club_manager
 * @property string $telephone_n
 * @property string $mail
 * @property string $coach
 * @property \Cake\I18n\FrozenTime|null $last_login
 * @property \Cake\I18n\FrozenTime $created_date
 * @property \Cake\I18n\FrozenTime $modified_date
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
        'club_code' => true,
        'club_name' => true,
        'fiscal_code' => true,
        'short_name' => true,
        'club_manager' => true,
        'telephone_n' => true,
        'mail' => true,
        'coach' => true,
        'last_login' => true,
        'created_date' => true,
        'modified_date' => true,
        'athletes' => true,
        'club_inscriptions' => true,
        'teams' => true,
    ];
}

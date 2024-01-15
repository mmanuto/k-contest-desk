<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * User Entity
 *
 * @property int $id
 * @property string|null $name
 * @property string $username
 * @property string $password
 * @property string|null $email
 *
 * @property \App\Model\Entity\Score[] $scores
 * @property \App\Model\Entity\ScoresOld[] $scores_old
 * @property \App\Model\Entity\Categorycode[] $categorycodes
 */
class User extends Entity
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
        'name' => true,
        'username' => true,
        'password' => true,
        'email' => true,
        'scores' => true,
        'scores_old' => true,
        'categorycodes' => true,
    ];

    /**
     * Fields that are excluded from JSON versions of the entity.
     *
     * @var array<string>
     */
    protected $_hidden = [
        'password',
    ];
}

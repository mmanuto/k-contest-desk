<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Agecategory Entity
 *
 * @property string $id
 * @property string $description
 * @property int $age_min
 * @property int $age_max
 * @property bool $agonist
 * @property bool $out_category
 *
 * @property \App\Model\Entity\Categorycode[] $categorycodes
 */
class Agecategory extends Entity
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
        'description' => true,
        'age_min' => true,
        'age_max' => true,
        'agonist' => true,
        'out_category' => true,
        'categorycodes' => true,
    ];
}

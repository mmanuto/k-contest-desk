<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Categorycode Entity
 *
 * @property string $id
 * @property string $categoria
 * @property string|null $agecategory_id
 * @property int $anno_min
 * @property int $anno_max
 * @property string $sesso
 * @property string $grado
 * @property string|null $cat_peso
 * @property int|null $peso_min
 * @property int|null $peso_max
 * @property string $specialita
 * @property float $costo_intero
 * @property float $costo_scontato
 * @property int $codiceTipoCategorie
 * @property int $order_number
 *
 * @property \App\Model\Entity\Agecategory $agecategory
 * @property \App\Model\Entity\AthleteInscription[] $athlete_inscriptions
 * @property \App\Model\Entity\Score[] $scores
 * @property \App\Model\Entity\User[] $users
 */
class Categorycode extends Entity
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
        'categoria' => true,
        'agecategory_id' => true,
        'anno_min' => true,
        'anno_max' => true,
        'sesso' => true,
        'grado' => true,
        'cat_peso' => true,
        'peso_min' => true,
        'peso_max' => true,
        'specialita' => true,
        'costo_intero' => true,
        'costo_scontato' => true,
        'codiceTipoCategorie' => true,
        'order_number' => true,
        'agecategory' => true,
        'athlete_inscriptions' => true,
        'scores' => true,
        'users' => true,
    ];
}

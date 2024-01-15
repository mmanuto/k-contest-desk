<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Athlete Entity
 *
 * @property int $id
 * @property int $club_id
 * @property string $nome
 * @property string|null $cognome
 * @property string|null $data_nascita
 * @property string|null $sesso
 * @property string|null $cod_fiscale
 * @property int|null $federation_id
 * @property string|null $n_tessera
 * @property int|null $peso
 * @property string|null $grado
 *
 * @property \App\Model\Entity\Club $club
 * @property \App\Model\Entity\Federation $federation
 * @property \App\Model\Entity\AthleteInscription[] $athlete_inscriptions
 */
class Athlete extends Entity
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
        'nome' => true,
        'cognome' => true,
        'data_nascita' => true,
        'sesso' => true,
        'cod_fiscale' => true,
        'federation_id' => true,
        'n_tessera' => true,
        'peso' => true,
        'grado' => true,
        'club' => true,
        'federation' => true,
        'athlete_inscriptions' => true,
    ];
}

<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * Competition Entity
 *
 * @property string $id
 * @property string $responsabile
 * @property string $club_name
 * @property string|null $logo
 * @property string $email
 * @property string $telephone
 * @property string $nome_gara
 * @property \Cake\I18n\FrozenDate|null $apertura_iscrizioni
 * @property \Cake\I18n\FrozenDate $chiusura_iscrizioni
 * @property \Cake\I18n\FrozenDate $data_gara
 * @property string|null $locandina
 * @property string|null $circolare
 * @property int $nr_atleti
 * @property string $richieste
 * @property int|null $codiceTipoCategorie
 * @property string $comp_status
 * @property int $flag_tabs
 * @property int $flag_classifications
 * @property int $flag_timetable
 *
 * @property \App\Model\Entity\AthleteInscription[] $athlete_inscriptions
 * @property \App\Model\Entity\ClubInscription[] $club_inscriptions
 * @property \App\Model\Entity\Team[] $teams
 */
class Competition extends Entity
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
        'responsabile' => true,
        'club_name' => true,
        'logo' => true,
        'email' => true,
        'telephone' => true,
        'nome_gara' => true,
        'apertura_iscrizioni' => true,
        'chiusura_iscrizioni' => true,
        'data_gara' => true,
        'locandina' => true,
        'circolare' => true,
        'nr_atleti' => true,
        'richieste' => true,
        'codiceTipoCategorie' => true,
        'comp_status' => true,
        'flag_tabs' => true,
        'flag_classifications' => true,
        'flag_timetable' => true,
        'athlete_inscriptions' => true,
        'club_inscriptions' => true,
        'teams' => true,
    ];
}

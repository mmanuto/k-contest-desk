<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * AthleteInscription Entity
 *
 * @property int $id
 * @property string $athlete_id
 * @property string $categorycode_id
 * @property string $competition_id
 * @property string $cintura
 * @property int|null $n_iscrizione
 * @property int $inviato
 * @property int $modificato
 * @property int $accorpamento
 * @property int $deleted
 * @property string|null $old_category
 * @property \Cake\I18n\FrozenTime $created_date
 * @property \Cake\I18n\FrozenTime $modified_date
 * @property int|null $final_ranking
 *
 * @property \App\Model\Entity\Athlete $athlete
 * @property \App\Model\Entity\Categorycode $categorycode
 * @property \App\Model\Entity\Competition $competition
 * @property \App\Model\Entity\ResultsJudgedPanel[] $results_judged_panel
 * @property \App\Model\Entity\ResultsTimed[] $results_timed
 * @property \App\Model\Entity\Score[] $scores
 * @property \App\Model\Entity\ScoresOld[] $scores_old
 */
class AthleteInscription extends Entity
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
        'athlete_id' => true,
        'categorycode_id' => true,
        'competition_id' => true,
        'cintura' => true,
        'n_iscrizione' => true,
        'inviato' => true,
        'modificato' => true,
        'accorpamento' => true,
        'deleted' => true,
        'old_category' => true,
        'created_date' => true,
        'modified_date' => true,
        'final_ranking' => true,
        'athlete' => true,
        'categorycode' => true,
        'competition' => true,
        'results_judged_panel' => true,
        'results_timed' => true,
        'scores' => true,
        'scores_old' => true,
    ];
}

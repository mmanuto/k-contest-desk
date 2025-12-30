<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * ResultsMatchFixture
 */
class ResultsMatchFixture extends TestFixture
{
    /**
     * Table name
     *
     * @var string
     */
    public $table = 'results_match';
    /**
     * Init method
     *
     * @return void
     */
    public function init(): void
    {
        $this->records = [
            [
                'id' => 1,
                'categorycode_id' => 'Lorem ip',
                'round' => 1,
                'match_number' => 1,
                'athlete_aka_inscription_id' => 1,
                'athlete_ao_inscription_id' => 1,
                'winner_inscription_id' => 1,
                'loser_inscription_id' => 1,
                'kata_id_aka' => 1,
                'kata_id_ao' => 1,
                'score_aka' => 1,
                'score_ao' => 1,
                'senshu_aka' => 1,
                'senshu_ao' => 1,
                'yuko_aka' => 1,
                'yuko_ao' => 1,
                'wazaari_aka' => 1,
                'wazaari_ao' => 1,
                'ippon_aka' => 1,
                'ippon_ao' => 1,
                'chui_1_aka' => 1,
                'chui_1_ao' => 1,
                'chui_2_aka' => 1,
                'chui_2_ao' => 1,
                'chui_3_aka' => 1,
                'chui_3_ao' => 1,
                'hans_chui_aka' => 1,
                'hans_chui_ao' => 1,
                'hans_aka' => 1,
                'hans_ao' => 1,
                'method_of_win' => 'Lorem ipsum d',
            ],
        ];
        parent::init();
    }
}

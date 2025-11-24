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
                'method_of_win' => 'Lorem ipsum dolor sit amet',
                'score_aka' => 1,
                'score_ao' => 1,
                'senshu_aka' => 1,
                'senshu_ao' => 1,
                'penalties_aka' => 1,
                'penalties_ao' => 1,
                'kiken' => 1,
            ],
        ];
        parent::init();
    }
}

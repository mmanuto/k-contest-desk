<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * ResultsJudgedPanelFixture
 */
class ResultsJudgedPanelFixture extends TestFixture
{
    /**
     * Table name
     *
     * @var string
     */
    public $table = 'results_judged_panel';
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
                'athlete_inscription_id' => 1,
                'user_id' => 'Lorem ip',
                'categorycode_id' => 'Lorem ip',
                'round' => 1,
                'kata_id' => 1,
                'referee_1' => 1,
                'referee_2' => 1,
                'referee_3' => 1,
                'referee_4' => 1,
                'referee_5' => 1,
                'partial_score' => 1,
                'penalties_points' => 1,
                'total_score' => 1,
                'pool_ranking' => 1,
            ],
        ];
        parent::init();
    }
}

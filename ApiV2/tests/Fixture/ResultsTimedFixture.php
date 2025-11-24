<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * ResultsTimedFixture
 */
class ResultsTimedFixture extends TestFixture
{
    /**
     * Table name
     *
     * @var string
     */
    public $table = 'results_timed';
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
                'user_id' => 'Lorem ip',
                'athlete_inscription_id' => 1,
                'categorycode_id' => 'Lorem ip',
                'time_seconds' => 1,
                'penalties_count' => 1,
                'penalty_value_seconds' => 1,
                'total_time' => 1,
                'pool_ranking' => 1,
            ],
        ];
        parent::init();
    }
}

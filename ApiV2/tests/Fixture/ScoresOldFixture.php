<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * ScoresOldFixture
 */
class ScoresOldFixture extends TestFixture
{
    /**
     * Table name
     *
     * @var string
     */
    public $table = 'scores_old';
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
                'user_id' => 1,
                'category_code' => 'Lorem ip',
                'type' => 'Lor',
                'n_prova' => 1,
                'kata_id' => 1,
                'opponentid' => 1.5,
                'referee_1_pt' => 1,
                'referee_2_pt' => 1,
                'referee_3_pt' => 1,
                'referee_4_pt' => 1,
                'referee_5_pt' => 1,
                'min_pt' => 1,
                'max_pt' => 1,
                'valid_1_pt' => 1,
                'valid_2_pt' => 1,
                'valid_3_pt' => 1,
                'total_pt' => 1,
                'referee_1_pa' => 1,
                'referee_2_pa' => 1,
                'referee_3_pa' => 1,
                'referee_4_pa' => 1,
                'referee_5_pa' => 1,
                'min_pa' => 1,
                'max_pa' => 1,
                'valid_1_pa' => 1,
                'valid_2_pa' => 1,
                'valid_3_pa' => 1,
                'total_pa' => 1,
                'minutes' => 1,
                'seconds' => 1,
                'milliseconds' => 1,
                'penalty' => 1,
                'total_time' => 'Lorem ipsum dolor sit amet',
                'total_time_seconds' => 1,
                'total' => 1,
                'senshu' => 1,
                'C1_CH' => 1,
                'C1_KK' => 1,
                'C1_HC' => 1,
                'C1_H' => 1,
                'C2_CH' => 1,
                'C2_KK' => 1,
                'C2_HC' => 1,
                'C2_H' => 1,
                'ippon' => 1,
                'yuko' => 1,
                'wazaari' => 1,
                'win' => 1,
                'color' => 'Lorem ip',
                'cl_position' => 1,
            ],
        ];
        parent::init();
    }
}
